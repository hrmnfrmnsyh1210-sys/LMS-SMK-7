<x-layouts.app title="Rekap Kelas">
    <x-page-header title="Rekap Kelas" :subtitle="'Kehadiran dan rata-rata nilai akhir per kelas · '.($tahunAktif?->label ?? '-').' · KKM '.$kkm" />

    <div class="card">
        <div class="table-wrap px-2 py-3 sm:px-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>Wali Kelas</th>
                        <th class="text-center">Siswa</th>
                        <th class="w-48">Kehadiran</th>
                        <th class="text-center">Rata-rata Nilai</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekap as $r)
                        <tr>
                            <td>
                                <div class="font-medium text-slate-800">{{ $r['kelas']->nama_kelas }}</div>
                                <div class="text-xs text-slate-500">{{ $r['kelas']->jurusan?->nama }}</div>
                            </td>
                            <td class="text-slate-600">{{ $r['kelas']->waliKelas?->nama ?? '-' }}</td>
                            <td class="text-center">{{ $r['siswa'] }}</td>
                            <td>
                                @if ($r['kehadiran'] !== null)
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-emerald-400" style="width: {{ $r['kehadiran'] }}%"></div>
                                        </div>
                                        <span class="w-12 text-right text-xs font-semibold text-slate-600">{{ $r['kehadiran'] }}%</span>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">Belum ada presensi</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($r['rata'] !== null)
                                    <span @class(['font-bold', 'text-emerald-600' => $r['rata'] >= $kkm, 'text-rose-600' => $r['rata'] < $kkm])>{{ $r['rata'] }}</span>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('kepsek.monitoring.kelas.show', $r['kelas']) }}" class="btn-secondary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="6" pesan="Belum ada kelas pada tahun ajaran aktif." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
