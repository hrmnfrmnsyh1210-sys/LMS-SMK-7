<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/** Berita, prestasi, dan agenda yang tampil di website sekolah. */
class Berita extends Model
{
    use HasFactory;

    protected $table = 'berita';

    public const KATEGORI = ['berita' => 'Berita', 'prestasi' => 'Prestasi', 'agenda' => 'Agenda'];

    protected $fillable = ['penulis_user_id', 'kategori', 'judul', 'slug', 'ringkasan', 'isi', 'gambar', 'diterbitkan_pada'];

    protected function casts(): array
    {
        return ['diterbitkan_pada' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (Berita $berita) {
            $berita->slug ??= static::slugUnik($berita->judul);
        });
    }

    /** Slug dari judul, diberi akhiran angka bila sudah dipakai. */
    public static function slugUnik(string $judul): string
    {
        $dasar = Str::slug($judul) ?: 'berita';
        $slug = $dasar;

        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = "{$dasar}-{$i}";
        }

        return $slug;
    }

    /** Hanya berita yang sudah terbit (bukan draf dan bukan terjadwal). */
    public function scopeTerbit(Builder $query): Builder
    {
        return $query->whereNotNull('diterbitkan_pada')->where('diterbitkan_pada', '<=', now());
    }

    public function sudahTerbit(): bool
    {
        return $this->diterbitkan_pada !== null && $this->diterbitkan_pada->lte(now());
    }

    public function labelKategori(): string
    {
        return self::KATEGORI[$this->kategori] ?? ucfirst($this->kategori);
    }

    /** Ringkasan untuk kartu: kolom ringkasan atau potongan isi. */
    public function cuplikan(int $panjang = 140): string
    {
        return $this->ringkasan ?: Str::limit(trim(strip_tags(Str::markdown($this->isi))), $panjang);
    }

    /** Isi berformat Markdown; HTML mentah dibuang agar aman ditampilkan. */
    public function isiHtml(): HtmlString
    {
        return new HtmlString(Str::markdown($this->isi, ['html_input' => 'strip', 'allow_unsafe_links' => false]));
    }

    public function penulis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penulis_user_id');
    }
}
