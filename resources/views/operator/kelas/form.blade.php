@php $edit = $kelas->exists; @endphp

<x-layouts.app :title="$edit ? 'Ubah Kelas' : 'Tambah Kelas'">
    <x-page-header :title="$edit ? 'Ubah Kelas' : 'Tambah Kelas'" />

    <form method="POST" action="{{ $edit ? route('operator.kelas.update', $kelas) : route('operator.kelas.store') }}"
          class="card card-body max-w-2xl space-y-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field.input name="nama_kelas" label="Nama kelas" :value="$kelas->nama_kelas" placeholder="XI RPL 1" required />
            <x-field.select name="tingkat" label="Tingkat" :options="array_combine(\App\Models\Kelas::TINGKAT, \App\Models\Kelas::TINGKAT)"
                            :value="$kelas->tingkat" placeholder="— Pilih —" required />
            <x-field.select name="jurusan_id" label="Jurusan" :options="$daftarJurusan->mapWithKeys(fn ($j) => [$j->id => $j->kode.' — '.$j->nama])->all()"
                            :value="$kelas->jurusan_id" placeholder="— Pilih —" required />
            <x-field.select name="wali_kelas_id" label="Wali kelas" :options="$daftarGuru->pluck('nama', 'id')->all()"
                            :value="$kelas->wali_kelas_id" placeholder="— Belum ditentukan —" />
            <x-field.select name="tahun_ajaran_id" label="Tahun ajaran" :options="$daftarTahun->pluck('label', 'id')->all()"
                            :value="$kelas->tahun_ajaran_id ?? $tahunAktif?->id" required />
        </div>

        <x-form-actions :batal="route('operator.kelas.index')" />
    </form>
</x-layouts.app>
