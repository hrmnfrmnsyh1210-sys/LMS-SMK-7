@php $edit = $guru->exists; @endphp

<x-layouts.app :title="$edit ? 'Ubah Guru' : 'Tambah Guru'">
    <x-page-header :title="$edit ? 'Ubah Data Guru' : 'Tambah Guru'"
                   :subtitle="$edit ? null : 'Username default = NIP. Password awal = NIP (atau username jika NIP kosong).'" />

    <form method="POST" action="{{ $edit ? route('operator.guru.update', $guru) : route('operator.guru.store') }}" class="card card-body space-y-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field.input name="nama" label="Nama lengkap (dengan gelar)" :value="$guru->nama" required />
            <x-field.input name="nip" label="NIP" :value="$guru->nip" help="Kosongkan jika belum memiliki NIP." />
            <x-field.input name="username" label="Username" :value="$guru->user?->username"
                           :help="$edit ? 'Username untuk login.' : 'Opsional. Kosongkan untuk memakai NIP.'" />
            <x-field.input name="email" type="email" label="Email" :value="$guru->user?->email" />
            <x-field.select name="jenis_kelamin" label="Jenis kelamin" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']"
                            :value="$guru->jenis_kelamin" placeholder="— Pilih —" required />
            <x-field.input name="no_hp" label="Nomor HP" :value="$guru->no_hp" />
            <x-field.select name="status" label="Status akun" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']"
                            :value="$guru->user?->status ?? 'aktif'" required />
        </div>
        <x-field.textarea name="alamat" label="Alamat" :value="$guru->alamat" />

        <x-form-actions :batal="route('operator.guru.index')" />
    </form>
</x-layouts.app>
