<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            $table->string('diabsen_oleh', 20)->default('guru')->after('status');
            $table->timestamp('waktu_absen')->nullable()->after('diabsen_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('presensi', function (Blueprint $table) {
            $table->dropColumn(['diabsen_oleh', 'waktu_absen']);
        });
    }
};
