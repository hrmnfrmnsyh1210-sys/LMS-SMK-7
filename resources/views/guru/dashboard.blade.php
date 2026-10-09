<x-layouts.app title="Dashboard">
    <x-banner-sapaan :nama="auth()->user()->nama" pesan="Semangat mengajar! Berikut ringkasan kelas Anda hari ini.">
        <a href="{{ route('guru.kelas.index') }}" class="btn-primary"><x-icon name="book" class="h-4 w-4" /> Kelas Ajar</a>
        @if ($kelasWali)
            <a href="{{ route('guru.wali.rekap') }}" class="btn-secondary"><x-icon name="star" class="h-4 w-4" /> Wali {{ $kelasWali->nama_kelas }}</a>
        @endif
    </x-banner-sapaan>

    <div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-4">
        <x-card-stat label="Kelas Ajar" :value="$statistik['kelas']" ikon="book" warna="blue" keterangan="Tahun ajaran aktif" :href="route('guru.kelas.index')" />
        <x-card-stat label="Siswa Diajar" :value="$statistik['siswa']" ikon="academic-cap" warna="emerald" keterangan="Seluruh kelas" />
        <x-card-stat label="Tugas Perlu Dinilai" :value="$statistik['perlu_dinilai']" ikon="document" warna="amber" keterangan="Belum diberi nilai" />
        @if ($kelasWali)
            <x-card-stat label="Surat Izin" :value="$statistik['surat_menunggu']" ikon="megaphone" warna="rose" keterangan="Menunggu persetujuan" :href="route('guru.wali.surat-izin')" />
        @else
            <x-card-stat label="Esai Belum Dikoreksi" :value="$statistik['esai_menunggu']" ikon="list" warna="violet" keterangan="Jawaban kuis" />
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Jadwal hari ini --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Jadwal Mengajar Hari Ini</h3>
            </div>
            <ul class="space-y-3 px-4 pb-5 sm:px-6">
                @forelse ($jadwalHariIni as $j)
                    <li class="flex items-center gap-3 rounded-xl border border-slate-100 p-3">
                        <span class="flex h-11 w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-blue-50 text-xs font-semibold text-blue-700">
                            {{ substr($j->jam_mulai, 0, 5) }}<span class="font-normal text-blue-400">{{ substr($j->jam_selesai, 0, 5) }}</span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('guru.kelas.show', $j->pengampu) }}" class="block truncate text-sm font-semibold text-slate-800 hover:text-blue-700">{{ $j->pengampu->mapel->nama_mapel }}</a>
                            <div class="text-xs text-slate-500">{{ $j->pengampu->kelas->nama_kelas }}@if ($j->ruang) · {{ $j->ruang }}@endif</div>
                        </div>
                        <a href="{{ route('guru.presensi.form', $j->pengampu) }}" class="btn-secondary btn-sm">Presensi</a>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">Tidak ada jadwal mengajar hari ini.</li>
                @endforelse
            </ul>
        </div>

        {{-- Tugas perlu dinilai --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tugas Perlu Dinilai</h3>
            </div>
            <ul class="divide-y divide-slate-100 px-4 pb-3 sm:px-6">
                @forelse ($perluDinilai as $p)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-slate-800">{{ $p->siswa->nama }}</div>
                            <div class="truncate text-xs text-slate-500">{{ $p->tugas->judul }} · {{ $p->tugas->pengampu->kelas->nama_kelas }}</div>
                        </div>
                        <a href="{{ route('guru.tugas.show', [$p->tugas->pengampu, $p->tugas]) }}" class="card-link shrink-0">Nilai</a>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">Semua tugas sudah dinilai. 🎉</li>
                @endforelse
            </ul>
        </div>

        {{-- Notifikasi & pengumuman --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pengumuman</h3>
                <a href="{{ route('guru.pengumuman.index') }}" class="card-link">Lihat Semua</a>
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

    <div class="grid gap-6 md:grid-cols-2">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Kalender</h3><span class="text-xs text-slate-500">• deadline tugas</span></div>
            <x-kalender class="px-4 pb-5 sm:px-6" :tandai="$deadline" />
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Notifikasi Terbaru</h3></div>
            <ul class="divide-y divide-slate-100 px-4 pb-3 sm:px-6">
                @forelse ($notifikasi as $n)
                    <li class="flex items-start gap-3 py-3 text-sm">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-blue-500"></span>
                        <div class="flex-1">{{ $n->data['pesan'] ?? '-' }}</div>
                        <span class="shrink-0 text-xs text-slate-400">{{ $n->created_at->locale('id')->diffForHumans() }}</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">Tidak ada notifikasi baru.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-layouts.app>
