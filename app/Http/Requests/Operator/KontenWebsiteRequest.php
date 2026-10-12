<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;

class KontenWebsiteRequest extends FormRequest
{
    /** Kunci pengaturan yang berupa gambar. */
    public const GAMBAR = ['web_hero_latar', 'web_hero_objek', 'web_sambutan_foto'];

    /** Tautan YouTube (watch, youtu.be, shorts, embed) beserta ID videonya. */
    public const POLA_YOUTUBE = '~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/)|youtu\.be/)([\w-]{11})~';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Operator boleh menempel kode <iframe> lengkap dari Google Maps atau tautannya saja;
     * yang disimpan hanya tautan sematan, teks lain di sekitarnya diabaikan.
     */
    protected function prepareForValidation(): void
    {
        $maps = trim(html_entity_decode((string) $this->input('web_maps_embed')));

        if (preg_match('~https://www\.google\.com/maps/embed\?[^"\'\s<>]+~i', $maps, $cocok)) {
            $maps = $cocok[0];
        }

        $this->merge([
            'web_maps_embed' => $maps === '' ? null : $maps,
            'web_ppdb_aktif' => $this->boolean('web_ppdb_aktif') ? '1' : '0',
        ]);
    }

    public function rules(): array
    {
        return [
            'web_hero_judul' => ['required', 'string', 'max:150'],
            'web_hero_subjudul' => ['nullable', 'string', 'max:300'],
            'web_hero_latar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:3072'],
            'web_hero_objek' => ['nullable', 'image', 'mimes:png,webp', 'max:2048'],
            'web_nama_singkat' => ['nullable', 'string', 'max:20'],
            'web_video_profil' => ['nullable', 'url', 'regex:'.self::POLA_YOUTUBE],
            'web_tahun_berdiri' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
            'web_akreditasi' => ['nullable', 'string', 'max:10'],

            'web_sambutan_nama' => ['nullable', 'string', 'max:150'],
            'web_sambutan_foto' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'web_sambutan_isi' => ['nullable', 'string', 'max:3000'],

            'web_visi' => ['nullable', 'string', 'max:1000'],
            'web_misi' => ['nullable', 'string', 'max:3000'],
            'web_sejarah' => ['nullable', 'string', 'max:5000'],

            'web_ppdb_aktif' => ['required', 'in:0,1'],
            'web_ppdb_judul' => ['nullable', 'string', 'max:150'],
            'web_ppdb_teks' => ['nullable', 'string', 'max:500'],
            'web_ppdb_tautan' => ['nullable', 'url', 'max:500'],

            'web_maps_embed' => ['nullable', 'url', 'starts_with:https://www.google.com/maps/embed', 'max:2000'],
            'web_whatsapp' => ['nullable', 'regex:/^[0-9+\- ]{8,20}$/'],
            'web_instagram' => ['nullable', 'url', 'max:255'],
            'web_facebook' => ['nullable', 'url', 'max:255'],
            'web_youtube' => ['nullable', 'url', 'max:255'],
            'web_tiktok' => ['nullable', 'url', 'max:255'],

            'hapus_gambar' => ['nullable', 'array'],
            'hapus_gambar.*' => ['in:'.implode(',', self::GAMBAR)],
        ];
    }

    public function messages(): array
    {
        return [
            'web_maps_embed.starts_with' => 'Tautan peta harus berupa tautan sematan Google Maps (Bagikan → Sematkan peta).',
            'web_video_profil.regex' => 'Tautan video harus berupa tautan YouTube.',
            'web_whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, +, atau -.',
        ];
    }

    public function attributes(): array
    {
        return [
            'web_hero_judul' => 'judul utama', 'web_hero_subjudul' => 'subjudul', 'web_hero_latar' => 'foto latar', 'web_hero_objek' => 'foto objek', 'web_nama_singkat' => 'nama singkat', 'web_video_profil' => 'video profil',
            'web_tahun_berdiri' => 'tahun berdiri', 'web_akreditasi' => 'akreditasi',
            'web_sambutan_nama' => 'nama kepala sekolah', 'web_sambutan_foto' => 'foto kepala sekolah', 'web_sambutan_isi' => 'isi sambutan',
            'web_visi' => 'visi', 'web_misi' => 'misi', 'web_sejarah' => 'sejarah',
            'web_ppdb_judul' => 'judul PPDB', 'web_ppdb_teks' => 'keterangan PPDB', 'web_ppdb_tautan' => 'tautan pendaftaran',
            'web_maps_embed' => 'tautan peta', 'web_whatsapp' => 'nomor WhatsApp',
            'web_instagram' => 'Instagram', 'web_facebook' => 'Facebook', 'web_youtube' => 'YouTube', 'web_tiktok' => 'TikTok',
        ];
    }
}
