<x-layouts.app title="Asisten Belajar">
    <x-page-header title="Asisten Belajar" subtitle="Tanya materi yang belum kamu pahami. Asisten membimbing cara berpikirnya, bukan memberi contekan." />

    @if (! $aiAktif)
        <div class="card card-body text-center text-sm text-slate-500">Asisten belajar belum diaktifkan oleh sekolah.</div>
    @else
        <div class="card flex h-[calc(100vh-15rem)] min-h-[28rem] flex-col overflow-hidden"
             x-data="{
                pesan: [], input: '', mapel: @js($mapelTerpilih ? (string) $mapelTerpilih : ''), memuat: false, galat: '',
                saran: ['Jelaskan konsep ini dengan contoh sederhana', 'Buatkan 3 soal latihan beserta pembahasannya', 'Apa bedanya ... dan ...?'],
                async kirim(teks) {
                    teks = (teks ?? this.input).trim();
                    if (! teks || this.memuat) return;
                    const riwayat = this.pesan.map(({ role, content }) => ({ role, content }));
                    this.pesan.push({ role: 'user', content: teks });
                    this.input = ''; this.galat = ''; this.memuat = true;
                    this.gulir();
                    try {
                        const res = await fetch(@js(route('siswa.asisten.kirim')), {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ pesan: teks, pengampu_id: this.mapel || null, riwayat: riwayat.slice(-10) }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (! res.ok) throw new Error(data.pesan || data.message || 'Asisten sedang tidak bisa menjawab. Coba lagi.');
                        this.pesan.push({ role: 'assistant', content: data.balasan, html: data.html });
                    } catch (e) {
                        this.pesan.pop();
                        this.input = teks;
                        this.galat = e.message;
                    } finally {
                        this.memuat = false;
                        this.gulir();
                    }
                },
                gulir() { this.$nextTick(() => this.$refs.daftar.scrollTop = this.$refs.daftar.scrollHeight); },
             }">
            <div class="flex flex-col gap-2 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-linear-to-br from-blue-100 to-pink-100 text-blue-600"><x-icon name="sparkles" class="h-5 w-5" /></span>
                    <div>
                        <div class="text-sm font-semibold text-slate-800">Asisten AI</div>
                        <div class="text-xs text-slate-500">Jawaban AI bisa keliru, cek lagi dengan materi dari guru.</div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <select x-model="mapel" class="input sm:w-56" aria-label="Mata pelajaran">
                        <option value="">Semua mapel</option>
                        @foreach ($daftarMapel as $id => $nama)
                            <option value="{{ $id }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn-secondary shrink-0" @click="pesan = []; galat = ''" title="Percakapan baru"><x-icon name="refresh" class="h-4 w-4" /></button>
                </div>
            </div>

            <div x-ref="daftar" class="flex-1 space-y-4 overflow-y-auto p-4">
                <template x-if="pesan.length === 0">
                    <div class="mx-auto max-w-md py-8 text-center">
                        <p class="text-sm text-slate-500">Mulai dengan pertanyaan, atau coba salah satu:</p>
                        <div class="mt-3 flex flex-wrap justify-center gap-2">
                            <template x-for="s in saran" :key="s">
                                <button type="button" class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100" @click="input = s; $refs.input.focus()" x-text="s"></button>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-for="(p, i) in pesan" :key="i">
                    <div class="flex" :class="p.role === 'user' ? 'justify-end' : 'justify-start'">
                        <div x-show="p.role === 'user'" class="max-w-[85%] rounded-2xl rounded-br-md bg-blue-600 px-4 py-2.5 text-sm whitespace-pre-line text-white" x-text="p.content"></div>
                        <div x-show="p.role === 'assistant'" class="konten-ai max-w-[85%] rounded-2xl rounded-bl-md bg-slate-50 px-4 py-3" x-html="p.html"></div>
                    </div>
                </template>
                <div x-show="memuat" x-cloak class="flex justify-start">
                    <div class="rounded-2xl rounded-bl-md bg-slate-50 px-4 py-3 text-sm text-slate-500">Asisten sedang mengetik…</div>
                </div>
            </div>

            <form class="border-t border-slate-100 p-3" @submit.prevent="kirim()">
                <p x-show="galat" x-text="galat" x-cloak class="mb-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700"></p>
                <div class="flex items-end gap-2">
                    <textarea x-ref="input" x-model="input" rows="1" maxlength="2000" placeholder="Tulis pertanyaanmu…" class="input max-h-40 resize-none"
                              @keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); kirim(); }"></textarea>
                    <button type="submit" class="btn-primary shrink-0" :disabled="memuat || ! input.trim()"><x-icon name="arrow-right" class="h-4 w-4" /><span class="sr-only">Kirim</span></button>
                </div>
            </form>
        </div>
    @endif
</x-layouts.app>
