@php
    $warna = ['bg-violet-50 text-violet-700', 'bg-pink-50 text-pink-700', 'bg-sky-50 text-sky-700', 'bg-emerald-50 text-emerald-700', 'bg-amber-50 text-amber-700', 'bg-rose-50 text-rose-700'];
    $semua = $total->sum();
    $persen = $semua ? round($total['hadir'] / $semua * 100, 1) : null;
@endphp

<x-layouts.app title="Jadwal & Presensi">
    <x-page-header
        title="Jadwal & Presensi"
        :subtitle="($kelas ? 'Kelas '.$kelas->nama_kelas.' · ' : '').now()->isoFormat('dddd, D MMMM Y')" />

    {{-- ================================================================ --}}
    {{-- JADWAL MINGGUAN + ABSEN MANDIRI (hari ini)                       --}}
    {{-- ================================================================ --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach (\App\Models\Jadwal::HARI as $i => $hari)
            @php $isHariIni = ($hari === $hariIni); @endphp
            <div @class(['card overflow-hidden', 'ring-2 ring-blue-400 shadow-md' => $isHariIni])>
                {{-- Header hari --}}
                <div @class([
                    'flex items-center justify-between px-5 py-3',
                    'bg-linear-to-r from-blue-500 to-pink-400 text-white' => $isHariIni,
                    'bg-slate-50 text-slate-600' => ! $isHariIni,
                ])>
                    <span class="font-semibold">{{ $hari }}</span>
                    @if ($isHariIni)
                        <span class="rounded-full bg-white/25 px-2.5 py-0.5 text-xs font-medium">Hari ini</span>
                    @endif
                </div>

                {{-- Daftar mata pelajaran --}}
                <ul class="space-y-2 p-3">
                    @forelse ($jadwal->get($hari, collect()) as $j)
                        @php $p = $isHariIni ? $presensiHariIni->get($j->pengampu_id) : null; @endphp
                        <li @class([
                            'flex items-center gap-3 rounded-2xl p-2.5 transition',
                            'bg-blue-50/50' => $isHariIni && ! $p,
                            'bg-emerald-50/50' => $isHariIni && $p?->status === 'hadir',
                            'bg-sky-50/50' => $isHariIni && $p?->status === 'izin',
                            'bg-amber-50/50' => $isHariIni && $p?->status === 'sakit',
                            'hover:bg-slate-50' => ! $isHariIni,
                        ])>
                            {{-- Jam --}}
                            <span class="flex h-12 w-14 shrink-0 flex-col items-center justify-center rounded-2xl text-xs font-semibold {{ $warna[$j->pengampu->mapel_id % count($warna)] }}">
                                {{ substr($j->jam_mulai, 0, 5) }}
                                <span class="font-normal opacity-70">{{ substr($j->jam_selesai, 0, 5) }}</span>
                            </span>

                            {{-- Info mapel --}}
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('siswa.mapel.show', $j->pengampu) }}"
                                   class="block truncate text-sm font-semibold text-slate-800 hover:text-blue-700">
                                    {{ $j->pengampu->mapel->nama_mapel }}
                                </a>
                                <div class="truncate text-xs text-slate-500">
                                    {{ $j->pengampu->guru->nama }}@if ($j->ruang) · {{ $j->ruang }}@endif
                                </div>
                                @if ($isHariIni && $p)
                                    <div class="mt-0.5 text-[11px] text-slate-400">
                                        Absen pukul {{ $p->waktu_absen?->format('H:i') ?? '-' }}
                                    </div>
                                @endif
                            </div>

                            {{-- Status / tombol absen (hanya hari ini) --}}
                            @if ($isHariIni)
                                @if ($p)
                                    <x-badge :status="$p->status" class="shrink-0" />
                                @elseif ($sesiTerbukaPengampuIds->contains($j->pengampu_id))
                                    <button type="button"
                                            class="btn-primary btn-sm shrink-0"
                                            @click="$dispatch('buka-presensi', {
                                                id: {{ $j->pengampu_id }},
                                                mapel: '{{ addslashes($j->pengampu->mapel->nama_mapel) }}',
                                                guru: '{{ addslashes($j->pengampu->guru->nama) }}'
                                            })">
                                        Absen
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 shrink-0" title="Guru belum melakukan absen masuk kelas">
                                        <x-icon name="clock" class="h-3 w-3" /> Menunggu Guru
                                    </span>
                                @endif
                            @endif
                        </li>
                    @empty
                        <li class="py-4 text-center text-sm text-slate-400">Tidak ada pelajaran</li>
                    @endforelse
                </ul>
            </div>
        @endforeach
    </div>

    {{-- ================================================================ --}}
    {{-- REKAP PRESENSI SEMESTER                                          --}}
    {{-- ================================================================ --}}
    <div class="mt-2">
        <h2 class="mb-3 flex items-center gap-2 text-base font-semibold text-slate-700">
            <x-icon name="check-badge" class="h-5 w-5 text-blue-600" /> Rekap Presensi Semester
        </h2>

        {{-- Ringkasan angka --}}
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
                        'text-amber-600'   => $persen !== null && $persen < 80,
                        'text-slate-800'   => $persen === null,
                    ])>{{ $persen !== null ? $persen.'%' : '-' }}</div>
                    <div class="text-xs text-slate-400">dari {{ $semua }} pertemuan</div>
                </div>
            </div>
            @foreach ([
                ['hadir', 'Hadir', 'emerald', 'check'],
                ['izin', 'Izin', 'sky', 'document'],
                ['sakit', 'Sakit', 'amber', 'info'],
                ['alpa', 'Alpa', 'rose', 'warning'],
            ] as [$k, $label, $wrn, $ikon])
                <div class="card flex items-center gap-3 p-4">
                    <span @class([
                        'flex h-10 w-10 items-center justify-center rounded-xl shrink-0',
                        'bg-emerald-50 text-emerald-600' => $wrn === 'emerald',
                        'bg-sky-50 text-sky-600'         => $wrn === 'sky',
                        'bg-amber-50 text-amber-600'     => $wrn === 'amber',
                        'bg-rose-50 text-rose-600'       => $wrn === 'rose',
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

        {{-- Rekap per mapel + riwayat --}}
        <div class="mt-4 grid gap-6 lg:grid-cols-3">
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
                                                        'bg-amber-500'   => $r['persen'] >= 60 && $r['persen'] < 80,
                                                        'bg-rose-500'    => $r['persen'] < 60,
                                                    ]) style="width: {{ $r['persen'] }}%"></div>
                                                </div>
                                                <span @class([
                                                    'w-12 text-right text-xs font-semibold',
                                                    'text-emerald-600' => $r['persen'] >= 80,
                                                    'text-amber-600'   => $r['persen'] >= 60 && $r['persen'] < 80,
                                                    'text-rose-600'    => $r['persen'] < 60,
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
                    @forelse ($riwayat as $rp)
                        <li class="flex items-start justify-between gap-3 py-3">
                            <div class="min-w-0 space-y-1">
                                <div class="truncate text-sm font-semibold text-slate-800">{{ $rp->pengampu->mapel->nama_mapel }}</div>
                                <div class="text-xs text-slate-500">
                                    Pertemuan {{ $rp->pertemuan_ke }} · {{ tanggal_indo($rp->tanggal, false) }}
                                </div>
                                @if ($rp->materi)
                                    <div class="text-xs text-slate-700">
                                        <span class="font-semibold text-blue-700">Materi:</span> {{ $rp->materi }}
                                    </div>
                                @endif
                                @if ($rp->tugas)
                                    <div class="text-xs text-slate-600">
                                        <span class="font-semibold text-amber-700">Tugas:</span> {{ $rp->tugas }}
                                    </div>
                                @endif
                                @if ($rp->catatan)
                                    <div class="text-[11px] text-slate-400 italic">"{{ $rp->catatan }}"</div>
                                @endif
                                @if ($rp->dokumentasi)
                                    <div class="mt-1">
                                        <a href="{{ file_url($rp->dokumentasi) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 hover:text-emerald-700 hover:underline">
                                            <x-icon name="photo" class="h-3.5 w-3.5" />
                                            <span>Bukti Foto Pembelajaran</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                            <div class="shrink-0">
                                @php $bw = ['hadir' => 'emerald', 'izin' => 'sky', 'sakit' => 'amber', 'alpa' => 'rose']; @endphp
                                <x-badge :warna="$bw[$rp->status]">{{ ucfirst($rp->status) }}</x-badge>
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-500">Belum ada presensi.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <x-siswa.modal-presensi />
</x-layouts.app>
