@php
    $jenis = [
        'success' => ['bg-emerald-50 border-emerald-200 text-emerald-800', 'check'],
        'error' => ['bg-red-50 border-red-200 text-red-800', 'warning'],
        'warning' => ['bg-amber-50 border-amber-200 text-amber-800', 'warning'],
        'info' => ['bg-sky-50 border-sky-200 text-sky-800', 'info'],
    ];
@endphp

@foreach ($jenis as $kunci => [$kelas, $ikon])
    @if (session($kunci))
        <div x-data="{ tampil: true }" x-show="tampil" x-transition
             class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $kelas }}" role="alert">
            <x-icon :name="$ikon" class="mt-0.5 h-5 w-5 shrink-0" />
            <div class="flex-1">{{ session($kunci) }}</div>
            <button type="button" class="opacity-60 hover:opacity-100" @click="tampil = false" aria-label="Tutup">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    @endif
@endforeach
