@props(['name' => 'file', 'label' => 'File', 'pathLama' => null, 'help' => null, 'accept' => null])

<div>
    <label for="{{ $name }}" class="label">{{ $label }}</label>
    @if ($pathLama)
        <div class="mb-2 flex flex-wrap items-center gap-3 rounded-lg bg-slate-50 px-3 py-2 text-sm">
            <x-icon name="document" class="h-4 w-4 text-blue-600" />
            <a href="{{ file_url($pathLama) }}" target="_blank" class="link truncate">{{ basename($pathLama) }}</a>
            <label class="ml-auto flex items-center gap-1.5 text-xs text-red-600">
                <input type="checkbox" name="hapus_file" value="1" class="rounded border-slate-300"> Hapus file
            </label>
        </div>
    @endif
    <input type="file" name="{{ $name }}" id="{{ $name }}" @if ($accept) accept="{{ $accept }}" @endif
           class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
    <p class="mt-1 text-xs text-slate-500">{{ $help ?? 'Maksimal '.round(config('lms.upload_max_kb') / 1024).' MB.' }}{{ $pathLama ? ' Unggah file baru untuk mengganti.' : '' }}</p>
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
