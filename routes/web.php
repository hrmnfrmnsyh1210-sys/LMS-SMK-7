<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BerkasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Guru;
use App\Http\Controllers\Operator;
use App\Http\Controllers\ProfilController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:20,1');
});

Route::middleware(['auth', 'aktif'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    // Profil & ganti password (bisa diakses walau masih wajib ganti password).
    Route::get('profil', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::get('profil/password', [ProfilController::class, 'password'])->name('profil.password');
    Route::put('profil/password', [ProfilController::class, 'updatePassword'])->name('profil.password.update');

    // Fallback penyajian file bila storage:link tidak tersedia.
    Route::get('berkas/{path}', BerkasController::class)->where('path', '.*')->name('berkas');

    Route::middleware('ganti.password')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'redirect'])->name('dashboard');

        // ===================== OPERATOR =====================
        Route::prefix('operator')->name('operator.')->middleware('role:operator')->group(function () {
            Route::get('/', Operator\DashboardController::class)->name('dashboard');

            // Manajemen pengguna
            Route::resource('guru', Operator\GuruController::class)->except('show')->parameters(['guru' => 'guru']);
            Route::resource('siswa', Operator\SiswaController::class)->except('show')->parameters(['siswa' => 'siswa']);
            Route::resource('kepsek', Operator\KepsekController::class)->except('show')->parameters(['kepsek' => 'kepsek']);
            Route::post('akun/{user}/reset-password', [Operator\AkunController::class, 'resetPassword'])->name('akun.reset-password');
            Route::patch('akun/{user}/status', [Operator\AkunController::class, 'toggleStatus'])->name('akun.status');

            // Import Excel
            Route::get('import', [Operator\ImportController::class, 'index'])->name('import.index');
            Route::get('import/template/{jenis}', [Operator\ImportController::class, 'template'])->whereIn('jenis', ['guru', 'siswa'])->name('import.template');
            Route::post('import/{jenis}', [Operator\ImportController::class, 'store'])->whereIn('jenis', ['guru', 'siswa'])->name('import.store');

            // Data master akademik
            Route::resource('tahun-ajaran', Operator\TahunAjaranController::class)->except('show')->parameters(['tahun-ajaran' => 'tahunAjaran']);
            Route::post('tahun-ajaran/{tahunAjaran}/aktifkan', [Operator\TahunAjaranController::class, 'aktifkan'])->name('tahun-ajaran.aktifkan');
            Route::resource('jurusan', Operator\JurusanController::class)->except('show')->parameters(['jurusan' => 'jurusan']);
            Route::resource('kelas', Operator\KelasController::class)->except('show')->parameters(['kelas' => 'kelas']);
            Route::resource('mapel', Operator\MapelController::class)->except('show')->parameters(['mapel' => 'mapel']);

            // Penugasan
            Route::get('kelas/{kelas}/siswa', [Operator\KelasSiswaController::class, 'show'])->name('kelas.siswa.index');
            Route::post('kelas/{kelas}/siswa', [Operator\KelasSiswaController::class, 'store'])->name('kelas.siswa.store');
            Route::delete('kelas/{kelas}/siswa/{siswa}', [Operator\KelasSiswaController::class, 'destroy'])->name('kelas.siswa.destroy');
            Route::resource('pengampu', Operator\PengampuController::class)->except('show')->parameters(['pengampu' => 'pengampu']);
            Route::get('wali-kelas', [Operator\WaliKelasController::class, 'index'])->name('wali-kelas.index');
            Route::put('wali-kelas', [Operator\WaliKelasController::class, 'update'])->name('wali-kelas.update');

            // Jadwal
            Route::resource('jadwal', Operator\JadwalController::class)->except('show')->parameters(['jadwal' => 'jadwal']);

            // Pengaturan sistem
            Route::get('pengaturan', [Operator\PengaturanController::class, 'edit'])->name('pengaturan.edit');
            Route::put('pengaturan', [Operator\PengaturanController::class, 'update'])->name('pengaturan.update');
            Route::get('kenaikan-kelas', [Operator\KenaikanKelasController::class, 'index'])->name('kenaikan-kelas.index');
            Route::post('kenaikan-kelas/buat-kelas', [Operator\KenaikanKelasController::class, 'buatKelas'])->name('kenaikan-kelas.buat-kelas');
            Route::post('kenaikan-kelas/proses', [Operator\KenaikanKelasController::class, 'proses'])->name('kenaikan-kelas.proses');
            Route::get('pergantian-tahun-ajaran', [Operator\PergantianTahunAjaranController::class, 'index'])->name('pergantian-tahun-ajaran.index');
            Route::post('pergantian-tahun-ajaran', [Operator\PergantianTahunAjaranController::class, 'store'])->name('pergantian-tahun-ajaran.store');

            // Log & backup
            Route::get('log-aktivitas', [Operator\LogAktivitasController::class, 'index'])->name('log.index');
            Route::get('backup', [Operator\BackupController::class, 'index'])->name('backup.index');
            Route::get('backup/download', [Operator\BackupController::class, 'download'])->name('backup.download');
            Route::post('backup/restore', [Operator\BackupController::class, 'restore'])->name('backup.restore');
        });

        // ========== KEPSEK / GURU / SISWA (dilengkapi pada fase 2 & 3) ==========
        Route::prefix('kepsek')->name('kepsek.')->middleware('role:kepsek')->group(function () {
            Route::get('/', [DashboardController::class, 'role'])->name('dashboard');
        });

        // ======================= GURU =======================
        Route::prefix('guru')->name('guru.')->middleware('role:guru')->group(function () {
            Route::get('/', Guru\DashboardController::class)->name('dashboard');
            Route::get('kelas', [Guru\KelasAjarController::class, 'index'])->name('kelas.index');
            Route::get('soal/template', [Guru\SoalController::class, 'template'])->name('soal.template');

            Route::get('pengumuman', [Guru\PengumumanController::class, 'index'])->name('pengumuman.index');
            Route::post('pengumuman', [Guru\PengumumanController::class, 'store'])->name('pengumuman.store');
            Route::delete('pengumuman/{pengumuman}', [Guru\PengumumanController::class, 'destroy'])->name('pengumuman.destroy');

            // Wali kelas
            Route::get('wali-kelas', [Guru\WaliKelasController::class, 'rekap'])->name('wali.rekap');
            Route::get('wali-kelas/surat-izin', [Guru\WaliKelasController::class, 'suratIzin'])->name('wali.surat-izin');
            Route::put('wali-kelas/surat-izin/{suratIzin}', [Guru\WaliKelasController::class, 'proses'])->name('wali.surat-izin.proses');

            // Pembelajaran per kelas ajar — hanya pengampu milik guru (policy) & data anak harus milik pengampu (scoped).
            Route::prefix('kelas/{pengampu}')->middleware('can:kelola,pengampu')->scopeBindings()->group(function () {
                Route::get('/', [Guru\KelasAjarController::class, 'show'])->name('kelas.show');

                Route::resource('materi', Guru\MateriController::class)->except('show')->parameters(['materi' => 'materi']);

                Route::resource('tugas', Guru\TugasController::class)->parameters(['tugas' => 'tugas']);
                Route::put('tugas/{tugas}/pengumpulan/{pengumpulan}', [Guru\TugasController::class, 'nilai'])->name('tugas.nilai');

                Route::resource('kuis', Guru\KuisController::class)->parameters(['kuis' => 'kuis']);
                Route::resource('kuis.soal', Guru\SoalController::class)->except(['index', 'show'])
                    ->parameters(['kuis' => 'kuis', 'soal' => 'soal'])->names('soal');
                Route::post('kuis/{kuis}/soal-import', [Guru\SoalController::class, 'import'])->name('soal.import');
                Route::get('kuis/{kuis}/hasil', [Guru\HasilKuisController::class, 'index'])->name('kuis.hasil');
                Route::post('kuis/{kuis}/hasil/hitung-ulang', [Guru\HasilKuisController::class, 'hitungUlang'])->name('kuis.hitung-ulang');
                Route::get('kuis/{kuis}/hasil/{jawaban}', [Guru\HasilKuisController::class, 'koreksi'])->name('kuis.koreksi');
                Route::put('kuis/{kuis}/hasil/{jawaban}', [Guru\HasilKuisController::class, 'simpanKoreksi'])->name('kuis.koreksi.simpan');

                Route::get('presensi', [Guru\PresensiController::class, 'index'])->name('presensi.index');
                Route::get('presensi/isi', [Guru\PresensiController::class, 'form'])->name('presensi.form');
                Route::post('presensi', [Guru\PresensiController::class, 'store'])->name('presensi.store');

                Route::get('nilai', [Guru\NilaiController::class, 'index'])->name('nilai.index');
                Route::put('nilai', [Guru\NilaiController::class, 'simpan'])->name('nilai.simpan');
                Route::put('nilai/bobot', [Guru\NilaiController::class, 'bobot'])->name('nilai.bobot');
                Route::post('nilai/ambil-ujian', [Guru\NilaiController::class, 'ambilUjian'])->name('nilai.ambil-ujian');
                Route::get('nilai/ekspor', [Guru\NilaiController::class, 'ekspor'])->name('nilai.ekspor');

                Route::get('forum', [Guru\ForumController::class, 'index'])->name('forum.index');
                Route::post('forum', [Guru\ForumController::class, 'store'])->name('forum.store');
                Route::get('forum/{topik}', [Guru\ForumController::class, 'show'])->name('forum.show');
                Route::post('forum/{topik}/balas', [Guru\ForumController::class, 'balas'])->name('forum.balas');
                Route::delete('forum/{topik}', [Guru\ForumController::class, 'destroy'])->name('forum.destroy');
                Route::delete('forum/{topik}/balasan/{balasan}', [Guru\ForumController::class, 'hapusBalasan'])->name('forum.balasan.destroy');

                Route::get('monitoring', [Guru\MonitoringController::class, 'index'])->name('monitoring.index');
                Route::get('monitoring/{siswa}', [Guru\MonitoringController::class, 'siswa'])->name('monitoring.siswa')->withoutScopedBindings();
            });
        });

        Route::prefix('siswa')->name('siswa.')->middleware('role:siswa')->group(function () {
            Route::get('/', [DashboardController::class, 'role'])->name('dashboard');
        });
    });
});
