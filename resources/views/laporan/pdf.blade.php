{{-- Laporan PDF (dompdf): kop sekolah, info, tabel, ringkasan, dan tanda tangan pencetak. --}}
@php
    $logoPath = $sekolah['logo'] ? storage_path('app/public/'.$sekolah['logo']) : null;
    $logo = $logoPath && is_file($logoPath)
        ? 'data:'.mime_content_type($logoPath).';base64,'.base64_encode(file_get_contents($logoPath))
        : null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $laporan['judul'] }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        .kop { width: 100%; border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 12px; }
        .kop td { vertical-align: middle; }
        .kop .nama { font-size: 15px; font-weight: bold; color: #1e3a8a; }
        .kop .alamat { font-size: 9px; color: #475569; }
        h1 { font-size: 13px; margin: 0 0 2px; text-align: center; }
        .sub { text-align: center; color: #475569; margin-bottom: 10px; }
        .info { margin-bottom: 8px; }
        .info td { padding: 1px 8px 1px 0; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #1e3a8a; color: #fff; padding: 5px 4px; font-size: 9px; text-align: center; }
        table.data td { border: 1px solid #cbd5e1; padding: 4px; }
        table.data tr:nth-child(even) td { background: #f1f5f9; }
        .angka { text-align: center; }
        .ringkasan { margin-top: 12px; width: 55%; border-collapse: collapse; }
        .ringkasan td { border: 1px solid #cbd5e1; padding: 4px 6px; }
        .ringkasan td:first-child { background: #eff6ff; width: 45%; }
        .ttd { margin-top: 28px; width: 100%; }
        .ttd td { width: 50%; vertical-align: top; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            @if ($logo)
                <td style="width: 56px"><img src="{{ $logo }}" style="height: 48px"></td>
            @endif
            <td>
                <div class="nama">{{ $sekolah['nama_sekolah'] }}</div>
                <div class="alamat">
                    {{ $sekolah['alamat_sekolah'] }}
                    @if ($sekolah['telepon_sekolah'] ?? null) · Telp. {{ $sekolah['telepon_sekolah'] }} @endif
                    @if ($sekolah['email_sekolah'] ?? null) · {{ $sekolah['email_sekolah'] }} @endif
                </div>
            </td>
        </tr>
    </table>

    <h1>{{ mb_strtoupper($laporan['judul']) }}</h1>
    <div class="sub">{{ $laporan['subjudul'] }}</div>

    @if ($laporan['info'])
        <table class="info">
            @foreach ($laporan['info'] as $label => $nilai)
                <tr><td>{{ $label }}</td><td>: {{ $nilai }}</td></tr>
            @endforeach
        </table>
    @endif

    <table class="data">
        <thead>
            <tr>
                @foreach ($laporan['kolom'] as $k)
                    <th>{{ $k }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($laporan['baris'] as $b)
                <tr>
                    @foreach ($b as $sel)
                        <td @class(['angka' => is_numeric($sel) || $sel === null])>{{ $sel ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($laporan['kolom']) }}" class="angka">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="ringkasan">
        @foreach ($laporan['ringkasan'] as $label => $nilai)
            <tr><td>{{ $label }}</td><td>{{ $nilai }}</td></tr>
        @endforeach
    </table>

    <table class="ttd">
        <tr>
            <td></td>
            <td>
                {{ tanggal_indo(now(), false) }}<br>
                {{ $pencetak->label_role }}<br><br><br><br>
                <strong>{{ $pencetak->nama }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
