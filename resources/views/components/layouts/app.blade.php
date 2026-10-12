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
        'ortu' => $profil?->anak->isNotEmpty() ? 'Orang tua '.$profil->anak->pluck('nama')->map(fn ($n) => \Illuminate\Support\Str::of($n)->explode(' ')->first())->join(', ') : 'Orang Tua',
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
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700|roboto-slab:600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-latar font-sans text-slate-800 antialiased" data-tema="{{ $user->role }}"
      x-data="{
          sidebar: false,
          ciut: localStorage.getItem('sidebar-ciut') === '1',
          tip: null,
          tunjuk(el, teks) {
              if (! this.ciut || window.innerWidth < 1024) return;
              const r = el.getBoundingClientRect();
              this.tip = { teks, y: r.top + r.height / 2 };
          },
      }"
      x-init="$watch('ciut', v => { tip = null; localStorage.setItem('sidebar-ciut', v ? '1' : '0') })">

    {{-- Overlay drawer (HP) --}}
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/30 backdrop-blur-sm lg:hidden" @click="sidebar = false"></div>

    {{-- ================= SIDEBAR ================= --}}
    {{-- Desktop: lebar penuh (w-64) atau rel ikon (w-20) saat diciutkan. HP: laci geser. --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col overflow-hidden bg-sidebar text-white shadow-xl shadow-slate-900/10 transition-[width,translate] duration-200 lg:translate-x-0"
           :class="{ 'translate-x-0': sidebar, 'lg:w-20': ciut }">
        {{-- Identitas sekolah --}}
        <a href="{{ route('dashboard') }}" class="flex h-16 shrink-0 items-center gap-3 border-b border-white/10 px-5" :class="ciut && 'lg:justify-center lg:px-0'">
            @if ($logo)
                <img src="{{ $logo }}" alt="Logo" class="h-10 w-10 shrink-0 object-contain">
            @else
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 text-aksen"><x-icon name="academic-cap" class="h-6 w-6" /></span>
            @endif
            <span class="min-w-0 leading-tight whitespace-nowrap" :class="ciut && 'lg:hidden'">
                <span class="block truncate font-display text-base font-bold">{{ $sekolah['nama_sekolah'] }}</span>
                <span class="block text-[10px] font-medium tracking-[0.18em] text-white/50 uppercase">Learning Management</span>
            </span>
        </a>

        <nav class="sidebar-scroll flex-1 space-y-0.5 overflow-x-hidden overflow-y-auto px-3 py-4 text-sm" @scroll="tip = null">
            @foreach ($menu as $item)
                @if (isset($item['judul']))
                    <div class="px-3 pt-5 pb-2 text-[10px] font-semibold tracking-[0.18em] whitespace-nowrap text-white/40 uppercase" :class="ciut && 'lg:hidden'">{{ $item['judul'] }}</div>
                    <div class="mx-2 my-3 hidden border-t border-white/10" :class="ciut && 'lg:block'"></div>
                @else
                    @php $aktif = request()->routeIs(...(array) ($item['aktif'] ?? $item['route'])); @endphp
                    <a href="{{ route($item['route']) }}" aria-label="{{ $item['label'] }}" @if ($aktif) aria-current="page" @endif
                       @mouseenter="tunjuk($el, @js($item['label']))" @mouseleave="tip = null"
                       :class="ciut && 'lg:justify-center lg:px-0'"
                       @class([
                           'group relative flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition',
                           'bg-white/10 text-white before:absolute before:inset-y-2 before:-left-3 before:w-1 before:rounded-r-full before:bg-aksen' => $aktif,
                           'text-white/65 hover:bg-white/5 hover:text-white' => ! $aktif,
                       ])>
                        <x-icon :name="$item['ikon']" class="h-5 w-5 shrink-0 transition {{ $aktif ? 'text-aksen' : 'text-white/50 group-hover:text-white' }}" />
                        <span class="truncate whitespace-nowrap" :class="ciut && 'lg:hidden'">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </nav>

        <div class="space-y-1 border-t border-white/10 p-3">
            <div class="flex items-center gap-3 rounded-lg bg-white/5 px-3 py-2.5" :class="ciut && 'lg:justify-center lg:px-0'"
                 @mouseenter="tunjuk($el, @js('Tahun ajaran: '.($tahunAktif?->label ?? 'belum diatur')))" @mouseleave="tip = null">
                <x-icon name="calendar" class="h-5 w-5 shrink-0 text-aksen" />
                <div class="min-w-0 leading-tight whitespace-nowrap" :class="ciut && 'lg:hidden'">
                    <div class="text-[10px] font-medium tracking-[0.15em] text-white/45 uppercase">Tahun ajaran aktif</div>
                    <div class="truncate text-sm font-semibold">{{ $tahunAktif?->label ?? 'Belum diatur' }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" aria-label="Keluar" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/65 transition hover:bg-red-500/15 hover:text-red-200"
                        :class="ciut && 'lg:justify-center lg:px-0'" @mouseenter="tunjuk($el, 'Keluar')" @mouseleave="tip = null">
                    <x-icon name="logout" class="h-5 w-5 shrink-0" /> <span :class="ciut && 'lg:hidden'">Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Tooltip nama menu saat sidebar diciutkan (di luar aside agar tidak terpotong). --}}
    <div x-show="tip" x-cloak x-transition.opacity.duration.100ms
         class="pointer-events-none fixed left-22 z-50 hidden -translate-y-1/2 rounded-md bg-slate-900 px-2.5 py-1.5 text-xs font-medium whitespace-nowrap text-white shadow-lg lg:block"
         :style="tip && { top: tip.y + 'px' }" x-text="tip?.teks"></div>

    {{-- ================= KONTEN ================= --}}
    <div class="flex min-h-screen flex-col transition-[padding] duration-200 lg:pl-64" :class="ciut && 'lg:pl-20!'">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-100 bg-white px-4 sm:px-6">
            <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100"
                    @click="window.innerWidth >= 1024 ? ciut = !ciut : sidebar = true" aria-label="Menu">
                <x-icon name="menu" class="h-6 w-6" />
            </button>

            <h1 class="truncate font-display text-lg font-bold text-slate-900">{{ $title }}</h1>

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
                            @if (! empty($notif->data['url']))
                                <a href="{{ $notif->data['url'] }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-50">{{ $notif->data['pesan'] ?? 'Notifikasi baru' }}</a>
                            @else
                                <div class="rounded-lg px-3 py-2 text-sm hover:bg-slate-50">{{ $notif->data['pesan'] ?? 'Notifikasi baru' }}</div>
                            @endif
                        @empty
                            <div class="px-3 py-6 text-center text-sm text-slate-500">Tidak ada notifikasi baru.</div>
                        @endforelse
                    </div>
                </div>

                {{-- Profil --}}
                <div class="relative" x-data="{ buka: false }" @click.outside="buka = false">
                    <button type="button" class="flex items-center gap-3 rounded-xl py-1 pr-1 pl-1 hover:bg-slate-50 sm:pr-3" @click="buka = !buka">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sidebar text-sm font-semibold text-white ring-2 ring-blue-100">{{ $inisial }}</span>
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
