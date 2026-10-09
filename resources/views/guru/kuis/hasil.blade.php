<x-layouts.app :title="'Hasil · '.$kuis->judul">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="kuis" />

    <x-page-header :title="'Hasil '.$kuis->label_jenis.': '.$kuis->judul">
        <a href="{{ route('guru.kuis.show', [$pengampu, $kuis]) }}" class="btn-secondary">Bank Soal</a>
        <form method="POST" action="{{ route('guru.kuis.hitung-ulang', [$pengampu, $kuis]) }}">
            @csrf
            <button type="submit" class="btn-secondary"><x-icon name="refresh" class="h-4 w-4" /> Hitung Ulang PG</button>
        </form>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <x-card-stat label="Mengerjakan" :value="$ringkasan['mengerjakan'].'/'.$daftar->count()" ikon="user-group" warna="blue" />
        <x-card-stat label="Menunggu Koreksi" :value="$ringkasan['menunggu']" ikon="pencil" warna="amber" />
        <x-card-stat label="Rata-rata" :value="$ringkasan['rata'] ?? '-'" ikon="chart" warna="emerald" />
        <x-card-stat label="Tertinggi" :value="$ringkasan['tertinggi'] ?? '-'" ikon="arrow-up" warna="violet" />
        <x-card-stat label="Terendah" :value="$ringkasan['terendah'] ?? '-'" ikon="warning" warna="rose" />
    </div>

    <div class="card">
        <div class="table-wrap p-2 sm:p-4">
            <table class="table">
                <thead><tr><th>#</th><th>Siswa</th><th>Status</th><th>Mulai</th><th>Selesai</th><th>Pindah Tab</th><th>Skor</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @foreach ($daftar as $baris)
                        @php $j = $baris['jawaban']; @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="font-medium text-slate-800">{{ $baris['siswa']->nama }}</td>
                            <td>
                                @if (! $j)
                                    <x-badge warna="slate">Belum mengerjakan</x-badge>
                                @elseif (! $j->sudahSubmit())
                                    <x-badge warna="sky">Sedang mengerjakan</x-badge>
                                @elseif ($j->menungguKoreksi())
                                    <x-badge warna="amber">Menunggu koreksi</x-badge>
                                @else
                                    <x-badge warna="emerald">Selesai</x-badge>
                                @endif
                            </td>
                            <td class="text-xs whitespace-nowrap">{{ $j ? $j->waktu_mulai->format('d/m H:i') : '-' }}</td>
                            <td class="text-xs whitespace-nowrap">{{ $j?->waktu_submit?->format('d/m H:i') ?? '-' }}</td>
                            <td>
                                @if ($j && $j->jumlah_pindah_tab)
                                    <x-badge warna="rose">{{ $j->jumlah_pindah_tab }}×</x-badge>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="font-bold text-slate-800">{{ $j?->total_skor !== null ? (float) $j->total_skor : '-' }}</td>
                            <td class="text-right">
                                @if ($j?->sudahSubmit())
                                    <a href="{{ route('guru.kuis.koreksi', [$pengampu, $kuis, $j]) }}" @class(['btn-sm', 'btn-primary' => $j->menungguKoreksi(), 'btn-secondary' => ! $j->menungguKoreksi()])>
                                        {{ $j->menungguKoreksi() ? 'Koreksi' : 'Detail' }}
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
