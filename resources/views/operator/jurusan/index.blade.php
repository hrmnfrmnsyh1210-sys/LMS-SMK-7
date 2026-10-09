<x-layouts.app title="Jurusan">
    <x-page-header title="Jurusan" subtitle="Kompetensi keahlian yang tersedia di sekolah.">
        <a href="{{ route('operator.jurusan.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah</a>
    </x-page-header>

    <div class="card">
        <div class="border-b border-slate-100 p-4">
            <x-search placeholder="Cari kode atau nama jurusan" :value="$cari" />
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Kode</th><th>Nama Jurusan</th><th>Kelas (tahun aktif)</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($jurusan as $j)
                        <tr>
                            <td class="font-medium text-slate-800">{{ $j->kode }}</td>
                            <td>{{ $j->nama }}</td>
                            <td>{{ $j->kelas_count }}</td>
                            <td>
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('operator.jurusan.edit', $j) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
                                    <x-confirm :action="route('operator.jurusan.destroy', $j)" message="Jurusan {{ $j->nama }} akan dihapus.">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </x-confirm>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="4" pesan="Belum ada jurusan." />
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $jurusan->links() }}</div>
    </div>
</x-layouts.app>
