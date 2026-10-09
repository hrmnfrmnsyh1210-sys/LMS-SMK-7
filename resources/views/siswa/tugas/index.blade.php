<x-layouts.app title="Tugas">
    <x-page-header title="Tugas" subtitle="Semua tugas dari mata pelajaranmu. Yang belum dikumpulkan tampil paling atas." />

    <div class="flex gap-2 overflow-x-auto pb-1 text-sm">
        <a href="{{ route('siswa.tugas.index') }}"
           @class(['shrink-0 rounded-full px-4 py-1.5 font-medium transition', 'bg-blue-600 text-white shadow-sm' => ! $status, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $status])>
            Semua <span class="opacity-70">{{ $ringkasan->sum() }}</span>
        </a>
        @foreach (\App\Models\Tugas::STATUS_SISWA as $kunci => $label)
            <a href="{{ route('siswa.tugas.index', ['status' => $kunci]) }}"
               @class(['shrink-0 rounded-full px-4 py-1.5 font-medium transition', 'bg-blue-600 text-white shadow-sm' => $status === $kunci, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $status !== $kunci])>
                {{ $label }} <span class="opacity-70">{{ $ringkasan[$kunci] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <x-siswa.daftar-tugas :daftar="$daftar" />
</x-layouts.app>
