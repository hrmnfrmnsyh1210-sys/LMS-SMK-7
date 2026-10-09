@props(['name', 'label' => null, 'value' => null, 'rows' => 3, 'help' => null, 'required' => false])

@php $id = $attributes->get('id', $name); @endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-500">*</span>@endif</label>
    @endif
    <textarea name="{{ $name }}" id="{{ $id }}" rows="{{ $rows }}" @required($required)
              {{ $attributes->except('id')->class(['input', 'input-error' => $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
    @if ($help)
        <p class="mt-1 text-xs text-slate-500">{{ $help }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
