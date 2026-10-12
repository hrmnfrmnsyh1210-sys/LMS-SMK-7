@php $edit = $berita->exists; @endphp

<x-layouts.app :title="$edit ? 'Ubah Berita' : 'Tulis Berita'">
    <x-page-header :title="$edit ? 'Ubah Berita' : 'Tulis Berita'" />

    <form method="POST" action="{{ $edit ? route('operator.berita.update', $berita) : route('operator.berita.store') }}"
          enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3"
          x-data="{ terbit: @js((bool) old('terbitkan', ! $edit || $berita->diterbitkan_pada !== null)) }">
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="card card-body space-y-4 lg:col-span-2">
            <x-field.input name="judul" label="Judul" :value="$berita->judul" required />
            <x-field.textarea name="ringkasan" label="Ringkasan" :value="$berita->ringkasan" rows="2"
                              help="Opsional, maks. 300 karakter. Ditampilkan pada kartu berita." />
            <x-field.textarea name="isi" label="Isi berita" :value="$berita->isi" rows="16" required
                              help="Pisahkan paragraf dengan baris kosong. Mendukung format Markdown: **tebal**, *miring*, ## Subjudul, - daftar, [teks](https://tautan)." />
        </div>

        <div class="space-y-6">
            <div class="card card-body space-y-4">
                <x-field.select name="kategori" label="Kategori" :options="\App\Models\Berita::KATEGORI" :value="$berita->kategori" required />

                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="hidden" name="terbitkan" value="0">
                    <input type="checkbox" name="terbitkan" value="1" x-model="terbit" class="rounded border-slate-300 text-blue-600">
                    Terbitkan di website
                </label>
                <div x-show="terbit" x-cloak>
                    <x-field.input name="diterbitkan_pada" type="datetime-local" label="Tanggal terbit"
                                   :value="$berita->diterbitkan_pada?->format('Y-m-d\TH:i')"
                                   help="Kosongkan untuk terbit sekarang. Isi tanggal mendatang untuk menjadwalkan." />
                </div>
                <p x-show="!terbit" x-cloak class="text-xs text-slate-500">Berita disimpan sebagai draf dan tidak tampil di website.</p>
            </div>

            <div class="card card-body">
                @include('operator.website.partials.gambar', [
                    'name' => 'gambar', 'label' => 'Gambar sampul', 'path' => $berita->gambar,
                    'help' => 'PNG/JPG/WEBP, maks. 2 MB. Rasio 16:9 disarankan.', 'hapusName' => 'hapus_gambar',
                ])
            </div>
        </div>

        <div class="lg:col-span-3">
            <x-form-actions :batal="route('operator.berita.index')" />
        </div>
    </form>
</x-layouts.app>
