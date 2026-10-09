@props(['nama', 'ukuran' => 'h-9 w-9 text-xs'])

@php
    $inisial = \Illuminate\Support\Str::of($nama)->explode(' ')->filter()->take(2)
        ->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('');
@endphp

<span {{ $attributes->merge(['class' => "flex shrink-0 items-center justify-center rounded-full bg-linear-to-br from-blue-500 to-blue-700 font-semibold text-white {$ukuran}"]) }}>{{ $inisial }}</span>
