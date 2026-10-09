<?php

namespace Tests;

use App\Models\Guru;
use App\Models\Kepsek;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Cache statis per request harus dibersihkan antar test.
        TahunAjaran::lupakanAktif();
        Pengaturan::lupakan();

        $this->withoutVite();
    }

    protected function operator(): User
    {
        return User::factory()->operator()->create();
    }

    protected function userRole(string $role): User
    {
        return match ($role) {
            'operator' => $this->operator(),
            'kepsek' => Kepsek::factory()->create()->user,
            'guru' => Guru::factory()->create()->user,
            'siswa' => Siswa::factory()->create()->user,
        };
    }
}
