@props(['rekap', 'total', 'riwayat'])

{{-- Rekap presensi satu siswa (lihat AkademikSiswaService::presensi). --}}
@php
    $semua = $total->sum();
    $persen = $semua ? round($total['hadir'] / $semua * 100, 1) : null;
    $statusWarna = ['hadir' => 'emerald', 'izin' => 'sky', 'sakit' => 'amber', 'alpa' => 'rose'];
    $statusLabel = ['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'alpa' => 'Alpa'];
@endphp

{{-- Ringkasan Total --}}
<div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
    <div class="card col-span-2 lg:col-span-1 flex items-center gap-4 p-5"
         style="background: linear-gradient(135deg,#eff6ff,#fdf2f8)">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm">
            <x-icon name="check-badge" class="h-7 w-7" />
        </div>
        <div>
            <div class="text-xs font-medium text-slate-500">Total Kehadiran</div>
            <div @class([
                'text-2xl font-bold',
                'text-emerald-600' => $persen !== null && $persen >= 80,
                'text-amber-600' => $persen !== null && $persen < 80,
                'text-slate-800' => $persen === null,
            ])>{{ $persen !== null ? $persen.'%' : '-' }}</div>
            <div class="text-xs text-slate-400">dari {{ $semua }} pertemuan</div>
        </div>
    </div>
    @foreach ([
        ['hadir', 'Hadir', 'emerald', 'check'],
        ['izin', 'Izin', 'sky', 'document'],
        ['sakit', 'Sakit', 'amber', 'info'],
        ['alpa', 'Alpa', 'rose', 'warning'],
    ] as [$k, $label, $warna, $ikon])
        <div class="card p-4 flex items-center gap-3">
            <span @class([
                'flex h-10 w-10 items-center justify-center rounded-xl shrink-0',
                'bg-emerald-50 text-emerald-600' => $warna === 'emerald',
                'bg-sky-50 text-sky-600' => $warna === 'sky',
                'bg-amber-50 text-amber-600' => $warna === 'amber',
                'bg-rose-50 text-rose-600' => $warna === 'rose',
            ])>
                <x-icon :name="$ikon" class="h-5 w-5" />
            </span>
            <div>
                <div class="text-xs text-slate-500">{{ $label }}</div>
                <div class="text-2xl font-bold text-slate-800">{{ $total[$k] }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Rekap Per Mata Pelajaran --}}
    <div class="card lg:col-span-2">
        <div class="card-header"><h3 class="card-title">Rekap Per Mata Pelajaran</h3></div>
        <div class="table-wrap px-2 pb-3 sm:px-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mata Pelajaran</th>
                        <th class="text-center">Hadir</th>
                        <th class="text-center">Izin</th>
                        <th class="text-center">Sakit</th>
                        <th class="text-center">Alpa</th>
                        <th>Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekap as $r)
                        <tr>
                            <td class="font-medium text-slate-800">{{ $r['pengampu']->mapel->nama_mapel }}</td>
                            <td class="text-center">
                                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-emerald-50 text-xs font-bold text-emerald-700">{{ $r['hadir'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-sky-50 text-xs font-bold text-sky-700">{{ $r['izin'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-amber-50 text-xs font-bold text-amber-700">{{ $r['sakit'] }}</span>
                            </td>
                            <td class="text-center">
                                @if ($r['alpa'] >= 3)
                                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-rose-100 text-xs font-bold text-rose-700 ring-2 ring-rose-300">{{ $r['alpa'] }}</span>
                                @else
                                    <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-rose-50 text-xs font-bold text-rose-600">{{ $r['alpa'] }}</span>
                                @endif
                            </td>
                            <td>
                                @if ($r['persen'] !== null)
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div @class([
                                                'h-full rounded-full transition-all',
                                                'bg-emerald-500' => $r['persen'] >= 80,
                                                'bg-amber-500' => $r['persen'] >= 60 && $r['persen'] < 80,
                                                'bg-rose-500' => $r['persen'] < 60,
                                            ]) style="width: {{ $r['persen'] }}%"></div>
                                        </div>
                                        <span @class([
                                            'w-12 text-right text-xs font-semibold',
                                            'text-emerald-600' => $r['persen'] >= 80,
                                            'text-amber-600' => $r['persen'] >= 60 && $r['persen'] < 80,
                                            'text-rose-600' => $r['persen'] < 60,
                                        ])>{{ $r['persen'] }}%</span>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Belum ada data</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="6" pesan="Belum ada mata pelajaran." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Riwayat Terbaru --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Riwayat Kehadiran</h3></div>
        <ul class="divide-y divide-slate-100 px-4 pb-3 sm:px-6">
            @forelse ($riwayat as $p)
                <li class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-slate-800">{{ $p->pengampu->mapel->nama_mapel }}</div>
                        <div class="text-xs text-slate-500">
                            Pertemuan {{ $p->pertemuan_ke }} · {{ tanggal_indo($p->tanggal, false) }}
                        </div>
                    </div>
                    <div class="shrink-0">
                        @php
                            $badgeWarna = ['hadir' => 'emerald', 'izin' => 'sky', 'sakit' => 'amber', 'alpa' => 'rose'];
                        @endphp
                        <x-badge :warna="$badgeWarna[$p->status]">{{ ucfirst($p->status) }}</x-badge>
                    </div>
                </li>
            @empty
                <li class="py-6 text-center text-sm text-slate-500">Belum ada presensi.</li>
            @endforelse
        </ul>
    </div>
</div>
