@props(['url', 'aktif' => false])

{{-- Panel "Analisis AI": mengirim filter laporan yang sama (query string) ke endpoint analisis. --}}
<div class="card card-body space-y-3"
     x-data="{
        memuat: false, html: '', galat: '',
        async analisis() {
            this.memuat = true; this.galat = '';
            try {
                const res = await fetch(@js($url) + window.location.search, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                });
                const data = await res.json().catch(() => ({}));
                if (! res.ok) throw new Error(data.pesan || data.message || 'Analisis gagal. Coba lagi.');
                this.html = data.html;
            } catch (e) {
                this.galat = e.message;
            } finally {
                this.memuat = false;
            }
        },
     }">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600"><x-icon name="sparkles" class="h-5 w-5" /></span>
            <div>
                <h3 class="card-title">Analisis AI</h3>
                <p class="text-xs text-slate-500">Ringkasan temuan dan rekomendasi tindak lanjut dari data laporan di bawah.</p>
            </div>
        </div>
        @if ($aktif)
            <button type="button" class="btn-primary" @click="analisis()" :disabled="memuat">
                <x-icon name="sparkles" class="h-4 w-4" />
                <span x-text="memuat ? 'Menganalisis…' : (html ? 'Analisis ulang' : 'Analisis dengan AI')"></span>
            </button>
        @else
            <span class="text-xs text-slate-500">Fitur AI belum diaktifkan (API key belum diisi).</span>
        @endif
    </div>
    <p x-show="galat" x-text="galat" x-cloak class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"></p>
    <div x-show="html" x-cloak class="konten-ai rounded-xl border border-violet-100 bg-violet-50/40 p-4" x-html="html"></div>
    <p x-show="html" x-cloak class="text-xs text-slate-400">Hasil AI dapat keliru. Periksa kembali sebelum dijadikan dasar keputusan.</p>
</div>
