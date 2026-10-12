{{--
    Kerangka halaman error. Sengaja mandiri (CSS inline, tanpa Vite/database/auth)
    supaya tetap tampil walau aset belum di-build atau database sedang bermasalah.
    Gaya visual mengikuti website sekolah: navy, aksen kuning, judul serif.
--}}
@php
    $dataSekolah = isset($sekolah) && is_array($sekolah) ? $sekolah : [];
    $namaSekolah = ($dataSekolah['nama_sekolah'] ?? null) ?: config('app.name');
    $logo = ! empty($dataSekolah['logo']) && function_exists('file_url') ? file_url($dataSekolah['logo']) : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('kode') · @yield('ringkas') — {{ $namaSekolah }}</title>
    @if ($logo)
        <link rel="icon" href="{{ $logo }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600|roboto-slab:600,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #0b316e; --navy-gelap: #051633; --kuning: #fbc63d; --kuning-muda: #fdd977;
            --putih-redup: rgba(255, 255, 255, .72); --garis: rgba(255, 255, 255, .14);
            --serif: 'Roboto Slab', Georgia, 'Times New Roman', serif;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0; min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden;
            font-family: 'Poppins', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: #fff; background: linear-gradient(180deg, #0d3577 0%, #08224d 55%, var(--navy-gelap) 100%);
            position: relative; isolation: isolate;
        }
        a { color: inherit; }

        /* Kode error raksasa sebagai latar teks, seperti nama sekolah di hero website. */
        .latar-kode {
            position: absolute; inset: 8% 0 auto; z-index: -1; text-align: center; pointer-events: none; user-select: none;
            font-family: var(--serif); font-weight: 700; line-height: .85; letter-spacing: -.02em;
            font-size: clamp(9rem, 38vw, 30rem);
            background: linear-gradient(180deg, rgba(255, 255, 255, .22), rgba(255, 255, 255, 0) 85%);
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }

        .atas {
            width: 100%; max-width: 1200px; margin: 0 auto; padding: 22px 20px;
            display: flex; align-items: center; justify-content: space-between; gap: 16px;
        }
        .merek { display: flex; align-items: center; gap: 12px; text-decoration: none; min-width: 0; }
        .merek img, .merek svg { width: 42px; height: 42px; flex-shrink: 0; object-fit: contain; }
        .merek small { display: block; font-size: 10px; letter-spacing: .2em; text-transform: uppercase; opacity: .7; }
        .merek strong { display: block; font-family: var(--serif); font-size: 17px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tautan-atas { font-size: 14px; font-weight: 500; text-decoration: none; color: var(--putih-redup); white-space: nowrap; }
        .tautan-atas:hover { color: #fff; }

        main {
            flex: 1; width: 100%; max-width: 1200px; margin: 0 auto; padding: 40px 20px 56px;
            display: flex; flex-direction: column; justify-content: flex-end;
        }
        .isi { max-width: 620px; text-shadow: 0 2px 16px rgba(5, 22, 51, .45); }
        .label {
            display: inline-flex; align-items: center; gap: 8px; margin: 0;
            font-size: 13px; font-weight: 600; letter-spacing: .18em; text-transform: uppercase; color: var(--kuning-muda);
        }
        .label svg { width: 20px; height: 20px; }
        h1 { font-family: var(--serif); font-size: clamp(2rem, 5vw, 3.5rem); line-height: 1.1; margin: 14px 0 0; }
        .pesan { margin: 16px 0 0; font-size: 16px; line-height: 1.7; color: var(--putih-redup); }

        .aksi { margin-top: 32px; display: flex; gap: 12px 24px; align-items: center; flex-wrap: wrap; }
        .tombol {
            display: inline-flex; align-items: center; gap: 8px; padding: 0; border: 0; background: none; cursor: pointer;
            font: 600 14px/1 inherit; font-family: inherit; color: #fff; text-decoration: none;
            text-underline-offset: 8px;
        }
        .tombol:hover { text-decoration: underline; }
        .tombol.utama {
            padding: 13px 24px; border-radius: 999px; background: var(--kuning); color: var(--navy-gelap); text-shadow: none;
        }
        .tombol.utama:hover { background: var(--kuning-muda); text-decoration: none; }
        .tombol svg { width: 16px; height: 16px; }

        .kaki {
            width: 100%; max-width: 1200px; margin: 0 auto; padding: 18px 20px; border-top: 1px solid var(--garis);
            font-size: 12px; color: rgba(255, 255, 255, .5);
        }
        @media (min-width: 768px) {
            .atas, main, .kaki { padding-left: 32px; padding-right: 32px; }
            main { padding-bottom: 80px; }
        }
    </style>
</head>
<body>
    <div class="latar-kode" aria-hidden="true">@yield('kode')</div>

    <header class="atas">
        <a href="{{ url('/') }}" class="merek">
            @if ($logo)
                <img src="{{ $logo }}" alt="Logo {{ $namaSekolah }}">
            @else
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                </svg>
            @endif
            <span style="min-width: 0">
                <small>Website Resmi</small>
                <strong>{{ $namaSekolah }}</strong>
            </span>
        </a>
        <a href="{{ url('/') }}" class="tautan-atas">Beranda</a>
    </header>

    <main>
        <div class="isi">
            <p class="label">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="@yield('ikon')" />
                </svg>
                @yield('kode') · @yield('ringkas')
            </p>
            <h1>@yield('judul')</h1>
            <p class="pesan">@yield('pesan')</p>

            <div class="aksi">
                @hasSection('aksi')
                    @yield('aksi')
                @else
                    <a href="{{ url('/') }}" class="tombol utama">
                        Ke Beranda
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                    </a>
                    <button type="button" class="tombol" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')">Kembali ke halaman sebelumnya</button>
                @endif
            </div>
        </div>
    </main>

    <footer class="kaki">&copy; {{ date('Y') }} {{ $namaSekolah }} · Learning Management System</footer>
</body>
</html>
