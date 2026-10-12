@extends('errors.layout')

@section('kode', '419')
@section('ringkas', 'Sesi berakhir')
@section('judul', 'Istirahatnya kelamaan, sesi Anda sudah habis.')
@section('pesan', 'Demi keamanan, sesi otomatis berakhir bila halaman dibiarkan terbuka cukup lama. Silakan masuk lagi, lalu ulangi langkah terakhir Anda.')
@section('ikon', 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z')

@section('aksi')
    <a href="{{ route('login') }}" class="tombol utama">Masuk Lagi</a>
    <button type="button" class="tombol" onclick="history.back()">Kembali</button>
@endsection
