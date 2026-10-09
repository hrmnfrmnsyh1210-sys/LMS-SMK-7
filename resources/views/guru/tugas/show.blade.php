<x-layouts.app :title="'Tugas · '.$tugas->judul">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="tugas" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-body space-y-3 lg:col-span-1">
            <div class="flex items-start justify-between gap-2">
                <h3 class="card-title">{{ $tugas->judul }}</h3>
                <a href="{{ route('guru.tugas.edit', [$pengampu, $tugas]) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
            </div>
            <p class="text-sm whitespace-pre-line text-slate-600">{{ $tugas->deskripsi }}</p>
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Deadline</dt><dd class="font-medium">{{ tanggal_indo($tugas->deadline, true, true) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Nilai maksimal</dt><dd class="font-medium">{{ $tugas->nilai_maks }}</dd></div>
            </dl>
            @if ($tugas->file_path)
                <a href="{{ file_url($tugas->file_path) }}" target="_blank" class="link text-sm">📎 {{ basename($tugas->file_path) }}</a>
            @endif

            <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-center">
                @foreach (['sudah' => ['Tepat waktu', 'text-emerald-600'], 'terlambat' => ['Terlambat', 'text-amber-600'], 'belum' => ['Belum', 'text-rose-600']] as $k => [$label, $warna])
                    <a href="{{ route('guru.tugas.show', [$pengampu, $tugas, 'status' => $k]) }}" class="rounded-lg p-2 hover:bg-slate-50 {{ $filter === $k ? 'bg-slate-50 ring-1 ring-slate-200' : '' }}">
                        <div class="text-xl font-bold {{ $warna }}">{{ $ringkasan[$k] ?? 0 }}</div>
                        <div class="text-xs text-slate-500">{{ $label }}</div>
                    </a>
                @endforeach
            </div>
            @if ($filter)
                <a href="{{ route('guru.tugas.show', [$pengampu, $tugas]) }}" class="card-link">Tampilkan semua</a>
            @endif
        </div>

        <div class="space-y-3 lg:col-span-2">
            @foreach ($daftar as $baris)
                @php $p = $baris['pengumpulan']; @endphp
                <div class="card p-4" x-data="{ buka: false }">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-slate-800">{{ $baris['siswa']->nama }}</div>
                            <div class="text-xs text-slate-500">
                                {{ $baris['siswa']->nis }}
                                @if ($p) · dikumpulkan {{ tanggal_indo($p->waktu_kumpul, false, true) }} @endif
                            </div>
                        </div>
                        @if ($baris['status'] === 'belum')
                            <x-badge warna="rose">Belum mengumpulkan</x-badge>
                        @elseif ($baris['status'] === 'terlambat')
                            <x-badge warna="amber">Terlambat</x-badge>
                        @else
                            <x-badge warna="emerald">Tepat waktu</x-badge>
                        @endif
                        @if ($p)
                            @if ($p->nilai !== null)
                                <span class="rounded-lg bg-blue-50 px-2.5 py-1 text-sm font-bold text-blue-700">{{ (float) $p->nilai }}/{{ $tugas->nilai_maks }}</span>
                            @endif
                            <button type="button" class="btn-secondary btn-sm" @click="buka = !buka" x-text="buka ? 'Tutup' : '{{ $p->nilai === null ? 'Nilai' : 'Ubah nilai' }}'"></button>
                        @endif
                    </div>

                    @if ($p)
                        <div x-show="buka" x-cloak x-transition class="mt-4 grid gap-4 border-t border-slate-100 pt-4 md:grid-cols-2">
                            <div class="space-y-2 text-sm">
                                <div class="label">Jawaban siswa</div>
                                @if ($p->jawaban_teks)
                                    <div class="rounded-lg bg-slate-50 p-3 whitespace-pre-line text-slate-700">{{ $p->jawaban_teks }}</div>
                                @endif
                                @if ($p->file_path)
                                    <a href="{{ file_url($p->file_path) }}" target="_blank" class="link">📎 {{ basename($p->file_path) }}</a>
                                @endif
                                @if (! $p->jawaban_teks && ! $p->file_path)
                                    <p class="text-slate-400">Tidak ada isi.</p>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('guru.tugas.nilai', [$pengampu, $tugas, $p]) }}" class="space-y-3">
                                @csrf
                                @method('PUT')
                                <x-field.input name="nilai" type="number" step="0.01" min="0" :max="$tugas->nilai_maks"
                                               :label="'Nilai (0–'.$tugas->nilai_maks.')'" :value="$p->nilai !== null ? (float) $p->nilai : null" required
                                               :id="'nilai_'.$p->id" />
                                <x-field.textarea name="feedback" label="Feedback" :value="$p->feedback" rows="3" :id="'feedback_'.$p->id" />
                                <button type="submit" class="btn-primary">Simpan Nilai</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach

            @if ($daftar->isEmpty())
                <div class="card card-body text-center text-sm text-slate-500">Tidak ada siswa pada filter ini.</div>
            @endif
        </div>
    </div>
</x-layouts.app>
