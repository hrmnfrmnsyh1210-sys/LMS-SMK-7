<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['operator', 'kepsek', 'guru', 'siswa', 'ortu'];

    public const LABEL_ROLE = [
        'operator' => 'Operator',
        'kepsek' => 'Kepala Sekolah',
        'guru' => 'Guru',
        'siswa' => 'Siswa',
        'ortu' => 'Orang Tua',
    ];

    protected $fillable = [
        'username', 'password', 'email', 'role', 'status', 'wajib_ganti_password', 'last_login',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login' => 'datetime',
            'wajib_ganti_password' => 'boolean',
        ];
    }

    public function operator(): HasOne
    {
        return $this->hasOne(Operator::class);
    }

    public function kepsek(): HasOne
    {
        return $this->hasOne(Kepsek::class);
    }

    public function guru(): HasOne
    {
        return $this->hasOne(Guru::class);
    }

    public function siswa(): HasOne
    {
        return $this->hasOne(Siswa::class);
    }

    /** Profil orang tua; nama relasi sama dengan kunci role agar profil() bekerja. */
    public function ortu(): HasOne
    {
        return $this->hasOne(OrangTua::class);
    }

    /** Profil sesuai role (operator/kepsek/guru/siswa/ortu). */
    public function profil(): Operator|Kepsek|Guru|Siswa|OrangTua|null
    {
        return $this->{$this->role};
    }

    public function getNamaAttribute(): string
    {
        return $this->profil()?->nama ?? $this->username;
    }

    public function getLabelRoleAttribute(): string
    {
        return self::LABEL_ROLE[$this->role] ?? $this->role;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }

    public function dashboardRoute(): string
    {
        return $this->role.'.dashboard';
    }
}
