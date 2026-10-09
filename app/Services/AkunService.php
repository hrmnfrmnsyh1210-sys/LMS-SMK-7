<?php

namespace App\Services;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Kepsek;
use App\Models\OrangTua;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Membuat/mengubah akun beserta profilnya dalam satu transaksi.
 * Password default akun baru = NIS (siswa) / NIP (guru & kepsek) / username (operator & orang tua).
 */
class AkunService
{
    private const FIELD_USER = ['username', 'email', 'status'];

    public function buatGuru(array $data): Guru
    {
        return DB::transaction(function () use ($data) {
            $username = $data['username'] ?? null ?: $data['nip'];
            $user = $this->buatUser('guru', $username, $data);

            return $user->guru()->create(Arr::only($data, ['nip', 'nama', 'jenis_kelamin', 'no_hp', 'alamat']));
        });
    }

    public function buatSiswa(array $data): Siswa
    {
        return DB::transaction(function () use ($data) {
            $user = $this->buatUser('siswa', $data['nis'], $data);

            return $user->siswa()->create(Arr::only($data, [
                'nis', 'nisn', 'nama', 'jenis_kelamin', 'tgl_lahir', 'alamat', 'nama_ortu',
            ]));
        });
    }

    public function buatKepsek(array $data): Kepsek
    {
        return DB::transaction(function () use ($data) {
            $user = $this->buatUser('kepsek', $data['username'], $data);

            return $user->kepsek()->create(Arr::only($data, ['nip', 'nama', 'no_hp', 'jenis_kelamin']));
        });
    }

    /** Akun orang tua beserta anak yang dipantau (siswa_ids). */
    public function buatOrtu(array $data): OrangTua
    {
        return DB::transaction(function () use ($data) {
            $user = $this->buatUser('ortu', $data['username'], $data);
            $ortu = $user->ortu()->create(Arr::only($data, ['nama', 'hubungan', 'no_hp', 'alamat']));
            $ortu->anak()->sync($data['siswa_ids'] ?? []);

            return $ortu;
        });
    }

    /**
     * Buat akun orang tua untuk siswa aktif di kelas yang belum punya akun orang tua.
     * Username "ortu{NIS}", nama dari kolom nama_ortu siswa.
     *
     * @return int jumlah akun yang dibuat
     */
    public function buatOrtuMassal(Kelas $kelas): int
    {
        $siswa = $kelas->siswa()
            ->where('siswa.status', 'aktif')
            ->whereDoesntHave('orangTua')
            ->get()
            ->reject(fn (Siswa $s) => User::where('username', 'ortu'.$s->nis)->exists());

        $siswa->each(fn (Siswa $s) => $this->buatOrtu([
            'username' => 'ortu'.$s->nis,
            'nama' => $s->nama_ortu ?: 'Orang Tua '.$s->nama,
            'hubungan' => 'wali',
            'siswa_ids' => [$s->id],
        ]));

        return $siswa->count();
    }

    /** Ubah data user + profil. */
    public function perbarui(Guru|Siswa|Kepsek|OrangTua $profil, array $data, array $fieldProfil): void
    {
        DB::transaction(function () use ($profil, $data, $fieldProfil) {
            $profil->user->update(Arr::only($data, self::FIELD_USER));
            $profil->update(Arr::only($data, $fieldProfil));
        });
    }

    public function passwordDefault(User $user): string
    {
        return match ($user->role) {
            'siswa' => $user->siswa?->nis,
            'guru' => $user->guru?->nip,
            'kepsek' => $user->kepsek?->nip,
            default => null,
        } ?: $user->username;
    }

    public function resetPassword(User $user): string
    {
        $password = $this->passwordDefault($user);
        $user->update(['password' => $password, 'wajib_ganti_password' => true]);

        return $password;
    }

    private function buatUser(string $role, string $username, array $data): User
    {
        $passwordDefault = $role === 'kepsek' ? ($data['nip'] ?? null ?: $username) : $username;

        return User::create([
            'username' => $username,
            'email' => $data['email'] ?? null ?: null,
            'password' => $data['password'] ?? null ?: $passwordDefault,
            'role' => $role,
            'status' => $data['status'] ?? 'aktif',
            'wajib_ganti_password' => empty($data['password']),
        ]);
    }
}
