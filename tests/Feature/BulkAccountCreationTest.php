<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BulkAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_multiple_accounts_with_selected_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('Impor akun dari file')
            ->assertSee('accounts_file')
            ->assertSee('Word (.docx)')
            ->assertSee('Excel (.xlsx)')
            ->assertSee('template-akun.csv')
            ->assertDontSee('Tambah akun')
            ->assertDontSee('accountRowTemplate');

        $this->actingAs($admin)
            ->post(route('accounts.store'), [
                'accounts' => [
                    [
                        'name' => 'Siswa School Care',
                        'email' => 'siswa@schoolcare.id',
                        'role' => 'reporter',
                        'class_name' => 'XII RPL A',
                        'password' => 'Reporter123!',
                    ],
                    [
                        'name' => 'Petugas Sekolah',
                        'email' => 'petugas@example.com',
                        'role' => 'admin',
                        'class_name' => 'Petugas Sarpras',
                        'password' => 'Petugas123!',
                    ],
                ],
            ])
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHas('success', '2 akun berhasil dibuat dan disimpan ke database.');

        $this->assertDatabaseHas('users', [
            'name' => 'Siswa School Care',
            'email' => 'siswa@schoolcare.id',
            'role' => 'reporter',
            'class_name' => 'XII RPL A',
        ]);
        $this->assertDatabaseHas('users', [
            'name' => 'Petugas Sekolah',
            'email' => 'petugas@example.com',
            'role' => 'admin',
            'class_name' => 'Petugas Sarpras',
        ]);

        $this->assertTrue(Hash::check('Reporter123!', User::where('email', 'siswa@schoolcare.id')->firstOrFail()->password));
        $this->assertTrue(Hash::check('Petugas123!', User::where('email', 'petugas@example.com')->firstOrFail()->password));

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.submit'), [
            'email' => 'siswa@schoolcare.id',
            'password' => 'Reporter123!',
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'siswa@schoolcare.id', 'role' => 'reporter']);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.submit'), [
            'email' => 'petugas@example.com',
            'password' => 'Petugas123!',
        ])->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'petugas@example.com', 'role' => 'admin']);
    }

    public function test_duplicate_emails_in_a_batch_do_not_create_any_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('accounts.index'))
            ->post(route('accounts.store'), [
                'accounts' => [
                    [
                        'name' => 'Siswa Satu',
                        'email' => 'siswa@example.com',
                        'role' => 'reporter',
                        'class_name' => 'XII RPL A',
                        'password' => 'Reporter123!',
                    ],
                    [
                        'name' => 'Siswa Dua',
                        'email' => 'SISWA@example.com',
                        'role' => 'reporter',
                        'class_name' => 'XII RPL B',
                        'password' => 'Reporter456!',
                    ],
                ],
            ])
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHasErrors('accounts.1.email')
            ->assertSessionMissing('_old_input.accounts.1.password');

        $this->assertDatabaseMissing('users', ['email' => 'siswa@example.com']);
    }

    public function test_admin_can_create_accounts_from_a_csv_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $csv = UploadedFile::fake()->createWithContent(
            'accounts.csv',
            "\xEF\xBB\xBFname,email,role,class_name,password\r\n".
            "Siswa CSV,siswa.csv@example.com,reporter,XII RPL A,Reporter123!\r\n".
            "Petugas CSV,petugas.csv@example.com,admin,Petugas Sarpras,Petugas123!\r\n"
        );

        $this->actingAs($admin)
            ->post(route('accounts.store'), ['accounts_file' => $csv])
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHas('success', '2 akun berhasil dibuat dan disimpan ke database.');

        $this->assertDatabaseHas('users', [
            'name' => 'Siswa CSV',
            'email' => 'siswa.csv@example.com',
            'role' => 'reporter',
            'class_name' => 'XII RPL A',
        ]);
        $this->assertDatabaseHas('users', [
            'name' => 'Petugas CSV',
            'email' => 'petugas.csv@example.com',
            'role' => 'admin',
            'class_name' => 'Petugas Sarpras',
        ]);
        $this->assertTrue(Hash::check('Reporter123!', User::where('email', 'siswa.csv@example.com')->firstOrFail()->password));
    }

    public function test_invalid_csv_data_does_not_create_any_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $csv = UploadedFile::fake()->createWithContent(
            'accounts.csv',
            "name,email,role,class_name,password\n".
            "Siswa Valid,valid.csv@example.com,reporter,XII RPL A,Reporter123!\n".
            "Siswa Invalid,invalid.csv@example.com,manager,XII RPL B,short\n"
        );

        $this->actingAs($admin)
            ->from(route('accounts.index'))
            ->post(route('accounts.store'), ['accounts_file' => $csv])
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHasErrors(['accounts.1.role', 'accounts.1.password'])
            ->assertSessionMissing('_old_input.accounts.0.password');

        $this->assertDatabaseMissing('users', ['email' => 'valid.csv@example.com']);
        $this->assertDatabaseMissing('users', ['email' => 'invalid.csv@example.com']);
    }

    public function test_csv_import_requires_the_expected_header(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $csv = UploadedFile::fake()->createWithContent('accounts.csv', "name,email\nSiswa,siswa@example.com\n");

        $this->actingAs($admin)
            ->from(route('accounts.index'))
            ->post(route('accounts.store'), ['accounts_file' => $csv])
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHasErrors('accounts_file');

        $this->assertDatabaseMissing('users', ['email' => 'siswa@example.com']);
    }

    public function test_admin_can_create_accounts_from_an_excel_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = $this->makeOfficeFile('accounts.xlsx', [
            '[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>',
            'xl/workbook.xml' => '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Accounts" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml' => $this->spreadsheetXml([
                ['name', 'email', 'role', 'class_name', 'password'],
                ['Excel Siswa', 'excel.siswa@example.com', 'reporter', 'XII RPL A', 'Excel1234!'],
            ]),
        ]);

        $this->actingAs($admin)
            ->post(route('accounts.store'), ['accounts_file' => $file])
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHas('success', '1 akun berhasil dibuat dan disimpan ke database.');

        $this->assertDatabaseHas('users', [
            'name' => 'Excel Siswa',
            'email' => 'excel.siswa@example.com',
            'role' => 'reporter',
            'class_name' => 'XII RPL A',
        ]);
        $this->assertTrue(Hash::check('Excel1234!', User::where('email', 'excel.siswa@example.com')->firstOrFail()->password));
    }

    public function test_admin_can_create_accounts_from_a_word_table(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = $this->makeOfficeFile('accounts.docx', [
            'word/document.xml' => $this->wordDocumentXml([
                ['name', 'email', 'role', 'class_name', 'password'],
                ['Word Petugas', 'word.petugas@example.com', 'admin', 'Petugas Sarpras', 'Word12345!'],
            ]),
        ]);

        $this->actingAs($admin)
            ->post(route('accounts.store'), ['accounts_file' => $file])
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHas('success', '1 akun berhasil dibuat dan disimpan ke database.');

        $this->assertDatabaseHas('users', [
            'name' => 'Word Petugas',
            'email' => 'word.petugas@example.com',
            'role' => 'admin',
            'class_name' => 'Petugas Sarpras',
        ]);
    }

    public function test_admin_can_read_excel_shared_strings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $rows = [
            ['name', 'email', 'role', 'class_name', 'password'],
            ['Excel Shared', 'excel.shared@example.com', 'reporter', 'XII RPL C', 'Shared1234!'],
        ];
        $file = $this->makeOfficeFile('accounts.xlsx', [
            '[Content_Types].xml' => '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"/>',
            'xl/workbook.xml' => '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Accounts" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
            'xl/sharedStrings.xml' => $this->sharedStringsXml($rows),
            'xl/worksheets/sheet1.xml' => $this->spreadsheetSharedStringsXml($rows),
        ]);

        $this->actingAs($admin)
            ->post(route('accounts.store'), ['accounts_file' => $file])
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHas('success', '1 akun berhasil dibuat dan disimpan ke database.');

        $this->assertDatabaseHas('users', ['email' => 'excel.shared@example.com', 'name' => 'Excel Shared']);
    }

    private function makeOfficeFile(string $name, array $entries): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'school-care-office-');
        $archive = new \ZipArchive;
        $this->assertSame(true, $archive->open($path, \ZipArchive::OVERWRITE));

        foreach ($entries as $entry => $contents) {
            $archive->addFromString($entry, $contents);
        }

        $archive->close();
        $contents = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    private function spreadsheetXml(array $rows): string
    {
        $xmlRows = '';
        foreach ($rows as $rowIndex => $row) {
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                $column = chr(ord('A') + $columnIndex);
                $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $cells .= '<c r="'.$column.($rowIndex + 1).'" t="inlineStr"><is><t>'.$escaped.'</t></is></c>';
            }
            $xmlRows .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }

        return '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$xmlRows.'</sheetData></worksheet>';
    }

    private function spreadsheetSharedStringsXml(array $rows): string
    {
        $xmlRows = '';
        $valueIndex = 0;
        foreach ($rows as $rowIndex => $row) {
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                $column = chr(ord('A') + $columnIndex);
                $cells .= '<c r="'.$column.($rowIndex + 1).'" t="s"><v>'.$valueIndex.'</v></c>';
                $valueIndex++;
            }
            $xmlRows .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }

        return '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$xmlRows.'</sheetData></worksheet>';
    }

    private function sharedStringsXml(array $rows): string
    {
        $strings = '';
        foreach ($rows as $row) {
            foreach ($row as $value) {
                $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $strings .= '<si><t>'.$escaped.'</t></si>';
            }
        }

        return '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.$strings.'</sst>';
    }

    private function wordDocumentXml(array $rows): string
    {
        $tableRows = '';
        foreach ($rows as $row) {
            $cells = '';
            foreach ($row as $value) {
                $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $cells .= '<w:tc><w:p><w:r><w:t>'.$escaped.'</w:t></w:r></w:p></w:tc>';
            }
            $tableRows .= '<w:tr>'.$cells.'</w:tr>';
        }

        return '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:tbl>'.$tableRows.'</w:tbl></w:body></w:document>';
    }
}
