<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengampu_id')->constrained('pengampu')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedInteger('pertemuan_ke');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])->default('hadir');
            $table->timestamps();
            $table->unique(['pengampu_id', 'siswa_id', 'pertemuan_ke']);
        });

        Schema::create('nilai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengampu_id')->constrained('pengampu')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->decimal('nilai_tugas', 5, 2)->nullable();
            $table->decimal('nilai_kuis', 5, 2)->nullable();
            $table->decimal('nilai_uts', 5, 2)->nullable();
            $table->decimal('nilai_uas', 5, 2)->nullable();
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['pengampu_id', 'siswa_id']);
        });

        Schema::create('bobot_nilai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengampu_id')->unique()->constrained('pengampu')->cascadeOnDelete();
            $table->unsignedTinyInteger('bobot_tugas')->default(20);
            $table->unsignedTinyInteger('bobot_kuis')->default(20);
            $table->unsignedTinyInteger('bobot_uts')->default(30);
            $table->unsignedTinyInteger('bobot_uas')->default(30);
            $table->timestamps();
        });

        Schema::create('surat_izin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('jenis', ['izin', 'sakit']);
            $table->text('keterangan');
            $table->string('file_path')->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->foreignId('diproses_oleh')->nullable()->constrained('guru')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_izin');
        Schema::dropIfExists('bobot_nilai');
        Schema::dropIfExists('nilai');
        Schema::dropIfExists('presensi');
    }
};
