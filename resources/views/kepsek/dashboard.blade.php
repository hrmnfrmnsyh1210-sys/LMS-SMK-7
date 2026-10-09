<x-layouts.app title="Dashboard">
    <x-banner-sapaan :nama="auth()->user()->nama" pesan="Ringkasan kegiatan belajar mengajar sekolah pada tahun ajaran aktif.">
        <a href="{{ route('kepsek.monitoring.kelas') }}" class="btn-primary"><x-icon name="chart" class="h-4 w-4" /> Rekap Kelas</a>
        <a href="{{ route('kepsek.monitoring.guru') }}" class="btn-secondary"><x-icon name="user-group" class="h-4 w-4" /> Keaktifan Guru</a>
    </x-banner-sapaan>

    <div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-4">
        <x-card-stat label="Siswa Aktif" :value="$statistik['siswa']" ikon="academic-cap" warna="blue" />
        <x-card-stat label="Guru" :value="$statistik['guru']" ikon="user-group" warna="emerald" :href="route('kepsek.monitoring.guru')" />
        <x-card-stat label="Kelas" :value="$statistik['kelas']" ikon="building" warna="violet" keterangan="Tahun ajaran aktif" :href="route('kepsek.monitoring.kelas')" />
        <x-card-stat label="Mata Pelajaran" :value="$statistik['mapel']" ikon="book" warna="amber" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Kehadiran Siswa</h3></div>
            <div class="px-4 pb-6 sm:px-6">
                <x-grafik-donat :data="$grafikPresensi" label-tengah="hadir" :nilai-tengah="$statistik['kehadiran'] !== null ? $statistik['kehadiran'].'%' : '-'" />
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Aktivitas Pembelajaran</h3></div>
            <div class="space-y-3 px-4 pb-6 sm:px-6">
                @foreach ([['Materi diunggah', $statistik['materi'], 'book', 'bg-blue-50 text-blue-600'], ['Tugas diberikan', $statistik['tugas'], 'clipboard-check', 'bg-pink-50 text-pink-600'], ['Kuis & ujian', $statistik['kuis'], 'puzzle', 'bg-sky-50 text-sky-600']] as [$label, $nilai, $ikon, $warna])
                    <div class="flex items-center gap-3 rounded-xl border border-slate-100 p-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $warna }}"><x-icon :name="$ikon" class="h-5 w-5" /></span>
                        <span class="flex-1 text-sm text-slate-600">{{ $label }}</span>
                        <span class="text-xl font-bold text-slate-800">{{ $nilai }}</span>
                    </div>
                @endforeach
                <div class="flex items-center gap-3 rounded-xl bg-emerald-50 p-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-emerald-600"><x-icon name="chart" class="h-5 w-5" /></span>
                    <span class="flex-1 text-sm text-emerald-800">Rata-rata nilai sekolah</span>
                    <span class="text-xl font-bold text-emerald-700">{{ $statistik['rata'] ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Kelas Terbaik</h3>
                <a href="{{ route('kepsek.monitoring.kelas') }}" class="card-link">Semua</a>
            </div>
            <ul class="divide-y divide-slate-100 px-4 pb-3 sm:px-6">
                @forelse ($kelasTerbaik as $r)
                    <li class="flex items-center gap-3 py-3">
                        <span @class([
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                            'bg-amber-100 text-amber-700' => $loop->iteration === 1,
                            'bg-slate-100 text-slate-600' => $loop->iteration > 1,
                        ])>{{ $loop->iteration }}</span>
                        <a href="{{ route('kepsek.monitoring.kelas.show', $r['kelas']) }}" class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-slate-800 hover:text-blue-700">{{ $r['kelas']->nama_kelas }}</div>
                            <div class="text-xs text-slate-500">Kehadiran {{ $r['kehadiran'] !== null ? $r['kehadiran'].'%' : '-' }}</div>
                        </a>
                        <span class="font-bold text-slate-800">{{ $r['rata'] }}</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">Belum ada nilai yang direkap guru.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid gap-6 md:grid-cols-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Aktivitas Guru Terbaru</h3>
            </div>
            <ul class="divide-y divide-slate-100 px-4 pb-3 sm:px-6">
                @forelse ($aktivitasGuru as $log)
                    <li class="flex items-start gap-3 py-3 text-sm">
                        <x-avatar :nama="$log->user?->nama ?? '-'" />
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-slate-800">{{ $log->user?->nama }}</div>
                            <div class="line-clamp-2 text-xs text-slate-500">{{ $log->keterangan }}</div>
                        </div>
                        <span class="shrink-0 text-xs text-slate-400">{{ $log->created_at?->locale('id')->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">Belum ada aktivitas guru.</li>
                @endforelse
            </ul>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pengumuman</h3>
                <a href="{{ route('kepsek.pengumuman.index') }}" class="card-link">Kelola</a>
            </div>
            <ul class="space-y-4 px-4 pb-5 sm:px-6">
                @forelse ($pengumuman as $p)
                    <li class="flex gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><x-icon name="megaphone" class="h-5 w-5" /></span>
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
</x-layouts.app>
