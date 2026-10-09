@php $edit = $tahunAjaran->exists; @endphp

<x-layouts.app :title="$edit ? 'Ubah Tahun Ajaran' : 'Tambah Tahun Ajaran'">
    <x-page-header :title="$edit ? 'Ubah Tahun Ajaran' : 'Tambah Tahun Ajaran'" />

    <form method="POST" action="{{ $edit ? route('operator.tahun-ajaran.update', $tahunAjaran) : route('operator.tahun-ajaran.store') }}"
          class="card card-body max-w-xl space-y-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <x-field.input name="nama" label="Tahun ajaran" :value="$tahunAjaran->nama" placeholder="2026/2027" required />
        <x-field.select name="semester" label="Semester" :options="['ganjil' => 'Ganjil', 'genap' => 'Genap']"
                        :value="$tahunAjaran->semester" placeholder="— Pilih —" required />

        <x-form-actions :batal="route('operator.tahun-ajaran.index')" />
    </form>
</x-layouts.app>
