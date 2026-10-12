<x-layouts.web :title="$berita->judul" :latar-akhir="$lainnya->isNotEmpty() ? '#f6f8fc' : '#ffffff'" :description="$berita->cuplikan(160)">
    @if ($berita->gambar)
        @push('head')
            <meta property="og:image" content="{{ file_url($berita->gambar) }}">
        @endpush
    @endif

    <article class="pt-32 pb-16 lg:pt-36">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <a href="{{ route('web.berita.index') }}" class="inline-flex items-center gap-1 text-sm font-medium text-navy-700 hover:text-navy-900">
                <x-icon name="chevron-left" class="h-4 w-4" /> Kembali ke berita
            </a>
            <div class="mt-6 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                <span class="rounded-full bg-kuning-300/40 px-3 py-1 text-xs font-semibold text-navy-900">{{ $berita->labelKategori() }}</span>
                <span class="flex items-center gap-1.5"><x-icon name="calendar" class="h-4 w-4" /> {{ $berita->diterbitkan_pada->translatedFormat('l, d F Y') }}</span>
            </div>
            <h1 class="mt-4 font-display text-3xl leading-tight font-bold text-navy-900 sm:text-5xl">{{ $berita->judul }}</h1>
            @if ($berita->ringkasan)
                <p class="mt-4 text-lg text-slate-500">{{ $berita->ringkasan }}</p>
            @endif
        </div>

        @if ($berita->gambar)
            <div class="mx-auto mt-8 max-w-5xl px-4 sm:px-6">
                <img src="{{ file_url($berita->gambar) }}" alt="{{ $berita->judul }}" class="aspect-video w-full rounded-xl object-cover">
            </div>
        @endif

        <div class="konten-artikel mx-auto mt-10 max-w-3xl px-4 sm:px-6">
            {{ $berita->isiHtml() }}
        </div>
    </article>

    @if ($lainnya->isNotEmpty())
        <section class="bg-latar py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <h2 class="font-display text-2xl font-bold text-navy-900">Berita lainnya</h2>
                <div class="mt-8 grid gap-6 md:grid-cols-3">
                    @foreach ($lainnya as $b)
                        @include('web.berita.kartu', ['berita' => $b])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts.web>
