<x-layouts.app :title="'Kuis · '.$pengampu->label">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="kuis" />

    <x-page-header title="Kuis & Ujian" subtitle="Kuis harian, UTS, dan UAS dengan soal pilihan ganda dan esai.">
        <a href="{{ route('guru.kuis.create', $pengampu) }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Buat Kuis</a>
    </x-page-header>

    @php
        $badgeWaktu = ['akan_datang' => ['sky', 'Akan datang'], 'berlangsung' => ['emerald', 'Berlangsung'], 'selesai' => ['slate', 'Selesai']];
    @endphp

    <div class="card">
        <div class="table-wrap p-2 sm:p-4">
            <table class="table">
                <thead><tr><th>Judul</th><th>Jenis</th><th>Waktu</th><th>Durasi</th><th>Soal</th><th>Dikerjakan</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($kuis as $k)
                        <tr>
                            <td>
                                <a href="{{ route('guru.kuis.show', [$pengampu, $k]) }}" class="font-semibold text-slate-800 hover:text-blue-700">{{ $k->judul }}</a>
                                <div class="mt-0.5"><x-badge :warna="$badgeWaktu[$k->status_waktu][0]">{{ $badgeWaktu[$k->status_waktu][1] }}</x-badge></div>
                            </td>
                            <td><x-badge warna="violet">{{ $k->label_jenis }}</x-badge></td>
                            <td class="text-xs whitespace-nowrap">
                                {{ tanggal_indo($k->waktu_mulai, false, true) }}<br>s.d. {{ tanggal_indo($k->waktu_selesai, false, true) }}
                            </td>
                            <td>{{ $k->durasi_menit }} menit</td>
                            <td>{{ $k->soal_count }} <span class="text-xs text-slate-400">({{ (int) $k->soal_sum_bobot }} poin)</span></td>
                            <td>{{ $k->dikerjakan_count }}</td>
                            <td>
                                <div class="flex justify-end gap-1.5 whitespace-nowrap">
                                    <a href="{{ route('guru.kuis.show', [$pengampu, $k]) }}" class="btn-secondary btn-sm">Soal</a>
                                    <a href="{{ route('guru.kuis.hasil', [$pengampu, $k]) }}" class="btn-primary btn-sm">Hasil</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="7" pesan="Belum ada kuis." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
