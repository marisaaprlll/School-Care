<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Halaman: Profil (Pelapor & Admin)
 * Tampilan: resources/views/profile/index.blade.php
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.index', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user->id)],
            'class_name' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->class_name = $data['class_name'];

        $initials = preg_replace('/[^A-Za-z]/', '', $data['name']);
        $user->avatar = strtoupper(substr($initials ?: 'US', 0, 2));

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function accounts(): View
    {
        abort_unless(request()->user()?->isAdmin(), 403, 'Hanya admin yang dapat membuat akun.');

        $oldAccounts = old('accounts');

        return view('accounts.index', [
            'users' => User::whereKeyNot(request()->user()->id)
                ->orderBy('role')
                ->orderBy('name')
                ->get(),
            'activePage' => 'accounts',
            'crumb' => __('nav.menu_accounts'),
            'oldAccounts' => is_array($oldAccounts) && $oldAccounts !== [] ? array_values($oldAccounts) : [[]],
            'roleLabel' => [
                'reporter' => 'Pelapor (siswa / guru)',
                'admin' => 'Admin (petugas)',
            ],
        ]);
    }

    /** Jumlah akun maksimal untuk satu kali kirim (manual maupun file). */
    private const MAX_ACCOUNTS = 1000;

    private const REQUIRED_HEADERS = ['name', 'email', 'role', 'class_name', 'password'];

    /** Nama kolom lain yang juga diterima di baris header file. */
    private const HEADER_ALIASES = [
        'nama' => 'name',
        'nama_lengkap' => 'name',
        'peran' => 'role',
        'kelas' => 'class_name',
        'kelas_jabatan' => 'class_name',
        'jabatan' => 'class_name',
        'kata_sandi' => 'password',
        'sandi' => 'password',
    ];

    public function createUser(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Hanya admin yang dapat menambah akun.');

        // Hash ratusan password butuh waktu; jangan sampai terpotong batas waktu PHP.
        @set_time_limit(0);

        if ($request->hasFile('accounts_file')) {
            // ---- Mode 1: impor dari file (CSV / XLSX / DOCX) ----
            $request->validate([
                'accounts_file' => ['required', 'file', 'extensions:csv,txt,docx,xlsx', 'max:5120'],
            ], [
                'accounts_file.max' => 'Ukuran file maksimal 5 MB.',
                'accounts_file.extensions' => 'File harus berformat CSV, XLSX, atau DOCX.',
                'accounts_file.file' => 'File gagal diunggah. Coba lagi.',
            ]);

            $file = $request->file('accounts_file');
            $rows = match (strtolower($file->getClientOriginalExtension())) {
                'csv', 'txt' => $this->rowsFromCsv($file),
                'docx' => $this->rowsFromDocx($file),
                'xlsx' => $this->rowsFromXlsx($file),
                default => throw ValidationException::withMessages([
                    'accounts_file' => 'File harus berformat CSV, XLSX, atau DOCX.',
                ]),
            };

            $accounts = $this->accountsFromRows($rows);
            $errorField = 'accounts_file';
        } elseif ($request->exists('accounts_file')) {
            throw ValidationException::withMessages([
                'accounts_file' => 'Pilih file CSV, DOCX, atau XLSX yang berisi data akun.',
            ]);
        } else {
            // ---- Mode 2: input manual (satu atau beberapa baris dari form) ----
            $input = $request->input('accounts');

            if (! is_array($input)) {
                $input = [$request->only(self::REQUIRED_HEADERS)];
            }

            $accounts = [];
            foreach (array_values($input) as $i => $row) {
                $row = is_array($row) ? $row : [];
                $accounts[] = [
                    'name' => trim((string) ($row['name'] ?? '')),
                    'email' => trim((string) ($row['email'] ?? '')),
                    'role' => trim((string) ($row['role'] ?? '')),
                    'class_name' => trim((string) ($row['class_name'] ?? '')),
                    'password' => (string) ($row['password'] ?? ''),
                    '_line' => $i + 1,
                ];
            }
            $errorField = 'accounts';
        }

        if (count($accounts) > self::MAX_ACCOUNTS) {
            throw ValidationException::withMessages([
                $errorField => 'Maksimal '.self::MAX_ACCOUNTS.' akun dalam satu kali kirim. Pecah datamu menjadi beberapa file.',
            ]);
        }

        $problems = $this->validateAccounts($accounts, $errorField === 'accounts_file' ? 'Baris' : 'Akun ke-');

        if ($problems !== []) {
            $shown = array_slice($problems, 0, 25);
            if (count($problems) > 25) {
                $shown[] = '... dan '.(count($problems) - 25).' kesalahan lain.';
            }

            $old = collect($accounts)
                ->map(fn ($account) => collect($account)->except(['password', '_line'])->all())
                ->all();

            return back()
                ->withErrors(['import' => $shown])
                ->withInput($errorField === 'accounts' ? ['accounts' => $old] : []);
        }

        $now = now();
        $records = [];
        foreach ($accounts as $account) {
            $initials = preg_replace('/[^A-Za-z]/', '', $account['name']);

            $records[] = [
                'name' => $account['name'],
                'email' => strtolower($account['email']),
                'role' => $account['role'],
                'class_name' => $account['class_name'],
                'avatar' => strtoupper(substr($initials ?: 'US', 0, 2)),
                // cost 10 = tetap aman, tapi ~4x lebih cepat dari 12 untuk impor massal
                'password' => Hash::make($account['password'], ['rounds' => 10]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($records) {
            foreach (array_chunk($records, 200) as $chunk) {
                User::insert($chunk);
            }
        });

        $count = count($records);

        return redirect()->route('accounts.index')
            ->with('success', "{$count} akun berhasil dibuat dan disimpan ke database.");
    }

    /**
     * Cek semua akun sekaligus. Mengembalikan daftar pesan kesalahan
     * (kosong = semua valid). Tiap pesan menyebut nomor baris/akun-nya.
     */
    private function validateAccounts(array &$accounts, string $label): array
    {
        $problems = [];
        $seenEmails = [];
        $emailsToCheck = [];

        foreach ($accounts as $index => &$account) {
            $line = $account['_line'] ?? ($index + 1);
            $where = $label === 'Baris' ? "Baris {$line}" : "{$label}{$line}";
            $rowProblems = [];

            $account['role'] = $this->normalizeRole($account['role']);

            if ($account['name'] === '') {
                $rowProblems[] = 'nama wajib diisi';
            } elseif (mb_strlen($account['name']) > 150) {
                $rowProblems[] = 'nama maksimal 150 karakter';
            }

            $email = strtolower($account['email']);
            if ($email === '') {
                $rowProblems[] = 'email wajib diisi';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
                $rowProblems[] = "email \"{$account['email']}\" tidak valid";
            } elseif (isset($seenEmails[$email])) {
                $rowProblems[] = "email \"{$account['email']}\" sama dengan baris/akun ke-{$seenEmails[$email]}";
            } else {
                $seenEmails[$email] = $line;
                $emailsToCheck[$email] = $where;
            }

            if (! in_array($account['role'], ['reporter', 'admin'], true)) {
                $rowProblems[] = 'peran harus reporter atau admin';
            }

            if ($account['class_name'] === '') {
                $rowProblems[] = 'kelas / jabatan wajib diisi';
            } elseif (mb_strlen($account['class_name']) > 100) {
                $rowProblems[] = 'kelas / jabatan maksimal 100 karakter';
            }

            if (mb_strlen($account['password']) < 8) {
                $rowProblems[] = 'password minimal 8 karakter';
            }

            if ($rowProblems !== []) {
                $problems[] = "{$where}: ".implode(', ', $rowProblems).'.';
            }
        }
        unset($account);

        // Satu query per 500 email (bukan satu query per akun).
        foreach (array_chunk(array_keys($emailsToCheck), 500) as $chunk) {
            foreach (User::whereIn('email', $chunk)->pluck('email') as $existing) {
                $where = $emailsToCheck[strtolower($existing)] ?? null;
                if ($where !== null) {
                    $problems[] = "{$where}: email \"{$existing}\" sudah terdaftar.";
                }
            }
        }

        return $problems;
    }

    /** Terima penulisan peran yang umum, mis. "Siswa", "Guru", "Pelapor", "Admin". */
    private function normalizeRole(string $role): string
    {
        $role = strtolower(trim($role));

        return match ($role) {
            'reporter', 'pelapor', 'siswa', 'guru', 'murid' => 'reporter',
            'admin', 'administrator', 'petugas' => 'admin',
            default => $role,
        };
    }

    /**
     * Ubah baris-baris mentah (array of ['line' => no, 'cells' => [...]]) menjadi akun.
     * Baris pertama = header. Urutan kolom bebas, kolom ekstra diabaikan.
     */
    private function accountsFromRows(array $rows): array
    {
        if ($rows === []) {
            throw ValidationException::withMessages([
                'accounts_file' => 'File kosong. Gunakan templat yang tersedia.',
            ]);
        }

        $headerRow = array_shift($rows);
        $columns = [];

        foreach ($headerRow['cells'] as $position => $header) {
            $key = strtolower(trim((string) $header));
            $key = preg_replace('/^\xEF\xBB\xBF/', '', $key);
            $key = trim(preg_replace('/[^a-z]+/', '_', $key), '_');
            $key = self::HEADER_ALIASES[$key] ?? $key;

            if (in_array($key, self::REQUIRED_HEADERS, true) && ! isset($columns[$key])) {
                $columns[$key] = $position;
            }
        }

        $missing = array_diff(self::REQUIRED_HEADERS, array_keys($columns));
        if ($missing !== []) {
            throw ValidationException::withMessages([
                'accounts_file' => 'Kolom belum lengkap. Baris pertama harus berisi: name, email, role, class_name, password. Yang belum ada: '.implode(', ', $missing).'.',
            ]);
        }

        $accounts = [];
        foreach ($rows as $row) {
            $cells = $row['cells'];

            if (count(array_filter($cells, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // baris kosong dilewati
            }

            $account = ['_line' => $row['line']];
            foreach ($columns as $key => $position) {
                $value = (string) ($cells[$position] ?? '');
                $account[$key] = $key === 'password' ? $value : trim($value);
            }
            $accounts[] = $account;

            if (count($accounts) > self::MAX_ACCOUNTS) {
                throw ValidationException::withMessages([
                    'accounts_file' => 'Maksimal '.self::MAX_ACCOUNTS.' akun dalam satu file. Pecah datamu menjadi beberapa file.',
                ]);
            }
        }

        if ($accounts === []) {
            throw ValidationException::withMessages([
                'accounts_file' => 'File tidak berisi data akun setelah baris header.',
            ]);
        }

        return $accounts;
    }

    private function rowsFromCsv(UploadedFile $file): array
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false || trim($contents) === '') {
            throw ValidationException::withMessages([
                'accounts_file' => 'File CSV kosong. Gunakan templat yang tersedia.',
            ]);
        }

        // Buang BOM UTF-8, dan ubah encoding Windows-1252 (Excel lama) ke UTF-8.
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        // Excel versi Indonesia menyimpan CSV dengan titik koma (;), bukan koma.
        $firstLine = strtok($contents, "\r\n") ?: '';
        $delimiter = ',';
        $best = substr_count($firstLine, ',');
        foreach ([';', "\t", '|'] as $candidate) {
            $count = substr_count($firstLine, $candidate);
            if ($count > $best) {
                $best = $count;
                $delimiter = $candidate;
            }
        }

        $handle = fopen('php://temp', 'r+b');
        fwrite($handle, $contents);
        rewind($handle);

        $rows = [];
        $line = 0;

        try {
            while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
                $line++;
                $rows[] = ['line' => $line, 'cells' => array_map(fn ($v) => (string) $v, $row)];
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    private function rowsFromXlsx(UploadedFile $file): array
    {
        $archive = $this->openOfficeArchive($file);

        try {
            $workbook = $this->officeXml($archive, 'xl/workbook.xml');
            $workbook->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $sheets = $workbook->xpath('/x:workbook/x:sheets/x:sheet');

            if ($sheets === false || $sheets === []) {
                throw ValidationException::withMessages([
                    'accounts_file' => 'File Excel tidak memiliki lembar kerja.',
                ]);
            }

            $relationshipId = (string) $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id;
            $relationships = $this->officeXml($archive, 'xl/_rels/workbook.xml.rels');
            $sheetTarget = null;

            foreach ($relationships->xpath('//*[local-name()="Relationship"]') ?: [] as $relationship) {
                if ((string) $relationship['Id'] === $relationshipId
                    && ! str_starts_with((string) $relationship['TargetMode'], 'External')) {
                    $sheetTarget = $this->resolveOfficePath('xl/workbook.xml', (string) $relationship['Target']);
                    break;
                }
            }

            if ($sheetTarget === null) {
                throw ValidationException::withMessages([
                    'accounts_file' => 'Lembar pertama pada file Excel tidak dapat dibaca.',
                ]);
            }

            $sharedStrings = [];
            if ($archive->locateName('xl/sharedStrings.xml') !== false) {
                $stringsXml = $this->officeXml($archive, 'xl/sharedStrings.xml');
                $stringsXml->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach ($stringsXml->xpath('/x:sst/x:si') ?: [] as $string) {
                    $sharedStrings[] = implode('', array_map(
                        static fn ($text) => (string) $text,
                        $string->xpath('.//*[local-name()="t"]') ?: []
                    ));
                }
            }

            $sheet = $this->officeXml($archive, $sheetTarget);
            $sheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rows = [];

            foreach ($sheet->xpath('/x:worksheet/x:sheetData/x:row') ?: [] as $row) {
                $cells = [];

                foreach ($row->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                    if ($cell->xpath('./*[local-name()="f"]') && $cell->xpath('./*[local-name()="v"]') === []) {
                        throw ValidationException::withMessages([
                            'accounts_file' => 'Hapus formula dari lembar Excel; setiap data akun harus berupa nilai teks biasa.',
                        ]);
                    }

                    preg_match('/^([A-Z]+)/i', (string) $cell['r'], $columnMatch);
                    $column = $this->spreadsheetColumnIndex($columnMatch[1] ?? '');
                    $type = (string) $cell['t'];

                    if ($type === 'inlineStr') {
                        $valueNodes = $cell->xpath('./*[local-name()="is"]//*[local-name()="t"]') ?: [];
                        $value = implode('', array_map(static fn ($text) => (string) $text, $valueNodes));
                    } else {
                        $value = (string) ($cell->xpath('./*[local-name()="v"]')[0] ?? '');
                        if ($type === 's') {
                            $value = $sharedStrings[(int) $value] ?? '';
                        }
                    }

                    if ($column >= 0) {
                        $cells[$column] = trim($value);
                    }
                }

                if ($cells !== []) {
                    // Isi sel yang kosong supaya urutan kolom tetap benar.
                    $filled = array_fill(0, max(array_keys($cells)) + 1, '');
                    foreach ($cells as $position => $value) {
                        $filled[$position] = $value;
                    }

                    $rows[] = [
                        'line' => (int) ($row['r'] ?? count($rows) + 1),
                        'cells' => $filled,
                    ];
                }
            }

            return $rows;
        } finally {
            $archive->close();
        }
    }

    private function rowsFromDocx(UploadedFile $file): array
    {
        $archive = $this->openOfficeArchive($file);

        try {
            $document = $this->officeXml($archive, 'word/document.xml');
            $document->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $tables = $document->xpath('/w:document/w:body/w:tbl');

            if ($tables === false || $tables === []) {
                throw ValidationException::withMessages([
                    'accounts_file' => 'File Word harus berisi satu tabel dengan data akun.',
                ]);
            }

            $rows = [];
            $line = 0;
            foreach ($tables[0]->xpath('./w:tr') ?: [] as $row) {
                $line++;
                $cells = [];
                foreach ($row->xpath('./w:tc') ?: [] as $cell) {
                    $paragraphs = [];
                    foreach ($cell->xpath('.//w:p') ?: [] as $paragraph) {
                        $paragraphs[] = implode('', array_map(
                            static fn ($node) => (string) $node,
                            $paragraph->xpath('.//w:t') ?: []
                        ));
                    }
                    $cells[] = trim(implode(' ', $paragraphs));
                }

                $rows[] = ['line' => $line, 'cells' => $cells];
            }

            return $rows;
        } finally {
            $archive->close();
        }
    }

    private function openOfficeArchive(UploadedFile $file): \ZipArchive
    {
        if (! class_exists(\ZipArchive::class)) {
            throw ValidationException::withMessages([
                'accounts_file' => 'Pembacaan Excel dan Word memerlukan ekstensi PHP ZIP. Aktifkan extension=zip di php.ini.',
            ]);
        }

        $archive = new \ZipArchive;
        if ($archive->open($file->getRealPath()) !== true) {
            throw ValidationException::withMessages([
                'accounts_file' => 'Isi file bukan dokumen Excel atau Word yang valid.',
            ]);
        }

        if ($archive->numFiles > 1000) {
            $archive->close();
            throw ValidationException::withMessages([
                'accounts_file' => 'Dokumen berisi terlalu banyak bagian dan tidak dapat diproses.',
            ]);
        }

        $uncompressedSize = 0;
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $stat = $archive->statIndex($index);
            $uncompressedSize += (int) ($stat['size'] ?? 0);
            if ($uncompressedSize > 30 * 1024 * 1024) {
                $archive->close();
                throw ValidationException::withMessages([
                    'accounts_file' => 'Ukuran isi dokumen terlalu besar. Batas ukuran setelah diekstrak adalah 30 MB.',
                ]);
            }
        }

        return $archive;
    }

    private function officeXml(\ZipArchive $archive, string $path): \SimpleXMLElement
    {
        $contents = $archive->getFromName($path);
        if ($contents === false) {
            throw ValidationException::withMessages([
                'accounts_file' => 'Struktur dokumen tidak lengkap atau tidak dapat dibaca.',
            ]);
        }

        $previousErrorMode = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($contents, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOBLANKS);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }

        if ($xml === false) {
            throw ValidationException::withMessages([
                'accounts_file' => 'Data XML di dalam dokumen tidak valid.',
            ]);
        }

        return $xml;
    }

    private function resolveOfficePath(string $basePath, string $target): string
    {
        $parts = str_starts_with($target, '/') ? [] : explode('/', dirname($basePath));

        foreach (explode('/', ltrim($target, '/')) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                if ($parts === []) {
                    throw ValidationException::withMessages([
                        'accounts_file' => 'Rujukan lembar kerja pada file Excel tidak valid.',
                    ]);
                }
                array_pop($parts);

                continue;
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    private function spreadsheetColumnIndex(string $letters): int
    {
        $index = 0;
        foreach (str_split(strtoupper($letters)) as $letter) {
            $index = ($index * 26) + ord($letter) - ord('A') + 1;
        }

        return $letters === '' ? -1 : $index - 1;
    }
}
