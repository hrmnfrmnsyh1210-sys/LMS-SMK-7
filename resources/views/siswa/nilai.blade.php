<x-layouts.app title="Nilai">
    <x-page-header title="Nilai" :subtitle="'Nilai per mata pelajaran · KKM '.$kkm" />

    <x-akademik.nilai :pengampu="$pengampu" :nilai="$nilai" :kkm="$kkm" :peringkat="$peringkat" :jumlah-siswa="$jumlahSiswa" />
</x-layouts.app>
