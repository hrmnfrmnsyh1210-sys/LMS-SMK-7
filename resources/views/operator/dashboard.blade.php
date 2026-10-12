<x-layouts.app title="Dashboard">
    <x-banner-sapaan :nama="auth()->user()->nama" pesan="Kelola data sekolah dan pantau aktivitas sistem dari sini." />

    @unless ($tahunAktif)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Belum ada tahun ajaran aktif. <a href="{{ route('operator.tahun-ajaran.create') }}" class="font-semibold underline">Buat tahun ajaran</a> terlebih dahulu.
        </div>
    @endunless

    {{-- Kartu statistik --}}
    <div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        <x-card-stat label="Siswa Aktif" :value="$statistik['siswa']" ikon="academic-cap" warna="blue"
                     keterangan="Seluruh tingkat" :href="route('operator.siswa.index')" />
        <x-card-stat label="Guru" :value="$statistik['guru']" ikon="user-group" warna="emerald"
                     keterangan="{{ $statistik['pengampu'] }} pengampu" :href="route('operator.guru.index')" />
        <x-card-stat label="Kelas" :value="$statistik['kelas']" ikon="building" warna="violet"
                     keterangan="Tahun aktif" :href="route('operator.kelas.index')" />
        <x-card-stat label="Mapel" :value="$statistik['mapel']" ikon="book" warna="amber"
                     keterangan="Terdaftar" :href="route('operator.mapel.index')" />
        <x-card-stat label="Tanpa Kelas" :value="$statistik['siswa_tanpa_kelas']" ikon="warning" warna="rose"
                     keterangan="Perlu kelas" :href="route('operator.siswa.index', ['kelas_id' => 0])" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Aktivitas Sistem</h3>
                    <p class="text-xs text-slate-500">Login dan aktivitas pengguna 14 hari terakhir</p>
                </div>
                <a href="{{ route('operator.log.index') }}" class="card-link">Log</a>
            </div>
            <x-grafik.area class="h-72 lg:h-[22rem] px-4 pb-5 sm:px-6" :label="$grafik['aktivitas']['label']" :seri="$grafik['aktivitas']['seri']"
                           kosong="Belum ada aktivitas dalam 14 hari terakhir." />
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Kelengkapan Data</h3>
                    <p class="text-xs text-slate-500">Kesiapan data tahun ajaran aktif</p>
                </div>
            </div>
            <x-grafik.cincin class="px-4 pb-6 sm:px-6" :data="$grafik['kesehatan']" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Siswa per Kelas</h3>
                    <p class="text-xs text-slate-500">Jumlah anggota tiap kelas tahun ajaran aktif</p>
                </div>
                <a href="{{ route('operator.laporan.index', ['jenis' => 'kesiswaan']) }}" class="card-link">Laporan</a>
            </div>
            <x-grafik.batang class="h-72 lg:h-80 px-4 pb-5 sm:px-6" :label="$grafik['siswaKelas']['label']" :nilai="$grafik['siswaKelas']['nilai']"
                             nama="Jumlah siswa" kosong="Belum ada kelas pada tahun ajaran aktif." />
        </div>

        {{-- Komposisi siswa per jurusan --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Siswa per Jurusan</h3>
                <a href="{{ route('operator.kelas.index') }}" class="card-link">Lihat Kelas</a>
            </div>
            <x-grafik-donat class="px-4 pb-6 sm:px-6" :data="$komposisiJurusan" label-tengah="Siswa berkelas" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Aktivitas terbaru --}}
        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="card-title">Aktivitas Terbaru</h3>
                <a href="{{ route('operator.log.index') }}" class="card-link">Lihat Semua</a>
            </div>
            <div class="table-wrap px-2 pb-3 sm:px-4">
                <table class="table">
                    <thead><tr><th>Pengguna</th><th>Aksi</th><th>Keterangan</th><th>Waktu</th></tr></thead>
                    <tbody>
                        @forelse ($logTerbaru as $log)
                            <tr>
                                <td class="whitespace-nowrap font-medium text-slate-800">{{ $log->user?->nama ?? 'Sistem' }}</td>
                                <td><x-badge warna="sky">{{ $log->aksi }}</x-badge></td>
                                <td class="max-w-xs truncate">{{ $log->keterangan }}</td>
                                <td class="whitespace-nowrap text-slate-500">{{ $log->created_at?->locale('id')->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="4" pesan="Belum ada aktivitas." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Papan pengumuman --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Papan Pengumuman</h3>
            </div>
            <ul class="space-y-4 px-4 pb-5 sm:px-6">
                @forelse ($pengumumanTerbaru as $p)
                    <li class="flex gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <x-icon name="megaphone" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-slate-800">{{ $p->judul }}</div>
                            <div class="line-clamp-2 text-xs text-slate-500">{{ $p->isi }}</div>
                            <div class="mt-0.5 text-xs font-medium text-blue-600">{{ tanggal_indo($p->created_at, false) }}</div>
                        </div>
                    </li>
                @empty
                    <li class="flex flex-col items-center py-6 text-center text-sm text-slate-500">
                        <span class="mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-500"><x-icon name="megaphone" class="h-6 w-6" /></span>
                        Belum ada pengumuman.
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        {{-- Kalender --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Kalender Akademik</h3></div>
            <x-kalender class="px-4 pb-5 sm:px-6" />
        </div>

        {{-- Akses cepat --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Akses Cepat</h3></div>
            <div class="grid grid-cols-3 gap-3 px-4 pb-5 sm:px-6">
                @foreach ([
                    ['Import Data', 'operator.import.index', 'upload', 'bg-blue-50 text-blue-600'],
                    ['Kelas', 'operator.kelas.index', 'building', 'bg-emerald-50 text-emerald-600'],
                    ['Jadwal', 'operator.jadwal.index', 'clock', 'bg-violet-50 text-violet-600'],
                    ['Pengampu', 'operator.pengampu.index', 'link', 'bg-amber-50 text-amber-600'],
                    ['Backup', 'operator.backup.index', 'database', 'bg-sky-50 text-sky-600'],
                    ['Pengaturan', 'operator.pengaturan.edit', 'cog', 'bg-rose-50 text-rose-600'],
                ] as [$label, $route, $ikon, $warna])
                    <a href="{{ route($route) }}" class="group flex flex-col items-center gap-2 rounded-xl p-2 text-center transition hover:bg-slate-50">
                        <span class="flex h-12 w-12 items-center justify-center rounded-xl {{ $warna }} transition group-hover:scale-105">
                            <x-icon :name="$ikon" class="h-6 w-6" />
                        </span>
                        <span class="text-xs font-medium text-slate-700">{{ $label }}</span>
                    </a>
                @endforeach
            </div>
        </div>

    </div>
</x-layouts.app>
