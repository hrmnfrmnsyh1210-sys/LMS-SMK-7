<x-layouts.app :title="'Rekap Kelas '.$kelas->nama_kelas">
    <x-page-header :title="'Rekap Kelas '.$kelas->nama_kelas" :subtitle="$kelas->jurusan->nama.' · '.($tahunAktif?->label ?? '').' · Anda wali kelas ini'">
        <a href="{{ route('guru.wali.surat-izin') }}" class="btn-secondary"><x-icon name="document" class="h-4 w-4" /> Surat Izin</a>
    </x-page-header>

    @php
        $dinilai = $ranking->whereNotNull('rata');
        $warnaPodium = [1 => 'bg-amber-100 text-amber-700', 2 => 'bg-slate-200 text-slate-700', 3 => 'bg-orange-100 text-orange-700'];
    @endphp

    <div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-4">
        <x-card-stat label="Jumlah Siswa" :value="$ranking->count()" ikon="academic-cap" warna="blue" />
        <x-card-stat label="Mata Pelajaran" :value="$pengampu->count()" ikon="book" warna="violet" />
        <x-card-stat label="Rata-rata Kelas" :value="$dinilai->isEmpty() ? '-' : round($dinilai->avg('rata'), 2)" ikon="chart" warna="emerald" />
        <x-card-stat label="Di Bawah KKM" :value="$dinilai->filter(fn ($r) => $r['rata'] < $kkm)->count()" ikon="warning" warna="rose" :keterangan="'KKM '.$kkm" />
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Ranking & Nilai Akhir per Mapel</h3>
        </div>
        <div class="table-wrap px-2 pb-3 sm:px-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Siswa</th>
                        @foreach ($pengampu as $p)
                            <th class="text-center" title="{{ $p->mapel->nama_mapel }} — {{ $p->guru->nama }}">{{ $p->mapel->kode }}</th>
                        @endforeach
                        <th>Rata-rata</th>
                        <th class="text-center">H</th><th class="text-center">I</th><th class="text-center">S</th><th class="text-center">A</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ranking as $r)
                        @php
                            $nilaiSiswa = ($nilai[$r['siswa']->id] ?? collect())->keyBy('pengampu_id');
                            $hadir = $kehadiran[$r['siswa']->id] ?? collect();
                        @endphp
                        <tr>
                            <td>
                                @if ($r['peringkat'] && $r['peringkat'] <= 3)
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $warnaPodium[$r['peringkat']] }}">{{ $r['peringkat'] }}</span>
                                @else
                                    <span class="pl-2 text-slate-500">{{ $r['peringkat'] ?? '-' }}</span>
                                @endif
                            </td>
                            <td class="font-medium whitespace-nowrap text-slate-800">{{ $r['siswa']->nama }}</td>
                            @foreach ($pengampu as $p)
                                @php $na = $nilaiSiswa->get($p->id)?->nilai_akhir; @endphp
                                <td @class(['text-center', 'font-semibold text-rose-600' => $na !== null && $na < $kkm])>{{ $na !== null ? (float) $na : '-' }}</td>
                            @endforeach
                            <td class="font-bold">{{ $r['rata'] ?? '-' }}</td>
                            <td class="text-center text-emerald-600">{{ $hadir['hadir'] ?? 0 }}</td>
                            <td class="text-center text-sky-600">{{ $hadir['izin'] ?? 0 }}</td>
                            <td class="text-center text-amber-600">{{ $hadir['sakit'] ?? 0 }}</td>
                            <td @class(['text-center text-rose-600', 'font-bold' => ($hadir['alpa'] ?? 0) >= 3])>{{ $hadir['alpa'] ?? 0 }}</td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="7 + $pengampu->count()" pesan="Belum ada siswa di kelas ini." />
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap gap-x-4 gap-y-1 border-t border-slate-100 px-4 py-3 text-xs text-slate-500 sm:px-6">
            @foreach ($pengampu as $p)
                <span><strong>{{ $p->mapel->kode }}</strong> = {{ $p->mapel->nama_mapel }}</span>
            @endforeach
        </div>
    </div>
</x-layouts.app>
