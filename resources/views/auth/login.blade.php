<x-layouts.guest title="Masuk">
    <h2 class="text-2xl font-bold text-slate-800">Selamat Datang <span aria-hidden="true">👋</span></h2>
    <p class="mt-1 mb-6 text-sm text-slate-500">Masuk dengan username dan password yang diberikan sekolah.</p>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4" x-data="{ lihat: false }">
        @csrf

        <x-field.input name="username" label="Username" :value="old('username')" required autofocus autocomplete="username"
                       help="Siswa: NIS · Guru: NIP/username" />

        <div>
            <label for="password" class="label">Password</label>
            <div class="relative">
                <input :type="lihat ? 'text' : 'password'" type="password" name="password" id="password" required
                       autocomplete="current-password" @class(['input pr-16', 'input-error' => $errors->has('password')])>
                <button type="button" class="absolute inset-y-0 right-0 px-3 text-xs font-medium text-slate-500 hover:text-slate-800"
                        @click="lihat = !lihat" x-text="lihat ? 'Sembunyi' : 'Lihat'">Lihat</button>
            </div>
            @error('password')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300"> Ingat saya
        </label>

        <button type="submit" class="btn-primary w-full">Masuk</button>
    </form>
</x-layouts.guest>
