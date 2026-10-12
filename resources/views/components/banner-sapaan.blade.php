@props(['nama', 'pesan' => 'Pantau aktivitas sekolah Anda hari ini.'])

@php
    $jam = (int) now()->format('G');
    $sapaan = match (true) {
        $jam >= 4 && $jam < 11 => 'Selamat pagi',
        $jam >= 11 && $jam < 15 => 'Selamat siang',
        $jam >= 15 && $jam < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $foto = file_url($sekolah['web_hero_latar'] ?? null);
@endphp

{{-- Warna mengikuti panel sidebar tema peran; foto hero sekolah (bila ada) tampil di sisi kanan. --}}
<div class="banner-sapaan relative isolate overflow-hidden rounded-2xl bg-sidebar px-6 py-8 text-white sm:px-8 sm:py-9">
    @if ($foto)
        <img src="{{ $foto }}" alt="" class="absolute inset-y-0 right-0 -z-20 hidden h-full w-3/5 object-cover md:block">
        <div class="absolute inset-0 -z-10 hidden bg-linear-to-r from-sidebar from-40% via-sidebar/80 via-60% to-sidebar/20 md:block"></div>
    @else
        <x-icon name="academic-cap" class="pointer-events-none absolute -right-8 -bottom-12 -z-10 h-64 w-64 text-white/5" />
    @endif

    <div class="max-w-xl">
        <p class="text-xs font-semibold tracking-[0.18em] text-aksen uppercase">{{ tanggal_indo(now()) }}</p>
        <p class="mt-3 text-white/70">{{ $sapaan }},</p>
        <h2 class="font-display text-2xl leading-tight font-bold sm:text-3xl">{{ $nama }}</h2>
        <p class="mt-2 text-sm text-white/70">{{ $pesan }}</p>
        @if ($slot->isNotEmpty())
            <div class="mt-5 flex flex-wrap gap-2">{{ $slot }}</div>
        @endif
    </div>
</div>
