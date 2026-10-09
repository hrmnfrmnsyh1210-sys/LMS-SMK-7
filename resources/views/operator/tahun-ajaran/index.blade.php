<x-layouts.app title="Tahun Ajaran">
    <x-page-header title="Tahun Ajaran" subtitle="Hanya satu tahun ajaran yang aktif. Seluruh data operasional mengikuti tahun ajaran aktif.">
        <a href="{{ route('operator.tahun-ajaran.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah</a>
    </x-page-header>

    <div class="card">
        <div class="border-b border-slate-100 p-4">
            <x-search placeholder="Cari tahun, mis. 2026" :value="$cari" />
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Tahun Ajaran</th><th>Semester</th><th>Kelas</th><th>Pengampu</th><th>Status</th><th class="text-right">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($tahunAjaran as $ta)
                        <tr>
                            <td class="font-medium text-slate-800">{{ $ta->nama }}</td>
                            <td><x-badge :status="$ta->semester" /></td>
                            <td>{{ $ta->kelas_count }}</td>
                            <td>{{ $ta->pengampu_count }}</td>
                            <td>
                                @if ($ta->status_aktif)
                                    <x-badge warna="emerald">Aktif</x-badge>
                                @else
                                    <x-badge warna="slate">Tidak aktif</x-badge>
                                @endif
                            </td>
                            <td>
                                <div class="flex justify-end gap-1.5 whitespace-nowrap">
                                    @unless ($ta->status_aktif)
                                        <x-confirm :action="route('operator.tahun-ajaran.aktifkan', $ta)" method="POST" title="Aktifkan tahun ajaran?"
                                                   message="Tahun ajaran {{ $ta->label }} akan menjadi tahun ajaran aktif. Untuk menyalin kelas & pengampu, gunakan menu Pergantian Tahun Ajaran."
                                                   confirm-label="Ya, aktifkan" confirm-class="btn-success" button-class="btn-success btn-sm">
                                            <x-icon name="check" class="h-3.5 w-3.5" /> Aktifkan
                                        </x-confirm>
                                    @endunless
                                    <a href="{{ route('operator.tahun-ajaran.edit', $ta) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
                                    <x-confirm :action="route('operator.tahun-ajaran.destroy', $ta)" message="Tahun ajaran {{ $ta->label }} akan dihapus.">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </x-confirm>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="6" pesan="Belum ada tahun ajaran." />
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $tahunAjaran->links() }}</div>
    </div>
</x-layouts.app>
