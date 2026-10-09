@php $edit = $pengampu->exists; @endphp

<x-layouts.app :title="$edit ? 'Ubah Pengampu' : 'Tambah Pengampu'">
    <x-page-header :title="$edit ? 'Ubah Pengampu' : 'Tambah Pengampu'" :subtitle="'Tahun ajaran '.($tahunAktif?->label ?? '-')" />

    @unless ($tahunAktif)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Aktifkan tahun ajaran terlebih dahulu.</div>
    @endunless

    <form method="POST" action="{{ $edit ? route('operator.pengampu.update', $pengampu) : route('operator.pengampu.store') }}"
          class="card card-body max-w-2xl space-y-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <x-field.select name="kelas_id" label="Kelas" :options="$daftarKelas->pluck('nama_kelas', 'id')->all()"
                        :value="$pengampu->kelas_id" placeholder="— Pilih kelas —" required />
        <x-field.select name="mapel_id" label="Mata pelajaran" :options="$daftarMapel->mapWithKeys(fn ($m) => [$m->id => $m->kode.' — '.$m->nama_mapel])->all()"
                        :value="$pengampu->mapel_id" placeholder="— Pilih mapel —" required />
        <x-field.select name="guru_id" label="Guru pengampu" :options="$daftarGuru->pluck('nama', 'id')->all()"
                        :value="$pengampu->guru_id" placeholder="— Pilih guru —" required />

        <x-form-actions :batal="route('operator.pengampu.index')" />
    </form>
</x-layouts.app>
