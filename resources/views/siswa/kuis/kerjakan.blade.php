<x-layouts.app :title="'Mengerjakan · '.$kuis->judul">
    <form method="POST" action="{{ route('siswa.kuis.submit', [$pengampu, $kuis]) }}" x-ref="form"
          x-data="pengerjaanKuis({
              sisa: {{ $sisaDetik }},
              urlJawab: @js(route('siswa.kuis.jawab', [$pengampu, $kuis])),
              urlTab: @js(route('siswa.kuis.pindah-tab', [$pengampu, $kuis])),
              terjawab: @js($isian->filter(fn ($v) => filled($v))->keys()->map(fn ($id) => (string) $id)->values()),
              jumlahSoal: {{ $soal->count() }},
          })"
          @submit="kirim($event)" class="space-y-6">
        @csrf

        {{-- Bar timer (menempel di atas) --}}
        <div class="sticky top-16 z-10 -mx-4 bg-latar/90 px-4 py-2 backdrop-blur sm:-mx-6 sm:px-6">
            <div class="card flex flex-wrap items-center gap-3 p-3 sm:p-4">
                <div class="min-w-0 flex-1">
                    <div class="truncate font-semibold text-slate-800">{{ $kuis->judul }}</div>
                    <div class="text-xs text-slate-500">
                        {{ $pengampu->mapel->nama_mapel }} · <span x-text="terjawab.length"></span>/{{ $soal->count() }} terjawab
                        <span x-show="menyimpan > 0" x-cloak class="ml-1 text-blue-600">· menyimpan…</span>
                        <span x-show="galat" x-cloak class="ml-1 text-rose-600" x-text="'· ' + galat"></span>
                    </div>
                </div>
                <div :class="sisa <= 60 ? 'bg-rose-50 text-rose-600 animate-pulse' : (sisa <= 300 ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700')"
                     class="inline-flex items-center gap-2 rounded-full px-4 py-2 font-mono text-lg font-bold tabular-nums">
                    <x-icon name="clock" class="h-5 w-5" /> <span x-text="formatWaktu()">--:--</span>
                </div>
                <button type="submit" class="btn-primary"><x-icon name="check" class="h-4 w-4" /> Kirim</button>
            </div>
        </div>

        <div x-show="peringatanTab" x-cloak x-transition
             class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <x-icon name="warning" class="h-5 w-5 shrink-0" />
            <div class="flex-1">Kamu berpindah tab/aplikasi. Aktivitas ini tercatat dan dapat dilihat guru.</div>
            <button type="button" class="text-amber-600 hover:text-amber-800" @click="peringatanTab = false"><x-icon name="x" class="h-4 w-4" /></button>
        </div>

        <div class="grid gap-6 lg:grid-cols-4">
            {{-- Soal --}}
            <div class="space-y-4 lg:col-span-3">
                @foreach ($soal as $s)
                    <div class="card scroll-mt-40 p-4 sm:p-6" id="soal-{{ $loop->iteration }}">
                        <div class="flex items-start gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold transition"
                                  :class="terjawab.includes('{{ $s->id }}') ? 'bg-linear-to-br from-blue-500 to-pink-400 text-white' : 'bg-slate-100 text-slate-500'">{{ $loop->iteration }}</span>
                            <div class="min-w-0 flex-1 space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm whitespace-pre-line text-slate-800 sm:text-base">{{ $s->pertanyaan }}</p>
                                    <span class="shrink-0 text-xs text-slate-400">{{ $s->bobot }} poin</span>
                                </div>

                                @if ($s->isPilihanGanda())
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach (\App\Models\Soal::OPSI as $o)
                                            <label class="group flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 px-3 py-2.5 text-sm transition hover:border-blue-300 hover:bg-blue-50/50 has-checked:border-blue-400 has-checked:bg-blue-50 has-checked:ring-2 has-checked:ring-blue-100">
                                                <input type="radio" name="jawaban[{{ $s->id }}]" value="{{ $o }}" class="peer sr-only"
                                                       @checked(($isian[$s->id] ?? null) === $o)
                                                       @change="simpan('{{ $s->id }}', $event.target.value)">
                                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500 peer-checked:bg-blue-600 peer-checked:text-white">{{ strtoupper($o) }}</span>
                                                <span class="text-slate-700">{{ $s->{'opsi_'.$o} }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <textarea name="jawaban[{{ $s->id }}]" rows="5" class="input" placeholder="Tulis jawabanmu di sini…"
                                              @input.debounce.800ms="simpan('{{ $s->id }}', $event.target.value)">{{ $isian[$s->id] ?? '' }}</textarea>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Navigasi nomor soal --}}
            <div class="hidden lg:block">
                <div class="card sticky top-44 p-4">
                    <div class="mb-3 text-sm font-semibold text-slate-700">Navigasi Soal</div>
                    <div class="grid grid-cols-5 gap-2">
                        @foreach ($soal as $s)
                            <a href="#soal-{{ $loop->iteration }}"
                               class="flex h-9 items-center justify-center rounded-xl text-xs font-semibold transition"
                               :class="terjawab.includes('{{ $s->id }}') ? 'bg-linear-to-br from-blue-500 to-pink-400 text-white shadow-sm' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'">{{ $loop->iteration }}</a>
                        @endforeach
                    </div>
                    <div class="mt-4 space-y-1 text-xs text-slate-500">
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-linear-to-br from-blue-500 to-pink-400"></span> Sudah dijawab</div>
                        <div class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-slate-200"></span> Belum dijawab</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-600">Sudah yakin dengan jawabanmu? Setelah dikirim, jawaban tidak dapat diubah.</p>
            <button type="submit" class="btn-primary"><x-icon name="check" class="h-4 w-4" /> Kirim Jawaban</button>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('pengerjaanKuis', (opsi) => ({
                    sisa: opsi.sisa,
                    terjawab: opsi.terjawab,
                    menyimpan: 0,
                    galat: '',
                    peringatanTab: false,
                    terkirim: false,
                    berakhirPada: Date.now() + opsi.sisa * 1000,

                    init() {
                        const timer = setInterval(() => {
                            this.sisa = Math.max(0, Math.round((this.berakhirPada - Date.now()) / 1000));
                            if (this.sisa === 0) {
                                clearInterval(timer);
                                this.kirimOtomatis();
                            }
                        }, 1000);

                        document.addEventListener('visibilitychange', () => {
                            if (document.hidden && !this.terkirim) {
                                this.peringatanTab = true;
                                this.post(opsi.urlTab, {});
                            }
                        });

                        window.addEventListener('beforeunload', (e) => {
                            if (!this.terkirim && this.menyimpan > 0) {
                                e.preventDefault();
                            }
                        });
                    },

                    formatWaktu() {
                        const j = Math.floor(this.sisa / 3600);
                        const m = String(Math.floor((this.sisa % 3600) / 60)).padStart(2, '0');
                        const d = String(this.sisa % 60).padStart(2, '0');
                        return (j > 0 ? j + ':' : '') + m + ':' + d;
                    },

                    async simpan(soalId, jawaban) {
                        const terisi = jawaban.trim() !== '';
                        this.terjawab = this.terjawab.filter((id) => id !== soalId);
                        if (terisi) this.terjawab.push(soalId);

                        this.menyimpan++;
                        try {
                            const res = await this.post(opsi.urlJawab, { soal_id: soalId, jawaban });
                            const data = await res.json();
                            if (!res.ok) throw new Error(data.pesan || 'Gagal menyimpan');
                            this.galat = '';
                            this.berakhirPada = Date.now() + data.sisa_detik * 1000;
                        } catch (e) {
                            this.galat = e.message || 'Gagal menyimpan, periksa koneksi';
                        } finally {
                            this.menyimpan--;
                        }
                    },

                    post(url, body) {
                        return fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify(body),
                        });
                    },

                    kirim(event) {
                        const kosong = opsi.jumlahSoal - this.terjawab.length;
                        const pesan = kosong > 0
                            ? `Masih ada ${kosong} soal yang belum dijawab. Tetap kirim?`
                            : 'Kirim jawaban sekarang?';
                        if (!this.terkirim && !confirm(pesan)) {
                            event.preventDefault();
                            return;
                        }
                        this.terkirim = true;
                    },

                    kirimOtomatis() {
                        if (this.terkirim) return;
                        this.terkirim = true;
                        this.$refs.form.submit();
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.app>
