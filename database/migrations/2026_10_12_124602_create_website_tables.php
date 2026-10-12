<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Konten website profil sekolah (landing page) yang dikelola operator. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penulis_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kategori', 20)->default('berita');
            $table->string('judul');
            $table->string('slug')->unique();
            $table->string('ringkasan', 300)->nullable();
            $table->longText('isi');
            $table->string('gambar')->nullable();
            $table->timestamp('diterbitkan_pada')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('galeri', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('keterangan', 500)->nullable();
            $table->string('gambar');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::table('jurusan', function (Blueprint $table) {
            $table->text('deskripsi')->nullable()->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('jurusan', function (Blueprint $table) {
            $table->dropColumn('deskripsi');
        });

        Schema::dropIfExists('galeri');
        Schema::dropIfExists('berita');
    }
};
