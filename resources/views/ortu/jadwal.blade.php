<x-layouts.app title="Jadwal Anak">
    <x-ortu.anak-header :anak="$anak" judul="Jadwal Pelajaran" :subjudul="$tahunAktif?->label" />

    <x-akademik.jadwal :jadwal="$jadwal" :tautan-mapel="false" />
</x-layouts.app>
