@extends('errors.layout')

@section('kode', '503')
@section('ringkas', 'Sedang pemeliharaan')
@section('judul', 'Sedang berbenah agar makin nyaman dipakai.')
@section('pesan', 'Kami sedang melakukan pemeliharaan dan pembaruan sistem. Tidak akan lama, silakan kembali beberapa saat lagi.')
@section('ikon', 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99')

@section('aksi')
    <button type="button" class="tombol utama" onclick="location.reload()">Coba Lagi</button>
@endsection
