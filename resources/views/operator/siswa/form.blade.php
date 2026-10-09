@php $edit = $siswa->exists; @endphp

<x-layouts.app :title="$edit ? 'Ubah Siswa' : 'Tambah Siswa'">
    <x-page-header :title="$edit ? 'Ubah Data Siswa' : 'Tambah Siswa'"
                   subtitle="Username siswa = NIS. Password awal akun baru = NIS." />

    <form method="POST" action="{{ $edit ? route('operator.siswa.update', $siswa) : route('operator.siswa.store') }}" class="card card-body space-y-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field.input name="nama" label="Nama lengkap" :value="$siswa->nama" required />
            <x-field.input name="nis" label="NIS" :value="$siswa->nis" required />
            <x-field.input name="nisn" label="NISN" :value="$siswa->nisn" required />
            <x-field.select name="jenis_kelamin" label="Jenis kelamin" :options="['L' => 'Laki-laki', 'P' => 'Perempuan']"
                            :value="$siswa->jenis_kelamin" placeholder="— Pilih —" required />
            <x-field.input name="tgl_lahir" type="date" label="Tanggal lahir" :value="$siswa->tgl_lahir?->format('Y-m-d')" />
            <x-field.input name="nama_ortu" label="Nama orang tua/wali" :value="$siswa->nama_ortu" />
            <x-field.input name="email" type="email" label="Email" :value="$siswa->user?->email" />
            <x-field.select name="kelas_id" label="Kelas ({{ $tahunAktif?->label ?? 'belum ada tahun ajaran aktif' }})"
                            :options="$daftarKelas->pluck('nama_kelas', 'id')->all()" :value="$kelasSaatIni?->id" placeholder="— Belum ditentukan —" />
            <x-field.select name="status" label="Status akun" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']"
                            :value="$siswa->user?->status ?? 'aktif'" required />
        </div>
        <x-field.textarea name="alamat" label="Alamat" :value="$siswa->alamat" />

        <x-form-actions :batal="route('operator.siswa.index')" />
    </form>
</x-layouts.app>
