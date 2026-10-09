@php
    $namaPanggilan = \Illuminate\Support\Str::of($user->nama)->explode(' ')->first();
    $jam = (int) now()->format('G');
    [$sapaan, $ikonWaktu] = match (true) {
        $jam >= 4 && $jam < 11 => ['Pagi', 'sun'],
        $jam >= 11 && $jam < 15 => ['Siang', 'sun'],
        $jam >= 15 && $jam < 18 => ['Sore', 'sun'],
        default => ['Malam', 'moon'],
    };
    $kutipan = [
        'Progress kecil tetap progress. Keep going!',
        'Belajar dikit-dikit, lama-lama jadi pro.',
        'Kamu nggak harus sempurna, cukup konsisten.',
        'Satu tugas selesai = satu langkah lebih dekat ke mimpi.',
        'Rehat boleh, menyerah jangan.',
        'Hari ini mungkin berat, tapi kamu lebih kuat.',
        'Fokus ke diri sendiri, bukan ke timeline orang lain.',
    ];
    $kutipanHariIni = $kutipan[now()->dayOfYear % count($kutipan)];
    // [ikon, judul, nilai, keterangan, route, gradasi kartu, gradasi ikon]
    $ringkasan = [
        ['clipboard-check', 'Tugas', $statistik['tugas'], 'belum dikumpulkan', route('siswa.tugas.index', ['status' => 'belum']), 'from-pink-50 to-pink-100/60', 'from-pink-300 to-pink-400'],
        ['puzzle', 'Kuis', $statistik['kuis'], 'sedang dibuka', route('siswa.kuis.index'), 'from-sky-50 to-sky-100/60', 'from-sky-300 to-sky-400'],
        ['check-badge', 'Kehadiran', $statistik['kehadiran'] !== null ? $statistik['kehadiran'].'%' : '-', 'tahun ajaran ini', route('siswa.presensi'), 'from-emerald-50 to-emerald-100/60', 'from-emerald-200 to-emerald-500'],
        ['chart', 'Rata-rata', $statistik['nilai'] ?? '-', 'nilai akhir', route('siswa.nilai'), 'from-violet-50 to-violet-100/60', 'from-violet-300 to-violet-400'],
    ];
@endphp

