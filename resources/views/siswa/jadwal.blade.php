<x-layouts.app title="Jadwal Pelajaran">
    <x-page-header title="Jadwal Pelajaran" :subtitle="($kelas ? 'Kelas '.$kelas->nama_kelas.' · ' : '').($tahunAktif?->label ?? '-')" />

    <x-akademik.jadwal :jadwal="$jadwal" />
</x-layouts.app>
