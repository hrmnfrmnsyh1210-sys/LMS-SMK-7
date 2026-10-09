@props(['title' => null])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — ' : '' }}{{ $sekolah['nama_sekolah'] }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-latar font-sans text-slate-800 antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Panel biru (desktop) --}}
        <div class="relative hidden flex-col justify-between overflow-hidden bg-sidebar p-12 text-white lg:flex">
            <div class="flex items-center gap-3">
                @if ($sekolah['logo'])
                    <img src="{{ file_url($sekolah['logo']) }}" alt="Logo" class="h-12 w-12 rounded-xl bg-white object-contain p-1">
                @else
                    <x-icon name="academic-cap" class="h-11 w-11" />
                @endif
                <div>
                    <div class="text-xl font-bold">{{ $sekolah['nama_sekolah'] }}</div>
                    <div class="text-sm text-blue-100">Learning Management System</div>
                </div>
            </div>

            <div class="relative z-10 max-w-md">
                <h2 class="text-3xl leading-snug font-bold">Belajar lebih teratur, kapan saja dan di mana saja.</h2>
                <p class="mt-3 text-blue-100">Materi, tugas, kuis, presensi, dan nilai dalam satu sistem untuk siswa, guru, dan sekolah.</p>
            </div>

            <x-ilustrasi-buku class="absolute -right-6 -bottom-4 w-96 opacity-95" />
            <p class="relative z-10 text-xs text-blue-100">&copy; {{ date('Y') }} {{ $sekolah['nama_sekolah'] }}</p>
        </div>

        {{-- Form --}}
        <div class="flex flex-col items-center justify-center px-4 py-10">
            <div class="mb-6 flex flex-col items-center text-center lg:hidden">
                <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-linear-to-br from-blue-600 to-blue-500 text-white">
                    <x-icon name="academic-cap" class="h-9 w-9" />
                </div>
                <h1 class="text-xl font-bold">{{ $sekolah['nama_sekolah'] }}</h1>
                <p class="text-sm text-slate-500">Learning Management System</p>
            </div>

            <div class="card w-full max-w-md">
                <div class="p-6 sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>
