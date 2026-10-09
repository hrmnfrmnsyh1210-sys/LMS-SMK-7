<x-layouts.app :title="'Rekap Kelas '.$kelas->nama_kelas">
    <x-page-header :title="'Rekap Kelas '.$kelas->nama_kelas"
                   :subtitle="$kelas->jurusan?->nama.' · Wali kelas: '.($kelas->waliKelas?->nama ?? '-')">
        <a href="{{ route('kepsek.monitoring.kelas') }}" class="btn-secondary">Kembali</a>
    </x-page-header>

    <x-rekap-kelas :pengampu="$pengampu" :ranking="$ranking" :nilai="$nilai" :kehadiran="$kehadiran" :kkm="$kkm" />
</x-layouts.app>
