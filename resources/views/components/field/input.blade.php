@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])

@php
    $id = $attributes->get('id', str_replace(['[', ']'], ['_', ''], $name));
    $kunciError = str_replace(['[', ']'], ['.', ''], $name);
    $nilai = $type === 'password' ? null : old($kunciError, $value);
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}"
           @if (! is_null($nilai) && $type !== 'file') value="{{ $nilai }}" @endif
           @required($required)
           {{ $attributes->except('id')->class(['input', 'input-error' => $errors->has($kunciError)]) }}>
    @if ($help)
        <p class="mt-1 text-xs text-slate-500">{{ $help }}</p>
    @endif
    @error($kunciError)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
