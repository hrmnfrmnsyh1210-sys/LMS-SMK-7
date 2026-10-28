<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Absensi guru per sesi mengajar (jam masuk & keluar kelas sesuai jadwal), dipantau kepala sekolah dan operator. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('guru')->cascadeOnDelete();
            $table->foreignId('jadwal_id')->constrained('jadwal')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_masuk');
            $table->time('jam_keluar')->nullable();
            $table->string('status', 20)->default('hadir');
            $table->string('ip_masuk', 45)->nullable();
            $table->string('ip_keluar', 45)->nullable();
            $table->timestamps();

            $table->unique(['jadwal_id', 'tanggal']);
            $table->index(['guru_id', 'tanggal']);
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_guru');
    }
};
