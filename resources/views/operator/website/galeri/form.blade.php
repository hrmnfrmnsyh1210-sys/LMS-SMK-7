@php $edit = $galeri->exists; @endphp

<x-layouts.app :title="$edit ? 'Ubah Foto' : 'Tambah Foto'">
    <x-page-header :title="$edit ? 'Ubah Foto' : 'Tambah Foto'" />

    <form method="POST" action="{{ $edit ? route('operator.galeri.update', $galeri) : route('operator.galeri.store') }}"
          enctype="multipart/form-data" class="card card-body grid max-w-4xl gap-6 md:grid-cols-2">
        @csrf
        @if ($edit) @method('PUT') @endif

        @include('operator.website.partials.gambar', [
            'name' => 'gambar', 'label' => 'Foto', 'path' => $galeri->gambar, 'tinggi' => 'h-56',
            'help' => 'PNG/JPG/WEBP, maks. 2 MB.'.($edit ? ' Unggah foto baru untuk mengganti.' : ''),
        ])

        <div class="space-y-4">
            <x-field.input name="judul" label="Judul" :value="$galeri->judul" placeholder="Praktik kerja di laboratorium" required />
            <x-field.textarea name="keterangan" label="Keterangan" :value="$galeri->keterangan" rows="3" />
            <x-field.input name="urutan" type="number" min="0" label="Urutan tampil" :value="$galeri->urutan ?? 0" />
        </div>

        <div class="md:col-span-2">
            <x-form-actions :batal="route('operator.galeri.index')" />
        </div>
    </form>
</x-layouts.app>
