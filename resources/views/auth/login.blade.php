<x-layouts.guest title="Masuk">
    <p class="text-xs font-semibold tracking-[0.2em] text-navy-600 uppercase">Masuk LMS</p>
    <h2 class="mt-2 font-display text-3xl font-bold text-navy-900">Selamat datang kembali</h2>
    <p class="mt-2 mb-8 text-sm text-slate-500">Masuk dengan username dan password yang diberikan sekolah.</p>

    @if (session('status'))
        <div class="mb-5 border-l-4 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ lihat: false }">
        @csrf

        <x-field.input name="username" label="Username" :value="old('username')" required autofocus autocomplete="username"
                       help="Siswa: NIS · Guru: NIP/username"
                       class="py-2.5 focus:border-navy-600 focus:ring-navy-600/15" />

        <div>
            <label for="password" class="label">Password</label>
            <div class="relative">
                <input :type="lihat ? 'text' : 'password'" type="password" name="password" id="password" required
                       autocomplete="current-password" @class(['input py-2.5 pr-11 focus:border-navy-600 focus:ring-navy-600/15', 'input-error' => $errors->has('password')])>
                <button type="button" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-navy-700"
                        @click="lihat = !lihat" :aria-label="lihat ? 'Sembunyikan password' : 'Tampilkan password'" aria-label="Tampilkan password">
                    <x-icon name="eye" class="h-5 w-5" />
                </button>
            </div>
            @error('password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-navy-700 focus:ring-navy-600"> Ingat saya
        </label>

        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-navy-800 px-6 py-3 text-sm font-semibold text-white transition hover:bg-navy-700 focus:ring-2 focus:ring-navy-600 focus:ring-offset-2 focus:outline-none">
            Masuk <x-icon name="arrow-right" class="h-4 w-4" />
        </button>
    </form>

    <p class="mt-8 text-xs leading-relaxed text-slate-400">Lupa password? Hubungi operator sekolah atau wali kelas untuk mengatur ulang akun.</p>
</x-layouts.guest>
