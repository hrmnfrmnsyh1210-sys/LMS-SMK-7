# Panduan Deployment ke Shared Hosting

Panduan ini untuk memasang LMS SMK di shared hosting berbasis cPanel/hPanel (contoh: Niagahoster/Hostinger) **tanpa Node.js, Redis, atau worker antrean** di server. Aset front-end dibangun di komputer lokal lalu diunggah.

## 1. Persyaratan server

- PHP **8.3 atau lebih baru** (64-bit).
- MySQL 5.7+ / MariaDB 10.3+.
- Ekstensi PHP: `ctype`, `dom`, `fileinfo`, `filter`, `gd`, `hash`, `iconv`, `json`, `libxml`, `mbstring`, `openssl`, `pcre`, `pdo_mysql`, `session`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip`, `zlib`.
- Disarankan akses SSH (tersedia di sebagian besar paket). Tanpa SSH tetap bisa, lihat bagian 8.
- Atur PHP di panel hosting (menu *PHP Configuration*):

  | Opsi | Nilai minimal | Alasan |
  |---|---|---|
  | `upload_max_filesize` | `25M` | Upload materi/tugas hingga `UPLOAD_MAX_KB` (bawaan 20 MB) |
  | `post_max_size` | `30M` | Harus lebih besar dari `upload_max_filesize` |
  | `memory_limit` | `256M` | Import Excel & backup database |
  | `max_execution_time` | `120` | Import Excel & restore backup |

## 2. Siapkan paket di komputer lokal

```bash
git pull                                       # versi terbaru
composer install --no-dev --optimize-autoloader
npm ci && npm run build                        # menghasilkan public/build
```

Lalu kompres seluruh folder proyek **kecuali** `node_modules`, `.git`, `tests`, `storage/logs/*`, dan file `.env` lokal. Folder `vendor` dan `public/build` **wajib ikut** karena server tidak menjalankan Composer/Node.

> Jika server mendukung Composer lewat SSH, Anda boleh mengunggah tanpa `vendor` lalu menjalankan `composer install --no-dev --optimize-autoloader` di server.

## 3. Struktur folder di server

Kode aplikasi diletakkan **di luar** `public_html` agar file `.env`, `storage`, dan kode sumber tidak dapat diakses dari browser.

```
/home/USER/
├── lms/                 ← seluruh isi proyek (app, bootstrap, config, vendor, storage, ...)
│   └── public/          ← tidak dipakai langsung
└── public_html/         ← isi folder lms/public disalin ke sini
    ├── build/
    ├── index.php        ← diubah (lihat di bawah)
    ├── .htaccess
    ├── favicon.ico
    └── robots.txt
```

Langkah:

1. Ekstrak arsip ke `/home/USER/lms`.
2. Salin **isi** `lms/public/` (termasuk `.htaccess` yang tersembunyi) ke `public_html/`.
3. Ubah `public_html/index.php` agar menunjuk ke folder `lms` dan memakai `public_html` sebagai folder publik:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../lms/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../lms/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../lms/bootstrap/app.php';

// Folder publik ada di public_html, bukan lms/public.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
```

> **Alternatif:** bila panel hosting mengizinkan mengubah *document root* domain/subdomain, arahkan langsung ke `/home/USER/lms/public` dan lewati langkah 2–3.

## 4. Database

1. Buat database dan user MySQL di panel hosting, beri user tersebut **semua hak akses** ke database.
2. Catat nama database, username, dan password (biasanya berawalan `u123456_`).

## 5. File `.env` produksi

Salin `.env.example` menjadi `/home/USER/lms/.env`, lalu sesuaikan:

```dotenv
APP_NAME="LMS SMK"
APP_ENV=production
APP_KEY=                       # diisi oleh php artisan key:generate
APP_DEBUG=false                # WAJIB false di produksi
APP_URL=https://lms.namasekolah.sch.id
APP_TIMEZONE=Asia/Jakarta

LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456_lms
DB_USERNAME=u123456_lms
DB_PASSWORD=rahasia-yang-kuat

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true     # hanya bila situs memakai HTTPS
CACHE_STORE=database
QUEUE_CONNECTION=sync          # tidak ada worker antrean di shared hosting
FILESYSTEM_DISK=public

UPLOAD_MAX_KB=20480

# Fitur AI (SumoPod). Kosongkan AI_API_KEY bila tidak dipakai.
AI_BASE_URL=https://ai.sumopod.com/v1
AI_API_KEY=
AI_MODEL=gpt-4o-mini
AI_TIMEOUT=60
```

Fitur AI memanggil `ai.sumopod.com` lewat HTTPS keluar. Bila hosting membatasi `max_execution_time` di bawah 60 detik, turunkan `AI_TIMEOUT` agar permintaan AI gagal dengan pesan yang jelas, bukan error 500.

Pastikan HTTPS aktif (SSL gratis dari panel hosting) sebelum mengisi `SESSION_SECURE_COOKIE=true`.

## 6. Perintah instalasi (via SSH)

```bash
cd ~/lms
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force          # di APP_ENV=production hanya membuat akun operator + pengaturan awal
ln -s ~/lms/storage/app/public ~/public_html/storage   # opsional, lihat catatan di bawah
php artisan optimize                 # cache config, route, view, event
```

- **Akun awal:** `operator` / `password`. Sistem **memaksa ganti password** saat login pertama.
- **Tautan storage:** jangan memakai `php artisan storage:link` pada struktur bagian 3, karena perintah CLI membuat tautan di `lms/public`, bukan di `public_html`. Gunakan `ln -s` di atas. Jika hosting tidak mengizinkan symlink, lewati saja: aplikasi otomatis menyajikan file lewat rute `/berkas/...` bila `public_html/storage` tidak ada. (Bila memakai alternatif *document root* ke `lms/public`, `php artisan storage:link` boleh dipakai.)
- Izin folder: `storage/` dan `bootstrap/cache/` harus dapat ditulis (umumnya `755` untuk folder, `644` untuk file sudah cukup).

## 7. Cron job (penjadwal)

Tambahkan satu cron job per menit di panel hosting (*Advanced → Cron Jobs*):

```
* * * * * cd /home/USER/lms && php artisan schedule:run >> /dev/null 2>&1
```

Tugas yang dijalankan:

| Perintah | Jadwal | Fungsi |
|---|---|---|
| `lms:tutup-kuis` | tiap 5 menit | Mengirim otomatis pengerjaan kuis yang waktunya habis (mis. siswa menutup browser) agar skor langsung terhitung |
| `lms:bersihkan-notifikasi` | harian 02:00 | Menghapus notifikasi dibaca > 60 hari dan belum dibaca > 180 hari |

Jika hosting menonaktifkan `proc_open` (penjadwal gagal berjalan), daftarkan perintahnya langsung sebagai dua cron terpisah:

```
*/5 * * * * cd /home/USER/lms && php artisan lms:tutup-kuis >> /dev/null 2>&1
0 2 * * *   cd /home/USER/lms && php artisan lms:bersihkan-notifikasi >> /dev/null 2>&1
```

Pastikan `php` di cron adalah PHP 8.3 (beberapa hosting memakai path seperti `/usr/bin/php8.3` atau `/opt/alt/php83/usr/bin/php`).

## 8. Tanpa akses SSH

1. Di komputer lokal, isi `.env` produksi lalu jalankan `php artisan key:generate` untuk mendapatkan `APP_KEY`, salin nilainya ke `.env` server.
2. Impor struktur dan data awal: jalankan migrasi + seeder produksi ke database lokal kosong (`APP_ENV=production php artisan migrate:fresh --seed`), ekspor lewat phpMyAdmin/`mysqldump`, lalu impor file SQL tersebut melalui phpMyAdmin di hosting.
3. Jangan unggah folder `bootstrap/cache` hasil `optimize` dari lokal (path-nya berbeda); cukup kosongkan isinya kecuali `.gitignore`.
4. Cron job tetap dapat didaftarkan lewat panel tanpa SSH.

## 9. Pemeriksaan setelah deploy

- [ ] Buka situs → muncul halaman login dengan tampilan normal (CSS termuat).
- [ ] Login `operator`, ganti password, isi **Pengaturan Sekolah** (nama, logo, KKM).
- [ ] Unggah logo → gambar tampil (memastikan penyajian file berjalan).
- [ ] Buka alamat acak (mis. `/tidak-ada`) → muncul halaman 404 berbahasa Indonesia, **bukan** detail error Laravel.
- [ ] Buat tahun ajaran aktif, jurusan, kelas, mapel; import guru & siswa dari template Excel.
- [ ] Unduh **Backup** database dari menu operator untuk memastikan fitur backup berjalan.
- [ ] Cek log cron/penjadwal setelah beberapa menit (`php artisan schedule:list` via SSH).

## 10. Memperbarui aplikasi

```bash
# Lokal
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

