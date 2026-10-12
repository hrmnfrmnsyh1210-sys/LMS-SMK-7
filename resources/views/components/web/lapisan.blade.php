@props(['sudut' => 'kanan-atas', 'cincin' => true, 'y' => 'top-1/2 -translate-y-1/2'])

{{--
    Lapisan biru bergradasi (dekoratif), bergaya slide "custom charts".
    - $sudut 'kiri' | 'kanan': setengah lingkaran berlapis utuh yang muncul dari tepi layar; $y mengatur letak vertikal.
      Dipakai di tengah area atau melintasi batas dua section agar tidak terpotong garis batas.
    - $sudut 'kanan-atas' | 'kiri-bawah' | 'kanan-bawah' | 'kiri-atas': lengkungan di sudut sebuah kartu/section
      yang latarnya berbeda dari sekitarnya (induk `relative isolate overflow-hidden`).
--}}
@php
    $id = 'lap-'.\Illuminate\Support\Str::random(6);
    $tepi = in_array($sudut, ['kiri', 'kanan'], true);
    $posisi = [
        'kiri' => "left-0 {$y}",
        'kanan' => "right-0 -scale-x-100 {$y}",
        'kanan-atas' => '-top-24 -right-32 sm:-top-28 sm:-right-24',
        'kiri-bawah' => '-bottom-24 -left-32 rotate-180 sm:-bottom-28 sm:-left-24',
        'kanan-bawah' => '-bottom-24 -right-32 rotate-90 sm:-bottom-28 sm:-right-24',
        'kiri-atas' => '-top-24 -left-32 -rotate-90 sm:-top-28 sm:-left-24',
    ][$sudut];
@endphp

<div aria-hidden="true" {{ $attributes->class([
    'pointer-events-none absolute -z-10',
    'h-80 w-40 opacity-60 sm:h-[38rem] sm:w-76 sm:opacity-100' => $tepi,
    'h-80 w-80 sm:h-[26rem] sm:w-[26rem]' => ! $tepi,
    $posisi,
]) }}>
    @if ($tepi)
        <svg viewBox="0 0 300 600" preserveAspectRatio="xMinYMid meet" class="h-full w-full">
            <defs>
                <linearGradient id="{{ $id }}" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#2f6fed" />
                    <stop offset="100%" stop-color="#13b5cf" />
                </linearGradient>
            </defs>
            {{-- Setengah lingkaran konsentris dari tepi kiri, makin dalam makin pekat. --}}
            <path d="M0 20 A 280 280 0 0 1 0 580 Z" fill="url(#{{ $id }})" opacity="0.08" />
            <path d="M0 100 A 200 200 0 0 1 0 500 Z" fill="url(#{{ $id }})" opacity="0.14" />
            <path d="M0 180 A 120 120 0 0 1 0 420 Z" fill="url(#{{ $id }})" opacity="0.85" />
            @if ($cincin)
                <circle cx="236" cy="96" r="22" fill="none" stroke="#2f6fed" stroke-opacity="0.3" stroke-width="2" />
                <circle cx="236" cy="96" r="11" fill="none" stroke="#13b5cf" stroke-opacity="0.45" stroke-width="2" stroke-dasharray="3 5" />
                <circle cx="210" cy="512" r="5" fill="#2f6fed" fill-opacity="0.35" />
            @endif
        </svg>
    @else
        <svg viewBox="0 0 400 400" class="h-full w-full">
            <defs>
                <linearGradient id="{{ $id }}" x1="0" y1="1" x2="1" y2="0">
                    <stop offset="0%" stop-color="#13b5cf" />
                    <stop offset="100%" stop-color="#2f6fed" />
                </linearGradient>
            </defs>
            {{-- Tiga lapisan dari sudut kanan atas, makin dalam makin pekat. --}}
            <path d="M400 0 V330 C 250 340, 90 230, 70 0 Z" fill="url(#{{ $id }})" opacity="0.10" />
            <path d="M400 0 V260 C 290 262, 170 180, 150 0 Z" fill="url(#{{ $id }})" opacity="0.18" />
            <path d="M400 0 V180 C 320 176, 245 120, 236 0 Z" fill="url(#{{ $id }})" opacity="0.9" />
            @if ($cincin)
                <circle cx="150" cy="300" r="34" fill="none" stroke="#2f6fed" stroke-opacity="0.25" stroke-width="2" />
                <circle cx="150" cy="300" r="18" fill="none" stroke="#13b5cf" stroke-opacity="0.35" stroke-width="2" stroke-dasharray="4 6" />
                <circle cx="60" cy="210" r="5" fill="#2f6fed" fill-opacity="0.35" />
            @endif
        </svg>
    @endif
</div>