<x-layouts.app title="Dashboard">
    {{-- Hero sapaan --}}
    <div class="relative overflow-hidden rounded-3xl bg-linear-to-br from-blue-100 via-pink-50 to-amber-50 px-6 py-8 sm:px-10">
        <div class="pointer-events-none absolute -top-16 -right-10 h-56 w-56 rounded-full bg-pink-200/50 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 left-1/3 h-56 w-56 rounded-full bg-blue-200/60 blur-3xl"></div>

        {{-- Dekorasi ikon melayang --}}
        <div class="pointer-events-none absolute inset-y-0 right-6 hidden w-64 sm:block" aria-hidden="true">
            <div class="absolute top-6 right-4 flex h-16 w-16 rotate-12 items-center justify-center rounded-2xl bg-white/70 text-blue-500 shadow-lg shadow-blue-200/50 backdrop-blur">
                <x-icon name="rocket" class="h-8 w-8" />
            </div>
            <div class="absolute top-1/2 right-28 flex h-12 w-12 -rotate-12 items-center justify-center rounded-2xl bg-white/70 text-pink-500 shadow-lg shadow-pink-200/50 backdrop-blur">
                <x-icon name="sparkles" class="h-6 w-6" />
            </div>
            <div class="absolute right-8 bottom-6 flex h-11 w-11 rotate-6 items-center justify-center rounded-full bg-white/70 text-amber-500 shadow-lg shadow-amber-200/50 backdrop-blur">
                <x-icon name="light-bulb" class="h-5 w-5" />
            </div>
        </div>

        <div class="relative z-10 max-w-2xl">
            <p class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3 py-1 text-xs font-semibold text-blue-700">
                <x-icon :name="$ikonWaktu" class="h-4 w-4" /> Selamat {{ $sapaan }}
            </p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-800 sm:text-4xl">
                Hai, <span class="bg-linear-to-r from-blue-600 to-pink-500 bg-clip-text text-transparent">{{ $namaPanggilan }}</span>!
            </h2>
            <p class="mt-2 text-slate-600">
                @if ($statistik['tugas'] || $statistik['kuis'])
                    Ada {{ $statistik['tugas'] }} tugas dan {{ $statistik['kuis'] }} kuis yang menunggu. Yuk dicicil!
                @else
                    Semua tugas beres. Waktunya santai sebentar atau baca materi berikutnya.
                @endif
            </p>

            <div class="mt-5 flex flex-wrap gap-2 text-xs font-medium text-slate-700">
                @if ($kelas)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1.5 shadow-sm"><x-icon name="building" class="h-4 w-4 text-blue-500" /> {{ $kelas->nama_kelas }}</span>
                @endif
                @if ($siswa->nis)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1.5 shadow-sm"><x-icon name="identification" class="h-4 w-4 text-pink-500" /> NIS {{ $siswa->nis }}</span>
                @endif
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/80 px-3 py-1.5 shadow-sm"><x-icon name="calendar" class="h-4 w-4 text-amber-500" /> {{ tanggal_indo(now()) }}</span>
            </div>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($ringkasan as [$ikon, $judul, $nilai, $keterangan, $url, $latar, $latarIkon])
            <a href="{{ $url }}" class="card group relative overflow-hidden bg-linear-to-br p-5 transition hover:-translate-y-1 {{ $latar }}">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-linear-to-br text-white shadow-md transition group-hover:scale-110 group-hover:-rotate-6 {{ $latarIkon }}">
                    <x-icon :name="$ikon" class="h-6 w-6" />
                </div>
                <div class="mt-4 text-sm font-medium text-slate-600">{{ $judul }}</div>
                <div class="text-2xl font-bold text-slate-800">{{ $nilai }}</div>
                <div class="text-xs text-slate-500">{{ $keterangan }}</div>
                <x-icon :name="$ikon" class="pointer-events-none absolute -right-4 -bottom-4 h-24 w-24 text-white/60" />
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Jadwal hari ini --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title inline-flex items-center gap-2"><x-icon name="clock" class="h-5 w-5 text-blue-500" /> Jadwal Hari Ini</h3>
                <a href="{{ route('siswa.jadwal') }}" class="card-link">Semua</a>
            </div>
            <ul class="space-y-3 px-4 pb-5 sm:px-6">
                @forelse ($jadwalHariIni as $j)
                    <li class="flex items-center gap-3 rounded-2xl border border-slate-100 p-3">
                        <span class="flex h-11 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-blue-50 text-xs font-semibold text-blue-700">
                            {{ substr($j->jam_mulai, 0, 5) }}<span class="font-normal text-blue-400">{{ substr($j->jam_selesai, 0, 5) }}</span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('siswa.mapel.show', $j->pengampu) }}" class="block truncate text-sm font-semibold text-slate-800 hover:text-blue-700">{{ $j->pengampu->mapel->nama_mapel }}</a>
                            <div class="truncate text-xs text-slate-500">{{ $j->pengampu->guru->nama }}@if ($j->ruang) · {{ $j->ruang }}@endif</div>
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">Tidak ada pelajaran hari ini.</li>
                @endforelse
            </ul>
        </div>

        {{-- Tugas mendatang --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title inline-flex items-center gap-2"><x-icon name="clipboard-check" class="h-5 w-5 text-pink-500" /> Deadline Terdekat</h3>
                <a href="{{ route('siswa.tugas.index') }}" class="card-link">Semua</a>
            </div>
            <ul class="divide-y divide-slate-100 px-4 pb-3 sm:px-6">
                @forelse ($tugasMendatang as $t)
                    <li>
                        <a href="{{ route('siswa.tugas.show', [$t->pengampu_id, $t]) }}" class="flex items-center justify-between gap-3 py-3 hover:text-blue-700">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-800">{{ $t->judul }}</div>
                                <div class="truncate text-xs text-slate-500">{{ $t->pengampu->mapel->nama_mapel }}</div>
                            </div>
                            <span @class(['shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold', 'bg-rose-50 text-rose-600' => $t->deadline->lt(now()->addDay()), 'bg-amber-50 text-amber-700' => ! $t->deadline->lt(now()->addDay())])>
                                {{ $t->deadline->locale('id')->diffForHumans(['parts' => 1]) }}
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="flex flex-col items-center gap-2 py-6 text-center text-sm text-slate-500">
                        <x-icon name="check-badge" class="h-8 w-8 text-emerald-300" /> Tidak ada tugas yang menunggu.
                    </li>
                @endforelse
            </ul>
        </div>

        {{-- Kuis aktif --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title inline-flex items-center gap-2"><x-icon name="puzzle" class="h-5 w-5 text-sky-500" /> Kuis Dibuka</h3>
                <a href="{{ route('siswa.kuis.index') }}" class="card-link">Semua</a>
            </div>
            <ul class="space-y-3 px-4 pb-5 sm:px-6">
                @forelse ($kuisAktif as $k)
                    <li>
                        <a href="{{ route('siswa.kuis.show', [$k->pengampu_id, $k]) }}" class="flex items-center gap-3 rounded-2xl bg-linear-to-r from-sky-50 to-blue-50 p-3 transition hover:shadow-sm">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-sky-500 shadow-sm"><x-icon name="rocket" class="h-5 w-5" /></span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-slate-800">{{ $k->judul }}</div>
                                <div class="truncate text-xs text-slate-500">{{ $k->label_jenis }} · {{ $k->pengampu->mapel->nama_mapel }} · tutup {{ $k->waktu_selesai->locale('id')->diffForHumans() }}</div>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">Tidak ada kuis yang sedang dibuka.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Vibe hari ini --}}
            <div class="card relative overflow-hidden p-6">
                <x-icon name="sparkles" class="pointer-events-none absolute -right-4 -bottom-6 h-32 w-32 text-blue-100" />
                <p class="inline-flex items-center gap-1.5 text-xs font-semibold tracking-wider text-pink-500 uppercase">
                    <x-icon name="sparkles" class="h-4 w-4" /> Vibe hari ini
                </p>
                <p class="relative mt-2 text-lg font-semibold text-slate-700">“{{ $kutipanHariIni }}”</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title inline-flex items-center gap-2"><x-icon name="megaphone" class="h-5 w-5 text-blue-500" /> Pengumuman</h3>
                    <a href="{{ route('siswa.pengumuman') }}" class="card-link">Semua</a>
                </div>
                <ul class="space-y-4 px-4 pb-5 sm:px-6">
                    @forelse ($pengumuman as $p)
                        <li class="flex gap-3">
                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-linear-to-br from-blue-400 to-pink-400"></span>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-800">{{ $p->judul }}</div>
                                <div class="line-clamp-2 text-xs text-slate-500">{{ $p->isi }}</div>
                                <div class="mt-0.5 text-xs font-medium text-blue-600">{{ tanggal_indo($p->created_at, false) }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-500">Belum ada pengumuman.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title inline-flex items-center gap-2"><x-icon name="calendar" class="h-5 w-5 text-blue-500" /> Kalender</h3>
                <span class="text-xs text-slate-500">• deadline tugas</span>
            </div>
            <x-kalender class="px-4 pb-5 sm:px-6" :tandai="$deadline" />
        </div>
    </div>
</x-layouts.app>
