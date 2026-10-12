<a href="{{ route('web.berita.show', $berita) }}" class="group flex flex-col overflow-hidden rounded-xl bg-white ring-1 ring-slate-100 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-900/5">
    <div class="relative aspect-video overflow-hidden bg-slate-100">
        @if ($berita->gambar)
            <img src="{{ file_url($berita->gambar) }}" alt="{{ $berita->judul }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <div class="flex h-full items-center justify-center bg-linear-to-br from-navy-700 to-navy-900 text-white/30"><x-icon name="megaphone" class="h-12 w-12" /></div>
        @endif
        <span class="absolute top-3 left-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-navy-800 backdrop-blur">{{ $berita->labelKategori() }}</span>
    </div>
    <div class="flex flex-1 flex-col p-6">
        <div class="flex items-center gap-1.5 text-xs text-slate-400"><x-icon name="calendar" class="h-3.5 w-3.5" /> {{ $berita->diterbitkan_pada->translatedFormat('d F Y') }}</div>
        <h3 class="mt-2 line-clamp-2 font-semibold text-slate-800 group-hover:text-navy-700">{{ $berita->judul }}</h3>
        <p class="mt-2 line-clamp-3 text-sm text-slate-500">{{ $berita->cuplikan() }}</p>
        <span class="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-semibold text-navy-700">Baca selengkapnya <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-1" /></span>
    </div>
</a>
