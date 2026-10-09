@props(['jadwal', 'tautanMapel' => true])

{{-- Jadwal mingguan kelas siswa ($jadwal dikelompokkan per hari). $tautanMapel = false untuk orang tua. --}}
@php
    $hariIni = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'][now()->dayOfWeekIso];
    $warna = ['bg-violet-50 text-violet-700', 'bg-pink-50 text-pink-700', 'bg-sky-50 text-sky-700', 'bg-emerald-50 text-emerald-700', 'bg-amber-50 text-amber-700', 'bg-rose-50 text-rose-700'];
@endphp

<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
    @foreach (\App\Models\Jadwal::HARI as $i => $hari)
        <div @class(['card overflow-hidden', 'ring-2 ring-blue-300' => $hari === $hariIni])>
            <div @class([
                'flex items-center justify-between px-5 py-3',
                'bg-linear-to-r from-blue-500 to-pink-400 text-white' => $hari === $hariIni,
                'bg-slate-50 text-slate-700' => $hari !== $hariIni,
            ])>
                <span class="font-semibold">{{ $hari }}</span>
                @if ($hari === $hariIni)
                    <span class="rounded-full bg-white/25 px-2.5 py-0.5 text-xs font-medium">Hari ini</span>
                @endif
            </div>
            <ul class="space-y-2 p-4">
                @forelse ($jadwal->get($hari, collect()) as $j)
                    <li class="flex items-center gap-3">
                        <span class="flex h-12 w-14 shrink-0 flex-col items-center justify-center rounded-2xl text-xs font-semibold {{ $warna[$j->pengampu->mapel_id % count($warna)] }}">
                            {{ substr($j->jam_mulai, 0, 5) }}<span class="font-normal opacity-70">{{ substr($j->jam_selesai, 0, 5) }}</span>
                        </span>
                        <div class="min-w-0 flex-1">
                            @if ($tautanMapel)
                                <a href="{{ route('siswa.mapel.show', $j->pengampu) }}" class="block truncate text-sm font-semibold text-slate-800 hover:text-blue-700">{{ $j->pengampu->mapel->nama_mapel }}</a>
                            @else
                                <div class="truncate text-sm font-semibold text-slate-800">{{ $j->pengampu->mapel->nama_mapel }}</div>
                            @endif
                            <div class="truncate text-xs text-slate-500">{{ $j->pengampu->guru->nama }}@if ($j->ruang) · {{ $j->ruang }}@endif</div>
                        </div>
                    </li>
                @empty
                    <li class="py-4 text-center text-sm text-slate-400">Tidak ada pelajaran</li>
                @endforelse
            </ul>
        </div>
    @endforeach
</div>
