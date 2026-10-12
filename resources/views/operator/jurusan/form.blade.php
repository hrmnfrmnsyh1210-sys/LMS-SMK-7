@php $edit = $jurusan->exists; @endphp

<x-layouts.app :title="$edit ? 'Ubah Jurusan' : 'Tambah Jurusan'">
    <x-page-header :title="$edit ? 'Ubah Jurusan' : 'Tambah Jurusan'" />

    <form method="POST" action="{{ $edit ? route('operator.jurusan.update', $jurusan) : route('operator.jurusan.store') }}"
          class="card card-body max-w-xl space-y-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <x-field.input name="kode" label="Kode" :value="$jurusan->kode" placeholder="RPL" required />
        <x-field.input name="nama" label="Nama jurusan" :value="$jurusan->nama" placeholder="Rekayasa Perangkat Lunak" required />
        <x-field.textarea name="deskripsi" label="Deskripsi singkat" :value="$jurusan->deskripsi" rows="4"
                          help="Ditampilkan pada bagian Program Keahlian di website sekolah." />

        <x-form-actions :batal="route('operator.jurusan.index')" />
    </form>
</x-layouts.app>
