@props(['data' => []])

{{--
    Cincin progres konsentris (SVG, tanpa library), maks. 4 cincin, warna berurutan tetap.
    $data: list ['label' => ..., 'nilai' => 0–100 atau null, 'keterangan' => opsional].
--}}
@php
    $warna = [['#2f6fed', '#5b9bff'], ['#13b5cf', '#5fe0ef'], ['#8b6cf0', '#b9a4ff'], ['#ec5f8e', '#ff9bbd']];
    $data = array_slice(array_values($data), 0, 4);
    $id = 'cincin-'.\Illuminate\Support\Str::random(6);
@endphp

<div {{ $attributes->merge(['class' => '@container']) }}>
    <div class="flex flex-col items-center gap-6 @md:flex-row">
        <svg viewBox="0 0 200 200" class="h-44 w-44 shrink-0 -rotate-90" role="img"
             aria-label="{{ collect($data)->map(fn ($d) => $d['label'].' '.($d['nilai'] ?? '-').'%')->implode(', ') }}">
            <defs>
                @foreach ($data as $i => $d)
                    <linearGradient id="{{ $id }}-{{ $i }}" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="{{ $warna[$i][1] }}" />
                        <stop offset="100%" stop-color="{{ $warna[$i][0] }}" />
                    </linearGradient>
                @endforeach
            </defs>
            @foreach ($data as $i => $d)
                @php
                    $r = 88 - $i * 20;
                    $nilai = max(0, min(100, (float) ($d['nilai'] ?? 0)));
                @endphp
                <circle cx="100" cy="100" r="{{ $r }}" fill="none" stroke="#eef2f7" stroke-width="11" />
                @if ($nilai > 0)
                    <circle cx="100" cy="100" r="{{ $r }}" fill="none" stroke="url(#{{ $id }}-{{ $i }})" stroke-width="11"
                            stroke-linecap="round" pathLength="100" stroke-dasharray="{{ $nilai }} 100">
                        <title>{{ $d['label'] }}: {{ $d['nilai'] ?? '-' }}%</title>
                    </circle>
                @endif
            @endforeach
        </svg>
        <ul class="w-full min-w-0 space-y-3 text-sm">
            @foreach ($data as $i => $d)
                <li class="flex items-center gap-3">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $warna[$i][0] }}"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-slate-600">{{ $d['label'] }}</span>
                        @if ($d['keterangan'] ?? null)
                            <span class="block truncate text-xs text-slate-400">{{ $d['keterangan'] }}</span>
                        @endif
                    </span>
                    <span class="font-semibold text-slate-800">{{ $d['nilai'] !== null ? $d['nilai'].'%' : '-' }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
