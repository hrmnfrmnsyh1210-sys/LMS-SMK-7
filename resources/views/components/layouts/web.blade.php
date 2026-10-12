@props(['title' => null, 'description' => null])

@php
    $logo = $sekolah['logo'] ? file_url($sekolah['logo']) : null;
    $deskripsi = $description ?? $sekolah['web_hero_subjudul'];
    $beranda = request()->routeIs('beranda');
    // Tautan bagian beranda; dari halaman lain diarahkan kembali ke beranda.
    $bagian = fn (string $id) => $beranda ? "#{$id}" : route('beranda')."#{$id}";
    $menu = [
        ['label' => 'Profil', 'url' => $bagian('profil')],
        ['label' => 'Jurusan', 'url' => $bagian('jurusan')],
        ['label' => 'Berita', 'url' => route('web.berita.index'), 'aktif' => request()->routeIs('web.berita.*')],
        ['label' => 'Galeri', 'url' => route('web.galeri'), 'aktif' => request()->routeIs('web.galeri')],
        ['label' => 'Kontak', 'url' => $bagian('kontak')],
    ];
    $ctaPpdb = $sekolah['web_ppdb_aktif'] === '1' && $sekolah['web_ppdb_tautan'];
    $sosial = array_filter([
        'Instagram' => $sekolah['web_instagram'], 'Facebook' => $sekolah['web_facebook'],
        'YouTube' => $sekolah['web_youtube'], 'TikTok' => $sekolah['web_tiktok'],
    ]);
