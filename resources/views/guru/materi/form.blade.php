@php $edit = $materi->exists; @endphp

<x-layouts.app :title="($edit ? 'Ubah' : 'Tambah').' Materi'">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="materi" />

    <form method="POST" enctype="multipart/form-data"
          action="{{ $edit ? route('guru.materi.update', [$pengampu, $materi]) : route('guru.materi.store', $pengampu) }}"
          class="card card-body max-w-3xl space-y-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <h3 class="card-title">{{ $edit ? 'Ubah Materi' : 'Tambah Materi' }}</h3>

        <div class="grid gap-4 sm:grid-cols-4">
            <div class="sm:col-span-3">
                <x-field.input name="judul" label="Judul materi" :value="$materi->judul" required />
            </div>
            <x-field.input name="pertemuan_ke" type="number" min="1" label="Pertemuan ke" :value="$materi->pertemuan_ke" required />
        </div>
        <x-field.textarea name="deskripsi" label="Deskripsi / ringkasan" :value="$materi->deskripsi" rows="4" />
        <x-field.file label="File materi (PDF, PPT, Word, video, dll.)" :path-lama="$materi->file_path" />
        <x-field.input name="tautan" type="url" label="Tautan (YouTube, Google Drive, dll.)" :value="$materi->tautan" placeholder="https://" />

        <x-form-actions :batal="route('guru.materi.index', $pengampu)" />
    </form>
</x-layouts.app>
