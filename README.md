# LMS SMK

Learning Management System berbasis web untuk SMK dengan 4 role: **Operator, Kepala Sekolah, Guru, Siswa**.

Teknologi: Laravel 13 (PHP ≥ 8.3), MySQL/MariaDB, Blade + Tailwind CSS v4 + Alpine.js, `maatwebsite/excel` v4, `barryvdh/laravel-dompdf`.

## Status pengembangan

| Fase | Cakupan | Status |
|---|---|---|
| 1 | Fondasi (migrasi, model, seeder, login username, middleware, layout 4 role, profil) + seluruh fitur Operator | ✅ Selesai |
| 2 | Fitur Guru (kelas ajar, materi, tugas, kuis PG+esai, presensi, nilai, forum, pengumuman, monitoring & ranking, wali kelas + surat izin) | ✅ Selesai |
| 3 | Fitur Siswa & Kepala Sekolah | Belum |
| 4 | Pengujian lanjutan, halaman error, panduan deployment | Belum |

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

Data dummy: tahun ajaran 2026/2027 ganjil (aktif), 3 jurusan (RPL, TKJ, AKL), 6 kelas, 10 mapel, 8 guru, 60 siswa, 60 pengampu, dan jadwal tanpa bentrok.

Pada `APP_ENV=production`, `php artisan migrate --seed` hanya membuat akun `operator` / `password` (wajib ganti password saat login pertama) dan pengaturan awal.

Akun yang dibuat operator memakai password awal = **NIS** (siswa) / **NIP** (guru & kepsek, atau username jika NIP kosong), dan wajib diganti saat login pertama. Reset password juga mengembalikan ke nilai ini.

## Fitur Operator (Fase 1)

- **Pengguna:** CRUD guru, siswa, dan kepala sekolah (akun + profil dalam satu transaksi), reset password, aktif/nonaktif akun.
- **Import Excel:** guru dan siswa dari template `.xlsx` yang bisa diunduh. Validasi dilakukan per baris: baris valid tetap tersimpan, baris gagal ditampilkan beserta alasannya.
- **Data master:** tahun ajaran (hanya satu yang aktif), jurusan, kelas, mapel. Data master yang sudah dipakai tidak bisa dihapus.
- **Penugasan:** plotting pengampu, anggota kelas (pilih banyak siswa sekaligus), wali kelas.
- **Jadwal:** validasi bentrok untuk kelas dan guru, tampilan per kelas dan per guru.
- **Sistem:** profil sekolah & logo, kebijakan password & KKM, pergantian tahun ajaran/semester (opsional menyalin kelas, anggota, pengampu, dan jadwal), wizard kenaikan kelas (siswa XII → lulus + akun nonaktif).
- **Log aktivitas:** filter berdasarkan user, role, aksi, dan tanggal.
- **Backup & restore:** dump SQL murni lewat PHP (tanpa `mysqldump`). Restore memakai konfirmasi ganda: ketik `RESTORE` dan masukkan password.

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
