@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'help' => null, 'required' => false])

{{-- $options: [nilai => label]. --}}
@php
    $id = $attributes->get('id', str_replace(['[', ']'], ['_', ''], $name));
    $kunciError = str_replace(['[', ']'], ['.', ''], $name);
    $terpilih = (string) old($kunciError, $value);
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <select name="{{ $name }}" id="{{ $id }}" @required($required)
            {{ $attributes->except('id')->class(['input', 'input-error' => $errors->has($kunciError)]) }}>
        @if (! is_null($placeholder))
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $nilai => $teks)
            <option value="{{ $nilai }}" @selected($terpilih === (string) $nilai)>{{ $teks }}</option>
        @endforeach
    </select>
    @if ($help)
        <p class="mt-1 text-xs text-slate-500">{{ $help }}</p>
    @endif
    @error($kunciError)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
