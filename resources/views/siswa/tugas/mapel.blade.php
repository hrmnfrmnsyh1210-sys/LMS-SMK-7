<x-layouts.app :title="'Tugas · '.$pengampu->mapel->nama_mapel">
    <x-siswa.mapel-header :pengampu="$pengampu" aktif="tugas" />

    <x-siswa.daftar-tugas :daftar="$daftar" :tampil-mapel="false" />
</x-layouts.app>
