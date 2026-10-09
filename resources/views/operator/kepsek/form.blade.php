@php $edit = $kepsek->exists; @endphp

<x-layouts.app :title="$edit ? 'Ubah Kepala Sekolah' : 'Tambah Kepala Sekolah'">
    <x-page-header :title="$edit ? 'Ubah Akun Kepala Sekolah' : 'Tambah Akun Kepala Sekolah'"
                   :subtitle="$edit ? null : 'Password awal = NIP (atau username jika NIP kosong).'" />

    <form method="POST" action="{{ $edit ? route('operator.kepsek.update', $kepsek) : route('operator.kepsek.store') }}" class="card card-body space-y-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field.input name="nama" label="Nama lengkap (dengan gelar)" :value="$kepsek->nama" required />
            <x-field.input name="nip" label="NIP" :value="$kepsek->nip" />
            <x-field.input name="username" label="Username" :value="$kepsek->user?->username" required />
            <x-field.input name="email" type="email" label="Email" :value="$kepsek->user?->email" />
            <x-field.select name="jenis_kelamin" label="Jenis kelamin" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']"
                            :value="$kepsek->jenis_kelamin" placeholder="— Pilih —" required />
            <x-field.input name="no_hp" label="Nomor HP" :value="$kepsek->no_hp" />
            <x-field.select name="status" label="Status akun" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']"
                            :value="$kepsek->user?->status ?? 'aktif'" required />
        </div>

        <x-form-actions :batal="route('operator.kepsek.index')" />
    </form>
</x-layouts.app>
