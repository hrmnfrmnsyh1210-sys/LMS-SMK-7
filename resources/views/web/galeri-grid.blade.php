{{-- Grid foto dengan lightbox (Alpine). Variabel: $galeri (koleksi/paginator Galeri). --}}
@php
    $foto = collect($galeri instanceof \Illuminate\Contracts\Pagination\Paginator ? $galeri->items() : $galeri)->map(fn ($g) => ['src' => file_url($g->gambar), 'judul' => $g->judul, 'keterangan' => $g->keterangan])->values();
@endphp

<div x-data="{ foto: @js($foto), aktif: null }" @keydown.escape.window="aktif = null"
     @keydown.arrow-right.window="aktif !== null && (aktif = (aktif + 1) % foto.length)"
     @keydown.arrow-left.window="aktif !== null && (aktif = (aktif - 1 + foto.length) % foto.length)">
    <div class="mt-10 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
        <template x-for="(f, i) in foto" :key="i">
            <button type="button" @click="aktif = i" class="group relative aspect-square overflow-hidden rounded-2xl bg-slate-100">
                <img :src="f.src" :alt="f.judul" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-110">
                <span class="absolute inset-0 flex items-end bg-linear-to-t from-slate-900/70 via-transparent p-3 text-left text-sm font-medium text-white opacity-0 transition group-hover:opacity-100" x-text="f.judul"></span>
            </button>
        </template>
    </div>

    <template x-teleport="body">
        <div x-show="aktif !== null" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/90 p-4" @click.self="aktif = null">
            <button type="button" class="absolute top-4 right-4 rounded-full p-2 text-white hover:bg-white/10" @click="aktif = null" aria-label="Tutup"><x-icon name="x" class="h-7 w-7" /></button>
            <button type="button" x-show="foto.length > 1" class="absolute left-2 rounded-full p-2 text-white hover:bg-white/10 sm:left-6" @click="aktif = (aktif - 1 + foto.length) % foto.length" aria-label="Sebelumnya"><x-icon name="chevron-left" class="h-8 w-8" /></button>
            <figure class="max-w-5xl" x-show="aktif !== null">
                <img :src="foto[aktif]?.src" :alt="foto[aktif]?.judul" class="max-h-[80vh] w-auto rounded-xl object-contain">
                <figcaption class="mt-3 text-center text-white">
                    <div class="font-medium" x-text="foto[aktif]?.judul"></div>
                    <div class="text-sm text-slate-300" x-text="foto[aktif]?.keterangan"></div>
                </figcaption>
            </figure>
            <button type="button" x-show="foto.length > 1" class="absolute right-2 rounded-full p-2 text-white hover:bg-white/10 sm:right-6" @click="aktif = (aktif + 1) % foto.length" aria-label="Berikutnya"><x-icon name="chevron-right" class="h-8 w-8" /></button>
        </div>
    </template>
</div>
