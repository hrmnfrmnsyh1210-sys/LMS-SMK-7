<x-layouts.app title="Konten Website">
    <x-page-header title="Konten Website" subtitle="Atur isi halaman depan website profil sekolah yang dapat dilihat publik.">
        <a href="{{ route('beranda') }}" target="_blank" class="btn-secondary"><x-icon name="eye" class="h-4 w-4" /> Lihat Website</a>
    </x-page-header>

    <form method="POST" action="{{ route('operator.website.update') }}" enctype="multipart/form-data" class="space-y-6"
          x-data="{ tab: 'utama' }">
        @csrf
        @method('PUT')

        @php
            $tabs = ['utama' => 'Halaman Utama', 'sambutan' => 'Sambutan', 'profil' => 'Visi, Misi & Sejarah', 'ppdb' => 'PPDB', 'kontak' => 'Kontak & Sosial Media'];
            // Tab yang memuat error validasi dibuka otomatis.
            $tabError = collect([
                'utama' => ['web_hero_judul', 'web_hero_subjudul', 'web_hero_latar', 'web_hero_objek', 'web_nama_singkat', 'web_video_profil', 'web_tahun_berdiri', 'web_akreditasi'],
                'sambutan' => ['web_sambutan_nama', 'web_sambutan_foto', 'web_sambutan_isi'],
                'profil' => ['web_visi', 'web_misi', 'web_sejarah'],
                'ppdb' => ['web_ppdb_judul', 'web_ppdb_teks', 'web_ppdb_tautan'],
                'kontak' => ['web_maps_embed', 'web_whatsapp', 'web_instagram', 'web_facebook', 'web_youtube', 'web_tiktok'],
            ])->search(fn ($kunci) => $errors->hasAny($kunci));
        @endphp
        @if ($tabError)
            <span x-init="tab = @js($tabError)"></span>
        @endif

        <div class="card flex gap-1 overflow-x-auto p-1.5">
            @foreach ($tabs as $kunci => $label)
                <button type="button" @click="tab = @js($kunci)"
                        class="shrink-0 rounded-xl px-4 py-2 text-sm font-medium transition"
                        :class="tab === @js($kunci) ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700'">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Halaman utama --}}
        <div x-show="tab === 'utama'" class="grid gap-6 lg:grid-cols-3">
            <div class="card card-body space-y-4 lg:col-span-2">
                <h3 class="font-semibold">Bagian pembuka (hero)</h3>
                <x-field.input name="web_hero_judul" label="Judul utama" :value="$konten['web_hero_judul']" required />
                <x-field.textarea name="web_hero_subjudul" label="Subjudul" :value="$konten['web_hero_subjudul']" rows="2" />
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field.input name="web_nama_singkat" label="Nama singkat" :value="$konten['web_nama_singkat']" placeholder="SMKN 7"
                                   help="Teks besar di latar hero." />
                    <x-field.input name="web_tahun_berdiri" type="number" label="Tahun berdiri" :value="$konten['web_tahun_berdiri']" placeholder="1990" />
                    <x-field.input name="web_akreditasi" label="Akreditasi" :value="$konten['web_akreditasi']" placeholder="A" />
                </div>
                <x-field.input name="web_video_profil" type="url" label="Video profil sekolah (YouTube)" :value="$konten['web_video_profil']"
                               placeholder="https://www.youtube.com/watch?v=..." help="Bila diisi, tombol putar video muncul di pojok kanan bawah hero." />
                <p class="rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-700">
                    Jumlah siswa, guru, dan jurusan pada halaman depan dihitung otomatis dari data sistem.
                    Nama, alamat, logo, telepon, dan email sekolah diatur di menu <a href="{{ route('operator.pengaturan.edit') }}" class="font-semibold underline">Pengaturan Sekolah</a>.
                </p>
            </div>
            <div class="card card-body">
                @include('operator.website.partials.gambar', [
                    'name' => 'web_hero_objek', 'label' => 'Foto gedung tanpa latar (opsional)', 'path' => $konten['web_hero_objek'],
                    'help' => 'PNG/WEBP transparan berisi gedung sekolah saja, maks. 2 MB. Ditaruh di depan teks besar sehingga gedung tampak menutupi tulisan. Latar foto bisa dihapus gratis lewat remove.bg.',
                    'hapusName' => 'hapus_gambar[]', 'hapusValue' => 'web_hero_objek', 'tinggi' => 'h-48', 'pas' => 'object-contain',
                ])
            </div>
            <div class="card card-body lg:col-span-3">
                @include('operator.website.partials.gambar', [
                    'name' => 'web_hero_latar', 'label' => 'Foto latar bagian pembuka', 'path' => $konten['web_hero_latar'],
                    'help' => 'Foto dokumentasi sekolah yang tampil penuh satu layar, diberi gradasi biru di bagian atas agar teks terbaca. Gunakan foto mendatar (lanskap), min. lebar 1600 px. PNG/JPG/WEBP, maks. 3 MB. Kosongkan untuk memakai latar biru polos.',
                    'hapusName' => 'hapus_gambar[]', 'hapusValue' => 'web_hero_latar', 'tinggi' => 'h-56',
                ])
            </div>
        </div>

        {{-- Sambutan --}}
        <div x-show="tab === 'sambutan'" x-cloak class="grid gap-6 lg:grid-cols-3">
            <div class="card card-body space-y-4 lg:col-span-2">
                <h3 class="font-semibold">Sambutan kepala sekolah</h3>
                <x-field.input name="web_sambutan_nama" label="Nama kepala sekolah" :value="$konten['web_sambutan_nama']"
                               help="Kosongkan untuk memakai nama dari data Kepala Sekolah." />
                <x-field.textarea name="web_sambutan_isi" label="Isi sambutan" :value="$konten['web_sambutan_isi']" rows="9"
                                  help="Pisahkan paragraf dengan baris kosong. Bagian ini disembunyikan bila kosong." />
            </div>
            <div class="card card-body">
                @include('operator.website.partials.gambar', [
                    'name' => 'web_sambutan_foto', 'label' => 'Foto kepala sekolah', 'path' => $konten['web_sambutan_foto'],
                    'help' => 'Foto potret (tegak). PNG/JPG/WEBP, maks. 1 MB.',
                    'hapusName' => 'hapus_gambar[]', 'hapusValue' => 'web_sambutan_foto', 'tinggi' => 'h-64',
                ])
            </div>
        </div>

        {{-- Visi, misi, sejarah --}}
        <div x-show="tab === 'profil'" x-cloak class="card card-body space-y-4">
            <x-field.textarea name="web_visi" label="Visi" :value="$konten['web_visi']" rows="3" />
            <x-field.textarea name="web_misi" label="Misi" :value="$konten['web_misi']" rows="6" help="Tulis satu butir misi per baris." />
            <x-field.textarea name="web_sejarah" label="Sejarah singkat" :value="$konten['web_sejarah']" rows="8" help="Pisahkan paragraf dengan baris kosong." />
        </div>

        {{-- PPDB --}}
        <div x-show="tab === 'ppdb'" x-cloak class="card card-body max-w-3xl space-y-4">
            <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                <input type="hidden" name="web_ppdb_aktif" value="0">
                <input type="checkbox" name="web_ppdb_aktif" value="1" @checked(old('web_ppdb_aktif', $konten['web_ppdb_aktif']) === '1') class="mt-0.5 rounded border-slate-300 text-blue-600">
                <span>
                    <span class="block text-sm font-medium text-slate-800">Tampilkan pengumuman PPDB</span>
                    <span class="block text-xs text-slate-500">Banner pendaftaran muncul di halaman depan selama opsi ini aktif.</span>
                </span>
            </label>
            <x-field.input name="web_ppdb_judul" label="Judul" :value="$konten['web_ppdb_judul']" />
            <x-field.textarea name="web_ppdb_teks" label="Keterangan" :value="$konten['web_ppdb_teks']" rows="3" placeholder="Pendaftaran tahun ajaran 2027/2028 dibuka 1 Mei – 30 Juni 2027." />
            <x-field.input name="web_ppdb_tautan" type="url" label="Tautan pendaftaran" :value="$konten['web_ppdb_tautan']" placeholder="https://..." />
        </div>

        {{-- Kontak --}}
        <div x-show="tab === 'kontak'" x-cloak class="grid gap-6 lg:grid-cols-2">
            <div class="card card-body space-y-4">
                <h3 class="font-semibold">Peta lokasi</h3>
                <x-field.textarea name="web_maps_embed" label="Kode sematan Google Maps" :value="$konten['web_maps_embed']" rows="4"
                                  placeholder="Tempel kode iframe atau tautan https://www.google.com/maps/embed?pb=..."
                                  help="Buka Google Maps → Bagikan → Sematkan peta → Salin HTML, lalu tempel di sini." />
                @if ($konten['web_maps_embed'])
                    <iframe src="{{ $konten['web_maps_embed'] }}" class="h-48 w-full rounded-xl border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                @endif
            </div>
            <div class="card card-body space-y-4">
                <h3 class="font-semibold">Kontak & media sosial</h3>
                <x-field.input name="web_whatsapp" label="Nomor WhatsApp" :value="$konten['web_whatsapp']" placeholder="0812 3456 7890" />
                <x-field.input name="web_instagram" type="url" label="Instagram" :value="$konten['web_instagram']" placeholder="https://instagram.com/..." />
                <x-field.input name="web_facebook" type="url" label="Facebook" :value="$konten['web_facebook']" placeholder="https://facebook.com/..." />
                <x-field.input name="web_youtube" type="url" label="YouTube" :value="$konten['web_youtube']" placeholder="https://youtube.com/@..." />
                <x-field.input name="web_tiktok" type="url" label="TikTok" :value="$konten['web_tiktok']" placeholder="https://tiktok.com/@..." />
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Simpan Konten</button>
        </div>
    </form>
</x-layouts.app>
