@props(['nama', 'pesan' => 'Pantau aktivitas sekolah Anda hari ini.'])

@php
    $jam = (int) now()->format('G');
    $sapaan = match (true) {
        $jam >= 4 && $jam < 11 => 'Selamat Pagi',
        $jam >= 11 && $jam < 15 => 'Selamat Siang',
        $jam >= 15 && $jam < 18 => 'Selamat Sore',
        default => 'Selamat Malam',
    };
@endphp

<div class="relative overflow-hidden rounded-2xl border border-blue-100 bg-linear-to-r from-blue-50 via-sky-50 to-blue-100/70 px-6 py-7 sm:px-8">
    <div class="relative z-10 max-w-xl">
        <p class="text-lg font-medium text-slate-700">{{ $sapaan }}, <span aria-hidden="true">👋</span></p>
        <h2 class="mt-0.5 text-2xl font-bold text-blue-700 sm:text-3xl">{{ $nama }}!</h2>
        <p class="mt-2 text-sm text-slate-500">{{ $pesan }}</p>
        <p class="mt-1 text-xs font-medium text-slate-400">{{ tanggal_indo(now()) }}</p>
        @if ($slot->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">{{ $slot }}</div>
        @endif
    </div>
    <x-ilustrasi-buku class="pointer-events-none absolute right-4 -bottom-3 hidden w-72 md:block" />
</div>