Di server:

1. `php artisan down` (menampilkan halaman pemeliharaan 503).
2. **Backup database** dari menu operator.
3. Unggah file yang berubah (folder `app`, `config`, `database`, `resources`, `routes`, `vendor`, dan `public/build` → `public_html/build`). Jangan menimpa `.env` dan `storage/app`.
4. Jalankan:

```bash
php artisan migrate --force
php artisan optimize:clear && php artisan optimize
php artisan up
```

## 11. Pemecahan masalah

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| Error 500 tanpa keterangan | `.env` salah / `APP_KEY` kosong / izin `storage` | Lihat `storage/logs/laravel-*.log`; jangan menyalakan `APP_DEBUG=true` di situs publik kecuali sesaat |
| Tampilan polos tanpa CSS | `public_html/build` tidak terunggah | Jalankan `npm run build` di lokal, unggah folder `build` |
| "Unable to locate file in Vite manifest" | `usePublicPath` belum ditambahkan di `index.php` | Ikuti langkah 3 |
| Gambar/lampiran 404 | Symlink storage tidak ada dan cache config lama | `php artisan optimize:clear`; aplikasi otomatis memakai rute `/berkas` |
| Upload gagal untuk file besar | Batas PHP lebih kecil dari `UPLOAD_MAX_KB` | Naikkan `upload_max_filesize` & `post_max_size` |
| Halaman 419 saat mengirim form | Sesi kedaluwarsa atau `SESSION_SECURE_COOKIE=true` di situs tanpa HTTPS | Aktifkan HTTPS atau set ke `false` |
| Kuis tidak tertutup otomatis | Cron belum berjalan | Periksa cron job dan path PHP (bagian 7) |
