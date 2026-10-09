@php
    $edit = $jadwal->exists;
    $opsiPengampu = $daftarPengampu
        ->when($kelasId, fn ($c) => $c->sortByDesc(fn ($p) => $p->kelas_id === $kelasId))
        ->mapWithKeys(fn ($p) => [$p->id => $p->kelas->nama_kelas.' — '.$p->mapel->nama_mapel.' ('.$p->guru->nama.')'])
        ->all();
@endphp

<x-layouts.app :title="$edit ? 'Ubah Jadwal' : 'Tambah Jadwal'">
    <x-page-header :title="$edit ? 'Ubah Jadwal' : 'Tambah Jadwal'"
                   subtitle="Sistem menolak jadwal yang bentrok untuk kelas atau guru yang sama." />

    <form method="POST" action="{{ $edit ? route('operator.jadwal.update', $jadwal) : route('operator.jadwal.store') }}"
          class="card card-body max-w-2xl space-y-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <x-field.select name="pengampu_id" label="Kelas — Mapel (Guru)" :options="$opsiPengampu"
                        :value="$jadwal->pengampu_id" placeholder="— Pilih pengampu —" required />

        <div class="grid gap-4 sm:grid-cols-3">
            <x-field.select name="hari" label="Hari" :options="array_combine(\App\Models\Jadwal::HARI, \App\Models\Jadwal::HARI)"
                            :value="$jadwal->hari" required />
            <x-field.input name="jam_mulai" type="time" label="Jam mulai" :value="$jadwal->jam_mulai ? substr($jadwal->jam_mulai, 0, 5) : null" required />
            <x-field.input name="jam_selesai" type="time" label="Jam selesai" :value="$jadwal->jam_selesai ? substr($jadwal->jam_selesai, 0, 5) : null" required />
        </div>
        <x-field.input name="ruang" label="Ruang" :value="$jadwal->ruang" placeholder="Lab RPL 1" />

        <x-form-actions :batal="route('operator.jadwal.index')" />
    </form>
</x-layouts.app>
