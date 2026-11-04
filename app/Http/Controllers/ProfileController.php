<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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

        return view('accounts.index', [
            'users' => User::whereKeyNot(request()->user()->id)
                ->orderBy('role')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function createUser(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Hanya admin yang dapat menambah akun.');

        if ($request->hasFile('accounts_file')) {
            $request->validate([
                'accounts_file' => ['required', 'file', 'extensions:csv,docx,xlsx', 'max:2048'],
            ]);

            $file = $request->file('accounts_file');
            $accounts = match (strtolower($file->getClientOriginalExtension())) {
                'csv' => $this->accountsFromCsv($file),
                'docx' => $this->accountsFromDocx($file),
                'xlsx' => $this->accountsFromXlsx($file),
            };

            $request->merge(['accounts' => $accounts]);
        } elseif ($request->exists('accounts_file')) {
            throw ValidationException::withMessages([
                'accounts_file' => 'Pilih file CSV, DOCX, atau XLSX yang berisi data akun.',
            ]);
        } elseif (! $request->exists('accounts') && $request->has(['name', 'email', 'role', 'class_name', 'password'])) {
            $request->merge(['accounts' => [$request->only(['name', 'email', 'role', 'class_name', 'password'])]]);
        }

        $validator = Validator::make($request->all(), [
            'accounts' => ['required', 'array', 'min:1', 'max:100'],
            'accounts.*' => ['required', 'array'],
            'accounts.*.name' => ['required', 'string', 'max:150'],
            'accounts.*.email' => ['required', 'email', 'max:150', 'distinct:ignore_case', 'unique:users,email'],
            'accounts.*.role' => ['required', 'in:reporter,admin'],
            'accounts.*.class_name' => ['required', 'string', 'max:100'],
            'accounts.*.password' => ['required', 'string', 'min:8'],
        ], [], [
            'accounts.*.name' => 'nama',
            'accounts.*.email' => 'email',
            'accounts.*.role' => 'peran',
            'accounts.*.class_name' => 'kelas / jabatan',
            'accounts.*.password' => 'password',
        ]);

        if ($validator->fails()) {
            $accounts = $request->input('accounts', []);
            if (is_array($accounts)) {
                foreach ($accounts as &$account) {
                    if (is_array($account)) {
                        unset($account['password']);
                    }
                }
                unset($account);
            }

            return back()
                ->withErrors($validator)
                ->withInput([...$request->except('accounts'), 'accounts' => $accounts]);
        }

        $data = $validator->validated();

        DB::transaction(function () use ($data) {
            foreach ($data['accounts'] as $account) {
                $initials = preg_replace('/[^A-Za-z]/', '', $account['name']);

                User::create([
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'role' => $account['role'],
                    'class_name' => $account['class_name'],
                    'avatar' => strtoupper(substr($initials ?: 'US', 0, 2)),
                    'password' => Hash::make($account['password']),
                ]);
            }
        });

        $count = count($data['accounts']);

        return redirect()->route('accounts.index')
            ->with('success', "{$count} akun berhasil dibuat dan disimpan ke database.");
    }

    private function accountsFromCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'accounts_file' => 'File CSV tidak dapat dibaca.',
            ]);
        }

        try {
            $headers = fgetcsv($handle, escape: '');

            if ($headers === false) {
                throw ValidationException::withMessages([
                    'accounts_file' => 'File CSV kosong. Gunakan templat yang tersedia.',
                ]);
            }

            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
            $headers = array_map(fn ($header) => strtolower(trim((string) $header)), $headers);
            $requiredHeaders = ['name', 'email', 'role', 'class_name', 'password'];

            if (count($headers) !== count(array_unique($headers))
                || count($headers) !== count($requiredHeaders)
                || array_diff($requiredHeaders, $headers) !== []) {
                throw ValidationException::withMessages([
                    'accounts_file' => 'Kolom CSV harus berisi: name, email, role, class_name, password.',
                ]);
            }

            $accounts = [];
            $line = 1;

            while (($row = fgetcsv($handle, escape: '')) !== false) {
                $line++;

                if (count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                if (count($row) !== count($headers)) {
                    throw ValidationException::withMessages([
                        'accounts_file' => "Jumlah kolom pada baris {$line} tidak sesuai dengan templat.",
                    ]);
                }

                $accounts[] = array_combine($headers, array_map(
                    fn ($value) => trim((string) $value),
                    $row
                ));

                if (count($accounts) > 100) {
                    throw ValidationException::withMessages([
                        'accounts_file' => 'Maksimal 100 akun dapat dibuat dalam satu kali impor.',
                    ]);
                }
            }

            if ($accounts === []) {
                throw ValidationException::withMessages([
                    'accounts_file' => 'File CSV tidak berisi data akun.',
                ]);
            }

            return $accounts;
        } finally {
            fclose($handle);
        }
    }

    private function accountsFromXlsx(UploadedFile $file): array
    {
        $archive = $this->openOfficeArchive($file);

        try {
            $workbook = $this->officeXml($archive, 'xl/workbook.xml');
            $workbook->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $sheets = $workbook->xpath('/x:workbook/x:sheets/x:sheet');

            if ($sheets === false || $sheets === []) {
                throw ValidationException::withMessages([
                    'accounts_file' => 'File Excel tidak memiliki lembar kerja.',
                ]);
            }

            $relationshipId = (string) $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')->id;
            $relationships = $this->officeXml($archive, 'xl/_rels/workbook.xml.rels');
            $relationshipNodes = $relationships->xpath('//*[local-name()="Relationship"]');
            $sheetTarget = null;

            foreach ($relationshipNodes ?: [] as $relationship) {
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
            $sheet->registerXPathNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rows = [];

            foreach ($sheet->xpath('/x:worksheet/x:sheetData/x:row') ?: [] as $row) {
                $cells = [];

                foreach ($row->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                    if ($cell->xpath('./*[local-name()="f"]')) {
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
                    if (max(array_keys($cells)) >= 5) {
                        throw ValidationException::withMessages([
                            'accounts_file' => 'Lembar Excel hanya boleh memiliki lima kolom data akun.',
                        ]);
                    }

                    $rows[] = $cells;
                }
            }

            return $this->accountsFromTabularRows($rows);
        } finally {
            $archive->close();
        }
    }

    private function accountsFromDocx(UploadedFile $file): array
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
            foreach ($tables[0]->xpath('./w:tr') ?: [] as $row) {
                $cells = [];
                foreach ($row->xpath('./w:tc') ?: [] as $cell) {
                    $paragraphs = [];
                    foreach ($cell->xpath('.//w:p') ?: [] as $paragraph) {
                        $text = implode('', array_map(
                            static fn ($node) => (string) $node,
                            $paragraph->xpath('.//w:t') ?: []
                        ));
                        $paragraphs[] = $text;
                    }
                    $cells[] = trim(implode(' ', $paragraphs));
                }

                if (array_filter($cells, static fn ($value) => $value !== '') !== []) {
                    $rows[] = $cells;
                }
            }

            return $this->accountsFromTabularRows($rows);
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
            if ($uncompressedSize > 10 * 1024 * 1024) {
                $archive->close();
                throw ValidationException::withMessages([
                    'accounts_file' => 'Ukuran isi dokumen terlalu besar. Batas ukuran setelah diekstrak adalah 10 MB.',
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

    private function accountsFromTabularRows(array $rows): array
    {
        $headers = array_map(
            static fn ($header) => strtolower(trim((string) $header)),
            array_values($rows[0] ?? [])
        );
        $requiredHeaders = ['name', 'email', 'role', 'class_name', 'password'];

        if (count($headers) !== count($requiredHeaders)
            || count($headers) !== count(array_unique($headers))
            || array_diff($requiredHeaders, $headers) !== []) {
            throw ValidationException::withMessages([
                'accounts_file' => 'Baris pertama harus berisi lima kolom: name, email, role, class_name, password.',
            ]);
        }

        $accounts = [];
        foreach (array_slice($rows, 1) as $index => $row) {
            if (count(array_filter($row, static fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            if (count($row) !== count($headers) || array_diff(array_keys($row), range(0, count($headers) - 1)) !== []) {
                throw ValidationException::withMessages([
                    'accounts_file' => 'Jumlah kolom pada baris '.($index + 2).' tidak sesuai dengan header.',
                ]);
            }

            $values = array_replace(array_fill(0, count($headers), ''), $row);
            $accounts[] = array_combine($headers, array_map(
                static fn ($value) => trim((string) $value),
                $values
            ));

            if (count($accounts) > 100) {
                throw ValidationException::withMessages([
                    'accounts_file' => 'Maksimal 100 akun dapat dibuat dalam satu kali impor.',
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
}
