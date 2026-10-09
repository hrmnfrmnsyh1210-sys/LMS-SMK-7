{{-- Tombol reset password & aktif/nonaktif untuk sebuah akun ($akun = User). --}}
<x-confirm :action="route('operator.akun.reset-password', $akun)" method="POST" title="Reset password?"
           message="Password {{ $akun->username }} akan dikembalikan ke default (NIS/NIP/username) dan wajib diganti saat login."
           confirm-label="Ya, reset" confirm-class="btn-primary" button-class="btn-secondary btn-sm">
    <x-icon name="key" class="h-3.5 w-3.5" /> Reset
</x-confirm>

<form method="POST" action="{{ route('operator.akun.status', $akun) }}" class="inline">
    @csrf
    @method('PATCH')
    <button type="submit" class="btn-secondary btn-sm" title="{{ $akun->isAktif() ? 'Nonaktifkan' : 'Aktifkan' }} akun">
        <x-icon name="power" class="h-3.5 w-3.5" /> {{ $akun->isAktif() ? 'Nonaktifkan' : 'Aktifkan' }}
    </button>
</form>
