<x-layouts.app title="Kelas">
    <x-page-header title="Kelas" subtitle="Daftar kelas per tahun ajaran.">
        <a href="{{ route('operator.kelas.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Kelas</a>
    </x-page-header>

    <div class="card">
        <div class="border-b border-slate-100 p-4">
            <x-search placeholder="Cari nama kelas" :value="$cari">
                <select name="tahun_ajaran_id" class="input sm:w-52">
                    @foreach ($daftarTahun as $ta)
                        <option value="{{ $ta->id }}" @selected($tahunId === $ta->id)>{{ $ta->label }}{{ $ta->status_aktif ? ' (aktif)' : '' }}</option>
                    @endforeach
                </select>
                <select name="tingkat" class="input sm:w-32">
                    <option value="">Semua tingkat</option>
                    @foreach (\App\Models\Kelas::TINGKAT as $t)
                        <option value="{{ $t }}" @selected(request('tingkat') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
                <select name="jurusan_id" class="input sm:w-40">
                    <option value="">Semua jurusan</option>
                    @foreach ($daftarJurusan as $j)
                        <option value="{{ $j->id }}" @selected((string) request('jurusan_id') === (string) $j->id)>{{ $j->kode }}</option>
                    @endforeach
                </select>
            </x-search>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Kelas</th><th>Tingkat</th><th>Jurusan</th><th>Wali Kelas</th><th>Siswa</th><th>Pengampu</th><th class="text-right">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($kelas as $k)
                        <tr>
                            <td class="font-medium text-slate-800">{{ $k->nama_kelas }}</td>
                            <td>{{ $k->tingkat }}</td>
                            <td>{{ $k->jurusan->kode }}</td>
                            <td>{{ $k->waliKelas?->nama ?? '-' }}</td>
                            <td>{{ $k->siswa_count }}</td>
                            <td>{{ $k->pengampu_count }}</td>
                            <td>
                                <div class="flex justify-end gap-1.5 whitespace-nowrap">
                                    <a href="{{ route('operator.kelas.siswa.index', $k) }}" class="btn-secondary btn-sm"><x-icon name="academic-cap" class="h-3.5 w-3.5" /> Siswa</a>
                                    <a href="{{ route('operator.kelas.edit', $k) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
                                    <x-confirm :action="route('operator.kelas.destroy', $k)" message="Kelas {{ $k->nama_kelas }} akan dihapus.">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </x-confirm>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="7" pesan="Belum ada kelas pada tahun ajaran ini." />
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $kelas->links() }}</div>
    </div>
</x-layouts.app>
