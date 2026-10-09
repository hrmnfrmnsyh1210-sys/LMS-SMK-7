<x-layouts.app :title="'Kuis · '.$kuis->judul">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="kuis" />

    @php $terkunci = $kuis->jawaban_count > 0; @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <div class="card card-body space-y-3 text-sm">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <x-badge warna="violet">{{ $kuis->label_jenis }}</x-badge>
                        <h3 class="mt-1 card-title">{{ $kuis->judul }}</h3>
                    </div>
                    <a href="{{ route('guru.kuis.edit', [$pengampu, $kuis]) }}" class="btn-secondary btn-sm"><x-icon name="cog" class="h-3.5 w-3.5" /> Atur</a>
                </div>
                <dl class="space-y-1.5">
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Dibuka</dt><dd class="text-right font-medium">{{ tanggal_indo($kuis->waktu_mulai, false, true) }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Ditutup</dt><dd class="text-right font-medium">{{ tanggal_indo($kuis->waktu_selesai, false, true) }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Durasi</dt><dd class="font-medium">{{ $kuis->durasi_menit }} menit</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Jumlah soal</dt><dd class="font-medium">{{ $kuis->soal->count() }} ({{ $kuis->soal->sum('bobot') }} poin)</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Acak soal</dt><dd class="font-medium">{{ $kuis->acak_soal ? 'Ya' : 'Tidak' }}</dd></div>
                    <div class="flex justify-between gap-2"><dt class="text-slate-500">Hasil ke siswa</dt><dd class="font-medium">{{ $kuis->tampilkan_hasil ? 'Ditampilkan' : 'Disembunyikan' }}</dd></div>
                </dl>
                <a href="{{ route('guru.kuis.hasil', [$pengampu, $kuis]) }}" class="btn-primary w-full">Lihat Hasil ({{ $kuis->jawaban_count }} siswa)</a>
            </div>

            @unless ($terkunci)
                <div class="card card-body space-y-3">
                    <h3 class="card-title">Import Soal dari Excel</h3>
                    <p class="text-xs text-slate-500">Kolom: tipe (pg/esai), pertanyaan, opsi_a–opsi_d, kunci_jawaban (a/b/c/d), bobot.</p>
                    <a href="{{ route('guru.soal.template') }}" class="btn-secondary btn-sm w-full"><x-icon name="download" class="h-3.5 w-3.5" /> Unduh template</a>
                    <form method="POST" action="{{ route('guru.soal.import', [$pengampu, $kuis]) }}" enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                               class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700">
                        @error('file') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <button type="submit" class="btn-primary btn-sm w-full"><x-icon name="upload" class="h-3.5 w-3.5" /> Import</button>
                    </form>
                    @if (session('gagal_import'))
                        <div class="rounded-lg bg-red-50 p-3 text-xs text-red-700">
                            <div class="mb-1 font-semibold">{{ count(session('gagal_import')) }} baris gagal:</div>
                            <ul class="list-inside list-disc space-y-0.5">
                                @foreach (session('gagal_import') as $g)
                                    <li>Baris {{ $g['baris'] }}: {{ implode(' ', $g['alasan']) }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endunless
        </div>

        <div class="card lg:col-span-2">
            <div class="card-header">
                <h3 class="card-title">Bank Soal</h3>
                @unless ($terkunci)
                    <a href="{{ route('guru.soal.create', [$pengampu, $kuis]) }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-3.5 w-3.5" /> Tambah Soal</a>
                @endunless
            </div>
            @if ($terkunci)
                <div class="mx-4 mb-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 sm:mx-6">Soal terkunci karena sudah ada siswa yang mengerjakan.</div>
            @endif
            <ol class="space-y-3 px-4 pb-5 sm:px-6">
                @forelse ($kuis->soal as $s)
                    <li class="rounded-xl border border-slate-100 p-4">
                        <div class="flex items-start gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-xs font-bold text-blue-700">{{ $loop->iteration }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="mb-1 flex flex-wrap gap-1.5">
                                    <x-badge :warna="$s->isPilihanGanda() ? 'sky' : 'amber'">{{ $s->isPilihanGanda() ? 'Pilihan ganda' : 'Esai' }}</x-badge>
                                    <x-badge>{{ $s->bobot }} poin</x-badge>
                                </div>
                                <p class="text-sm whitespace-pre-line text-slate-800">{{ $s->pertanyaan }}</p>
                                @if ($s->isPilihanGanda())
                                    <ul class="mt-2 grid gap-1 text-sm sm:grid-cols-2">
                                        @foreach (\App\Models\Soal::OPSI as $o)
                                            <li @class(['rounded-lg px-2.5 py-1', 'bg-emerald-50 font-medium text-emerald-700' => $s->kunci_jawaban === $o, 'text-slate-600' => $s->kunci_jawaban !== $o])>
                                                {{ strtoupper($o) }}. {{ $s->{'opsi_'.$o} }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            @unless ($terkunci)
                                <div class="flex shrink-0 gap-1">
                                    <a href="{{ route('guru.soal.edit', [$pengampu, $kuis, $s]) }}" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Ubah"><x-icon name="pencil" class="h-4 w-4" /></a>
                                    <x-confirm :action="route('guru.soal.destroy', [$pengampu, $kuis, $s])" message="Soal nomor {{ $loop->iteration }} akan dihapus."
                                               button-class="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </x-confirm>
                                </div>
                            @endunless
                        </div>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-slate-500">Belum ada soal. Tambahkan manual atau import dari Excel.</li>
                @endforelse
            </ol>
        </div>
    </div>
</x-layouts.app>
