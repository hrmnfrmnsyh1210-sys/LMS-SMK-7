<x-layouts.app :title="'Koreksi · '.$jawaban->siswa->nama">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="kuis" />

    <x-page-header :title="'Jawaban '.$jawaban->siswa->nama" :subtitle="$kuis->label_jenis.' '.$kuis->judul.' · dikirim '.tanggal_indo($jawaban->waktu_submit, false, true)">
        <a href="{{ route('guru.kuis.hasil', [$pengampu, $kuis]) }}" class="btn-secondary">Kembali</a>
    </x-page-header>

    @php $adaEsai = $kuis->soal->contains(fn ($s) => ! $s->isPilihanGanda() && filled($detail->get($s->id)?->jawaban)); @endphp

    <form method="POST" action="{{ route('guru.kuis.koreksi.simpan', [$pengampu, $kuis, $jawaban]) }}" class="space-y-4"
          x-data="{
            saran: {}, memuat: false, galat: '',
            async mintaSaran() {
                this.memuat = true; this.galat = '';
                try {
                    const res = await fetch(@js(route('guru.kuis.koreksi.ai', [$pengampu, $kuis, $jawaban])), {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (! res.ok) throw new Error(data.pesan || data.message || 'Saran AI gagal dibuat.');
                    this.saran = data.saran;
                } catch (e) {
                    this.galat = e.message;
                } finally {
                    this.memuat = false;
                }
            },
            pakai(id) { document.getElementById('skor_' + id).value = this.saran[id].skor; },
            pakaiSemua() { Object.keys(this.saran).forEach((id) => this.pakai(id)); },
          }">
        @if ($adaEsai && $aiAktif)
            <div class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600"><x-icon name="sparkles" class="h-5 w-5" /></span>
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Bantu koreksi esai dengan AI</div>
                        <p class="text-xs text-slate-500">AI memberi saran skor dan alasan. Keputusan akhir tetap di tangan Anda.</p>
                        <p x-show="galat" x-text="galat" x-cloak class="mt-1 text-xs text-red-600"></p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn-secondary btn-sm" x-show="Object.keys(saran).length" x-cloak @click="pakaiSemua()">Pakai semua saran</button>
                    <button type="button" class="btn-primary btn-sm" @click="mintaSaran()" :disabled="memuat">
                        <x-icon name="sparkles" class="h-3.5 w-3.5" /> <span x-text="memuat ? 'Menilai…' : 'Minta saran AI'"></span>
                    </button>
                </div>
            </div>
        @endif

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
                            @if ($d)
                                <template x-if="saran[{{ $s->id }}]">
                                    <div class="flex flex-col gap-2 rounded-lg border border-violet-100 bg-violet-50/60 p-3 text-sm sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <span class="font-semibold text-violet-700">Saran AI: <span x-text="saran[{{ $s->id }}].skor"></span> / {{ $s->bobot }}</span>
                                            <p class="text-slate-600" x-text="saran[{{ $s->id }}].alasan"></p>
                                        </div>
                                        <button type="button" class="btn-secondary btn-sm shrink-0" @click="pakai({{ $s->id }})">Pakai skor</button>
                                    </div>
                                </template>
                            @endif
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
