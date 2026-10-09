<x-layouts.app title="Tugas Anak">
    <x-ortu.anak-header :anak="$anak" judul="Tugas" subjudul="Status pengumpulan dan nilai tugas dari semua mata pelajaran." />

    @php $ringkasan = $daftar->countBy('status'); @endphp
    <div class="flex flex-wrap gap-2 text-xs">
        @foreach (\App\Models\Tugas::STATUS_SISWA as $kunci => $label)
            <span class="rounded-full bg-white px-3 py-1.5 font-medium text-slate-600 ring-1 ring-slate-200">{{ $label }}: <strong>{{ $ringkasan[$kunci] ?? 0 }}</strong></span>
        @endforeach
    </div>

    <x-siswa.daftar-tugas :daftar="$daftar" :tautan="false" />
</x-layouts.app>
