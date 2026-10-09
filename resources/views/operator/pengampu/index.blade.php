<x-layouts.app title="Pengampu">
    <x-page-header title="Plotting Pengampu" subtitle="Guru – mata pelajaran – kelas pada tahun ajaran aktif.">
        <a href="{{ route('operator.pengampu.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Pengampu</a>
    </x-page-header>

    <div class="card">
        <div class="border-b border-slate-100 p-4">
            <x-search placeholder="Cari guru atau mapel" :value="$cari">
                <select name="kelas_id" class="input sm:w-40">
                    <option value="">Semua kelas</option>
                    @foreach ($daftarKelas as $k)
                        <option value="{{ $k->id }}" @selected((string) request('kelas_id') === (string) $k->id)>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
                <select name="guru_id" class="input sm:w-48">
                    <option value="">Semua guru</option>
                    @foreach ($daftarGuru as $g)
                        <option value="{{ $g->id }}" @selected((string) request('guru_id') === (string) $g->id)>{{ $g->nama }}</option>
                    @endforeach
                </select>
            </x-search>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Kelas</th><th>Mata Pelajaran</th><th>Guru</th><th>Jadwal</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($pengampu as $p)
                        <tr>
                            <td class="font-medium text-slate-800">{{ $p->kelas->nama_kelas }}</td>
                            <td>{{ $p->mapel->nama_mapel }}</td>
                            <td>{{ $p->guru->nama }}</td>
                            <td>
                                @if ($p->jadwal_count)
                                    {{ $p->jadwal_count }} sesi/minggu
                                @else
                                    <a href="{{ route('operator.jadwal.create', ['kelas_id' => $p->kelas_id]) }}" class="link">Atur jadwal</a>
                                @endif
                            </td>
                            <td>
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('operator.pengampu.edit', $p) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
                                    <x-confirm :action="route('operator.pengampu.destroy', $p)"
                                               message="Plotting {{ $p->mapel->nama_mapel }} di {{ $p->kelas->nama_kelas }} beserta jadwalnya akan dihapus.">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </x-confirm>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="5" pesan="Belum ada plotting pengampu." />
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $pengampu->links() }}</div>
    </div>
</x-layouts.app>
