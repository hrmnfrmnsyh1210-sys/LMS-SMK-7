@extends('errors.layout')

@php
    // Pesan abort() buatan aplikasi sudah berbahasa Indonesia; pesan bawaan framework diganti.
    $pesan = isset($exception) ? trim((string) $exception->getMessage()) : '';
    if (in_array($pesan, ['', 'Forbidden', 'This action is unauthorized.'], true)) {
        $pesan = 'Akun Anda belum punya izin untuk membuka halaman ini. Bila menurut Anda seharusnya bisa, hubungi operator sekolah agar hak aksesnya diperiksa.';
    }
@endphp

@section('kode', '403')
@section('ringkas', 'Akses ditolak')
@section('judul', 'Maaf, ruangan ini khusus untuk peran tertentu.')
@section('pesan', $pesan)
@section('ikon', 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z')
