@props(['rekap', 'total', 'riwayat'])

{{-- Rekap presensi satu siswa (lihat AkademikSiswaService::presensi). --}}
@php
    $semua = $total->sum();
    $persen = $semua ? round($total['hadir'] / $semua * 100, 1) : null;
@endphp

<div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
    <div class="card col-span-2 flex items-center gap-4 bg-linear-to-br from-blue-50 to-pink-50 p-5 lg:col-span-1">
        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm"><x-icon name="check-badge" class="h-7 w-7" /></div>
        <div>
            <div class="text-xs font-medium text-slate-500">Kehadiran</div>
            <div class="text-2xl font-bold text-slate-800">{{ $persen !== null ? $persen.'%' : '-' }}</div>
        </div>
    </div>
    @foreach ([['hadir', 'Hadir', 'emerald', 'check'], ['izin', 'Izin', 'sky', 'document'], ['sakit', 'Sakit', 'amber', 'info'], ['alpa', 'Alpa', 'rose', 'warning']] as [$k, $label, $warna, $ikon])
        <x-card-stat :label="$label" :value="$total[$k]" :ikon="$ikon" :warna="$warna" />
    @endforeach
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <div class="card-header"><h3 class="card-title">Per Mata Pelajaran</h3></div>
        <div class="table-wrap px-2 pb-3 sm:px-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mapel</th>
                        <th class="text-center">H</th><th class="text-center">I</th><th class="text-center">S</th><th class="text-center">A</th>
                        <th class="w-40">Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekap as $r)
                        <tr>
                            <td class="font-medium text-slate-800">{{ $r['pengampu']->mapel->nama_mapel }}</td>
                            <td class="text-center text-emerald-600">{{ $r['hadir'] }}</td>
                            <td class="text-center text-sky-600">{{ $r['izin'] }}</td>
                            <td class="text-center text-amber-600">{{ $r['sakit'] }}</td>
                            <td @class(['text-center text-rose-600', 'font-bold' => $r['alpa'] >= 3])>{{ $r['alpa'] }}</td>
                            <td>
                                @if ($r['persen'] !== null)
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-linear-to-r from-blue-400 to-pink-400" style="width: {{ $r['persen'] }}%"></div>
                                        </div>
                                        <span class="w-12 text-right text-xs font-semibold text-slate-600">{{ $r['persen'] }}%</span>
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

    <div class="card">
        <div class="card-header"><h3 class="card-title">Riwayat Terbaru</h3></div>
        <ul class="divide-y divide-slate-100 px-4 pb-3 sm:px-6">
            @forelse ($riwayat as $p)
                <li class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-slate-800">{{ $p->pengampu->mapel->nama_mapel }}</div>
                        <div class="text-xs text-slate-500">Pertemuan {{ $p->pertemuan_ke }} · {{ tanggal_indo($p->tanggal, false) }}</div>
                    </div>
                    <x-badge :status="$p->status" />
                </li>
            @empty
                <li class="py-6 text-center text-sm text-slate-500">Belum ada presensi.</li>
            @endforelse
        </ul>
    </div>
</div>
