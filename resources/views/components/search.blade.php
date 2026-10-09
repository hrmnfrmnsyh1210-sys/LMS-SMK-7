@props(['placeholder' => 'Cari...', 'value' => null])

{{-- Form pencarian GET; filter tambahan diletakkan di slot. --}}
<form method="GET" {{ $attributes->merge(['class' => 'flex flex-col gap-2 sm:flex-row sm:items-center']) }}>
    <div class="relative sm:w-72">
        <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-slate-400" />
        <input type="search" name="q" value="{{ $value ?? request('q') }}" placeholder="{{ $placeholder }}" class="input pl-9">
    </div>
    {{ $slot }}
    <div class="flex gap-2">
        <button type="submit" class="btn-secondary">Cari</button>
        @if (request()->except('page'))
            <a href="{{ url()->current() }}" class="btn text-slate-500 hover:text-slate-800">Reset</a>
        @endif
    </div>
</form>
