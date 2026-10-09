<x-layouts.app title="Kuis & Ujian Anak">
    <x-ortu.anak-header :anak="$anak" judul="Kuis & Ujian" subjudul="Skor tampil bila guru membuka hasil kuis tersebut." />

    <x-siswa.daftar-kuis :daftar="$daftar" :tautan="false" />
</x-layouts.app>
