@props(['label' => [], 'nilai' => [], 'nama' => 'Nilai', 'satuan' => '', 'maks' => null, 'garis' => null, 'kosong' => 'Belum ada data untuk grafik ini.'])

{{-- Grafik batang bergradasi (resources/js/grafik.js). $garis: ['label' => 'KKM', 'nilai' => 75] opsional. --}}
@if (collect($nilai)->filter(fn ($n) => $n !== null)->isEmpty())
    <p class="py-10 text-center text-sm text-slate-500">{{ $kosong }}</p>
@else
    <div {{ $attributes->class(['relative', 'h-72' => ! str_contains((string) $attributes->get('class'), 'h-')]) }}
         x-data="grafikBatang(@js(['label' => array_values($label), 'nilai' => array_values($nilai), 'nama' => $nama, 'satuan' => $satuan, 'maks' => $maks, 'garis' => $garis]))"></div>
    <table class="sr-only">
        <caption>{{ $nama }}</caption>
        @foreach (array_values($label) as $i => $l)
            <tr><th scope="row">{{ $l }}</th><td>{{ array_values($nilai)[$i] ?? '-' }}{{ $satuan }}</td></tr>
        @endforeach
    </table>
@endif
