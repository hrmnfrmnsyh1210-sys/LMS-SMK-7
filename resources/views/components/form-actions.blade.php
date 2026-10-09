@props(['batal', 'label' => 'Simpan'])

<div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
    <a href="{{ $batal }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">{{ $label }}</button>
</div>
