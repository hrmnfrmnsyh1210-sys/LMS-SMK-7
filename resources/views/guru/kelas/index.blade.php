@php
    $hariIni = \App\Models\Jadwal::HARI[now()->dayOfWeekIso - 1] ?? null;
    $jurusan = $pengampu->pluck('kelas.jurusan')->unique('id')->sortBy('kode')->values();
    // Aksen warna per jurusan agar kartu mudah dibedakan sekilas.
    $palet = [
        ['garis' => 'bg-amber-400', 'lencana' => 'bg-amber-400/15 text-amber-200 ring-amber-300/30', 'chip' => 'bg-amber-400'],
        ['garis' => 'bg-sky-400', 'lencana' => 'bg-sky-400/15 text-sky-200 ring-sky-300/30', 'chip' => 'bg-sky-400'],
        ['garis' => 'bg-emerald-400', 'lencana' => 'bg-emerald-400/15 text-emerald-200 ring-emerald-300/30', 'chip' => 'bg-emerald-400'],
        ['garis' => 'bg-rose-400', 'lencana' => 'bg-rose-400/15 text-rose-200 ring-rose-300/30', 'chip' => 'bg-rose-400'],
        ['garis' => 'bg-violet-400', 'lencana' => 'bg-violet-400/15 text-violet-200 ring-violet-300/30', 'chip' => 'bg-violet-400'],
    ];
    $warnaJurusan = $jurusan->mapWithKeys(fn ($j, $i) => [$j->id => $palet[$i % count($palet)]]);
    $jadwalHariIniByPengampu = $jadwalHariIni->keyBy('pengampu_id');
    $kelasHariIni = $pengampu->filter(fn ($p) => $jadwalHariIniByPengampu->has($p->id));
    $ringkasan = [
        ['label' => 'Kelas ajar', 'nilai' => $pengampu->count(), 'ikon' => 'book'],
        ['label' => 'Total siswa', 'nilai' => $pengampu->unique('kelas_id')->sum(fn ($p) => $jumlahSiswa[$p->kelas_id] ?? 0), 'ikon' => 'user-group'],
        ['label' => 'Mata pelajaran', 'nilai' => $pengampu->unique('mapel_id')->count(), 'ikon' => 'tag'],
        ['label' => 'Mengajar hari ini', 'nilai' => $kelasHariIni->count(), 'ikon' => 'clock'],
    ];
@endphp

