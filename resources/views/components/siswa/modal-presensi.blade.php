{{-- Modal Presensi Mandiri Siswa (Alpine.js) --}}
<div x-data="{
    buka: false,
    pengampuId: '',
    mapel: '',
    guru: '',
    status: 'hadir',
    catatan: ''
}"
@buka-presensi.window="
    pengampuId = $event.detail.id;
    mapel = $event.detail.mapel;
    guru = $event.detail.guru;
    status = 'hadir';
    catatan = '';
    buka = true;
"
class="inline">
    <template x-teleport="body">
        <div x-show="buka"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @keydown.escape.window="buka = false">
            <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
                 x-show="buka"
                 x-transition.opacity
                 @click="buka = false"></div>

            <div x-show="buka"
                 x-transition
                 class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
                {{-- Header Modal --}}
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                            <x-icon name="check-badge" class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-slate-800">Presensi Mandiri</h3>
                            <p class="text-xs text-slate-400">{{ tanggal_indo(today()) }}</p>
                        </div>
                    </div>
                    <button type="button"
                            @click="buka = false"
                            class="rounded-full p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
                            aria-label="Tutup">
                        <x-icon name="x" class="h-5 w-5" />
                    </button>
                </div>

                {{-- Info Mapel --}}
                <div class="mt-4 rounded-2xl bg-blue-50/50 p-3.5 border border-blue-100/60">
                    <div class="text-xs font-semibold text-blue-600 uppercase tracking-wide">Mata Pelajaran</div>
                    <div class="mt-0.5 text-sm font-bold text-slate-800" x-text="mapel"></div>
                    <div class="text-xs text-slate-500" x-text="guru"></div>
                </div>

                {{-- Form Presensi --}}
                <form method="POST" action="{{ route('siswa.presensi.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <input type="hidden" name="pengampu_id" :value="pengampuId">

                    {{-- Pilihan Status --}}
                    <div>
                        <label class="label">Status Kehadiran</label>
                        <div class="grid grid-cols-3 gap-2.5">
                            <label :class="status === 'hadir' ? 'border-emerald-500 bg-emerald-50 text-emerald-800 ring-2 ring-emerald-400/50' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                   class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-2xl border p-3 text-center transition">
                                <input type="radio" name="status" value="hadir" x-model="status" class="sr-only">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                    <x-icon name="check" class="h-4 w-4" />
                                </span>
                                <span class="text-xs font-semibold">Hadir</span>
                            </label>

                            <label :class="status === 'izin' ? 'border-sky-500 bg-sky-50 text-sky-800 ring-2 ring-sky-400/50' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                   class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-2xl border p-3 text-center transition">
                                <input type="radio" name="status" value="izin" x-model="status" class="sr-only">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-sky-100 text-sky-700">
                                    <x-icon name="document" class="h-4 w-4" />
                                </span>
                                <span class="text-xs font-semibold">Izin</span>
                            </label>

                            <label :class="status === 'sakit' ? 'border-amber-500 bg-amber-50 text-amber-800 ring-2 ring-amber-400/50' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                   class="flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-2xl border p-3 text-center transition">
                                <input type="radio" name="status" value="sakit" x-model="status" class="sr-only">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                    <x-icon name="info" class="h-4 w-4" />
                                </span>
                                <span class="text-xs font-semibold">Sakit</span>
                            </label>
                        </div>
                    </div>

                    {{-- Catatan --}}
                    <div>
                        <label for="catatan_presensi" class="label">
                            Catatan <span class="text-xs font-normal text-slate-400">(opsional)</span>
                        </label>
                        <textarea id="catatan_presensi"
                                  name="catatan"
                                  x-model="catatan"
                                  rows="2"
                                  class="input resize-none"
                                  placeholder="Tuliskan keterangan jika izin, sakit, atau ada informasi penting..."></textarea>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" class="btn-secondary" @click="buka = false">Batal</button>
                        <button type="submit" class="btn-primary">Kirim Presensi</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
