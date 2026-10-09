@php $edit = $tugas->exists; @endphp

<x-layouts.app :title="($edit ? 'Ubah' : 'Buat').' Tugas'">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="tugas" />

    <form method="POST" enctype="multipart/form-data"
          action="{{ $edit ? route('guru.tugas.update', [$pengampu, $tugas]) : route('guru.tugas.store', $pengampu) }}"
          class="card card-body max-w-3xl space-y-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <h3 class="card-title">{{ $edit ? 'Ubah Tugas' : 'Buat Tugas' }}</h3>

        <x-field.input name="judul" label="Judul tugas" :value="$tugas->judul" required />
        <x-field.textarea name="deskripsi" label="Instruksi tugas" :value="$tugas->deskripsi" rows="6" required />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field.input name="deadline" type="datetime-local" label="Deadline" :value="$tugas->deadline?->format('Y-m-d\TH:i')" required />
            <x-field.input name="nilai_maks" type="number" min="1" label="Nilai maksimal" :value="$tugas->nilai_maks" required />
        </div>
        <x-field.file label="Lampiran (opsional)" :path-lama="$tugas->file_path" />

        <x-form-actions :batal="$edit ? route('guru.tugas.show', [$pengampu, $tugas]) : route('guru.tugas.index', $pengampu)" />
    </form>
</x-layouts.app>