@endphp

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — ' : '' }}{{ $sekolah['nama_sekolah'] }}</title>
    @if ($deskripsi)
        <meta name="description" content="{{ \Illuminate\Support\Str::limit($deskripsi, 160) }}">
    @endif
    <meta property="og:title" content="{{ $title ?? $sekolah['nama_sekolah'] }}">
    <meta property="og:site_name" content="{{ $sekolah['nama_sekolah'] }}">
    @if ($logo)
        <link rel="icon" href="{{ $logo }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700|roboto-slab:500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-white font-sans text-slate-700 antialiased" x-data="{ menu: false, gulir: false, solid: @js(! $beranda) }"
      @scroll.window="gulir = window.scrollY > 24">

    {{-- ================= NAVBAR ================= --}}
    <header class="fixed inset-x-0 top-0 z-40 transition-colors duration-300"
            :class="gulir || menu || solid ? 'bg-white shadow-[0_1px_0_rgba(15,23,42,0.08)]' : 'bg-transparent'">
        <div class="mx-auto flex h-18 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:h-20">
            <a href="{{ route('beranda') }}" class="flex min-w-0 items-center gap-3">
                @if ($logo)
                    <img src="{{ $logo }}" alt="Logo {{ $sekolah['nama_sekolah'] }}" class="h-11 w-11 shrink-0 object-contain">
                @else
                    <x-icon name="academic-cap" class="h-10 w-10 shrink-0 transition" ::class="gulir || menu || solid ? 'text-navy-700' : 'text-white'" />
                @endif
                <span class="min-w-0 leading-tight transition" :class="gulir || menu || solid ? 'text-navy-900' : 'text-white'">
                    <span class="block text-[10px] font-medium tracking-[0.2em] uppercase opacity-75">Website Resmi</span>
                    <span class="block truncate font-display text-base font-bold sm:text-lg">{{ $sekolah['nama_sekolah'] }}</span>
                </span>
            </a>

            <nav class="mx-auto hidden items-center gap-8 lg:flex">
                @foreach ($menu as $item)
                    <a href="{{ $item['url'] }}"
                       class="relative py-2 text-sm font-medium transition after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:origin-left after:scale-x-0 after:bg-kuning-400 after:transition hover:after:scale-x-100 {{ ($item['aktif'] ?? false) ? 'after:scale-x-100' : '' }}"
                       :class="gulir || solid ? 'text-slate-700 hover:text-navy-700' : 'text-white/90 hover:text-white'">{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="ml-auto hidden items-center gap-5 lg:ml-0 lg:flex">
                @if ($ctaPpdb)
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="text-sm font-medium transition"
                       :class="gulir || solid ? 'text-slate-700 hover:text-navy-700' : 'text-white/90 hover:text-white'">{{ auth()->check() ? 'Dashboard' : 'Masuk' }}</a>
                    <a href="{{ $sekolah['web_ppdb_tautan'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full bg-kuning-400 px-5 py-2.5 text-sm font-semibold text-navy-950 transition hover:bg-kuning-300">
                        Daftar PPDB <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                @else
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="inline-flex items-center gap-2 rounded-full bg-kuning-400 px-5 py-2.5 text-sm font-semibold text-navy-950 transition hover:bg-kuning-300">
                        {{ auth()->check() ? 'Dashboard' : 'Masuk LMS' }} <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                @endif
            </div>

            <button type="button" class="ml-auto p-2 lg:hidden" @click="menu = !menu" aria-label="Menu"
                    :class="gulir || menu || solid ? 'text-navy-900' : 'text-white'">
                <x-icon x-show="!menu" name="menu" class="h-7 w-7" />
                <x-icon x-show="menu" x-cloak name="x" class="h-7 w-7" />
            </button>
        </div>

        {{-- Menu HP --}}
        <div x-show="menu" x-cloak x-transition.origin.top class="border-t border-slate-100 bg-white px-4 pt-2 pb-5 lg:hidden" @click="menu = false">
            @foreach ($menu as $item)
                <a href="{{ $item['url'] }}" class="block border-b border-slate-100 px-1 py-3 text-sm font-medium text-slate-700 hover:text-navy-700">{{ $item['label'] }}</a>
            @endforeach
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                @if ($ctaPpdb)
                    <a href="{{ $sekolah['web_ppdb_tautan'] }}" target="_blank" rel="noopener" class="rounded-full bg-kuning-400 px-5 py-2.5 text-center text-sm font-semibold text-navy-950">Daftar PPDB</a>
                @endif
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="rounded-full border border-navy-700 px-5 py-2.5 text-center text-sm font-semibold text-navy-700">{{ auth()->check() ? 'Dashboard' : 'Masuk LMS' }}</a>
            </div>
        </div>
    </header>

    <main>{{ $slot }}</main>

    {{-- ================= FOOTER ================= --}}
    <footer class="bg-navy-950 text-slate-400">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-3">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="" class="h-11 w-11 rounded-xl bg-white object-contain p-0.5">
                    @else
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-600 text-white"><x-icon name="academic-cap" class="h-6 w-6" /></span>
                    @endif
                    <div>
                        <div class="font-display text-lg font-bold text-white">{{ $sekolah['nama_sekolah'] }}</div>
                        @if ($sekolah['npsn'])
                            <div class="text-xs">NPSN {{ $sekolah['npsn'] }}</div>
                        @endif
                    </div>
                </div>
                @if ($sekolah['web_hero_subjudul'])
                    <p class="mt-4 max-w-md text-sm leading-relaxed">{{ $sekolah['web_hero_subjudul'] }}</p>
                @endif
                @if ($sosial)
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach ($sosial as $nama => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="rounded-full border border-slate-700 px-3.5 py-1.5 text-xs font-medium text-slate-300 transition hover:border-kuning-400 hover:text-white">{{ $nama }}</a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <h3 class="text-sm font-semibold tracking-wide text-white uppercase">Tautan</h3>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($menu as $item)
                        <li><a href="{{ $item['url'] }}" class="hover:text-white">{{ $item['label'] }}</a></li>
                    @endforeach
                    <li><a href="{{ route('login') }}" class="hover:text-white">Masuk LMS</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold tracking-wide text-white uppercase">Kontak</h3>
                <ul class="mt-4 space-y-3 text-sm">
                    @if ($sekolah['alamat_sekolah'])
                        <li class="flex gap-2.5"><x-icon name="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-kuning-400" /> {{ $sekolah['alamat_sekolah'] }}</li>
                    @endif
                    @if ($sekolah['telepon_sekolah'])
                        <li class="flex gap-2.5"><x-icon name="phone" class="mt-0.5 h-4 w-4 shrink-0 text-kuning-400" /> {{ $sekolah['telepon_sekolah'] }}</li>
                    @endif
                    @if ($sekolah['email_sekolah'])
                        <li class="flex gap-2.5"><x-icon name="envelope" class="mt-0.5 h-4 w-4 shrink-0 text-kuning-400" /> <a href="mailto:{{ $sekolah['email_sekolah'] }}" class="break-all hover:text-white">{{ $sekolah['email_sekolah'] }}</a></li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="mx-auto max-w-7xl px-4 py-5 text-xs sm:px-6">&copy; {{ date('Y') }} {{ $sekolah['nama_sekolah'] }}. Hak cipta dilindungi.</div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
