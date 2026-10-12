@props(['nilai' => null, 'label' => '', 'satuan' => '%', 'maks' => 100, 'acuan' => null, 'labelAcuan' => 'Target'])

{{-- Gauge setengah lingkaran bergradasi cyan→biru (SVG). $acuan: penanda target pada busur, mis. KKM. --}}
@php
    $id = 'gauge-'.\Illuminate\Support\Str::random(6);
    $persen = $nilai === null ? 0 : max(0, min(100, (float) $nilai / $maks * 100));
    $titikAcuan = null;
    if ($acuan !== null) {
        $sudut = M_PI * (1 - max(0, min(1, $acuan / $maks)));
        $titikAcuan = [
            round(110 + 74 * cos($sudut), 1), round(110 - 74 * sin($sudut), 1),
            round(110 + 106 * cos($sudut), 1), round(110 - 106 * sin($sudut), 1),
        ];
    }
@endphp

<div {{ $attributes->merge(['class' => 'mx-auto w-full max-w-64']) }}>
    <svg viewBox="0 0 220 128" class="w-full" role="img" aria-label="{{ $label }}: {{ $nilai ?? '-' }}{{ $satuan }}">
        <defs>
            <linearGradient id="{{ $id }}" x1="0" y1="0" x2="1" y2="0">
                <stop offset="0%" stop-color="#13b5cf" />
                <stop offset="100%" stop-color="#2f6fed" />
            </linearGradient>
        </defs>
        <path d="M 20 110 A 90 90 0 0 1 200 110" fill="none" stroke="#eef2f7" stroke-width="16" stroke-linecap="round" />
        @if ($persen > 0)
            <path d="M 20 110 A 90 90 0 0 1 200 110" fill="none" stroke="url(#{{ $id }})" stroke-width="16" stroke-linecap="round"
                  pathLength="100" stroke-dasharray="{{ $persen }} 100" />
        @endif
        @if ($titikAcuan)
            <line x1="{{ $titikAcuan[0] }}" y1="{{ $titikAcuan[1] }}" x2="{{ $titikAcuan[2] }}" y2="{{ $titikAcuan[3] }}"
                  stroke="#ec5f8e" stroke-width="3" stroke-linecap="round">
                <title>{{ $labelAcuan }}: {{ $acuan }}</title>
            </line>
        @endif
        <text x="110" y="94" text-anchor="middle" fill="#1e293b" style="font-size: 34px; font-weight: 700">{{ $nilai ?? '-' }}<tspan fill="#64748b" style="font-size: 15px; font-weight: 600">{{ $nilai !== null ? $satuan : '' }}</tspan></text>
        <text x="110" y="116" text-anchor="middle" fill="#64748b" style="font-size: 11px">{{ $label }}</text>
    </svg>
    @if ($acuan !== null)
        <div class="mt-1 flex items-center justify-center gap-1.5 text-xs text-slate-500">
            <span class="h-0.5 w-3 rounded" style="background: #ec5f8e"></span> {{ $labelAcuan }} {{ $acuan }}
        </div>
    @endif
</div>
