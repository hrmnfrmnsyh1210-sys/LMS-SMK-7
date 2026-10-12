# LMS SMK

Learning Management System berbasis web untuk SMK dengan 5 role: **Operator, Kepala Sekolah, Guru, Siswa, Orang Tua**.

Teknologi: Laravel 13 (PHP ≥ 8.3), MySQL/MariaDB, Blade + Tailwind CSS v4 + Alpine.js, `maatwebsite/excel` v4, `barryvdh/laravel-dompdf`.

## Status pengembangan

| Fase | Cakupan | Status |
|---|---|---|
| 1 | Fondasi (migrasi, model, seeder, login username, middleware, layout 4 role, profil) + seluruh fitur Operator | ✅ Selesai |
| 2 | Fitur Guru (kelas ajar, materi, tugas, kuis PG+esai, presensi, nilai, forum, pengumuman, monitoring & ranking, wali kelas + surat izin) | ✅ Selesai |
| 3 | Fitur Siswa (mapel & materi, tugas, kuis dengan timer/autosave, jadwal, presensi, nilai, forum, surat izin, pengumuman) & Kepala Sekolah (dashboard, keaktifan guru, rekap kelas, pengumuman sekolah) | ✅ Selesai |
| 3b | Role Orang Tua (akun per anak/kakak-adik, pantau nilai, presensi, tugas, kuis, jadwal, pengumuman, surat izin) | ✅ Selesai |
| 4 | Pengujian lanjutan, halaman error, header keamanan, penjadwal otomatis, panduan deployment ([DEPLOYMENT.md](DEPLOYMENT.md)) | ✅ Selesai |

## Instalasi lokal

```bash
composer install
cp .env.example .env          # sesuaikan DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate
php artisan migrate --seed     # seeder dummy (bukan produksi)
php artisan storage:link
npm install && npm run build
php artisan serve
```

Buka http://localhost:8000.

### Menjalankan test

Test memakai database MySQL **`lms_smk_test`** (lihat `phpunit.xml`). Buat database tersebut lalu jalankan:

```bash
php artisan test
```

## Akun default (seeder dummy)

| Role | Username | Password |
|---|---|---|
| Operator | `operator` | `password` |
| Kepala Sekolah | `kepsek` | `password` |
| Guru | NIP `198001012010011001` s.d. `198001012010011008` | `password` |
| Siswa | NIS `26001` s.d. `26060` | `password` |
| Orang Tua | `ortu26001` s.d. `ortu26010` (siswa X RPL 1; `ortu26001` juga memantau NIS `26011`) | `password` |

Data dummy: tahun ajaran 2026/2027 ganjil (aktif), 3 jurusan (RPL, TKJ, AKL), 6 kelas, 10 mapel, 8 guru, 60 siswa, 10 orang tua, 60 pengampu, dan jadwal tanpa bentrok.

Pada `APP_ENV=production`, `php artisan migrate --seed` hanya membuat akun `operator` / `password` (wajib ganti password saat login pertama) dan pengaturan awal.

Akun yang dibuat operator memakai password awal = **NIS** (siswa) / **NIP** (guru & kepsek, atau username jika NIP kosong) / **username** (orang tua), dan wajib diganti saat login pertama. Reset password juga mengembalikan ke nilai ini.

## Fitur Operator (Fase 1)

- **Pengguna:** CRUD guru, siswa, dan kepala sekolah (akun + profil dalam satu transaksi), reset password, aktif/nonaktif akun.
- **Import Excel:** guru dan siswa dari template `.xlsx` yang bisa diunduh. Validasi dilakukan per baris: baris valid tetap tersimpan, baris gagal ditampilkan beserta alasannya.
- **Data master:** tahun ajaran (hanya satu yang aktif), jurusan, kelas, mapel. Data master yang sudah dipakai tidak bisa dihapus.
- **Penugasan:** plotting pengampu, anggota kelas (pilih banyak siswa sekaligus), wali kelas.
- **Jadwal:** validasi bentrok untuk kelas dan guru, tampilan per kelas dan per guru.
- **Sistem:** profil sekolah & logo, kebijakan password & KKM, pergantian tahun ajaran/semester (opsional menyalin kelas, anggota, pengampu, dan jadwal), wizard kenaikan kelas (siswa XII → lulus + akun nonaktif).
- **Log aktivitas:** filter berdasarkan user, role, aksi, dan tanggal.
- **Backup & restore:** dump SQL murni lewat PHP (tanpa `mysqldump`). Restore memakai konfirmasi ganda: ketik `RESTORE` dan masukkan password.

## Fitur Siswa & Kepala Sekolah (Fase 3)

**Siswa** (tema lavender–pink, hanya mengakses mapel di kelasnya lewat policy `PengampuPolicy::ikuti`):

- **Dashboard:** jadwal hari ini, deadline terdekat, kuis yang dibuka, ringkasan kehadiran & nilai, pengumuman, kalender deadline.
- **Mata pelajaran:** materi per pertemuan, tugas, kuis, dan forum per mapel.
- **Tugas:** kumpul file dan/atau teks, boleh kirim ulang sebelum dinilai, lewat deadline tetap diterima tetapi ditandai terlambat.
- **Kuis/UTS/UAS:** batas waktu = mulai + durasi (maksimal waktu tutup kuis), urutan soal diacak per siswa, autosave per soal, pindah tab dicatat, kirim otomatis saat waktu habis, PG dikoreksi otomatis. Pembahasan baru dibuka setelah kuis ditutup.
- **Jadwal, presensi, nilai** (dengan status tuntas KKM dan peringkat kelas), **pengumuman**, dan **surat izin** ke wali kelas (bisa dibatalkan selama menunggu).

