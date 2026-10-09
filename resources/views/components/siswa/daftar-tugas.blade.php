@props(['daftar', 'tampilMapel' => true, 'tautan' => true])

{{-- Daftar kartu tugas siswa; $daftar berisi ['tugas', 'pengumpulan', 'status'] (lihat AkademikSiswaService). $tautan = false untuk tampilan baca-saja (orang tua). --}}
@php
    $gaya = [
        'belum' => ['amber', 'clock', 'from-amber-200 to-orange-300'],
        'dikumpulkan' => ['sky', 'check', 'from-sky-200 to-blue-300'],
        'dinilai' => ['emerald', 'check-badge', 'from-emerald-200 to-teal-300'],
        'terlewat' => ['rose', 'warning', 'from-rose-200 to-pink-300'],
    ];
@endphp

<div class="space-y-3">
    @forelse ($daftar as ['tugas' => $t, 'pengumpulan' => $p, 'status' => $status])
        @php [$warna, $ikon, $gradasi] = $gaya[$status]; @endphp
        <{{ $tautan ? 'a' : 'div' }} @if ($tautan) href="{{ route('siswa.tugas.show', [$t->pengampu_id, $t]) }}" @endif @class(['card group flex items-center gap-4 p-4 sm:p-5', 'transition hover:-translate-y-0.5 hover:shadow-md' => $tautan])>
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br text-white shadow-sm {{ $gradasi }}">
                <x-icon :name="$ikon" class="h-6 w-6" />
            </span>
            <div class="min-w-0 flex-1">
                <div class="truncate font-semibold text-slate-800 group-hover:text-blue-700">{{ $t->judul }}</div>
                <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                    @if ($tampilMapel)
                        <span class="inline-flex items-center gap-1"><x-icon name="book" class="h-3.5 w-3.5" /> {{ $t->pengampu->mapel->nama_mapel }}</span>
                    @endif
                    <span @class(['inline-flex items-center gap-1', 'font-semibold text-rose-500' => $status === 'belum' && $t->deadline->diffInHours(now(), true) < 24])>
                        <x-icon name="calendar" class="h-3.5 w-3.5" /> {{ tanggal_indo($t->deadline, false, true) }}
                        @if ($status === 'belum')
                            · {{ $t->deadline->locale('id')->diffForHumans() }}
                        @endif
                    </span>
                </div>
            </div>
            <div class="shrink-0 text-right">
                @if ($status === 'dinilai')
                    <div class="text-xl font-bold text-emerald-600">{{ (float) $p->nilai }}</div>
                    <div class="text-[11px] text-slate-400">dari {{ $t->nilai_maks }}</div>
                @else
                    <x-badge :warna="$warna">{{ \App\Models\Tugas::STATUS_SISWA[$status] }}</x-badge>
                @endif
            </div>
        </{{ $tautan ? 'a' : 'div' }}>
    @empty
        <div class="card card-body text-center text-sm text-slate-500">Belum ada tugas.</div>
    @endforelse
</div>
