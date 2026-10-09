<x-layouts.app :title="'Rekap Kelas '.$kelas->nama_kelas">
    <x-page-header :title="'Rekap Kelas '.$kelas->nama_kelas" :subtitle="$kelas->jurusan->nama.' · '.($tahunAktif?->label ?? '').' · Anda wali kelas ini'">
        <a href="{{ route('guru.wali.surat-izin') }}" class="btn-secondary"><x-icon name="document" class="h-4 w-4" /> Surat Izin</a>
    </x-page-header>

    <x-rekap-kelas :pengampu="$pengampu" :ranking="$ranking" :nilai="$nilai" :kehadiran="$kehadiran" :kkm="$kkm" />
</x-layouts.app>
