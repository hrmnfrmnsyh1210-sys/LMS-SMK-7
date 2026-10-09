<x-layouts.app title="Profil Akun">
    <x-page-header title="Profil Akun" subtitle="Perbarui data diri dan email Anda." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-body space-y-3 text-sm">
            <div>
                <div class="text-slate-500">Username</div>
                <div class="font-medium">{{ $user->username }}</div>
            </div>
            <div>
                <div class="text-slate-500">Role</div>
                <div class="font-medium">{{ $user->label_role }}</div>
            </div>
            @if ($user->role === 'siswa')
                <div>
                    <div class="text-slate-500">NIS / NISN</div>
                    <div class="font-medium">{{ $profil->nis }} / {{ $profil->nisn }}</div>
                </div>
                <div>
                    <div class="text-slate-500">Kelas</div>
                    <div class="font-medium">{{ $profil->kelasPada()?->nama_kelas ?? '-' }}</div>
                </div>
            @elseif (in_array($user->role, ['guru', 'kepsek']))
                <div>
                    <div class="text-slate-500">NIP</div>
                    <div class="font-medium">{{ $profil->nip ?: '-' }}</div>
                </div>
            @endif
            <div>
                <div class="text-slate-500">Login terakhir</div>
                <div class="font-medium">{{ tanggal_indo($user->last_login, true, true) }}</div>
            </div>
            <a href="{{ route('profil.password') }}" class="btn-secondary w-full">
                <x-icon name="key" class="h-4 w-4" /> Ganti Password
            </a>
        </div>

        <form method="POST" action="{{ route('profil.update') }}" class="card card-body space-y-4 lg:col-span-2">
            @csrf
            @method('PUT')

            @if ($user->role === 'siswa')
                <div>
                    <div class="label">Nama</div>
                    <div class="text-sm">{{ $profil->nama }} <span class="text-xs text-slate-500">(hubungi operator untuk mengubah nama)</span></div>
                </div>
            @else
                <x-field.input name="nama" label="Nama lengkap" :value="$profil?->nama" required />
            @endif

            <x-field.input name="email" type="email" label="Email" :value="$user->email" />

            @if ($user->role !== 'siswa')
                <x-field.input name="no_hp" label="Nomor HP" :value="$profil?->no_hp" />
            @endif

            @if (in_array($user->role, ['kepsek', 'guru']))
                <x-field.select name="jenis_kelamin" label="Jenis kelamin" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']" :value="$profil?->jenis_kelamin" required />
            @endif

            @if (in_array($user->role, ['guru', 'siswa']))
                <x-field.textarea name="alamat" label="Alamat" :value="$profil?->alamat" />
            @endif

            @if ($user->role === 'siswa')
                <x-field.input name="nama_ortu" label="Nama orang tua/wali" :value="$profil?->nama_ortu" />
            @endif

            <div class="flex justify-end border-t border-slate-100 pt-4">
                <button type="submit" class="btn-primary">Simpan Profil</button>
            </div>
        </form>
    </div>
</x-layouts.app>