**Kepala sekolah** (baca-saja kecuali pengumuman):

- **Dashboard:** statistik sekolah, komposisi kehadiran, aktivitas pembelajaran, kelas terbaik, aktivitas guru terbaru.
- **Keaktifan guru:** jumlah kelas ajar, materi, tugas, kuis, dan pertemuan presensi per guru.
- **Rekap kelas:** kehadiran & rata-rata nilai per kelas, detail ranking per kelas (komponen yang sama dengan rekap wali kelas).
- **Pengumuman sekolah:** untuk semua, guru, siswa, atau orang tua, disertai notifikasi.

## Fitur Orang Tua

Tabel baru `orang_tua` dan pivot `orang_tua_siswa` (satu akun dapat memantau beberapa anak), kolom `surat_izin.pengaju_user_id`, serta nilai enum baru `users.role = ortu` dan `pengumuman.target = ortu`.

- **Operator:** CRUD akun orang tua dengan pilihan anak (cari nama/NIS), dan **buat akun massal per kelas** untuk siswa yang belum punya akun (username & password awal `ortu` + NIS, nama dari kolom nama orang tua siswa).
- **Dashboard orang tua** (tema teal): kartu ringkas tiap anak, kehadiran, rata-rata nilai, tugas belum dikumpulkan/terlewat, catatan alpa, nilai tugas & kuis terbaru, jadwal hari ini, kontak WhatsApp wali kelas.
- **Pantau anak** (anak yang dipilih disimpan di session): nilai, presensi, status tugas, hasil kuis (hanya bila guru menampilkan hasil), jadwal. Semuanya baca-saja.
- **Surat izin:** orang tua dapat mengajukan izin/sakit untuk anaknya; wali kelas melihat nama pengaju. Orang tua dan siswa sama-sama diberi notifikasi saat surat diproses.
- **Pengumuman:** orang tua melihat pengumuman untuk semua, untuk orang tua, dan untuk kelas anaknya; pengumuman kelas dari guru juga dikirim ke orang tua siswa kelas tersebut.
- **Notifikasi alpa:** orang tua diberi tahu saat anaknya baru tercatat alpa (menyimpan ulang presensi tidak mengirim notifikasi ganda).

## Kesiapan produksi (Fase 4)

- **Halaman error** 403, 404, 419, 429, 500, 503 berbahasa Indonesia (`resources/views/errors`), mandiri tanpa Vite/database agar tetap tampil saat aplikasi bermasalah.
- **Header keamanan** (`HeaderKeamanan` middleware): `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, dan HSTS bila HTTPS.
- **Penjadwal** (`routes/console.php`, butuh satu cron per menit): `lms:tutup-kuis` tiap 5 menit dan `lms:bersihkan-notifikasi` harian.
- **Test alur lintas role** guru → siswa → orang tua → wali kelas (`tests/Feature/Fase3AlurSiswaOrtuTest.php`).
- **Panduan deployment** shared hosting: [DEPLOYMENT.md](DEPLOYMENT.md).

## Catatan skema

Selain penanda **[TAMBAHAN]** di dokumen rancangan, kolom berikut ikut ditambahkan (perbarui ERD laporan):

| Tabel | Kolom | Alasan |
|---|---|---|
| `users` | `wajib_ganti_password` (bool) | Akun baru, reset, dan operator produksi wajib ganti password saat login pertama |
| `siswa` | `status` enum(aktif, lulus) | Menandai siswa lulus pada kenaikan kelas |
| `jawaban_kuis` | `urutan_soal` (json), `jumlah_pindah_tab` (int) | Acak soal per siswa dan pencatatan pindah tab (Fase 3) |
| `jawaban_kuis_detail` | unik (`jawaban_kuis_id`, `soal_id`) | Autosave per soal |
| `tahun_ajaran` | unik (`nama`, `semester`) | Mencegah duplikat |
| `kelas` | unik (`nama_kelas`, `tahun_ajaran_id`) | Nama kelas unik per tahun ajaran |
| `pengaturan` | kunci `kkm`, `telepon_sekolah`, `email_sekolah` | KKM untuk monitoring; kontak sekolah |

## Struktur kode penting

- `app/Http/Controllers/{Operator,Kepsek,Guru,Siswa}`: controller per role.
- `app/Http/Requests/Operator`: Form Request validasi.
- `app/Services`: `ActivityLogger`, `AkunService`, `JadwalService`, `KenaikanKelasService`, `TahunAjaranService`, `BackupService`.
- `app/Imports`: import Excel guru dan siswa.
- `config/menu.php`: menu sidebar per role.
- `resources/views/components`: komponen Blade (layout, tabel/search, modal konfirmasi, flash, badge, kartu statistik, field form).
- `app/helpers.php`: `tanggal_indo()`, `file_url()` (fallback route `berkas/{path}` bila `storage:link` tidak tersedia), `pengaturan()`.
