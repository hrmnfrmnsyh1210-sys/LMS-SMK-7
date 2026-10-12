@php
    $paragraf = fn (?string $teks) => collect(preg_split('/\R\s*\R/', trim((string) $teks)))->filter();
    $misi = collect(preg_split('/\R/', trim($sekolah['web_misi'])))->map(fn ($m) => trim(ltrim($m, "-*•0123456789. \t")))->filter();
    $heroLatar = file_url($sekolah['web_hero_latar']);
    $heroObjek = file_url($sekolah['web_hero_objek']);
    $namaSingkat = $sekolah['web_nama_singkat'] ?: $sekolah['nama_sekolah'];
    // Ukuran teks raksasa menyesuaikan panjang nama agar selalu muat ±80% lebar layar.
    $ukuranTeksLatar = round(110 / max(mb_strlen($namaSingkat), 4), 1);
    $idVideo = preg_match(\App\Http\Requests\Operator\KontenWebsiteRequest::POLA_YOUTUBE, (string) $sekolah['web_video_profil'], $cocok) ? $cocok[1] : null;
    $fotoKepsek = file_url($sekolah['web_sambutan_foto']);
    $namaKepsek = $sekolah['web_sambutan_nama'] ?: $namaKepsek;
    $angka = array_values(array_filter([
        ['nilai' => $statistik['siswa'], 'label' => 'Siswa aktif'],
        ['nilai' => $statistik['guru'], 'label' => 'Guru & tenaga pendidik'],
        ['nilai' => $statistik['jurusan'], 'label' => 'Program keahlian'],
        $sekolah['web_akreditasi'] ? ['nilai' => $sekolah['web_akreditasi'], 'label' => 'Terakreditasi'] : null,
        ! $sekolah['web_akreditasi'] && $sekolah['web_tahun_berdiri'] ? ['nilai' => $sekolah['web_tahun_berdiri'], 'label' => 'Tahun berdiri'] : null,
    ]));
    $adaProfil = $sekolah['web_visi'] || $misi->isNotEmpty() || $sekolah['web_sejarah'];
@endphp

