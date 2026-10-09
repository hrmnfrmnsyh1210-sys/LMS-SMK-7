@props(['pengampu', 'nilai', 'kkm', 'peringkat', 'jumlahSiswa'])

{{-- Nilai per mapel satu siswa (lihat AkademikSiswaService::nilai). --}}
@php
    $akhir = $nilai->whereNotNull('nilai_akhir')->pluck('nilai_akhir')->map(fn ($n) => (float) $n);
    $rata = $akhir->isEmpty() ? null : round($akhir->avg(), 2);
    $tuntas = $akhir->filter(fn ($n) => $n >= $kkm)->count();
@endphp

<div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-4">
    <div class="card relative overflow-hidden bg-linear-to-br from-blue-500 to-pink-400 p-5 text-white">
        <x-icon name="star" class="pointer-events-none absolute -right-3 -bottom-3 h-20 w-20 text-white/20" />
        <div class="text-sm text-white/85">Rata-rata nilai</div>
        <div class="text-3xl font-bold">{{ $rata ?? '-' }}</div>
    </div>
    <x-card-stat label="Peringkat Kelas" :value="$peringkat['peringkat'] ?? '-'" ikon="rocket" warna="violet" :keterangan="$jumlahSiswa ? 'dari '.$jumlahSiswa.' siswa' : null" />
    <x-card-stat label="Mapel Tuntas" :value="$tuntas.'/'.$akhir->count()" ikon="check-badge" warna="emerald" :keterangan="'Nilai ≥ '.$kkm" />
    <x-card-stat label="Mata Pelajaran" :value="$pengampu->count()" ikon="book" warna="sky" />
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Rincian Nilai</h3></div>
    <div class="table-wrap px-2 pb-3 sm:px-4">
        <table class="table">
            <thead>
                <tr>
                    <th>Mata Pelajaran</th>
                    <th class="text-center">Tugas</th>
                    <th class="text-center">Kuis</th>
                    <th class="text-center">UTS</th>
                    <th class="text-center">UAS</th>
                    <th class="text-center">Nilai Akhir</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pengampu as $p)
                    @php
                        $n = $nilai->get($p->id);
                        $na = $n?->nilai_akhir !== null ? (float) $n->nilai_akhir : null;
                    @endphp
                    <tr>
                        <td>
                            <div class="font-medium text-slate-800">{{ $p->mapel->nama_mapel }}</div>
                            <div class="text-xs text-slate-500">{{ $p->guru->nama }}</div>
                        </td>
                        @foreach (['nilai_tugas', 'nilai_kuis', 'nilai_uts', 'nilai_uas'] as $kolom)
                            <td class="text-center text-slate-600">{{ $n?->{$kolom} !== null ? (float) $n->{$kolom} : '-' }}</td>
                        @endforeach
                        <td class="text-center">
                            @if ($na !== null)
                                <span @class(['inline-flex min-w-12 justify-center rounded-full px-2.5 py-1 text-sm font-bold', 'bg-emerald-50 text-emerald-700' => $na >= $kkm, 'bg-rose-50 text-rose-600' => $na < $kkm])>{{ $na }}</span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td>
                            @if ($na === null)
                                <span class="text-xs text-slate-400">Belum direkap guru</span>
                            @elseif ($na >= $kkm)
                                <x-badge warna="emerald">Tuntas</x-badge>
                            @else
                                <x-badge warna="rose">Belum tuntas</x-badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <x-empty-row :colspan="7" pesan="Belum ada mata pelajaran." />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