<x-layouts.app title="Kelas & Absensi Mengajar">
    <x-page-header title="Kelas & Absensi Mengajar" :subtitle="'Absen masuk kelas, buka presensi siswa, dan kelola pembelajaran pada '.($tahunAktif?->label ?? '-')" />

    @if ($pengampu->isEmpty())
        <div class="card card-body flex flex-col items-center py-14 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600"><x-icon name="book" class="h-7 w-7" /></span>
            <p class="mt-3 font-medium text-slate-700">Belum ada kelas ajar pada tahun ajaran aktif.</p>
            <p class="text-sm text-slate-500">Hubungi operator untuk plotting pengampu.</p>
        </div>
    @else
        {{-- Ringkasan --}}
        <div class="card grid grid-cols-2 divide-slate-100 lg:grid-cols-4 lg:divide-x mb-6">
            @foreach ($ringkasan as $r)
                <div class="flex items-center gap-3 p-4 sm:p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700"><x-icon :name="$r['ikon']" class="h-5 w-5" /></span>
                    <div>
                        <div class="font-display text-2xl leading-none font-bold text-slate-900">{{ $r['nilai'] }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $r['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="space-y-6"
             x-data="{
                 jurusan: 'semua',
                 cari: '',
                 kelas: @js($pengampu->map(fn ($p) => ['j' => (string) $p->kelas->jurusan_id, 'k' => mb_strtolower($p->mapel->nama_mapel.' '.$p->kelas->nama_kelas.' '.$p->kelas->jurusan->nama)])),
                 cocok(j, k) {
                     return (this.jurusan === 'semua' || this.jurusan === j) && k.includes(this.cari.toLowerCase().trim());
                 },
             }">
            {{-- Saringan --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="jurusan = 'semua'"
                            class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                            :class="jurusan === 'semua' ? 'bg-blue-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-blue-700'">
                        Semua <span class="opacity-60">{{ $pengampu->count() }}</span>
                    </button>
                    @foreach ($jurusan as $j)
                        <button type="button" @click="jurusan = '{{ $j->id }}'"
                                class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium transition"
                                :class="jurusan === '{{ $j->id }}' ? 'bg-blue-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-blue-700'"
                                title="{{ $j->nama }}">
                            <span class="h-2 w-2 rounded-full {{ $warnaJurusan[$j->id]['chip'] }}"></span>
                            {{ $j->kode }} <span class="opacity-60">{{ $pengampu->where('kelas.jurusan_id', $j->id)->count() }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="relative md:w-72">
                    <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-slate-400" />
                    <input type="search" x-model="cari" placeholder="Cari mapel atau kelas..." class="input pl-9">
                </div>
            </div>

            {{-- Kartu Kelas / Mapel --}}
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($pengampu as $p)
                    @php
                        $warna = $warnaJurusan[$p->kelas->jurusan_id];
                        $jadwalP = $jadwalHariIniByPengampu->get($p->id);
                        $absenP = $jadwalP?->absensiGuru?->first();
                        $mengajarHariIni = (bool) $jadwalP;
                    @endphp
                    <div x-show="cocok(kelas[{{ $loop->index }}].j, kelas[{{ $loop->index }}].k)"
                         @class([
                             'card flex flex-col overflow-hidden transition hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-900/5',
                             'ring-2 ring-blue-500 shadow-md' => $mengajarHariIni && (! $absenP || ! $absenP->jam_keluar),
                         ])>
                        {{-- Kepala kartu --}}
                        <div class="relative isolate overflow-hidden bg-sidebar px-5 pt-5 pb-5 text-white">
                            <span class="absolute inset-y-0 left-0 w-1.5 {{ $warna['garis'] }}"></span>
                            <span aria-hidden="true" class="pointer-events-none absolute -right-2 -bottom-5 -z-10 font-display text-7xl font-bold text-white/5 select-none">{{ $p->mapel->kode }}</span>

                            <div class="flex items-start justify-between gap-3">
                                <span class="rounded-md px-2 py-0.5 text-[11px] font-semibold tracking-wide ring-1 ring-inset {{ $warna['lencana'] }}">{{ $p->kelas->jurusan->kode }}</span>
                                @if ($mengajarHariIni)
                                    @if (! $absenP)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-400 px-2.5 py-0.5 text-[11px] font-bold text-slate-900 shadow-xs">
                                            <x-icon name="clock" class="h-3 w-3" /> Hari Ini · {{ substr($jadwalP->jam_mulai, 0, 5) }}
                                        </span>
                                    @elseif (! $absenP->jam_keluar)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400 px-2.5 py-0.5 text-[11px] font-bold text-slate-900 shadow-xs">
                                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-slate-900"></span> Sedang Mengajar
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-600 px-2.5 py-0.5 text-[11px] font-medium text-slate-200">
                                            Selesai ({{ $absenP->durasi() }})
                                        </span>
                                    @endif
                                @endif
                            </div>
                            <h3 class="mt-3 font-display text-xl leading-snug font-bold">
                                <a href="{{ route('guru.kelas.show', $p) }}" class="hover:text-blue-200 transition">
                                    {{ $p->mapel->nama_mapel }}
                                </a>
                            </h3>
                            <div class="mt-1 flex items-center gap-1.5 text-sm text-white/70">
                                <x-icon name="building" class="h-4 w-4" /> Kelas {{ $p->kelas->nama_kelas }}
                            </div>
                        </div>

                        {{-- Statistik Cepat --}}
                        <div class="grid grid-cols-4 gap-1 px-3 py-3 border-b border-slate-100">
                            <a href="{{ route('guru.kelas.show', $p) }}" class="flex flex-col items-center rounded-lg py-1.5 transition hover:bg-slate-50" title="Daftar Siswa">
                                <x-icon name="user-group" class="h-4 w-4 text-slate-400" />
                                <div class="mt-1 text-base leading-none font-bold text-slate-800">{{ $jumlahSiswa[$p->kelas_id] ?? 0 }}</div>
                                <div class="mt-1 text-[11px] text-slate-500">Siswa</div>
                            </a>
                            <a href="{{ route('guru.materi.index', $p) }}" class="flex flex-col items-center rounded-lg py-1.5 transition hover:bg-slate-50" title="Materi Pembelajaran">
                                <x-icon name="document" class="h-4 w-4 text-slate-400" />
                                <div class="mt-1 text-base leading-none font-bold text-slate-800">{{ $p->materi_count }}</div>
                                <div class="mt-1 text-[11px] text-slate-500">Materi</div>
                            </a>
                            <a href="{{ route('guru.tugas.index', $p) }}" class="flex flex-col items-center rounded-lg py-1.5 transition hover:bg-slate-50" title="Tugas">
                                <x-icon name="clipboard-check" class="h-4 w-4 text-slate-400" />
                                <div class="mt-1 text-base leading-none font-bold text-slate-800">{{ $p->tugas_count }}</div>
                                <div class="mt-1 text-[11px] text-slate-500">Tugas</div>
                            </a>
                            <a href="{{ route('guru.kuis.index', $p) }}" class="flex flex-col items-center rounded-lg py-1.5 transition hover:bg-slate-50" title="Kuis & Ujian">
                                <x-icon name="puzzle" class="h-4 w-4 text-slate-400" />
                                <div class="mt-1 text-base leading-none font-bold text-slate-800">{{ $p->kuis_count }}</div>
                                <div class="mt-1 text-[11px] text-slate-500">Kuis</div>
                            </a>
                        </div>

                        {{-- Aksi Masuk Kelas & Presensi (Khusus Hari Ini) --}}
                        @if ($mengajarHariIni)
                            @if (! $absenP)
                                {{-- Belum Masuk Kelas --}}
                                <div class="mt-auto bg-blue-50/60 p-4 border-t border-blue-100 flex flex-col gap-2.5">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="inline-flex items-center gap-1.5 font-semibold text-blue-900">
                                            <x-icon name="clock" class="h-3.5 w-3.5 text-blue-600" />
                                            {{ substr($jadwalP->jam_mulai, 0, 5) }} - {{ substr($jadwalP->jam_selesai, 0, 5) }}@if ($jadwalP->ruang) · {{ $jadwalP->ruang }}@endif
                                        </span>
                                        <span class="text-[11px] font-medium text-amber-700 bg-amber-100/70 px-2 py-0.5 rounded-full">
                                            Presensi Tertutup
                                        </span>
                                    </div>
                                    <form method="POST" action="{{ route('guru.absensi.masuk', $jadwalP) }}">
                                        @csrf
                                        <button type="submit" class="btn-primary w-full justify-center text-xs py-2 shadow-xs">
                                            <x-icon name="check" class="h-4 w-4" /> Masuk Kelas (Buka Presensi)
                                        </button>
                                    </form>
                                </div>
                            @elseif (! $absenP->jam_keluar)
                                {{-- Sedang Mengajar --}}
                                <div class="mt-auto bg-emerald-50/60 p-4 border-t border-emerald-100 flex flex-col gap-2.5">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="inline-flex items-center gap-1.5 font-semibold text-emerald-900">
                                            <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"></span>
                                            Masuk pukul {{ substr($absenP->jam_masuk, 0, 5) }}
                                        </span>
                                        <x-badge :warna="$absenP->status === 'terlambat' ? 'amber' : 'emerald'">{{ $absenP->status === 'terlambat' ? 'Terlambat' : 'Tepat waktu' }}</x-badge>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('guru.presensi.form', $p) }}" class="btn-primary btn-sm flex-1 justify-center text-xs py-2 shadow-xs">
                                            <x-icon name="clipboard-check" class="h-4 w-4" /> Presensi & Jurnal
                                        </a>
                                        <form method="POST" action="{{ route('guru.absensi.keluar', $jadwalP) }}" class="shrink-0">
                                            @csrf
                                            <button type="submit" class="btn-danger btn-sm text-xs py-2" title="Selesai & Keluar Kelas">
                                                <x-icon name="logout" class="h-4 w-4" /> Keluar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @else
                                {{-- Selesai Mengajar --}}
                                <div class="mt-auto bg-slate-50 p-4 border-t border-slate-100 flex items-center justify-between text-xs">
                                    <span class="font-medium text-slate-600">
                                        Selesai mengajar · {{ $absenP->durasi() }}
                                    </span>
                                    <a href="{{ route('guru.presensi.index', $p) }}" class="inline-flex items-center gap-1 font-semibold text-blue-600 hover:text-blue-700 hover:underline">
                                        Presensi & Jurnal <x-icon name="arrow-right" class="h-3 w-3" />
                                    </a>
                                </div>
                            @endif
                        @else
                            {{-- Jadwal Hari Lain --}}
                            <div class="mt-auto flex items-center justify-between border-t border-slate-100 px-5 py-3.5 text-xs bg-slate-50/50">
                                <div class="flex min-w-0 flex-1 flex-wrap gap-1">
                                    @forelse ($p->jadwal as $j)
                                        <span class="rounded bg-white px-2 py-0.5 text-slate-600 font-medium ring-1 ring-slate-200 whitespace-nowrap">{{ $j->hari }}, {{ $j->jam }}</span>
                                    @empty
                                        <span class="text-slate-400">Belum ada jadwal</span>
                                    @endforelse
                                </div>
                                <a href="{{ route('guru.kelas.show', $p) }}" class="inline-flex shrink-0 items-center gap-1 font-semibold text-blue-600 hover:text-blue-700 hover:underline">
                                    Kelola Kelas <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                                </a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <p x-cloak x-show="! kelas.some(k => cocok(k.j, k.k))" class="card card-body text-center text-sm text-slate-500">
                Tidak ada kelas yang cocok dengan pencarian.
            </p>
        </div>

        {{-- Riwayat Absensi Mengajar Bulan Ini --}}
        @if ($riwayatAbsensi->isNotEmpty())
            <div x-data="{ buka: false }" class="card overflow-hidden mt-8">
                <button type="button" @click="buka = ! buka" class="flex w-full items-center justify-between px-5 py-3.5 text-left text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    <span class="flex items-center gap-2">
                        <x-icon name="calendar" class="h-4 w-4 text-blue-600" />
                        Riwayat Absensi Mengajar Bulan Ini
                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] text-blue-700 font-normal">
                            {{ $riwayatAbsensi->count() }} sesi · {{ $riwayatAbsensi->where('status', 'terlambat')->count() }} terlambat
                        </span>
                    </span>
                    <span class="flex items-center gap-1 text-slate-400">
                        <span x-text="buka ? 'Tutup Riwayat' : 'Lihat Riwayat'"></span>
                        <x-icon name="chevron-down" class="h-4 w-4 transition-transform duration-200" ::class="buka ? 'rotate-180' : ''" />
                    </span>
                </button>
                <div x-show="buka" x-cloak class="border-t border-slate-100 px-4 py-3 sm:px-6 overflow-x-auto">
                    <table class="table w-full text-xs">
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
                            @foreach ($riwayatAbsensi as $ra)
                                <tr>
                                    <td class="whitespace-nowrap">{{ $ra->tanggal->locale('id')->translatedFormat('D, j M Y') }}</td>
                                    <td>
                                        <div class="font-medium text-slate-800">{{ $ra->jadwal->pengampu->kelas->nama_kelas }}</div>
                                        <div class="text-[11px] text-slate-500">{{ $ra->jadwal->pengampu->mapel->nama_mapel }}</div>
                                    </td>
                                    <td class="text-center whitespace-nowrap">{{ $ra->jadwal->jam }}</td>
                                    <td class="text-center">{{ substr($ra->jam_masuk, 0, 5) }}</td>
                                    <td class="text-center">{{ $ra->jam_keluar ? substr($ra->jam_keluar, 0, 5) : '-' }}</td>
                                    <td><x-badge :warna="$ra->status === 'terlambat' ? 'amber' : 'emerald'">{{ $ra->status === 'terlambat' ? 'Terlambat' : 'Tepat waktu' }}</x-badge></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</x-layouts.app>
