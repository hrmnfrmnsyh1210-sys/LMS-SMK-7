<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            $table->string('dokumentasi')->nullable()->after('tugas');
        });

        Schema::table('absensi_guru', function (Blueprint $table) {
            $table->string('dokumentasi')->nullable()->after('aktivitas');
        });
    }

    public function down(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            $table->dropColumn('dokumentasi');
        });

        Schema::table('absensi_guru', function (Blueprint $table) {
            $table->dropColumn('dokumentasi');
        });
    }
};
