<x-layouts.app :title="'Presensi · '.$pengampu->label">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="presensi" />

    <x-page-header title="Presensi & Jurnal Mengajar" subtitle="Presensi kehadiran siswa dan jurnal harian materi serta tugas setiap pertemuan kelas.">
        <a href="{{ route('guru.presensi.form', $pengampu) }}" class="btn-primary">
            <x-icon name="check-badge" class="h-4 w-4" />
            @if ($pertemuanHariIni)
                Presensi Hari Ini (Pertemuan {{ $pertemuanHariIni }})
            @else
                Mulai Presensi Hari Ini (Pertemuan {{ $pertemuanOtomatis }})
            @endif
        </a>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Daftar Pertemuan & Jurnal --}}
        <div class="card lg:col-span-2">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Jurnal & Daftar Pertemuan</h3>
                    <p class="text-xs text-slate-500">Materi, tugas, dan rekap per sesi kelas</p>
                </div>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($pertemuan as $p)
                    <div class="flex items-start gap-3 px-4 py-3 sm:px-5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-sm font-bold text-blue-700">
                            {{ $p->pertemuan_ke }}
                        </span>
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-slate-800">{{ tanggal_indo($p->tanggal, false) }}</span>
                                <div class="flex gap-1.5 text-xs">
                                    <span class="text-emerald-600 font-medium">H:{{ (int) $p->hadir }}</span>
                                    <span class="text-sky-600 font-medium">I:{{ (int) $p->izin }}</span>
                                    <span class="text-amber-600 font-medium">S:{{ (int) $p->sakit }}</span>
                                    <span class="text-rose-600 font-medium">A:{{ (int) $p->alpa }}</span>
                                </div>
                            </div>

                            @if ($p->materi)
                                <div class="text-xs text-slate-700">
                                    <span class="font-semibold text-blue-700">Materi:</span> {{ $p->materi }}
                                </div>
                            @endif

                            @if ($p->tugas)
                                <div class="text-xs text-slate-600">
                                    <span class="font-semibold text-amber-700">Tugas:</span> {{ $p->tugas }}
                                </div>
                            @endif

                            @if ($p->catatan)
                                <p class="line-clamp-2 text-xs text-slate-400 italic">"{{ $p->catatan }}"</p>
                            @endif

                            @if ($p->dokumentasi)
                                <div class="pt-0.5">
                                    <a href="{{ file_url($p->dokumentasi) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 hover:text-blue-800">
                                        <x-icon name="photo" class="h-3.5 w-3.5" /> Lihat Bukti Foto
                                    </a>
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('guru.presensi.form', [$pengampu, 'pertemuan' => $p->pertemuan_ke]) }}" class="card-link shrink-0">Ubah</a>
                    </div>
                @empty
                    <div class="py-8 text-center text-sm text-slate-500">Belum ada presensi pertemuan.</div>
                @endforelse
            </div>
        </div>

        {{-- Rekap Kehadiran per Siswa --}}
        <div class="card lg:col-span-3">
            <div class="card-header"><h3 class="card-title">Rekap Semester</h3></div>
            <div class="table-wrap px-2 pb-3 sm:px-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Siswa</th>
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
                                <td class="font-medium text-slate-800">{{ $r['siswa']->nama }}</td>
                                <td class="text-center text-emerald-600 font-medium">{{ $r['hadir'] }}</td>
                                <td class="text-center text-sky-600">{{ $r['izin'] }}</td>
                                <td class="text-center text-amber-600">{{ $r['sakit'] }}</td>
                                <td class="text-center text-rose-600">
                                    {{ $r['alpa'] }}
                                    @if ($r['alpa'] >= 3) <x-badge warna="rose">Sering alpa</x-badge> @endif
                                </td>
                                <td>
                                    @if ($r['persen'] !== null)
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100">
                                                <div @class(['h-full rounded-full', 'bg-emerald-500' => $r['persen'] >= 80, 'bg-amber-500' => $r['persen'] < 80]) style="width: {{ $r['persen'] }}%"></div>
                                            </div>
                                            <span class="text-xs font-medium">{{ $r['persen'] }}%</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="6" pesan="Belum ada siswa." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
