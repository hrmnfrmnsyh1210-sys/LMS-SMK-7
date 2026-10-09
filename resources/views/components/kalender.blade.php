@props(['bulan' => null, 'tandai' => []])

{{-- Kalender bulanan (Senin di awal). $tandai = daftar tanggal 'Y-m-d' yang diberi titik. --}}
@php
    $bulan = ($bulan ? \Illuminate\Support\Carbon::parse($bulan) : now())->startOfMonth();
    $awal = $bulan->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
    $akhir = $bulan->copy()->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);
    $hariIni = now()->toDateString();
    $tanggal = \Carbon\CarbonPeriod::create($awal, $akhir);
@endphp

<div {{ $attributes }}>
    <div class="mb-3 text-center text-sm font-semibold text-slate-700">{{ $bulan->locale('id')->translatedFormat('F Y') }}</div>
    <div class="grid grid-cols-7 gap-y-1 text-center text-xs">
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $i => $hari)
            <div @class(['py-1 font-semibold', 'text-red-500' => $i === 6, 'text-slate-500' => $i !== 6])>{{ $hari }}</div>
        @endforeach

        @foreach ($tanggal as $t)
            @php
                $tgl = $t->toDateString();
                $diBulan = $t->month === $bulan->month;
            @endphp
            <div class="flex flex-col items-center py-0.5">
                <span @class([
                    'flex h-7 w-7 items-center justify-center rounded-lg',
                    'bg-blue-600 font-semibold text-white shadow-sm shadow-blue-600/30' => $tgl === $hariIni,
                    'text-slate-300' => ! $diBulan && $tgl !== $hariIni,
                    'text-red-500' => $diBulan && $t->isSunday() && $tgl !== $hariIni,
                    'text-slate-700' => $diBulan && ! $t->isSunday() && $tgl !== $hariIni,
                ])>{{ $t->day }}</span>
                <span @class(['mt-0.5 h-1 w-1 rounded-full', 'bg-blue-500' => in_array($tgl, $tandai, true)])></span>
            </div>
        @endforeach
    </div>
</div>
