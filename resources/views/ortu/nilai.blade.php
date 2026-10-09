<x-layouts.app title="Nilai Anak">
    <x-ortu.anak-header :anak="$anak" judul="Nilai" :subjudul="'Nilai per mata pelajaran · KKM '.$kkm" />

    <x-akademik.nilai :pengampu="$pengampu" :nilai="$nilai" :kkm="$kkm" :peringkat="$peringkat" :jumlah-siswa="$jumlahSiswa" />
</x-layouts.app>
