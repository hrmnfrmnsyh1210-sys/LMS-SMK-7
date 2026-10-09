@props(['label', 'value', 'ikon' => 'info', 'warna' => 'blue', 'keterangan' => null, 'href' => null])

@php
    // [latar ikon, warna ikon & keterangan]
    [$latar, $aksen] = [
        'blue' => ['bg-blue-50', 'text-blue-600'],
        'sky' => ['bg-sky-50', 'text-sky-600'],
        'emerald' => ['bg-emerald-50', 'text-emerald-600'],
        'violet' => ['bg-violet-50', 'text-violet-600'],
        'amber' => ['bg-amber-50', 'text-amber-600'],
        'rose' => ['bg-rose-50', 'text-rose-600'],
        'slate' => ['bg-slate-100', 'text-slate-600'],
    ][$warna] ?? ['bg-blue-50', 'text-blue-600'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card flex items-center gap-3 p-4'.($href ? ' transition hover:-translate-y-0.5 hover:shadow-md' : '')]) }}>
    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $latar }} {{ $aksen }}">
        <x-icon :name="$ikon" class="h-6 w-6" />
    </div>
    <div class="min-w-0">
        <div class="truncate text-sm font-medium text-slate-600">{{ $label }}</div>
        <div class="text-2xl font-bold text-slate-800">{{ $value }}</div>
        @if ($keterangan)
            <div class="truncate text-xs font-medium {{ $aksen }}">{{ $keterangan }}</div>
        @endif
    </div>
</{{ $tag }}>
