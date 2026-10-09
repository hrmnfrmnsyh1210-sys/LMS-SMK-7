<x-layouts.app title="Ganti Password">
    <x-page-header title="Ganti Password" subtitle="Password minimal {{ $sekolah['min_panjang_password'] }} karakter." />

    @if (auth()->user()->wajib_ganti_password)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Anda masih memakai password awal. Ganti password terlebih dahulu untuk melanjutkan.
        </div>
    @endif

    <form method="POST" action="{{ route('profil.password.update') }}" class="card card-body max-w-lg space-y-4">
        @csrf
        @method('PUT')

        <x-field.input name="password_lama" type="password" label="Password lama" required autocomplete="current-password" />
        <x-field.input name="password" type="password" label="Password baru" required autocomplete="new-password" />
        <x-field.input name="password_confirmation" type="password" label="Ulangi password baru" required autocomplete="new-password" />

        <div class="flex justify-end border-t border-slate-100 pt-4">
            <button type="submit" class="btn-primary">Ganti Password</button>
        </div>
    </form>
</x-layouts.app>
