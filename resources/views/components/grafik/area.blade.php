@props(['label' => [], 'seri' => [], 'satuan' => '', 'maks' => null, 'kosong' => 'Belum ada data untuk grafik ini.'])

{{-- Grafik area berlapis (resources/js/grafik.js). $seri: list ['nama' => ..., 'nilai' => [...]], maks. 4 seri. --}}
@if (collect($seri)->flatMap(fn ($s) => $s['nilai'])->filter(fn ($n) => $n !== null && $n != 0)->isEmpty())
    <p class="py-10 text-center text-sm text-slate-500">{{ $kosong }}</p>
@else
    <div {{ $attributes->class(['relative', 'h-72' => ! str_contains((string) $attributes->get('class'), 'h-')]) }}
         x-data="grafikArea(@js(['label' => array_values($label), 'seri' => collect($seri)->map(fn ($s) => ['nama' => $s['nama'], 'nilai' => array_values($s['nilai'])])->all(), 'satuan' => $satuan, 'maks' => $maks]))"></div>
    <table class="sr-only">
        <tr><th>Periode</th>@foreach ($seri as $s)<th>{{ $s['nama'] }}</th>@endforeach</tr>
        @foreach (array_values($label) as $i => $l)
            <tr><th scope="row">{{ $l }}</th>@foreach ($seri as $s)<td>{{ array_values($s['nilai'])[$i] ?? '-' }}{{ $satuan }}</td>@endforeach</tr>
        @endforeach
    </table>
@endif
