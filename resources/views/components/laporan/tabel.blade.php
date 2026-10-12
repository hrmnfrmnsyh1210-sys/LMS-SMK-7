@props(['laporan'])

{{-- Pratinjau laporan dari LaporanService: info, ringkasan, lalu tabel data. --}}
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">{{ $laporan['judul'] }}</h3>
            <p class="text-xs text-slate-500">
                {{ $laporan['subjudul'] }}
                @foreach ($laporan['info'] as $label => $nilai)
                    · {{ $label }}: {{ $nilai }}
                @endforeach
            </p>
        </div>
    </div>

    <dl class="grid grid-cols-2 gap-3 px-4 pb-4 sm:grid-cols-3 sm:px-6 lg:grid-cols-6">
        @foreach ($laporan['ringkasan'] as $label => $nilai)
            <div @class(['rounded-xl bg-slate-50 px-3 py-2', 'col-span-2 sm:col-span-3 lg:col-span-6' => mb_strlen($nilai) > 24])>
                <dt class="text-xs text-slate-500">{{ $label }}</dt>
                <dd class="text-sm font-semibold text-slate-800">{{ $nilai }}</dd>
            </div>
        @endforeach
    </dl>

    @if ($g = $laporan['grafik'] ?? null)
        <div @class(['grid gap-4 border-t border-slate-100 px-4 py-5 sm:px-6', 'lg:grid-cols-3' => $g['sorotan']])>
            <div @class(['lg:col-span-2' => $g['sorotan']])>
                <div class="mb-2 text-sm font-semibold text-slate-700">{{ $g['nama'] }}</div>
                <x-grafik.batang class="h-72" :label="$g['label']" :nilai="$g['nilai']" :nama="$g['nama']" :satuan="$g['satuan']" :maks="$g['maks']" :garis="$g['garis']" />
            </div>
            @if ($g['sorotan'])
                <div class="flex flex-col justify-center rounded-2xl bg-linear-to-b from-blue-50/70 to-white p-4">
                    <x-grafik.gauge :nilai="$g['sorotan']['nilai']" :label="$g['sorotan']['label']" :satuan="$g['sorotan']['satuan'] ?? '%'"
                                    :acuan="$g['sorotan']['acuan'] ?? null" label-acuan="KKM" />
                </div>
            @endif
        </div>
    @endif

    <div class="table-wrap px-2 pb-3 sm:px-4">
        <table class="table">
            <thead>
                <tr>
                    @foreach ($laporan['kolom'] as $k)
                        <th @class(['whitespace-nowrap', 'text-center' => $loop->index >= 3 || $loop->first])>{{ $k }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($laporan['baris'] as $b)
                    <tr>
                        @foreach ($b as $sel)
                            <td @class(['whitespace-nowrap', 'text-center' => is_numeric($sel) || $sel === null, 'font-medium text-slate-800' => $loop->index === 2 || ($loop->index === 1 && ! is_numeric($sel))])>{{ $sel ?? '-' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <x-empty-row :colspan="count($laporan['kolom'])" pesan="Belum ada data untuk laporan ini." />
                @endforelse
            </tbody>
        </table>
    </div>
</div>
