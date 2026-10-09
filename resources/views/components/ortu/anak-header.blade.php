@props(['anak', 'judul', 'subjudul' => null])

{{-- Kepala halaman pantauan: judul, anak yang sedang dipantau, dan tombol ganti anak (bila lebih dari satu). --}}
@php
    $semuaAnak = auth()->user()->ortu->anak;
    $kelas = $anak->kelasPada();
@endphp

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-xl font-semibold text-slate-800">{{ $judul }}</h2>
        @if ($subjudul)
            <p class="mt-0.5 text-sm text-slate-500">{{ $subjudul }}</p>
        @endif
    </div>

    <div class="relative" x-data="{ buka: false }" @click.outside="buka = false">
        <button type="button" @click="buka = !buka" @disabled($semuaAnak->count() < 2)
                class="flex items-center gap-3 rounded-2xl border border-blue-100 bg-white py-2 pr-3 pl-2 text-left shadow-sm transition enabled:hover:border-blue-200">
            <x-avatar :nama="$anak->nama" />
            <span>
                <span class="block text-sm font-semibold text-slate-800">{{ $anak->nama }}</span>
                <span class="block text-xs text-slate-500">{{ $kelas?->nama_kelas ?? 'Belum ada kelas' }} · NIS {{ $anak->nis }}</span>
            </span>
            @if ($semuaAnak->count() > 1)
                <x-icon name="chevron-down" class="h-4 w-4 text-slate-400" />
            @endif
        </button>
        @if ($semuaAnak->count() > 1)
            <div x-show="buka" x-cloak x-transition class="absolute right-0 z-20 mt-2 w-64 rounded-2xl border border-slate-100 bg-white p-1.5 shadow-xl">
                <div class="px-3 py-1.5 text-xs font-semibold text-slate-400">Ganti anak</div>
                @foreach ($semuaAnak as $a)
                    <form method="POST" action="{{ route('ortu.anak.pilih', $a) }}">
                        @csrf
                        <button type="submit" @class(['flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-50', 'font-semibold text-blue-700' => $a->id === $anak->id])>
                            @if ($a->id === $anak->id) <x-icon name="check" class="h-4 w-4" /> @else <span class="w-4"></span> @endif
                            {{ $a->nama }}
                        </button>
                    </form>
                @endforeach
            </div>
        @endif
    </div>
</div>
