<x-layouts.app :title="$kuis->label_jenis.' · '.$kuis->judul">
    <x-siswa.mapel-header :pengampu="$pengampu" aktif="kuis" />

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="card card-body space-y-5 lg:col-span-3">
            <div>
                <x-badge warna="violet">{{ $kuis->label_jenis }}</x-badge>
                <h2 class="mt-2 text-xl font-bold text-slate-800">{{ $kuis->judul }}</h2>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['calendar', 'Dibuka', tanggal_indo($kuis->waktu_mulai, true, true), 'bg-blue-50 text-blue-700'],
                    ['calendar', 'Ditutup', tanggal_indo($kuis->waktu_selesai, true, true), 'bg-pink-50 text-pink-700'],
                    ['clock', 'Durasi', $kuis->durasi_menit.' menit', 'bg-amber-50 text-amber-700'],
                    ['list', 'Jumlah soal', $kuis->soal_count.' soal'.($kuis->acak_soal ? ' · diacak' : ''), 'bg-emerald-50 text-emerald-700'],
                ] as [$ikon, $label, $nilai, $warna])
                    <div class="flex items-center gap-3 rounded-2xl p-3 {{ $warna }}">
                        <x-icon :name="$ikon" class="h-5 w-5 shrink-0" />
                        <div class="text-sm">
                            <div class="text-xs opacity-75">{{ $label }}</div>
                            <div class="font-semibold">{{ $nilai }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if (in_array($status, ['tersedia', 'dikerjakan'], true))
                <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 text-sm text-slate-600">
                    <div class="mb-2 inline-flex items-center gap-1.5 font-semibold text-slate-700"><x-icon name="info" class="h-4 w-4" /> Sebelum mulai</div>
                    <ul class="list-inside list-disc space-y-1">
                        <li>Waktu berjalan sejak kamu menekan tombol mulai dan tidak bisa dijeda.</li>
                        <li>Jawaban tersimpan otomatis setiap kamu memilih atau mengetik.</li>
                        <li>Berpindah tab atau aplikasi akan tercatat dan terlihat oleh guru.</li>
                        <li>Jika waktu habis, jawaban yang tersimpan dikirim otomatis.</li>
                    </ul>
                </div>
            @endif
        </div>

        <div class="lg:col-span-2">
            <div class="card relative overflow-hidden p-6 text-center">
                @switch($status)
                    @case('tersedia')
                        <x-icon name="rocket" class="mx-auto h-14 w-14 text-blue-400" />
                        <p class="mt-3 font-semibold text-slate-800">Siap mengerjakan?</p>
                        <p class="mt-1 text-sm text-slate-500">Kuis ditutup {{ $kuis->waktu_selesai->locale('id')->diffForHumans() }}.</p>
                        <form method="POST" action="{{ route('siswa.kuis.mulai', [$pengampu, $kuis]) }}" class="mt-5"
                              x-data @submit="if (!confirm('Mulai sekarang? Timer {{ $kuis->durasi_menit }} menit akan langsung berjalan.')) $event.preventDefault()">
                            @csrf
                            <button type="submit" class="btn-primary w-full" @disabled($kuis->soal_count === 0)>
                                <x-icon name="rocket" class="h-4 w-4" /> Mulai Kerjakan
                            </button>
                        </form>
                        @break

                    @case('dikerjakan')
                        <x-icon name="clock" class="mx-auto h-14 w-14 text-amber-400" />
                        <p class="mt-3 font-semibold text-slate-800">Kamu sedang mengerjakan</p>
                        <p class="mt-1 text-sm text-slate-500">Lanjutkan sebelum waktumu habis.</p>
                        <a href="{{ route('siswa.kuis.kerjakan', [$pengampu, $kuis]) }}" class="btn-primary mt-5 w-full">Lanjutkan</a>
                        @break

                    @case('akan_datang')
                        <x-icon name="calendar" class="mx-auto h-14 w-14 text-slate-300" />
                        <p class="mt-3 font-semibold text-slate-800">Belum dibuka</p>
                        <p class="mt-1 text-sm text-slate-500">Dibuka {{ $kuis->waktu_mulai->locale('id')->diffForHumans() }}.</p>
                        @break

                    @case('terlewat')
                        <x-icon name="warning" class="mx-auto h-14 w-14 text-rose-300" />
                        <p class="mt-3 font-semibold text-slate-800">Kuis sudah ditutup</p>
                        <p class="mt-1 text-sm text-slate-500">Kamu tidak mengerjakan kuis ini.</p>
                        @break

                    @default
                        <x-icon name="check-badge" class="pointer-events-none absolute -top-4 -right-4 h-28 w-28 text-emerald-100" />
                        <p class="relative text-sm font-medium text-slate-500">Jawaban terkirim {{ tanggal_indo($jawaban->waktu_submit, false, true) }}</p>
                        @if (! $kuis->tampilkan_hasil)
                            <p class="relative mt-3 text-sm text-slate-600">Guru belum menampilkan hasil untuk kuis ini.</p>
                        @elseif ($jawaban->total_skor === null)
                            <p class="relative mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-amber-600"><x-icon name="clock" class="h-4 w-4" /> Menunggu koreksi esai oleh guru</p>
                        @else
                            <div class="relative mt-2 bg-linear-to-r from-blue-500 to-pink-400 bg-clip-text text-6xl font-bold text-transparent">{{ (float) $jawaban->total_skor }}</div>
                            <div class="relative text-xs text-slate-400">skor dari 100</div>
                            @if ($kuis->status_waktu !== 'selesai')
                                <p class="relative mt-3 text-xs text-slate-500">Pembahasan dibuka setelah kuis ditutup.</p>
                            @endif
                        @endif
                @endswitch
            </div>
        </div>
    </div>

    @if ($bolehPembahasan)
        @php $detail = $jawaban->detail->keyBy('soal_id'); @endphp
        <div class="space-y-3">
            <h3 class="card-title">Pembahasan</h3>
            @foreach ($soal as $s)
                @php
                    $d = $detail->get($s->id);
                    $benar = $s->isPilihanGanda() && $d?->jawaban === $s->kunci_jawaban;
                @endphp
                <div class="card p-4 sm:p-5">
                    <div class="flex items-start gap-3">
                        <span @class([
                            'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                            'bg-emerald-50 text-emerald-600' => $benar,
                            'bg-rose-50 text-rose-500' => $s->isPilihanGanda() && ! $benar,
                            'bg-blue-50 text-blue-600' => ! $s->isPilihanGanda(),
                        ])>{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1 space-y-2">
                            <p class="text-sm whitespace-pre-line text-slate-800">{{ $s->pertanyaan }}</p>
                            @if ($s->isPilihanGanda())
                                <div class="grid gap-1.5 sm:grid-cols-2">
                                    @foreach (\App\Models\Soal::OPSI as $o)
                                        <div @class([
                                            'rounded-xl px-3 py-2 text-sm ring-1',
                                            'bg-emerald-50 text-emerald-700 ring-emerald-200' => $o === $s->kunci_jawaban,
                                            'bg-rose-50 text-rose-600 ring-rose-200' => $o === $d?->jawaban && $o !== $s->kunci_jawaban,
                                            'text-slate-600 ring-slate-100' => $o !== $s->kunci_jawaban && $o !== $d?->jawaban,
                                        ])><strong>{{ strtoupper($o) }}.</strong> {{ $s->{'opsi_'.$o} }}</div>
                                    @endforeach
                                </div>
                            @else
                                <div class="rounded-xl bg-slate-50 p-3 text-sm whitespace-pre-line text-slate-700">{{ $d?->jawaban ?: '(tidak dijawab)' }}</div>
                            @endif
                        </div>
                        <div class="shrink-0 text-right text-sm">
                            <span class="font-bold text-slate-800">{{ (float) ($d?->skor ?? 0) }}</span><span class="text-slate-400">/{{ $s->bobot }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>
