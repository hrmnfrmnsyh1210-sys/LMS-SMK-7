{{-- Unggah gambar dengan pratinjau. Variabel: $name, $label, $path (lama), $help, $hapusName (opsional), $tinggi, $pas (object-cover/object-contain). --}}
@php
    $tinggi ??= 'h-40';
    $pas ??= 'object-cover';
@endphp

<div x-data="{ pratinjau: @js(file_url($path)), hapus: false }" class="space-y-2">
    <span class="label">{{ $label }}</span>
    <div class="flex {{ $tinggi }} items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50">
        <template x-if="pratinjau && !hapus"><img :src="pratinjau" alt="" class="h-full w-full {{ $pas }}"></template>
        <template x-if="!pratinjau || hapus">
            <span class="flex flex-col items-center gap-1 text-sm text-slate-400"><x-icon name="photo" class="h-8 w-8" /> Belum ada gambar</span>
        </template>
    </div>
    <input type="file" name="{{ $name }}" accept="image/png,image/jpeg,image/webp"
           @change="if ($event.target.files[0]) { pratinjau = URL.createObjectURL($event.target.files[0]); hapus = false }"
           class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
    <p class="text-xs text-slate-500">{{ $help }}</p>
    @if ($path && ! empty($hapusName))
        <label class="flex items-center gap-1.5 text-xs text-red-600">
            <input type="checkbox" name="{{ $hapusName }}" value="{{ $hapusValue ?? 1 }}" x-model="hapus" class="rounded border-slate-300"> Hapus gambar
        </label>
    @endif
    @error($name) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
</div>
