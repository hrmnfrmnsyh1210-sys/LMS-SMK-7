<?php

/*
| Menu sidebar per role. Item dengan 'judul' adalah pemisah grup.
| 'aktif' = pola nama route untuk menandai menu aktif.
*/

return [
    'operator' => [
        ['label' => 'Dashboard', 'route' => 'operator.dashboard', 'ikon' => 'home'],

        ['judul' => 'Pengguna'],
        ['label' => 'Guru', 'route' => 'operator.guru.index', 'aktif' => 'operator.guru.*', 'ikon' => 'user-group'],
        ['label' => 'Siswa', 'route' => 'operator.siswa.index', 'aktif' => 'operator.siswa.*', 'ikon' => 'academic-cap'],
        ['label' => 'Kepala Sekolah', 'route' => 'operator.kepsek.index', 'aktif' => 'operator.kepsek.*', 'ikon' => 'user'],
        ['label' => 'Orang Tua', 'route' => 'operator.orang-tua.index', 'aktif' => 'operator.orang-tua.*', 'ikon' => 'heart'],
        ['label' => 'Import Excel', 'route' => 'operator.import.index', 'aktif' => 'operator.import.*', 'ikon' => 'upload'],

        ['judul' => 'Data Master'],
        ['label' => 'Tahun Ajaran', 'route' => 'operator.tahun-ajaran.index', 'aktif' => 'operator.tahun-ajaran.*', 'ikon' => 'calendar'],
        ['label' => 'Jurusan', 'route' => 'operator.jurusan.index', 'aktif' => 'operator.jurusan.*', 'ikon' => 'tag'],
        ['label' => 'Kelas', 'route' => 'operator.kelas.index', 'aktif' => 'operator.kelas.*', 'ikon' => 'building'],
        ['label' => 'Mata Pelajaran', 'route' => 'operator.mapel.index', 'aktif' => 'operator.mapel.*', 'ikon' => 'book'],

        ['judul' => 'Penugasan'],
        ['label' => 'Pengampu', 'route' => 'operator.pengampu.index', 'aktif' => 'operator.pengampu.*', 'ikon' => 'link'],
        ['label' => 'Wali Kelas', 'route' => 'operator.wali-kelas.index', 'aktif' => 'operator.wali-kelas.*', 'ikon' => 'star'],
        ['label' => 'Jadwal', 'route' => 'operator.jadwal.index', 'aktif' => 'operator.jadwal.*', 'ikon' => 'clock'],

        ['judul' => 'Website'],
        ['label' => 'Konten Halaman', 'route' => 'operator.website.edit', 'aktif' => 'operator.website.*', 'ikon' => 'sparkles'],
        ['label' => 'Berita & Agenda', 'route' => 'operator.berita.index', 'aktif' => 'operator.berita.*', 'ikon' => 'megaphone'],
        ['label' => 'Galeri', 'route' => 'operator.galeri.index', 'aktif' => 'operator.galeri.*', 'ikon' => 'photo'],

        ['judul' => 'Laporan'],
        ['label' => 'Laporan', 'route' => 'operator.laporan.index', 'ikon' => 'document'],

        ['judul' => 'Sistem'],
        ['label' => 'Pengaturan Sekolah', 'route' => 'operator.pengaturan.edit', 'aktif' => 'operator.pengaturan.*', 'ikon' => 'cog'],
        ['label' => 'Pergantian Tahun Ajaran', 'route' => 'operator.pergantian-tahun-ajaran.index', 'aktif' => 'operator.pergantian-tahun-ajaran.*', 'ikon' => 'refresh'],
        ['label' => 'Kenaikan Kelas', 'route' => 'operator.kenaikan-kelas.index', 'aktif' => 'operator.kenaikan-kelas.*', 'ikon' => 'arrow-up'],
        ['label' => 'Log Aktivitas', 'route' => 'operator.log.index', 'aktif' => 'operator.log.*', 'ikon' => 'list'],
        ['label' => 'Backup & Restore', 'route' => 'operator.backup.index', 'aktif' => 'operator.backup.*', 'ikon' => 'database'],
        ['label' => 'Profil Akun', 'route' => 'profil.edit', 'aktif' => 'profil.*', 'ikon' => 'user'],
    ],

    'kepsek' => [
        ['label' => 'Dashboard', 'route' => 'kepsek.dashboard', 'ikon' => 'home'],

        ['judul' => 'Monitoring'],
        ['label' => 'Keaktifan Guru', 'route' => 'kepsek.monitoring.guru', 'ikon' => 'user-group'],
        ['label' => 'Rekap Kelas', 'route' => 'kepsek.monitoring.kelas', 'aktif' => 'kepsek.monitoring.kelas*', 'ikon' => 'chart'],
        ['label' => 'Laporan', 'route' => 'kepsek.laporan.index', 'ikon' => 'document'],
        ['label' => 'Pengumuman', 'route' => 'kepsek.pengumuman.index', 'aktif' => 'kepsek.pengumuman.*', 'ikon' => 'megaphone'],

        ['judul' => 'Akun'],
        ['label' => 'Profil Akun', 'route' => 'profil.edit', 'aktif' => 'profil.*', 'ikon' => 'user'],
    ],

    'guru' => [
        ['label' => 'Dashboard', 'route' => 'guru.dashboard', 'ikon' => 'home'],
        ['label' => 'Kelas Ajar', 'route' => 'guru.kelas.index', 'aktif' => ['guru.kelas.*', 'guru.materi.*', 'guru.tugas.*', 'guru.kuis.*', 'guru.soal.*', 'guru.presensi.*', 'guru.nilai.*', 'guru.forum.*', 'guru.monitoring.*'], 'ikon' => 'book'],
        ['label' => 'Pengumuman', 'route' => 'guru.pengumuman.index', 'aktif' => 'guru.pengumuman.*', 'ikon' => 'megaphone'],
        ['label' => 'Laporan', 'route' => 'guru.laporan.index', 'ikon' => 'document'],

        // 'syarat' => 'wali': hanya tampil bila guru menjadi wali kelas di tahun ajaran aktif.
        ['judul' => 'Wali Kelas', 'syarat' => 'wali'],
        ['label' => 'Rekap Kelas', 'route' => 'guru.wali.rekap', 'ikon' => 'chart', 'syarat' => 'wali'],
        ['label' => 'Surat Izin', 'route' => 'guru.wali.surat-izin', 'aktif' => 'guru.wali.surat-izin*', 'ikon' => 'document', 'syarat' => 'wali'],

        ['judul' => 'Akun'],
        ['label' => 'Profil Akun', 'route' => 'profil.edit', 'aktif' => 'profil.*', 'ikon' => 'user'],
    ],

    'siswa' => [
        ['label' => 'Dashboard', 'route' => 'siswa.dashboard', 'ikon' => 'home'],

        ['judul' => 'Belajar'],
        ['label' => 'Mata Pelajaran', 'route' => 'siswa.mapel.index', 'aktif' => ['siswa.mapel.*', 'siswa.forum.*'], 'ikon' => 'book'],
        ['label' => 'Tugas', 'route' => 'siswa.tugas.index', 'aktif' => 'siswa.tugas.*', 'ikon' => 'clipboard-check'],
        ['label' => 'Kuis & Ujian', 'route' => 'siswa.kuis.index', 'aktif' => 'siswa.kuis.*', 'ikon' => 'puzzle'],
        ['label' => 'Jadwal', 'route' => 'siswa.jadwal', 'ikon' => 'clock'],
        ['label' => 'Asisten Belajar', 'route' => 'siswa.asisten', 'ikon' => 'sparkles'],

        ['judul' => 'Progres'],
        ['label' => 'Nilai', 'route' => 'siswa.nilai', 'ikon' => 'chart'],
        ['label' => 'Presensi', 'route' => 'siswa.presensi', 'ikon' => 'check-badge'],
        ['label' => 'Surat Izin', 'route' => 'siswa.surat-izin.index', 'aktif' => 'siswa.surat-izin.*', 'ikon' => 'document'],
        ['label' => 'Pengumuman', 'route' => 'siswa.pengumuman', 'ikon' => 'megaphone'],

        ['judul' => 'Akun'],
        ['label' => 'Profil Akun', 'route' => 'profil.edit', 'aktif' => 'profil.*', 'ikon' => 'user'],
    ],

    // Halaman pantauan menampilkan anak yang sedang dipilih (lihat Ortu\Concerns\AnakDipantau).
    'ortu' => [
        ['label' => 'Dashboard', 'route' => 'ortu.dashboard', 'ikon' => 'home'],

        ['judul' => 'Pantau Anak'],
        ['label' => 'Nilai', 'route' => 'ortu.nilai', 'ikon' => 'chart'],
        ['label' => 'Presensi', 'route' => 'ortu.presensi', 'ikon' => 'check-badge'],
        ['label' => 'Tugas', 'route' => 'ortu.tugas', 'ikon' => 'clipboard-check'],
        ['label' => 'Kuis & Ujian', 'route' => 'ortu.kuis', 'ikon' => 'puzzle'],
        ['label' => 'Jadwal', 'route' => 'ortu.jadwal', 'ikon' => 'clock'],

        ['judul' => 'Komunikasi'],
        ['label' => 'Surat Izin', 'route' => 'ortu.surat-izin.index', 'aktif' => 'ortu.surat-izin.*', 'ikon' => 'document'],
        ['label' => 'Pengumuman', 'route' => 'ortu.pengumuman', 'ikon' => 'megaphone'],

        ['judul' => 'Akun'],
        ['label' => 'Profil Akun', 'route' => 'profil.edit', 'aktif' => 'profil.*', 'ikon' => 'user'],
    ],
];
