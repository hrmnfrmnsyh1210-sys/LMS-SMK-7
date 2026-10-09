<x-layouts.app :title="'Koreksi · '.$jawaban->siswa->nama">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="kuis" />

    <x-page-header :title="'Jawaban '.$jawaban->siswa->nama" :subtitle="$kuis->label_jenis.' '.$kuis->judul.' · dikirim '.tanggal_indo($jawaban->waktu_submit, false, true)">
        <a href="{{ route('guru.kuis.hasil', [$pengampu, $kuis]) }}" class="btn-secondary">Kembali</a>
    </x-page-header>

    <form method="POST" action="{{ route('guru.kuis.koreksi.simpan', [$pengampu, $kuis, $jawaban]) }}" class="space-y-4">
        @csrf
        @method('PUT')

        @foreach ($kuis->soal as $s)
            @php $d = $detail->get($s->id); @endphp
            <div class="card p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-xs font-bold text-blue-700">{{ $loop->iteration }}</span>
                    <div class="min-w-0 flex-1 space-y-2">
                        <p class="text-sm whitespace-pre-line text-slate-800">{{ $s->pertanyaan }}</p>

                        @if ($s->isPilihanGanda())
                            @php $benar = $d && $d->jawaban === $s->kunci_jawaban; @endphp
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span>Jawaban: <strong>{{ $d?->jawaban ? strtoupper($d->jawaban).'. '.$s->{'opsi_'.$d->jawaban} : '(kosong)' }}</strong></span>
                                <x-badge :warna="$benar ? 'emerald' : 'rose'">{{ $benar ? 'Benar' : 'Salah' }}</x-badge>
                                @unless ($benar)
                                    <span class="text-xs text-slate-500">Kunci: {{ strtoupper($s->kunci_jawaban) }}</span>
                                @endunless
                            </div>
                        @else
                            <div class="rounded-lg bg-slate-50 p-3 text-sm whitespace-pre-line text-slate-700">{{ $d?->jawaban ?: '(tidak dijawab)' }}</div>
                        @endif
                    </div>
                    <div class="w-28 shrink-0 text-right">
                        @if ($s->isPilihanGanda())
                            <div class="text-lg font-bold text-slate-800">{{ (float) ($d?->skor ?? 0) }}</div>
                            <div class="text-xs text-slate-500">dari {{ $s->bobot }}</div>
                        @elseif ($d)
                            <label class="text-xs text-slate-500" for="skor_{{ $s->id }}">Skor (0–{{ $s->bobot }})</label>
                            <input type="number" step="0.5" min="0" max="{{ $s->bobot }}" name="skor[{{ $s->id }}]" id="skor_{{ $s->id }}"
                                   value="{{ old("skor.{$s->id}", $d->skor !== null ? (float) $d->skor : null) }}"
                                   @class(['input mt-1 text-right', 'border-amber-400 bg-amber-50' => $d->skor === null])>
                        @else
                            <div class="text-lg font-bold text-slate-800">0</div>
                            <div class="text-xs text-slate-500">dari {{ $s->bobot }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm">
                Skor akhir saat ini:
                <strong class="text-lg">{{ $jawaban->total_skor !== null ? (float) $jawaban->total_skor : 'belum final' }}</strong>
            </div>
            <button type="submit" class="btn-primary">Simpan Koreksi</button>
        </div>
    </form>
</x-layouts.app>
