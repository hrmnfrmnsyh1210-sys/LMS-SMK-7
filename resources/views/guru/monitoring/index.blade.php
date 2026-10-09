<x-layouts.app :title="'Monitoring · '.$pengampu->label">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="monitoring" />

    @php
        $dinilai = $ranking->whereNotNull('rata');
        $dibawahKkm = $dinilai->filter(fn ($r) => $r['rata'] < $kkm)->count();
        $seringAlpa = $kehadiran->filter(fn ($k) => $k['alpa'] >= 3)->count();
        $warnaPodium = [1 => 'bg-amber-100 text-amber-700', 2 => 'bg-slate-200 text-slate-700', 3 => 'bg-orange-100 text-orange-700'];
    @endphp

    <div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-4">
        <x-card-stat label="Rata-rata Kelas" :value="$dinilai->isEmpty() ? '-' : round($dinilai->avg('rata'), 2)" ikon="chart" warna="blue" />
        <x-card-stat label="Nilai Tertinggi" :value="$dinilai->max('rata') ?? '-'" ikon="star" warna="emerald" />
        <x-card-stat label="Di Bawah KKM" :value="$dibawahKkm" ikon="warning" warna="rose" :keterangan="'KKM '.$kkm" />
        <x-card-stat label="Sering Alpa" :value="$seringAlpa" ikon="calendar" warna="amber" keterangan="Alpa ≥ 3 kali" />
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Peringkat Siswa</h3>
            <a href="{{ route('guru.nilai.index', $pengampu) }}" class="card-link">Kelola Nilai</a>
        </div>
        <div class="table-wrap px-2 pb-3 sm:px-4">
            <table class="table">
                <thead><tr><th>Peringkat</th><th>Siswa</th><th>Tugas</th><th>Kuis</th><th>UTS</th><th>UAS</th><th>Nilai Akhir</th><th>Kehadiran</th><th></th></tr></thead>
                <tbody>
                    @forelse ($ranking as $r)
                        @php
                            $n = $r['nilai'];
                            $h = $kehadiran[$r['siswa']->id] ?? null;
                        @endphp
                        <tr>
                            <td>
                                @if ($r['peringkat'] && $r['peringkat'] <= 3)
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $warnaPodium[$r['peringkat']] }}">{{ $r['peringkat'] }}</span>
                                @else
                                    <span class="pl-2 text-slate-500">{{ $r['peringkat'] ?? '-' }}</span>
                                @endif
                            </td>
                            <td class="font-medium text-slate-800">{{ $r['siswa']->nama }}</td>
                            @foreach (['nilai_tugas', 'nilai_kuis', 'nilai_uts', 'nilai_uas'] as $kolom)
                                <td>{{ $n?->{$kolom} !== null ? (float) $n->{$kolom} : '-' }}</td>
                            @endforeach
                            <td>
                                @if ($r['rata'] !== null)
                                    <span @class(['font-bold', 'text-emerald-600' => $r['rata'] >= $kkm, 'text-rose-600' => $r['rata'] < $kkm])>{{ $r['rata'] }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                {{ $h && $h['persen'] !== null ? $h['persen'].'%' : '-' }}
                                @if ($h && $h['alpa'] >= 3) <x-badge warna="rose">{{ $h['alpa'] }} alpa</x-badge> @endif
                            </td>
                            <td class="text-right"><a href="{{ route('guru.monitoring.siswa', [$pengampu, $r['siswa']]) }}" class="card-link">Grafik</a></td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="9" pesan="Belum ada siswa." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
