@props([
    'action',
    'method' => 'DELETE',
    'message' => 'Data yang dihapus tidak dapat dikembalikan.',
    'title' => 'Hapus data?',
    'confirmLabel' => 'Ya, hapus',
    'buttonClass' => 'btn-danger btn-sm',
    'confirmClass' => 'btn-danger',
])

{{-- Tombol + modal konfirmasi (Alpine). Isi slot = label tombol. --}}
<div x-data="{ buka: false }" class="inline">
    <button type="button" class="{{ $buttonClass }}" @click="buka = true">{{ $slot }}</button>

    <template x-teleport="body">
        <div x-show="buka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="buka = false">
            <div class="absolute inset-0 bg-slate-900/50" @click="buka = false"></div>
            <div x-show="buka" x-transition class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <x-icon name="warning" class="h-5 w-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-slate-800">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $message }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ $action }}" class="mt-6 flex justify-end gap-2">
                    @csrf
                    @if (strtoupper($method) !== 'POST')
                        @method($method)
                    @endif
                    <button type="button" class="btn-secondary" @click="buka = false">Batal</button>
                    <button type="submit" class="{{ $confirmClass }}">{{ $confirmLabel }}</button>
                </form>
            </div>
        </div>
    </template>
</div>