<x-layouts.web>
    @if ($heroLatar)
        @push('head')
            <meta property="og:image" content="{{ $heroLatar }}">
        @endpush
    @endif

    {{-- ================= HERO ================= --}}
    <section class="relative isolate flex min-h-[40rem] flex-col overflow-hidden bg-navy-800 text-white sm:min-h-[46rem] lg:h-screen lg:max-h-[60rem] lg:min-h-[44rem]"
             x-data="{ video: false }" @keydown.escape.window="video = false">
        @if ($heroLatar)
            <img src="{{ $heroLatar }}" alt="" class="absolute inset-0 -z-30 h-full w-full object-cover" fetchpriority="high">
        @endif
        {{-- Biru tua di atas memudar ke foto di bawah. --}}
        <div @class([
            'absolute inset-0 -z-20',
            'bg-linear-to-b from-navy-900/75 via-navy-800/25 via-35% to-transparent' => $heroLatar,
            'bg-linear-to-b from-navy-800 to-navy-900' => ! $heroLatar,
        ])></div>

        {{-- Nama sekolah raksasa sebagai latar teks. --}}
        <div aria-hidden="true"
             class="pointer-events-none absolute inset-x-0 top-[15%] -z-10 bg-linear-to-b from-white/60 via-white/25 to-white/0 bg-clip-text text-center font-display leading-none font-bold tracking-tight whitespace-nowrap text-transparent uppercase select-none lg:top-[13%]"
             style="font-size: clamp(3.5rem, {{ $ukuranTeksLatar }}vw, 17rem)">{{ $namaSingkat }}</div>

        @if ($heroObjek)
            <img src="{{ $heroObjek }}" alt="" class="pointer-events-none absolute bottom-0 left-1/2 h-[72%] max-w-none -translate-x-1/2 object-contain object-bottom lg:h-[80%]">
        @endif
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/2 bg-linear-to-t from-navy-950/80 via-navy-950/25 to-transparent"></div>

        <x-web.gelombang />

        <div class="relative mx-auto mt-auto w-full max-w-7xl px-4 pt-40 pb-24 sm:px-6 sm:pb-28 lg:pb-32">
            <div class="flex flex-col gap-10 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl [text-shadow:0_2px_16px_rgba(5,22,51,0.45)]">
                    <p class="flex items-center gap-2 text-sm font-medium text-kuning-300">
                        <x-icon name="academic-cap" class="h-5 w-5" /> Selamat datang di {{ $sekolah['nama_sekolah'] }}
                    </p>
                    <h1 class="mt-3 font-display text-4xl leading-[1.1] font-bold sm:text-5xl lg:text-6xl">{{ $sekolah['web_hero_judul'] }}</h1>
                    @if ($sekolah['web_hero_subjudul'])
                        <p class="mt-5 max-w-xl text-base leading-relaxed text-white/80 sm:text-lg">{{ $sekolah['web_hero_subjudul'] }}</p>
                    @endif
                    <div class="mt-8 flex flex-wrap items-center gap-x-7 gap-y-4">
                        <a href="#jurusan" class="inline-flex items-center gap-2 rounded-full bg-kuning-400 px-6 py-3 text-sm font-semibold text-navy-950 transition hover:bg-kuning-300">
                            Program Keahlian <x-icon name="arrow-right" class="h-4 w-4" />
                        </a>
                        <a href="#profil" class="inline-flex items-center gap-2 text-sm font-semibold text-white underline-offset-8 hover:underline">Tentang sekolah</a>
                    </div>
                </div>

                @if ($idVideo)
                    <button type="button" @click="video = true" class="group flex items-center gap-4 self-start text-left lg:self-end">
                        <span class="relative flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/50 backdrop-blur transition group-hover:bg-kuning-400 group-hover:text-navy-950 group-hover:ring-kuning-400">
                            <span class="absolute inset-0 animate-ping rounded-full ring-1 ring-white/40 [animation-duration:2.5s]"></span>
                            <x-icon name="play" class="ml-1 h-6 w-6 fill-current" />
                        </span>
                        <span>
                            <span class="block font-display text-lg font-semibold">Profil Sekolah</span>
                            <span class="block text-sm text-white/70">Tonton video</span>
                        </span>
                    </button>

                    <template x-teleport="body">
                        <div x-show="video" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-navy-950/90 p-4" @click.self="video = false">
                            <button type="button" class="absolute top-4 right-4 p-2 text-white hover:text-kuning-300" @click="video = false" aria-label="Tutup"><x-icon name="x" class="h-8 w-8" /></button>
                            <template x-if="video">
                                <iframe src="https://www.youtube-nocookie.com/embed/{{ $idVideo }}?autoplay=1&rel=0" title="Video profil {{ $sekolah['nama_sekolah'] }}"
                                        class="aspect-video w-full max-w-5xl rounded-lg bg-black" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
                            </template>
                        </div>
                    </template>
                @endif
            </div>
        </div>
    </section>

    {{-- Statistik s.d. galeri dalam satu wadah: hiasan biru boleh melintasi batas section tanpa terpotong. --}}
    <div class="relative isolate overflow-hidden">

    {{-- Statistik --}}
    <section class="relative bg-white">
        <div @class(['mx-auto grid max-w-7xl grid-cols-2 px-4 sm:px-6 lg:divide-x lg:divide-slate-200', 'lg:grid-cols-4' => count($angka) === 4, 'lg:grid-cols-3' => count($angka) === 3])>
            @foreach ($angka as $a)
                <div class="px-2 py-8 lg:px-10 lg:first:pl-0">
                    <div class="font-display text-3xl font-bold text-navy-800 sm:text-4xl">{{ is_int($a['nilai']) ? number_format($a['nilai'], 0, ',', '.') : $a['nilai'] }}</div>
                    <div class="mt-1 text-xs font-medium tracking-wider text-slate-500 uppercase">{{ $a['label'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================= PPDB ================= --}}
    @if ($sekolah['web_ppdb_aktif'] === '1')
        <section class="mx-auto mt-16 max-w-7xl px-4 sm:px-6">
            <div class="relative flex flex-col items-start gap-6 overflow-hidden rounded-xl bg-navy-800 p-7 text-white sm:p-10 md:flex-row md:items-center">
                <div class="absolute inset-y-0 left-0 w-1.5 bg-kuning-400"></div>
                <div class="flex-1">
                    <p class="text-xs font-semibold tracking-[0.2em] text-kuning-300 uppercase">Pendaftaran dibuka</p>
                    <h2 class="mt-2 font-display text-2xl font-bold sm:text-3xl">{{ $sekolah['web_ppdb_judul'] ?: 'Penerimaan Peserta Didik Baru' }}</h2>
                    @if ($sekolah['web_ppdb_teks'])
                        <p class="mt-2 max-w-2xl text-white/75">{{ $sekolah['web_ppdb_teks'] }}</p>
                    @endif
                </div>
                @if ($sekolah['web_ppdb_tautan'])
                    <a href="{{ $sekolah['web_ppdb_tautan'] }}" target="_blank" rel="noopener" class="inline-flex shrink-0 items-center gap-2 rounded-full bg-kuning-400 px-6 py-3 text-sm font-semibold text-navy-950 transition hover:bg-kuning-300">
                        Daftar Sekarang <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                @endif
            </div>
        </section>
    @endif

    {{-- ================= SAMBUTAN ================= --}}
    @if ($sekolah['web_sambutan_isi'])
        <section class="relative">
            <x-web.lapisan sudut="kanan" y="top-6" />
            {{-- Melintasi batas sambutan → program keahlian (bila tidak ada section profil berlatar di antaranya). --}}
            <x-web.lapisan sudut="kiri" :y="$adaProfil ? 'top-1/2 -translate-y-1/2' : 'bottom-0 translate-y-1/2'" :cincin="false" />
            <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:py-24">
            <div class="grid items-center gap-12 lg:grid-cols-5">
                <div class="lg:col-span-2">
                    <div class="relative mx-auto max-w-sm">
                        <div class="absolute -inset-8 -z-10 rounded-[2.5rem] bg-linear-to-br from-[#2f6fed]/15 via-[#13b5cf]/10 to-transparent"></div>
                        <div class="absolute -top-5 -left-5 h-24 w-24 rounded-full border-2 border-dashed border-[#2f6fed]/25"></div>
                        <div class="absolute inset-0 translate-x-4 translate-y-4 rounded-xl bg-linear-to-br from-[#2f6fed] to-[#13b5cf] opacity-80"></div>
                        @if ($fotoKepsek)
                            <img src="{{ $fotoKepsek }}" alt="{{ $namaKepsek }}" class="relative aspect-4/5 w-full rounded-xl object-cover">
                        @else
                            <div class="relative flex aspect-4/5 w-full items-center justify-center rounded-xl bg-linear-to-br from-navy-700 to-navy-900 text-white">
                                <x-icon name="user" class="h-24 w-24 opacity-70" />
                            </div>
                        @endif
                    </div>
                </div>
                <div class="lg:col-span-3 lg:pr-24 xl:pr-28">
                    <span class="text-xs font-semibold tracking-[0.2em] text-navy-600 uppercase">Sambutan Kepala Sekolah</span>
                    <h2 class="mt-2 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Selamat datang di {{ $sekolah['nama_sekolah'] }}</h2>
                    <div class="mt-6 space-y-4 leading-relaxed text-slate-600">
                        @foreach ($paragraf($sekolah['web_sambutan_isi']) as $p)
                            <p>{!! nl2br(e($p)) !!}</p>
                        @endforeach
                    </div>
                    @if ($namaKepsek)
                        <div class="mt-6 border-l-4 border-kuning-400 pl-4">
                            <div class="font-semibold text-slate-800">{{ $namaKepsek }}</div>
                            <div class="text-sm text-slate-500">Kepala {{ $sekolah['nama_sekolah'] }}</div>
                        </div>
                    @endif
                </div>
            </div>
            </div>
        </section>
    @endif

    {{-- ================= PROFIL: VISI MISI SEJARAH ================= --}}
    @if ($adaProfil)
        <section id="profil" class="relative isolate scroll-mt-20 overflow-hidden bg-latar py-20 lg:py-24">
            <x-web.lapisan sudut="kiri-atas" :cincin="false" class="opacity-70" />
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="text-xs font-semibold tracking-[0.2em] text-navy-600 uppercase">Profil Sekolah</span>
                    <h2 class="mt-2 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Visi, Misi & Perjalanan Kami</h2>
                </div>

                <div class="mt-12 grid gap-6 lg:grid-cols-2">
                    @if ($sekolah['web_visi'])
                        <div class="relative isolate overflow-hidden rounded-xl bg-linear-to-br from-navy-800 via-navy-700 to-[#2f6fed] p-8 text-white">
                            <x-web.lapisan sudut="kanan-bawah" :cincin="false" class="opacity-40" />
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/20"><x-icon name="light-bulb" class="h-6 w-6" /></span>
                            <h3 class="mt-5 text-xl font-bold">Visi</h3>
                            <p class="mt-3 text-lg leading-relaxed text-white/75">{{ $sekolah['web_visi'] }}</p>
                        </div>
                    @endif
                    @if ($misi->isNotEmpty())
                        <div class="rounded-xl bg-white p-8 ring-1 ring-slate-100 @if (! $sekolah['web_visi']) lg:col-span-2 @endif">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-kuning-300/40 text-navy-800"><x-icon name="rocket" class="h-6 w-6" /></span>
                            <h3 class="mt-5 text-xl font-bold text-slate-800">Misi</h3>
                            <ol class="mt-4 space-y-3">
                                @foreach ($misi as $m)
                                    <li class="flex gap-3">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-navy-800 text-xs font-semibold text-white">{{ $loop->iteration }}</span>
                                        <span class="text-slate-600">{{ $m }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                </div>

                @if ($sekolah['web_sejarah'])
                    <div class="mt-6 rounded-xl bg-white p-8 ring-1 ring-slate-100">
                        <h3 class="flex items-center gap-2 text-xl font-bold text-slate-800"><x-icon name="clock" class="h-6 w-6 text-navy-700" /> Sejarah Singkat</h3>
                        <div class="mt-4 space-y-4 leading-relaxed text-slate-600">
                            @foreach ($paragraf($sekolah['web_sejarah']) as $p)
                                <p>{!! nl2br(e($p)) !!}</p>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ================= JURUSAN ================= --}}
    <section id="jurusan" class="relative scroll-mt-20 py-20 lg:py-24">
        @if ($berita->isEmpty() && $galeri->isNotEmpty())
            {{-- Melintasi batas program keahlian → galeri. --}}
            <x-web.lapisan sudut="kanan" y="bottom-0 translate-y-1/2" />
        @endif
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span class="text-xs font-semibold tracking-[0.2em] text-navy-600 uppercase">Program Keahlian</span>
                <h2 class="mt-2 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Pilih jalur kompetensimu</h2>
                <p class="mt-3 text-slate-500">Kurikulum yang selaras dengan kebutuhan dunia usaha dan dunia industri.</p>
            </div>

            @if ($jurusan->isEmpty())
                <p class="mt-10 text-center text-slate-400">Informasi program keahlian akan segera tersedia.</p>
            @else
                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @php $warna = ['from-navy-700 to-navy-800', 'from-navy-600 to-navy-700', 'from-navy-800 to-navy-900']; @endphp
                    @foreach ($jurusan as $j)
                        <div class="group relative isolate overflow-hidden rounded-xl bg-white p-7 shadow-sm shadow-slate-900/5 ring-1 ring-slate-100 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-[#2f6fed]/10">
                            <div aria-hidden="true" class="absolute -top-16 -right-16 -z-10 h-40 w-40 rounded-full bg-linear-to-br from-[#2f6fed]/15 to-[#13b5cf]/5 transition duration-500 group-hover:scale-125"></div>
                            <div aria-hidden="true" class="absolute -top-6 -right-6 -z-10 h-24 w-24 rounded-full bg-linear-to-br from-[#2f6fed]/20 to-[#13b5cf]/10"></div>
                            <div aria-hidden="true" class="absolute inset-x-0 bottom-0 h-1 bg-linear-to-r from-[#2f6fed] to-[#13b5cf] opacity-0 transition group-hover:opacity-100"></div>
                            <span class="inline-flex h-14 min-w-14 items-center justify-center rounded-2xl bg-linear-to-br {{ $warna[$loop->index % 3] }} px-3 text-sm font-bold text-white shadow-md">{{ $j->kode }}</span>
                            <h3 class="mt-5 text-lg font-semibold text-slate-800">{{ $j->nama }}</h3>
                            @if ($j->deskripsi)
                                <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $j->deskripsi }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ================= BERITA ================= --}}
    @if ($berita->isNotEmpty())
        <section class="relative isolate overflow-hidden bg-latar py-20 lg:py-24">
            <x-web.lapisan sudut="kiri-bawah" :cincin="false" class="opacity-60" />
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <span class="text-xs font-semibold tracking-[0.2em] text-navy-600 uppercase">Kabar Sekolah</span>
                        <h2 class="mt-2 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Berita & Agenda Terbaru</h2>
                    </div>
                    <a href="{{ route('web.berita.index') }}" class="btn-secondary rounded-full">Semua berita <x-icon name="arrow-right" class="h-4 w-4" /></a>
                </div>
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    @foreach ($berita as $b)
                        @include('web.berita.kartu', ['berita' => $b])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================= GALERI ================= --}}
    @if ($galeri->isNotEmpty())
        <section class="relative py-20 lg:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <span class="text-xs font-semibold tracking-[0.2em] text-navy-600 uppercase">Galeri</span>
                        <h2 class="mt-2 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Momen di sekolah kami</h2>
                    </div>
                    <a href="{{ route('web.galeri') }}" class="btn-secondary rounded-full">Lihat galeri <x-icon name="arrow-right" class="h-4 w-4" /></a>
                </div>
                @include('web.galeri-grid', ['galeri' => $galeri])
            </div>
        </section>
    @endif

    </div>

    {{-- ================= KONTAK ================= --}}
    <section id="kontak" class="relative isolate scroll-mt-20 overflow-hidden bg-latar pt-28 pb-8 lg:pt-36 lg:pb-10">
        <x-web.gelombang posisi="atas" :isi="$galeri->isNotEmpty() || $berita->isEmpty() ? '#ffffff' : '#f6f8fc'" />
        <div class="relative mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <span class="text-xs font-semibold tracking-[0.2em] text-navy-600 uppercase">Kontak</span>
                <h2 class="mt-2 font-display text-3xl font-bold text-navy-900 sm:text-4xl">Kunjungi & hubungi kami</h2>
                <ul class="mt-8 space-y-5">
                    @if ($sekolah['alamat_sekolah'])
                        <li class="flex gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-navy-700 shadow-sm"><x-icon name="map-pin" class="h-5 w-5" /></span>
                            <div><div class="text-sm font-semibold text-slate-800">Alamat</div><div class="text-sm text-slate-600">{{ $sekolah['alamat_sekolah'] }}</div></div>
                        </li>
                    @endif
                    @if ($sekolah['telepon_sekolah'])
                        <li class="flex gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-navy-700 shadow-sm"><x-icon name="phone" class="h-5 w-5" /></span>
                            <div><div class="text-sm font-semibold text-slate-800">Telepon</div><a href="tel:{{ preg_replace('/[^0-9+]/', '', $sekolah['telepon_sekolah']) }}" class="text-sm text-slate-600 hover:text-navy-700">{{ $sekolah['telepon_sekolah'] }}</a></div>
                        </li>
                    @endif
                    @if ($sekolah['email_sekolah'])
                        <li class="flex gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-navy-700 shadow-sm"><x-icon name="envelope" class="h-5 w-5" /></span>
                            <div><div class="text-sm font-semibold text-slate-800">Email</div><a href="mailto:{{ $sekolah['email_sekolah'] }}" class="text-sm break-all text-slate-600 hover:text-navy-700">{{ $sekolah['email_sekolah'] }}</a></div>
                        </li>
                    @endif
                    @if ($sekolah['web_whatsapp'])
                        @php $wa = preg_replace('/^0/', '62', preg_replace('/\D/', '', $sekolah['web_whatsapp'])); @endphp
                        <li class="flex gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-emerald-600 shadow-sm"><x-icon name="chat" class="h-5 w-5" /></span>
                            <div><div class="text-sm font-semibold text-slate-800">WhatsApp</div><a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="text-sm text-slate-600 hover:text-emerald-700">{{ $sekolah['web_whatsapp'] }}</a></div>
                        </li>
                    @endif
                </ul>
            </div>
            <div class="lg:col-span-3">
                @if ($sekolah['web_maps_embed'])
                    <iframe src="{{ $sekolah['web_maps_embed'] }}" title="Peta lokasi {{ $sekolah['nama_sekolah'] }}"
                            class="h-80 w-full rounded-xl border-0 shadow-sm lg:h-full lg:min-h-96" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                @else
                    <div class="flex h-80 flex-col items-center justify-center rounded-xl bg-white text-slate-400 ring-1 ring-slate-100 lg:h-full">
                        <x-icon name="map-pin" class="h-12 w-12" />
                        <span class="mt-2 text-sm">Peta lokasi belum tersedia</span>
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-layouts.web>
