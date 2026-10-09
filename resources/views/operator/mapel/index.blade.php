<x-layouts.app title="Mata Pelajaran">
    <x-page-header title="Mata Pelajaran">
        <a href="{{ route('operator.mapel.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah</a>
    </x-page-header>

    <div class="card">
        <div class="border-b border-slate-100 p-4">
            <x-search placeholder="Cari kode atau nama mapel" :value="$cari" />
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Kode</th><th>Nama Mata Pelajaran</th><th>Pengampu (tahun aktif)</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($mapel as $m)
                        <tr>
                            <td class="font-medium text-slate-800">{{ $m->kode }}</td>
                            <td>{{ $m->nama_mapel }}</td>
                            <td>{{ $m->pengampu_count }}</td>
                            <td>
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('operator.mapel.edit', $m) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
                                    <x-confirm :action="route('operator.mapel.destroy', $m)" message="Mata pelajaran {{ $m->nama_mapel }} akan dihapus.">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </x-confirm>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="4" pesan="Belum ada mata pelajaran." />
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $mapel->links() }}</div>
    </div>
</x-layouts.app>
