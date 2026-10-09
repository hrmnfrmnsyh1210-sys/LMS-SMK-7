<x-layouts.app title="Presensi">
    <x-page-header title="Presensi" subtitle="Rekap kehadiranmu di setiap mata pelajaran pada tahun ajaran aktif." />

    <x-akademik.presensi :rekap="$rekap" :total="$total" :riwayat="$riwayat" />
</x-layouts.app>
