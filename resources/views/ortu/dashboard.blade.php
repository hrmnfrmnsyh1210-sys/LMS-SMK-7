<x-layouts.app title="Dashboard">
    <x-banner-sapaan :nama="$ortu->nama" pesan="Pantau perkembangan belajar anak Anda di sekolah." />

    @if ($semuaAnak->isEmpty())
        <div class="card card-body flex flex-col items-center gap-3 py-10 text-center">
            <x-icon name="heart" class="h-12 w-12 text-blue-300" />
            <p class="font-semibold text-slate-800">Akun Anda belum terhubung dengan data siswa</p>
            <p class="max-w-md text-sm text-slate-500">Hubungi operator sekolah agar akun ini dihubungkan dengan anak Anda.</p>
        </div>
    @else
        {{-- Kartu tiap anak --}}
        <div @class(['grid gap-4', 'sm:grid-cols-2 xl:grid-cols-3' => $semuaAnak->count() > 1])>
            @foreach ($semuaAnak as ['siswa' => $s, 'kelas' => $k, 'ringkasan' => $r])
                @php $aktif = $s->id === $anak->id; @endphp
                <form method="POST" action="{{ route('ortu.anak.pilih', $s) }}">
                    @csrf
                    <button type="submit" @disabled($aktif)
                            @class(['card w-full p-5 text-left transition', 'ring-2 ring-blue-300' => $aktif && $semuaAnak->count() > 1, 'hover:-translate-y-0.5 hover:shadow-md' => ! $aktif])>
                        <div class="flex items-center gap-3">
                            <x-avatar :nama="$s->nama" ukuran="h-12 w-12 text-sm" />
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-semibold text-slate-800">{{ $s->nama }}</div>
                                <div class="text-xs text-slate-500">{{ $k?->nama_kelas ?? 'Belum ada kelas' }} · NIS {{ $s->nis }}</div>
                            </div>
                            @if ($aktif && $semuaAnak->count() > 1)
                                <x-badge warna="blue">Dipantau</x-badge>
                            @endif
                        </div>
                        <div class="mt-4 grid grid-cols-3 divide-x divide-slate-100 text-center">
                            <div>
                                <div class="text-lg font-bold text-slate-800">{{ $r['kehadiran'] !== null ? $r['kehadiran'].'%' : '-' }}</div>
                                <div class="text-[11px] text-slate-500">Kehadiran</div>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-slate-800">{{ $r['nilai'] ?? '-' }}</div>
                                <div class="text-[11px] text-slate-500">Rata-rata</div>
                            </div>
                            <div>
                                <div @class(['text-lg font-bold', 'text-rose-500' => $r['terlewat'] > 0, 'text-slate-800' => $r['terlewat'] === 0])>{{ $r['tugas'] + $r['terlewat'] }}</div>
                                <div class="text-[11px] text-slate-500">Tugas tertunda</div>
                            </div>
                        </div>
                    </button>
                </form>
            @endforeach
        </div>

        {{-- Detail anak yang dipantau --}}
        <div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-4">
            <x-card-stat label="Kehadiran" :value="$ringkasan['kehadiran'] !== null ? $ringkasan['kehadiran'].'%' : '-'" ikon="check-badge" warna="emerald" :href="route('ortu.presensi')" />
            <x-card-stat label="Rata-rata Nilai" :value="$ringkasan['nilai'] ?? '-'" ikon="chart" warna="blue" :href="route('ortu.nilai')" />
            <x-card-stat label="Tugas Belum Dikumpulkan" :value="$ringkasan['tugas']" ikon="clipboard-check" warna="amber" keterangan="Masih bisa dikerjakan" :href="route('ortu.tugas')" />
            <x-card-stat label="Tugas Terlewat" :value="$ringkasan['terlewat']" ikon="warning" warna="rose" keterangan="Lewat deadline" :href="route('ortu.tugas')" />
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Wali kelas --}}
            <div class="card card-body space-y-3">
                <h3 class="card-title">Wali Kelas {{ $kelas?->nama_kelas }}</h3>
                @if ($kelas?->waliKelas)
                    @php
                        $hp = preg_replace('/\D/', '', (string) $kelas->waliKelas->no_hp);
                        $wa = $hp ? (str_starts_with($hp, '0') ? '62'.substr($hp, 1) : $hp) : null;
                    @endphp
                    <div class="flex items-center gap-3">
                        <x-avatar :nama="$kelas->waliKelas->nama" ukuran="h-11 w-11 text-sm" />
                        <div>
                            <div class="font-semibold text-slate-800">{{ $kelas->waliKelas->nama }}</div>
                            <div class="text-xs text-slate-500">{{ $kelas->waliKelas->no_hp ?: 'Nomor HP belum diisi' }}</div>
                        </div>
                    </div>
                    @if ($wa)
                        <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="btn-secondary w-full"><x-icon name="chat" class="h-4 w-4" /> Hubungi via WhatsApp</a>
                    @endif
                @else
                    <p class="text-sm text-slate-500">Wali kelas belum ditentukan.</p>
                @endif
                <a href="{{ route('ortu.surat-izin.index') }}" class="btn-primary w-full"><x-icon name="document" class="h-4 w-4" /> Ajukan Surat Izin</a>
            </div>

            {{-- Perlu perhatian --}}
            <div class="card">
                <div class="card-header"><h3 class="card-title">Perlu Perhatian</h3></div>
                <ul class="divide-y divide-slate-100 px-4 pb-3 sm:px-6">
                    @foreach ($tugasBelum as $t)
                        <li class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-800">{{ $t->judul }}</div>
                                <div class="truncate text-xs text-slate-500">Tugas {{ $t->pengampu->mapel->nama_mapel }}</div>
                            </div>
                            <span class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700">{{ $t->deadline->locale('id')->diffForHumans(['parts' => 1]) }}</span>
                        </li>
                    @endforeach
                    @foreach ($perhatian as $p)
                        <li class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-800">Alpa — {{ $p->pengampu->mapel->nama_mapel }}</div>
                                <div class="text-xs text-slate-500">{{ tanggal_indo($p->tanggal) }}</div>
                            </div>
                            <x-badge status="alpa" />
                        </li>
                    @endforeach
                    @if ($tugasBelum->isEmpty() && $perhatian->isEmpty())
                        <li class="flex flex-col items-center gap-2 py-6 text-center text-sm text-slate-500">
                            <x-icon name="check-badge" class="h-8 w-8 text-emerald-300" /> Tidak ada yang perlu dikhawatirkan.
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Nilai terbaru --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Nilai Terbaru</h3>
                    <a href="{{ route('ortu.nilai') }}" class="card-link">Semua</a>
                </div>
                <ul class="divide-y divide-slate-100 px-4 pb-3 sm:px-6">
                    @forelse ($nilaiTerbaru as $n)
                        <li class="flex items-center justify-between gap-3 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-800">{{ $n['judul'] }}</div>
                                <div class="truncate text-xs text-slate-500">{{ $n['jenis'] }} · {{ $n['mapel'] }}</div>
                            </div>
                            <span class="shrink-0 text-right">
                                <span class="font-bold text-slate-800">{{ $n['nilai'] }}</span><span class="text-xs text-slate-400">/{{ $n['maks'] }}</span>
                            </span>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-500">Belum ada nilai.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Jadwal {{ $anak->nama }} Hari Ini</h3>
                    <a href="{{ route('ortu.jadwal') }}" class="card-link">Seminggu</a>
                </div>
                <ul class="space-y-3 px-4 pb-5 sm:px-6">
                    @forelse ($jadwalHariIni as $j)
                        <li class="flex items-center gap-3 rounded-xl border border-slate-100 p-3">
                            <span class="flex h-11 w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-blue-50 text-xs font-semibold text-blue-700">
                                {{ substr($j->jam_mulai, 0, 5) }}<span class="font-normal text-blue-400">{{ substr($j->jam_selesai, 0, 5) }}</span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-slate-800">{{ $j->pengampu->mapel->nama_mapel }}</div>
                                <div class="truncate text-xs text-slate-500">{{ $j->pengampu->guru->nama }}</div>
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-500">Tidak ada pelajaran hari ini.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pengumuman</h3>
                    <a href="{{ route('ortu.pengumuman') }}" class="card-link">Semua</a>
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
    @endif
</x-layouts.app>
