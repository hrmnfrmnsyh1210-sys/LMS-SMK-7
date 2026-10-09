<x-layouts.app :title="'Tugas · '.$tugas->judul">
    <x-siswa.mapel-header :pengampu="$pengampu" aktif="tugas" />

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Detail tugas --}}
        <div class="card card-body space-y-4 lg:col-span-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <h2 class="text-xl font-bold text-slate-800">{{ $tugas->judul }}</h2>
                <x-badge :warna="['belum' => 'amber', 'dikumpulkan' => 'sky', 'dinilai' => 'emerald', 'terlewat' => 'rose'][$status]">
                    {{ \App\Models\Tugas::STATUS_SISWA[$status] }}
                </x-badge>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div @class(['flex items-center gap-3 rounded-2xl p-3', 'bg-rose-50 text-rose-700' => $tugas->sudahLewatDeadline(), 'bg-blue-50 text-blue-700' => ! $tugas->sudahLewatDeadline()])>
                    <x-icon name="calendar" class="h-5 w-5 shrink-0" />
                    <div class="text-sm">
                        <div class="text-xs opacity-75">Deadline</div>
                        <div class="font-semibold">{{ tanggal_indo($tugas->deadline, true, true) }}</div>
                        <div class="text-xs">{{ $tugas->deadline->locale('id')->diffForHumans() }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 rounded-2xl bg-pink-50 p-3 text-pink-700">
                    <x-icon name="star" class="h-5 w-5 shrink-0" />
                    <div class="text-sm">
                        <div class="text-xs opacity-75">Nilai maksimal</div>
                        <div class="font-semibold">{{ $tugas->nilai_maks }}</div>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="mb-1 text-sm font-semibold text-slate-700">Instruksi</h3>
                <p class="text-sm whitespace-pre-line text-slate-600">{{ $tugas->deskripsi }}</p>
            </div>

            @if ($tugas->file_path)
                <a href="{{ file_url($tugas->file_path) }}" target="_blank" class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                    <x-icon name="download" class="h-4 w-4" /> Lampiran: {{ \Illuminate\Support\Str::limit(basename($tugas->file_path), 40) }}
                </a>
            @endif
        </div>

        {{-- Pengumpulan --}}
        <div class="space-y-6 lg:col-span-2">
            @if ($pengumpulan?->nilai !== null)
                <div class="card relative overflow-hidden p-6 text-center">
                    <x-icon name="check-badge" class="pointer-events-none absolute -top-4 -right-4 h-28 w-28 text-emerald-100" />
                    <div class="relative text-sm font-medium text-slate-500">Nilai kamu</div>
                    <div class="relative mt-1 bg-linear-to-r from-emerald-500 to-teal-400 bg-clip-text text-5xl font-bold text-transparent">{{ (float) $pengumpulan->nilai }}</div>
                    <div class="relative text-xs text-slate-400">dari {{ $tugas->nilai_maks }}</div>
                    @if ($pengumpulan->feedback)
                        <div class="relative mt-4 rounded-2xl bg-slate-50 p-3 text-left text-sm text-slate-600">
                            <div class="mb-1 inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500"><x-icon name="megaphone" class="h-3.5 w-3.5" /> Catatan guru</div>
                            <p class="whitespace-pre-line">{{ $pengumpulan->feedback }}</p>
                        </div>
                    @endif
                </div>
            @endif

            @if ($pengumpulan)
                <div class="card card-body space-y-2">
                    <h3 class="card-title">Jawaban Terkirim</h3>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <x-icon name="clock" class="h-3.5 w-3.5" /> {{ tanggal_indo($pengumpulan->waktu_kumpul, false, true) }}
                        @if ($pengumpulan->waktu_kumpul->greaterThan($tugas->deadline))
                            <x-badge warna="rose">Terlambat</x-badge>
                        @endif
                    </div>
                    @if ($pengumpulan->file_path)
                        <a href="{{ file_url($pengumpulan->file_path) }}" target="_blank" class="link inline-flex items-center gap-1.5 text-sm"><x-icon name="document" class="h-4 w-4" /> {{ basename($pengumpulan->file_path) }}</a>
                    @endif
                    @if ($pengumpulan->jawaban_teks)
                        <p class="rounded-2xl bg-slate-50 p-3 text-sm whitespace-pre-line text-slate-700">{{ $pengumpulan->jawaban_teks }}</p>
                    @endif
                </div>
            @endif

            @if ($pengumpulan?->nilai === null)
                <form method="POST" action="{{ route('siswa.tugas.kumpul', [$pengampu, $tugas]) }}" enctype="multipart/form-data" class="card card-body space-y-4">
                    @csrf
                    <h3 class="card-title">{{ $pengumpulan ? 'Perbarui Jawaban' : 'Kumpulkan Tugas' }}</h3>
                    @if ($tugas->sudahLewatDeadline())
                        <p class="flex items-start gap-2 rounded-2xl bg-amber-50 p-3 text-xs text-amber-700">
                            <x-icon name="warning" class="h-4 w-4 shrink-0" /> Deadline sudah lewat. Pengumpulan tetap bisa dilakukan, tetapi ditandai terlambat.
                        </p>
                    @endif
                    <x-field.file name="file" label="File jawaban" :help="$pengumpulan?->file_path ? 'Unggah file baru untuk mengganti file sebelumnya.' : null" />
                    <x-field.textarea name="jawaban_teks" label="Jawaban teks (opsional jika mengunggah file)" rows="5" :value="$pengumpulan?->jawaban_teks" />
                    <button type="submit" class="btn-primary w-full"><x-icon name="upload" class="h-4 w-4" /> {{ $pengumpulan ? 'Kirim Ulang' : 'Kumpulkan' }}</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
