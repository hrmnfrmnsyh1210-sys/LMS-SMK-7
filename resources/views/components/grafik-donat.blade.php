@props(['data' => [], 'labelTengah' => 'Total', 'nilaiTengah' => null])

{{--
    Grafik donat ringan dengan CSS conic-gradient (tanpa library).
    $data: list ['label' => ..., 'nilai' => angka, 'warna' => '#hex'].
--}}
@php
    $total = collect($data)->sum('nilai');
    $sudut = 0;
    $irisan = [];
    foreach ($data as $d) {
        $persen = $total > 0 ? $d['nilai'] / $total * 100 : 0;
        $irisan[] = "{$d['warna']} {$sudut}% ".($sudut + $persen).'%';
        $sudut += $persen;
    }
    $gradien = $total > 0 ? implode(', ', $irisan) : '#e2e8f0 0% 100%';
@endphp

<div {{ $attributes->merge(['class' => '@container']) }}>
<div class="flex flex-col items-center gap-6 @md:flex-row">
    <div class="relative h-40 w-40 shrink-0 rounded-full" style="background: conic-gradient({{ $gradien }});">
        <div class="absolute inset-5 flex flex-col items-center justify-center rounded-full bg-white text-center">
            <span class="text-2xl font-bold text-slate-800">{{ $nilaiTengah ?? $total }}</span>
            <span class="px-2 text-[11px] leading-tight text-slate-500">{{ $labelTengah }}</span>
        </div>
    </div>
    <ul class="w-full min-w-0 space-y-2.5 text-sm">
        @foreach ($data as $d)
            <li class="flex items-center gap-2">
                <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $d['warna'] }}"></span>
                <span class="flex-1 truncate text-slate-600">{{ $d['label'] }}</span>
                <span class="font-semibold text-slate-800">{{ $total > 0 ? round($d['nilai'] / $total * 100) : 0 }}%</span>
            </li>
        @endforeach
    </ul>
</div>
</div>
