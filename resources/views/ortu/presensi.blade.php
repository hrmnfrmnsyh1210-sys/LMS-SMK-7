<x-layouts.app title="Presensi Anak">
    <x-ortu.anak-header :anak="$anak" judul="Presensi" subjudul="Rekap kehadiran di setiap mata pelajaran pada tahun ajaran aktif." />

    <x-akademik.presensi :rekap="$rekap" :total="$total" :riwayat="$riwayat" />
</x-layouts.app>
