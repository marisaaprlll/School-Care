<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UserRoleByEmailTest extends TestCase
{
    public function test_email_domain_controls_role(): void
    {
        $this->assertSame('admin', User::roleFromEmail('admin@schoolcare.id'));
        $this->assertSame('admin', User::roleFromEmail('petugas@schoolcare.id'));
        $this->assertSame('reporter', User::roleFromEmail('siswa@gmail.com'));
        $this->assertSame('reporter', User::roleFromEmail('guru@outlook.com'));
    }
}
