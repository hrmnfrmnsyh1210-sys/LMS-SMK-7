@php $edit = $mapel->exists; @endphp

<x-layouts.app :title="$edit ? 'Ubah Mata Pelajaran' : 'Tambah Mata Pelajaran'">
    <x-page-header :title="$edit ? 'Ubah Mata Pelajaran' : 'Tambah Mata Pelajaran'" />

    <form method="POST" action="{{ $edit ? route('operator.mapel.update', $mapel) : route('operator.mapel.store') }}"
          class="card card-body max-w-xl space-y-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <x-field.input name="kode" label="Kode" :value="$mapel->kode" placeholder="MTK" required />
        <x-field.input name="nama_mapel" label="Nama mata pelajaran" :value="$mapel->nama_mapel" placeholder="Matematika" required />

        <x-form-actions :batal="route('operator.mapel.index')" />
    </form>
</x-layouts.app>
