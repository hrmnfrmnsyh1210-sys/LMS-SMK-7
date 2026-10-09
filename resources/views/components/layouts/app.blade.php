@props(['title' => null])

@php
    $user = auth()->user();
    $adalahWali = $user->role === 'guru' && $user->guru?->kelasWaliAktif();
    $menu = collect(config('menu.'.$user->role, []))
        ->reject(fn ($item) => ($item['syarat'] ?? null) === 'wali' && ! $adalahWali)
        ->all();
    $logo = $sekolah['logo'] ? file_url($sekolah['logo']) : null;
    $profil = $user->profil();
    $keteranganUser = match ($user->role) {
        'siswa' => trim('NIS '.$profil?->nis.' · '.($profil?->kelasPada()?->nama_kelas ?? '')),
        'guru' => $profil?->nip ? 'NIP '.$profil->nip : 'Guru',
        default => $user->label_role,
    };
    $inisial = \Illuminate\Support\Str::of($user->nama)->explode(' ')->filter()->take(2)->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('');
    $notifikasi = $user->unreadNotifications()->latest()->limit(5)->get();
    $jumlahNotif = $user->unreadNotifications()->count();
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
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-latar font-sans text-slate-800 antialiased"
      x-data="{ sidebar: false, ciut: localStorage.getItem('sidebar-ciut') === '1' }"
      x-init="$watch('ciut', v => localStorage.setItem('sidebar-ciut', v ? '1' : '0'))">

    {{-- Overlay drawer (HP) --}}
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" @click="sidebar = false"></div>

    {{-- ================= SIDEBAR ================= --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-linear-to-b lg:translate-x-0 from-blue-700 via-blue-600 to-blue-500 transition-transform duration-200"
           :class="{ 'translate-x-0': sidebar, 'lg:-translate-x-full!': ciut }">
        {{-- Identitas sekolah --}}
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center px-5 pt-7 pb-6 text-center text-white">
            @if ($logo)
                <img src="{{ $logo }}" alt="Logo" class="mb-3 h-14 w-14 rounded-xl bg-white object-contain p-1 shadow-lg shadow-blue-900/20">
            @else
                <x-icon name="academic-cap" class="mb-2 h-12 w-12" />
            @endif
            <div class="text-xl leading-tight font-bold tracking-wide">{{ $sekolah['nama_sekolah'] }}</div>
            <div class="mt-1 text-xs text-blue-100">Learning Management System</div>
        </a>

        {{-- Panel menu putih --}}
        <div class="ml-1.5 flex min-h-0 flex-1 flex-col rounded-tl-3xl bg-white">
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5 text-sm">
                @foreach ($menu as $item)
                    @if (isset($item['judul']))
                        <div class="px-3 pt-4 pb-1 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ $item['judul'] }}</div>
                    @else
                        @php $aktif = request()->routeIs(...(array) ($item['aktif'] ?? $item['route'])); @endphp
                        <a href="{{ route($item['route']) }}"
                           @class([
                               'flex items-center gap-3 rounded-xl px-3.5 py-2.5 font-medium transition',
                               'bg-blue-600 text-white shadow-md shadow-blue-600/30' => $aktif,
                               'text-slate-600 hover:bg-blue-50 hover:text-blue-700' => ! $aktif,
                           ])>
                            <x-icon :name="$item['ikon']" class="h-5 w-5 shrink-0" />
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="border-t border-slate-100 p-3">
                <div class="mb-2 rounded-xl bg-blue-50 px-3.5 py-2.5 text-xs text-slate-500">
                    Tahun ajaran aktif<br>
                    <span class="font-semibold text-blue-700">{{ $tahunAktif?->label ?? 'Belum diatur' }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium text-red-500 transition hover:bg-red-50">
                        <x-icon name="logout" class="h-5 w-5" /> Keluar
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ================= KONTEN ================= --}}
    <div class="flex min-h-screen flex-col transition-[padding] duration-200 lg:pl-64" :class="ciut && 'lg:pl-0!'">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-100 bg-white px-4 sm:px-6">
            <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100"
                    @click="window.innerWidth >= 1024 ? ciut = !ciut : sidebar = true" aria-label="Menu">
                <x-icon name="menu" class="h-6 w-6" />
            </button>

            <h1 class="truncate text-lg font-semibold text-slate-800">{{ $title }}</h1>

            <div class="ml-auto flex items-center gap-2 sm:gap-4">
                @if ($user->role === 'operator')
                    <form method="GET" action="{{ route('operator.siswa.index') }}" class="relative hidden md:block">
                        <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-slate-400" />
                        <input type="search" name="q" placeholder="Cari siswa..."
                               class="w-56 rounded-lg border border-slate-200 bg-slate-50 py-2 pr-3 pl-9 text-sm placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 focus:outline-none lg:w-64">
                    </form>
                @endif

                {{-- Notifikasi --}}
                <div class="relative" x-data="{ buka: false }" @click.outside="buka = false">
                    <button type="button" class="relative rounded-full p-2 text-slate-600 hover:bg-slate-100" @click="buka = !buka" aria-label="Notifikasi">
                        <x-icon name="bell" class="h-6 w-6" />
                        @if ($jumlahNotif)
                            <span class="absolute top-0.5 right-0.5 flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] font-bold text-white ring-2 ring-white">{{ $jumlahNotif }}</span>
                        @endif
                    </button>
                    <div x-show="buka" x-cloak x-transition class="absolute right-0 mt-2 w-80 rounded-2xl border border-slate-100 bg-white p-2 shadow-xl">
                        <div class="px-3 py-2 text-sm font-semibold">Notifikasi</div>
                        @forelse ($notifikasi as $notif)
                            <div class="rounded-lg px-3 py-2 text-sm hover:bg-slate-50">{{ $notif->data['pesan'] ?? 'Notifikasi baru' }}</div>
                        @empty
                            <div class="px-3 py-6 text-center text-sm text-slate-500">Tidak ada notifikasi baru.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Profil --}}
                <div class="relative" x-data="{ buka: false }" @click.outside="buka = false">
                    <button type="button" class="flex items-center gap-3 rounded-xl py-1 pr-1 pl-1 hover:bg-slate-50 sm:pr-3" @click="buka = !buka">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-blue-500 to-blue-700 text-sm font-semibold text-white ring-2 ring-blue-100">{{ $inisial }}</span>
                        <span class="hidden text-left sm:block">
                            <span class="block max-w-48 truncate text-sm font-semibold text-slate-800">Halo, {{ $user->nama }}</span>
                            <span class="block max-w-48 truncate text-xs text-slate-500">{{ $keteranganUser }}</span>
                        </span>
                        <x-icon name="chevron-down" class="hidden h-4 w-4 text-slate-400 sm:block" />
                    </button>
                    <div x-show="buka" x-cloak x-transition class="absolute right-0 mt-2 w-52 rounded-2xl border border-slate-100 bg-white p-1.5 text-sm shadow-xl">
                        <a href="{{ route('profil.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-50"><x-icon name="user" class="h-4 w-4" /> Profil Akun</a>
                        <a href="{{ route('profil.password') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 hover:bg-slate-50"><x-icon name="key" class="h-4 w-4" /> Ganti Password</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-red-600 hover:bg-red-50"><x-icon name="logout" class="h-4 w-4" /> Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 space-y-6 p-4 sm:p-6">
            <x-flash />
            {{ $slot }}
        </main>

        <footer class="flex flex-col gap-1 border-t border-slate-100 bg-white px-6 py-4 text-xs text-slate-500 sm:flex-row sm:justify-between">
            <span>&copy; {{ date('Y') }} {{ $sekolah['nama_sekolah'] }}. Hak cipta dilindungi.</span>
            <span>Dibuat dengan <span class="text-red-500">&hearts;</span> untuk warga sekolah</span>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
