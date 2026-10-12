@props(['title' => null])

@php
    $logo = $sekolah['logo'] ? file_url($sekolah['logo']) : null;
    $latar = file_url($sekolah['web_hero_latar']);
    $namaSingkat = $sekolah['web_nama_singkat'] ?: $sekolah['nama_sekolah'];
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — ' : '' }}{{ $sekolah['nama_sekolah'] }}</title>
    @if ($logo)
        <link rel="icon" href="{{ $logo }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700|roboto-slab:500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-700 antialiased">
    <div class="grid min-h-screen lg:grid-cols-12">
        {{-- ================= PANEL FOTO (desktop) ================= --}}
        <aside class="relative isolate hidden flex-col overflow-hidden bg-navy-800 text-white lg:col-span-7 lg:flex">
            @if ($latar)
                <img src="{{ $latar }}" alt="" class="absolute inset-0 -z-30 h-full w-full object-cover">
            @endif
            <div @class([
                'absolute inset-0 -z-20',
                'bg-linear-to-b from-navy-900/80 via-navy-800/30 via-40% to-navy-950/85' => $latar,
                'bg-linear-to-b from-navy-800 to-navy-900' => ! $latar,
            ])></div>
            <div aria-hidden="true"
                 class="pointer-events-none absolute inset-x-0 top-[18%] -z-10 bg-linear-to-b from-white/50 via-white/20 to-white/0 bg-clip-text text-center font-display leading-none font-bold tracking-tight whitespace-nowrap text-transparent uppercase select-none"
                 style="font-size: clamp(3rem, {{ round(64 / max(mb_strlen($namaSingkat), 4), 1) }}vw, 11rem)">{{ $namaSingkat }}</div>

            <a href="{{ route('beranda') }}" class="flex items-center gap-3 p-10 xl:px-14">
                @if ($logo)
                    <img src="{{ $logo }}" alt="Logo {{ $sekolah['nama_sekolah'] }}" class="h-11 w-11 object-contain">
                @else
                    <x-icon name="academic-cap" class="h-10 w-10" />
                @endif
                <span class="leading-tight">
                    <span class="block text-[10px] font-medium tracking-[0.2em] uppercase opacity-75">Website Resmi</span>
                    <span class="block font-display text-lg font-bold">{{ $sekolah['nama_sekolah'] }}</span>
                </span>
            </a>

            <div class="mt-auto max-w-xl p-10 [text-shadow:0_2px_16px_rgba(5,22,51,0.45)] xl:p-14">
                <p class="flex items-center gap-2 text-sm font-medium text-kuning-300">
                    <x-icon name="academic-cap" class="h-5 w-5" /> Learning Management System
                </p>
                <h1 class="mt-3 font-display text-4xl leading-[1.15] font-bold xl:text-5xl">Belajar lebih teratur, kapan saja dan di mana saja.</h1>
                <p class="mt-4 text-white/80">Materi, tugas, kuis, presensi, dan nilai dalam satu sistem untuk siswa, guru, orang tua, dan sekolah.</p>
            </div>
        </aside>

        {{-- ================= PANEL FORM ================= --}}
        <main class="flex flex-col lg:col-span-5">
            {{-- Kepala ringkas (HP) --}}
            <div class="relative isolate overflow-hidden bg-navy-800 px-5 pt-6 pb-10 text-white lg:hidden">
                @if ($latar)
                    <img src="{{ $latar }}" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover">
                    <div class="absolute inset-0 -z-10 bg-navy-900/75"></div>
                @endif
                <a href="{{ route('beranda') }}" class="flex items-center gap-3">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="Logo" class="h-10 w-10 object-contain">
                    @else
                        <x-icon name="academic-cap" class="h-9 w-9" />
                    @endif
                    <span class="font-display text-lg leading-tight font-bold">{{ $sekolah['nama_sekolah'] }}</span>
                </a>
                <p class="mt-4 text-sm text-kuning-300">Learning Management System</p>
            </div>

            <div class="hidden justify-end p-8 lg:flex">
                <a href="{{ route('beranda') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-navy-700">
                    <x-icon name="chevron-left" class="h-4 w-4" /> Kembali ke website
                </a>
            </div>

            <div class="flex flex-1 items-center justify-center px-5 py-10 sm:px-10 lg:py-0">
                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>

            <div class="flex items-center justify-between gap-4 px-5 py-6 text-xs text-slate-400 sm:px-10 lg:px-8">
                <span>&copy; {{ date('Y') }} {{ $sekolah['nama_sekolah'] }}</span>
                <a href="{{ route('beranda') }}" class="font-medium text-slate-500 hover:text-navy-700 lg:hidden">Kembali ke website</a>
            </div>
        </main>
    </div>
</body>
</html>
