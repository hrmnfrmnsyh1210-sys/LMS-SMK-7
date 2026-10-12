{{-- Bubble chat asisten AI untuk pengunjung website. Riwayat obrolan hanya disimpan di sessionStorage browser. --}}
@php
    $namaAsisten = 'Asisten '.($sekolah['web_nama_singkat'] ?: 'Sekolah');
    $saran = array_values(array_filter([
        'Apa saja program keahlian di sini?',
        $sekolah['web_ppdb_aktif'] === '1' ? 'Bagaimana cara mendaftar PPDB?' : 'Kapan pendaftaran siswa baru dibuka?',
        'Di mana lokasi sekolah?',
        'Bagaimana cara masuk ke LMS?',
    ]));
@endphp

<div class="fixed right-4 bottom-4 z-40 sm:right-6 sm:bottom-6"
     x-data="{
        buka: false, sapa: false, input: '', memuat: false, galat: '', pesan: [],
        init() {
            try { this.pesan = JSON.parse(sessionStorage.getItem('chat-ai-web') || '[]'); } catch { this.pesan = []; }
            setTimeout(() => { if (! this.buka && ! this.pesan.length) this.sapa = true; }, 4000);
            this.$watch('pesan', (v) => { try { sessionStorage.setItem('chat-ai-web', JSON.stringify(v.slice(-16))); } catch {} });
        },
        toggle() {
            this.buka = ! this.buka; this.sapa = false;
            if (this.buka) this.$nextTick(() => { this.gulir(); this.$refs.input?.focus(); });
        },
        async kirim(teks) {
            teks = (teks ?? this.input).trim();
            if (! teks || this.memuat) return;
            const riwayat = this.pesan.map(({ role, content }) => ({ role, content })).slice(-8);
            this.pesan.push({ role: 'user', content: teks });
            this.input = ''; this.galat = ''; this.memuat = true; this.gulir();
            try {
                const res = await fetch(@js(route('web.tanya-ai')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ pesan: teks, riwayat }),
                });
                const data = await res.json().catch(() => ({}));
                if (res.status === 429) throw new Error('Terlalu banyak pertanyaan. Coba lagi sebentar lagi, ya.');
                if (! res.ok) throw new Error(data.pesan || data.message || 'Asisten sedang tidak bisa menjawab.');
                this.pesan.push({ role: 'assistant', content: data.balasan, html: data.html });
            } catch (e) {
                this.pesan.pop(); this.input = teks; this.galat = e.message;
            } finally {
                this.memuat = false; this.gulir();
            }
        },
        gulir() { this.$nextTick(() => { if (this.$refs.daftar) this.$refs.daftar.scrollTop = this.$refs.daftar.scrollHeight; }); },
     }"
     @keydown.escape.window="buka = false">

    {{-- Panel obrolan --}}
    <div x-show="buka" x-cloak
         x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-4 scale-95 opacity-0" x-transition:enter-end="translate-y-0 scale-100 opacity-100"
         x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="translate-y-4 scale-95 opacity-0"
         class="absolute right-0 bottom-20 flex h-[min(34rem,calc(100dvh-7rem))] w-[calc(100vw-2rem)] origin-bottom-right flex-col overflow-hidden rounded-3xl bg-white shadow-2xl ring-1 shadow-navy-950/25 ring-slate-900/5 sm:w-96"
         role="dialog" aria-label="{{ $namaAsisten }}">

        <div class="relative isolate overflow-hidden bg-linear-to-br from-[#2f6fed] via-[#2a8de0] to-[#13b5cf] px-5 pt-5 pb-6 text-white">
            <div aria-hidden="true" class="absolute -top-10 -right-8 -z-10 h-32 w-32 rounded-full bg-white/10"></div>
            <div aria-hidden="true" class="absolute -bottom-12 left-10 -z-10 h-24 w-24 rounded-full border-2 border-dashed border-white/20"></div>
            <div class="flex items-center gap-3">
                <span class="bubble-blob flex h-11 w-11 shrink-0 items-center justify-center bg-white/20 ring-1 ring-white/40 backdrop-blur">
                    <x-icon name="sparkles" class="h-5 w-5" />
                </span>
                <div class="min-w-0 flex-1">
                    <div class="truncate font-display font-bold">{{ $namaAsisten }}</div>
                    <div class="flex items-center gap-1.5 text-xs text-white/85">
                        <span class="h-2 w-2 rounded-full bg-emerald-300 ring-2 ring-emerald-300/30"></span> Online · dijawab oleh AI
                    </div>
                </div>
                <button type="button" class="rounded-full p-1.5 text-white/80 hover:bg-white/15 hover:text-white" title="Percakapan baru" @click="pesan = []; galat = ''">
                    <x-icon name="refresh" class="h-4 w-4" />
                </button>
                <button type="button" class="rounded-full p-1.5 text-white/80 hover:bg-white/15 hover:text-white" aria-label="Tutup" @click="toggle()">
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            </div>
        </div>

        <div x-ref="daftar" class="relative z-10 -mt-3 flex-1 space-y-3 overflow-y-auto rounded-t-3xl bg-white px-4 pt-5 pb-3">
            <div class="flex gap-2">
                <span class="bubble-blob mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center bg-linear-to-br from-[#2f6fed] to-[#13b5cf] text-white"><x-icon name="sparkles" class="h-3.5 w-3.5" /></span>
                <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-slate-100 px-3.5 py-2.5 text-sm text-slate-700">
                    Halo! Saya asisten informasi {{ $sekolah['nama_sekolah'] }}. Tanyakan seputar jurusan, pendaftaran, lokasi, atau kegiatan sekolah.
                </div>
            </div>

            <template x-for="(p, i) in pesan" :key="i">
                <div class="flex gap-2" :class="p.role === 'user' ? 'justify-end' : ''">
                    <template x-if="p.role === 'assistant'">
                        <span class="bubble-blob mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center bg-linear-to-br from-[#2f6fed] to-[#13b5cf] text-white"><x-icon name="sparkles" class="h-3.5 w-3.5" /></span>
                    </template>
                    <div x-show="p.role === 'user'" class="max-w-[85%] rounded-2xl rounded-br-md bg-linear-to-br from-[#2f6fed] to-[#2a8de0] px-3.5 py-2.5 text-sm whitespace-pre-line text-white" x-text="p.content"></div>
                    <div x-show="p.role === 'assistant'" class="konten-ai max-w-[85%] rounded-2xl rounded-tl-md bg-slate-100 px-3.5 py-2.5" x-html="p.html"></div>
                </div>
            </template>

            <div x-show="memuat" x-cloak class="flex gap-2">
                <span class="bubble-blob flex h-7 w-7 shrink-0 items-center justify-center bg-linear-to-br from-[#2f6fed] to-[#13b5cf] text-white"><x-icon name="sparkles" class="h-3.5 w-3.5" /></span>
                <div class="flex items-center gap-1 rounded-2xl rounded-tl-md bg-slate-100 px-4 py-3" aria-label="Asisten sedang mengetik">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#2f6fed]"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#2a8de0] [animation-delay:150ms]"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#13b5cf] [animation-delay:300ms]"></span>
                </div>
            </div>

            <div x-show="! pesan.length && ! memuat" class="flex flex-wrap gap-2 pt-1 pl-9">
                @foreach ($saran as $s)
                    <button type="button" class="rounded-full bg-[#2f6fed]/8 px-3 py-1.5 text-left text-xs font-medium text-[#2f6fed] ring-1 ring-[#2f6fed]/20 transition hover:bg-[#2f6fed] hover:text-white" @click="kirim(@js($s))">{{ $s }}</button>
                @endforeach
            </div>
        </div>

        <form class="border-t border-slate-100 p-3" @submit.prevent="kirim()">
            <p x-show="galat" x-text="galat" x-cloak class="mb-2 rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700"></p>
            <div class="flex items-end gap-2 rounded-2xl bg-slate-50 p-1.5 ring-1 ring-slate-200 focus-within:ring-[#2f6fed]/50">
                <textarea x-ref="input" x-model="input" rows="1" maxlength="500" placeholder="Tulis pertanyaan…" aria-label="Pertanyaan"
                          class="max-h-28 flex-1 resize-none border-0 bg-transparent px-2.5 py-2 text-sm text-slate-800 placeholder:text-slate-400 focus:ring-0 focus:outline-none"
                          @keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); kirim(); }"></textarea>
                <button type="submit" :disabled="memuat || ! input.trim()" aria-label="Kirim"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-[#2f6fed] to-[#13b5cf] text-white transition hover:scale-105 disabled:opacity-40 disabled:hover:scale-100">
                    <x-icon name="arrow-up" class="h-4 w-4" />
                </button>
            </div>
            <p class="mt-1.5 text-center text-[10px] text-slate-400">Jawaban AI bisa keliru. Pastikan kembali ke pihak sekolah.</p>
        </form>
    </div>

    {{-- Sapaan --}}
    <div x-show="sapa && ! buka" x-cloak x-transition.opacity.duration.300ms
         class="absolute right-2 bottom-20 w-56 rounded-2xl rounded-br-md bg-white px-4 py-3 text-sm text-slate-700 shadow-xl ring-1 shadow-navy-950/15 ring-slate-900/5">
        <button type="button" class="absolute top-1.5 right-1.5 rounded-full p-0.5 text-slate-400 hover:text-slate-600" aria-label="Tutup sapaan" @click="sapa = false"><x-icon name="x" class="h-3.5 w-3.5" /></button>
        <span class="font-semibold text-navy-900">Hai, ada yang ingin ditanyakan?</span>
        <span class="mt-0.5 block text-xs text-slate-500">Tanya seputar sekolah, jawabannya langsung.</span>
    </div>

    {{-- Bubble: blob bergradasi yang berubah bentuk, dengan titik orbit. --}}
    <button type="button" @click="toggle()" :aria-expanded="buka" aria-label="Buka {{ $namaAsisten }}"
            class="group relative flex h-16 w-16 items-center justify-center focus:outline-none">
        <span aria-hidden="true" class="bubble-blob absolute inset-0 bg-linear-to-br from-[#2f6fed] via-[#2a8de0] to-[#13b5cf] shadow-xl ring-4 shadow-[#2f6fed]/40 ring-white transition duration-300 group-hover:scale-105"></span>
        <span aria-hidden="true" class="bubble-blob bubble-blob-lambat absolute -inset-2 bg-[#13b5cf]/20" x-show="! buka"></span>
        <span aria-hidden="true" class="bubble-orbit absolute -inset-3" x-show="! buka">
            <span class="absolute top-0 left-1/2 h-2.5 w-2.5 -translate-x-1/2 rounded-full bg-kuning-400 ring-2 ring-white"></span>
        </span>
        <span class="relative text-white">
            <x-icon name="chat" class="h-7 w-7 transition duration-300" x-show="! buka" />
            <x-icon name="x" class="h-7 w-7" x-show="buka" x-cloak />
        </span>
        <span x-show="! buka && pesan.length" x-cloak class="absolute -top-0.5 -right-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-kuning-400 px-1 text-[10px] font-bold text-navy-950 ring-2 ring-white" x-text="pesan.filter((p) => p.role === 'assistant').length"></span>
    </button>
</div>
