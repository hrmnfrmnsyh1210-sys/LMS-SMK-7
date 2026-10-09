<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengampu_id')->constrained('pengampu')->cascadeOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('file_path')->nullable();
            $table->string('tautan')->nullable();
            $table->unsignedInteger('pertemuan_ke');
            $table->timestamps();
        });

        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengampu_id')->constrained('pengampu')->cascadeOnDelete();
            $table->string('judul');
            $table->text('deskripsi');
            $table->string('file_path')->nullable();
            $table->dateTime('deadline');
            $table->unsignedInteger('nilai_maks')->default(100);
            $table->timestamps();
        });

        Schema::create('pengumpulan_tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')->constrained('tugas')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->string('file_path')->nullable();
            $table->text('jawaban_teks')->nullable();
            $table->dateTime('waktu_kumpul');
            $table->decimal('nilai', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            $table->unique(['tugas_id', 'siswa_id']);
        });

        Schema::create('kuis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengampu_id')->constrained('pengampu')->cascadeOnDelete();
            $table->string('judul');
            $table->enum('jenis', ['kuis', 'uts', 'uas'])->default('kuis');
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_selesai');
            $table->unsignedInteger('durasi_menit');
            $table->boolean('acak_soal')->default(false);
            $table->boolean('tampilkan_hasil')->default(true);
            $table->timestamps();
        });

        Schema::create('soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kuis_id')->constrained('kuis')->cascadeOnDelete();
            $table->enum('tipe', ['pg', 'esai'])->default('pg');
            $table->text('pertanyaan');
            $table->text('opsi_a')->nullable();
            $table->text('opsi_b')->nullable();
            $table->text('opsi_c')->nullable();
            $table->text('opsi_d')->nullable();
            $table->enum('kunci_jawaban', ['a', 'b', 'c', 'd'])->nullable();
            $table->unsignedInteger('bobot')->default(1);
            $table->timestamps();
        });

        Schema::create('jawaban_kuis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kuis_id')->constrained('kuis')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->decimal('total_skor', 5, 2)->nullable();
            $table->dateTime('waktu_mulai');
            $table->dateTime('waktu_submit')->nullable();
            $table->json('urutan_soal')->nullable();
            $table->unsignedInteger('jumlah_pindah_tab')->default(0);
            $table->timestamps();
            $table->unique(['kuis_id', 'siswa_id']);
        });

        Schema::create('jawaban_kuis_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jawaban_kuis_id')->constrained('jawaban_kuis')->cascadeOnDelete();
            $table->foreignId('soal_id')->constrained('soal')->cascadeOnDelete();
            $table->text('jawaban')->nullable();
            $table->decimal('skor', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['jawaban_kuis_id', 'soal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jawaban_kuis_detail');
        Schema::dropIfExists('jawaban_kuis');
        Schema::dropIfExists('soal');
        Schema::dropIfExists('kuis');
        Schema::dropIfExists('pengumpulan_tugas');
        Schema::dropIfExists('tugas');
        Schema::dropIfExists('materi');
    }
};
