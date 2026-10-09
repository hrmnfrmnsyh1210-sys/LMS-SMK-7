<x-layouts.app title="Jadwal Pelajaran">
    <x-page-header title="Jadwal Pelajaran" :subtitle="'Tahun ajaran '.($tahunAktif?->label ?? '-')">
        <a href="{{ route('operator.jadwal.create', $mode === 'kelas' ? ['kelas_id' => $pilihanId] : []) }}" class="btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Tambah Jadwal
        </a>
    </x-page-header>

    <div class="card">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center">
            <div class="inline-flex rounded-lg border border-slate-200 p-0.5 text-sm">
                <a href="{{ route('operator.jadwal.index', ['mode' => 'kelas']) }}"
                   @class(['rounded-md px-3 py-1.5', 'bg-slate-800 text-white' => $mode === 'kelas', 'text-slate-600' => $mode !== 'kelas'])>Per kelas</a>
                <a href="{{ route('operator.jadwal.index', ['mode' => 'guru']) }}"
                   @class(['rounded-md px-3 py-1.5', 'bg-slate-800 text-white' => $mode === 'guru', 'text-slate-600' => $mode !== 'guru'])>Per guru</a>
            </div>
            <form method="GET" class="flex gap-2">
                <input type="hidden" name="mode" value="{{ $mode }}">
                <select name="id" class="input sm:w-64" onchange="this.form.submit()">
                    @foreach ($mode === 'kelas' ? $daftarKelas : $daftarGuru as $opsi)
                        <option value="{{ $opsi->id }}" @selected($pilihanId === $opsi->id)>{{ $mode === 'kelas' ? $opsi->nama_kelas : $opsi->nama }}</option>
                    @endforeach
                </select>
                <noscript><button class="btn-secondary">Tampilkan</button></noscript>
            </form>
        </div>

        <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach (\App\Models\Jadwal::HARI as $hari)
                <div class="rounded-lg border border-slate-200">
                    <div class="border-b border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold">{{ $hari }}</div>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($jadwal->get($hari, collect()) as $j)
                            <li class="group flex items-start justify-between gap-2 px-3 py-2 text-sm">
                                <div>
                                    <div class="font-medium text-slate-800">{{ $j->jam }} · {{ $j->pengampu->mapel->nama_mapel }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $mode === 'kelas' ? $j->pengampu->guru->nama : $j->pengampu->kelas->nama_kelas }}
                                        @if ($j->ruang) · {{ $j->ruang }} @endif
                                    </div>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <a href="{{ route('operator.jadwal.edit', $j) }}" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Ubah">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                    </a>
                                    <x-confirm :action="route('operator.jadwal.destroy', $j)" message="Jadwal {{ $j->pengampu->mapel->nama_mapel }} {{ $hari }} {{ $j->jam }} akan dihapus."
                                               button-class="rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600">
                                        <x-icon name="trash" class="h-4 w-4" />
                                    </x-confirm>
                                </div>
                            </li>
                        @empty
                            <li class="px-3 py-3 text-xs text-slate-400">Tidak ada jadwal.</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
