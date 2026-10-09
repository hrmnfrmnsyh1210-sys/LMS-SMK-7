<x-layouts.app :title="'Kuis · '.$pengampu->mapel->nama_mapel">
    <x-siswa.mapel-header :pengampu="$pengampu" aktif="kuis" />

    <x-siswa.daftar-kuis :daftar="$daftar" :tampil-mapel="false" />
</x-layouts.app>
