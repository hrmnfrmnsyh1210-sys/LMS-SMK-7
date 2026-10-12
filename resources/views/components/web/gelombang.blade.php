@props(['posisi' => 'bawah', 'isi' => '#ffffff', 'tinggi' => 'h-16 sm:h-24 lg:h-28'])

{{--
    Gelombang biru berlapis sebagai pemisah section (dekoratif).
    $posisi: 'bawah' (menempel di dasar section) atau 'atas' (dibalik, menempel di puncak section).
    $isi: warna lapisan terdepan = warna latar section berikutnya/sebelumnya.
--}}
@php $id = 'gel-'.\Illuminate\Support\Str::random(6); @endphp

<div aria-hidden="true" {{ $attributes->class([
    'pointer-events-none absolute inset-x-0 z-0',
    $tinggi,
    'bottom-0' => $posisi === 'bawah',
    'top-0 rotate-180' => $posisi === 'atas',
]) }}>
    <svg viewBox="0 0 1440 120" preserveAspectRatio="none" class="h-full w-full">
        <defs>
            <linearGradient id="{{ $id }}" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0%" stop-color="#2f6fed" />
                <stop offset="100%" stop-color="#13b5cf" />
            </linearGradient>
        </defs>
        <path d="M0 52 C 240 8, 420 104, 720 60 S 1180 6, 1440 46 V120 H0 Z" fill="url(#{{ $id }})" opacity="0.35" />
        <path d="M0 74 C 300 30, 520 118, 860 72 S 1260 40, 1440 70 V120 H0 Z" fill="url(#{{ $id }})" opacity="0.55" />
        <path d="M0 96 C 260 70, 560 122, 900 96 S 1290 78, 1440 92 V120 H0 Z" fill="{{ $isi }}" />
    </svg>
</div>
