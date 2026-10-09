<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Role orang tua/wali: memantau aktivitas anak (satu akun dapat terhubung ke beberapa siswa).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['operator', 'kepsek', 'guru', 'siswa', 'ortu'])->change();
        });

        Schema::create('orang_tua', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('nama');
            $table->enum('hubungan', ['ayah', 'ibu', 'wali'])->default('wali');
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });

        Schema::create('orang_tua_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orang_tua_id')->constrained('orang_tua')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['orang_tua_id', 'siswa_id']);
        });

        Schema::table('pengumuman', function (Blueprint $table) {
            $table->enum('target', ['semua', 'guru', 'siswa', 'ortu', 'kelas'])->default('semua')->change();
        });

        // Siapa yang mengajukan surat izin (siswa sendiri atau orang tuanya).
        Schema::table('surat_izin', function (Blueprint $table) {
            $table->foreignId('pengaju_user_id')->nullable()->after('kelas_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('surat_izin', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pengaju_user_id');
        });

        Schema::table('pengumuman', function (Blueprint $table) {
            $table->enum('target', ['semua', 'guru', 'siswa', 'kelas'])->default('semua')->change();
        });

        Schema::dropIfExists('orang_tua_siswa');
        Schema::dropIfExists('orang_tua');

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['operator', 'kepsek', 'guru', 'siswa'])->change();
        });
    }
};
