@props(['daftar', 'tampilMapel' => true, 'tautan' => true])

{{-- Daftar kartu kuis siswa; $daftar berisi ['kuis', 'jawaban', 'status'] (lihat AkademikSiswaService). $tautan = false untuk tampilan baca-saja (orang tua). --}}
@php
    $gaya = [
        'tersedia' => ['blue', 'rocket', 'from-blue-300 to-pink-300'],
        'dikerjakan' => ['amber', 'clock', 'from-amber-200 to-orange-300'],
        'akan_datang' => ['slate', 'calendar', 'from-slate-200 to-slate-300'],
        'dikumpulkan' => ['emerald', 'check-badge', 'from-emerald-200 to-teal-300'],
        'terlewat' => ['rose', 'warning', 'from-rose-200 to-pink-300'],
    ];
@endphp

<div class="grid gap-4 md:grid-cols-2">
    @forelse ($daftar as ['kuis' => $k, 'jawaban' => $j, 'status' => $status])
        @php [$warna, $ikon, $gradasi] = $gaya[$status]; @endphp
        <{{ $tautan ? 'a' : 'div' }} @if ($tautan) href="{{ route('siswa.kuis.show', [$k->pengampu_id, $k]) }}" @endif @class(['card group flex flex-col gap-4 p-5', 'transition hover:-translate-y-0.5 hover:shadow-md' => $tautan])>
            <div class="flex items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br text-white shadow-sm {{ $gradasi }}">
                    <x-icon :name="$ikon" class="h-6 w-6" />
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge warna="violet">{{ $k->label_jenis }}</x-badge>
                        @if ($tampilMapel)
                            <span class="truncate text-xs text-slate-500">{{ $k->pengampu->mapel->nama_mapel }}</span>
                        @endif
                    </div>
                    <div class="mt-1 truncate font-semibold text-slate-800 group-hover:text-blue-700">{{ $k->judul }}</div>
                </div>
                @if ($status === 'dikumpulkan' && $k->tampilkan_hasil && $j?->total_skor !== null)
                    <div class="text-right">
                        <div class="text-xl font-bold text-emerald-600">{{ (float) $j->total_skor }}</div>
                        <div class="text-[11px] text-slate-400">skor</div>
                    </div>
                @endif
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1"><x-icon name="calendar" class="h-3.5 w-3.5" /> {{ tanggal_indo($k->waktu_mulai, false, true) }} – {{ $k->waktu_selesai->format('d/m H:i') }}</span>
                <span class="inline-flex items-center gap-2">
                    <span class="inline-flex items-center gap-1"><x-icon name="clock" class="h-3.5 w-3.5" /> {{ $k->durasi_menit }} mnt</span>
                    <x-badge :warna="$warna">{{ \App\Models\Kuis::STATUS_SISWA[$status] }}</x-badge>
                </span>
            </div>
        </{{ $tautan ? 'a' : 'div' }}>
    @empty
        <div class="card card-body col-span-full text-center text-sm text-slate-500">Belum ada kuis atau ujian.</div>
    @endforelse
</div>
