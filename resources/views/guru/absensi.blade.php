<x-layouts.app title="Absensi Mengajar">
    <x-page-header title="Absensi Mengajar"
                   :subtitle="'Absen masuk setiap memasuki kelas sesuai jadwal dan absen keluar saat selesai. Dibuka '.\App\Services\AbsensiGuruService::BUKA_SEBELUM_MENIT.' menit sebelum jam mulai, toleransi terlambat '.$toleransi.' menit.'" />

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Kelas Hari Ini</h3>
                <p class="text-xs text-slate-500">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</p>
            </div>
            <div class="font-display text-xl font-bold text-slate-800 tabular-nums"
                 x-data="{ jam: @js(now()->format('H:i:s')) }"
                 x-init="setInterval(() => jam = new Date().toLocaleTimeString('id-ID', { hour12: false }).replaceAll('.', ':'), 1000)"
                 x-text="jam">{{ now()->format('H:i:s') }}</div>
        </div>
        <ul class="grid gap-3 px-4 pb-5 sm:grid-cols-2 sm:px-6 xl:grid-cols-3">
            @forelse ($jadwalHariIni as $j)
                @php $a = $j->absensiGuru->first(); @endphp
                <li class="flex flex-col gap-3 rounded-xl border border-slate-100 p-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-blue-50 text-xs font-semibold text-blue-700">
                            {{ substr($j->jam_mulai, 0, 5) }}<span class="font-normal text-blue-400">{{ substr($j->jam_selesai, 0, 5) }}</span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-slate-800">{{ $j->pengampu->mapel->nama_mapel }}</div>
                            <div class="text-xs text-slate-500">{{ $j->pengampu->kelas->nama_kelas }}@if ($j->ruang) · {{ $j->ruang }}@endif</div>
                        </div>
                        @if ($a)
                            <x-badge :warna="$a->status === 'terlambat' ? 'amber' : 'emerald'">{{ $a->status === 'terlambat' ? 'Terlambat' : 'Tepat waktu' }}</x-badge>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-2 rounded-lg bg-slate-50 px-3 py-2 text-xs">
                        <div><span class="text-slate-500">Masuk</span> <span class="font-semibold text-slate-800">{{ $a ? substr($a->jam_masuk, 0, 5) : '--:--' }}</span></div>
                        <div><span class="text-slate-500">Keluar</span> <span class="font-semibold text-slate-800">{{ $a?->jam_keluar ? substr($a->jam_keluar, 0, 5) : '--:--' }}</span></div>
                    </div>

                    @if (! $a)
                        <form method="POST" action="{{ route('guru.absensi.masuk', $j) }}">
                            @csrf
                            <button type="submit" class="btn-primary w-full justify-center"><x-icon name="check" class="h-4 w-4" /> Masuk Kelas</button>
                        </form>
                    @elseif (! $a->jam_keluar)
                        <form method="POST" action="{{ route('guru.absensi.keluar', $j) }}">
                            @csrf
                            <button type="submit" class="btn-danger w-full justify-center"><x-icon name="logout" class="h-4 w-4" /> Keluar Kelas</button>
                        </form>
                    @else
                        <div class="rounded-lg bg-emerald-50 px-3 py-2 text-center text-xs font-medium text-emerald-700">Selesai mengajar · {{ $a->durasi() }}</div>
                    @endif
                </li>
            @empty
                <li class="py-6 text-center text-sm text-slate-500 sm:col-span-2 xl:col-span-3">Tidak ada jadwal mengajar hari ini.</li>
            @endforelse
        </ul>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Riwayat Bulan Ini</h3>
                <p class="text-xs text-slate-500">{{ $riwayat->count() }} sesi mengajar · {{ $riwayat->where('status', 'terlambat')->count() }} kali terlambat</p>
            </div>
        </div>
        <div class="table-wrap px-2 pb-3 sm:px-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kelas & Mapel</th>
                        <th class="text-center">Jadwal</th>
                        <th class="text-center">Masuk</th>
                        <th class="text-center">Keluar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riwayat as $a)
                        <tr>
                            <td class="whitespace-nowrap">{{ $a->tanggal->locale('id')->translatedFormat('D, j M Y') }}</td>
                            <td>
                                <div class="font-medium text-slate-800">{{ $a->jadwal->pengampu->kelas->nama_kelas }}</div>
                                <div class="text-xs text-slate-500">{{ $a->jadwal->pengampu->mapel->nama_mapel }}</div>
                            </td>
                            <td class="text-center whitespace-nowrap">{{ $a->jadwal->jam }}</td>
                            <td class="text-center">{{ substr($a->jam_masuk, 0, 5) }}</td>
                            <td class="text-center">{{ $a->jam_keluar ? substr($a->jam_keluar, 0, 5) : '-' }}</td>
                            <td><x-badge :warna="$a->status === 'terlambat' ? 'amber' : 'emerald'">{{ $a->status === 'terlambat' ? 'Terlambat' : 'Tepat waktu' }}</x-badge></td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="6" pesan="Belum ada absensi mengajar bulan ini." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
