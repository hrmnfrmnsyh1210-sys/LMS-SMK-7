<x-layouts.app :title="'Perkembangan · '.$siswa->nama">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="monitoring" />

    <x-page-header :title="'Perkembangan Nilai: '.$siswa->nama" :subtitle="'NIS '.$siswa->nis">
        <a href="{{ route('guru.monitoring.index', $pengampu) }}" class="btn-secondary">Kembali</a>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        @foreach (['nilai_tugas' => 'Tugas', 'nilai_kuis' => 'Kuis', 'nilai_uts' => 'UTS', 'nilai_uas' => 'UAS', 'nilai_akhir' => 'Nilai Akhir'] as $k => $label)
            <x-card-stat :label="$label" :value="$nilai?->{$k} !== null ? (float) $nilai->{$k} : '-'" :ikon="$k === 'nilai_akhir' ? 'star' : 'chart'"
                         :warna="$k === 'nilai_akhir' ? 'emerald' : 'blue'" />
        @endforeach
    </div>

    @php
        $berNilai = $riwayat->whereNotNull('nilai')->values();
        $grafik = [
            'labels' => $berNilai->map(fn ($r) => $r['jenis'].': '.\Illuminate\Support\Str::limit($r['judul'], 18))->all(),
            'nilai' => $berNilai->pluck('nilai')->all(),
            'kkm' => $berNilai->map(fn () => $kkm)->all(),
        ];
    @endphp

    <div class="card card-body">
        <h3 class="card-title mb-4">Grafik Nilai Tugas & Kuis</h3>
        @if ($berNilai->isEmpty())
            <p class="py-8 text-center text-sm text-slate-500">Belum ada nilai untuk ditampilkan.</p>
        @else
            <div class="h-72">
                <canvas x-data="{ d: @js($grafik) }" x-init="new Chart($el, {
                    type: 'line',
                    data: {
                        labels: d.labels,
                        datasets: [
                            { label: 'Nilai', data: d.nilai, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)', fill: true, tension: .35, pointRadius: 4 },
                            { label: 'KKM', data: d.kkm, borderColor: '#ef4444', borderDash: [6, 4], pointRadius: 0, fill: false },
                        ],
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 100 } }, plugins: { legend: { position: 'bottom' } } },
                })"></canvas>
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Riwayat Penilaian</h3></div>
        <div class="table-wrap px-2 pb-3 sm:px-4">
            <table class="table">
                <thead><tr><th>Tanggal</th><th>Jenis</th><th>Judul</th><th>Nilai</th></tr></thead>
                <tbody>
                    @forelse ($riwayat as $r)
                        <tr>
                            <td class="whitespace-nowrap">{{ tanggal_indo($r['tanggal'], false) }}</td>
                            <td><x-badge :warna="$r['jenis'] === 'Tugas' ? 'sky' : 'violet'">{{ $r['jenis'] }}</x-badge></td>
                            <td>{{ $r['judul'] }}</td>
                            <td>
                                @if ($r['nilai'] !== null)
                                    <span @class(['font-bold', 'text-emerald-600' => $r['nilai'] >= $kkm, 'text-rose-600' => $r['nilai'] < $kkm])>{{ $r['nilai'] }}</span>
                                @else
                                    <span class="text-xs text-slate-400">{{ $r['keterangan'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="4" pesan="Belum ada tugas atau kuis." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
