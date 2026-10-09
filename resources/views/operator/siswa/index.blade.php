<x-layouts.app title="Data Siswa">
    <x-page-header title="Data Siswa" subtitle="Kelola akun, profil, dan kelas siswa.">
        <a href="{{ route('operator.import.index') }}" class="btn-secondary"><x-icon name="upload" class="h-4 w-4" /> Import Excel</a>
        <a href="{{ route('operator.siswa.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Siswa</a>
    </x-page-header>

    <div class="card">
        <div class="border-b border-slate-100 p-4">
            <x-search placeholder="Cari nama, NIS, NISN..." :value="$cari">
                <select name="kelas_id" class="input sm:w-44">
                    <option value="">Semua kelas</option>
                    <option value="0" @selected(request('kelas_id') === '0')>— Belum punya kelas —</option>
                    @foreach ($daftarKelas as $k)
                        <option value="{{ $k->id }}" @selected((string) request('kelas_id') === (string) $k->id)>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
                <select name="status" class="input sm:w-36">
                    <option value="">Semua status</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                    <option value="lulus" @selected(request('status') === 'lulus')>Lulus</option>
                </select>
            </x-search>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>NIS / NISN</th>
                        <th>L/P</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($siswa as $s)
                        <tr>
                            <td>{{ $siswa->firstItem() + $loop->index }}</td>
                            <td class="font-medium text-slate-800">{{ $s->nama }}</td>
                            <td>
                                <div>{{ $s->nis }}</div>
                                <div class="text-xs text-slate-500">{{ $s->nisn }}</div>
                            </td>
                            <td>{{ $s->jenis_kelamin }}</td>
                            <td>{{ $s->kelas->first()?->nama_kelas ?? '-' }}</td>
                            <td class="space-x-1">
                                @if ($s->status === 'lulus')
                                    <x-badge status="lulus" />
                                @endif
                                <x-badge :status="$s->user->status" />
                            </td>
                            <td>
                                <div class="flex justify-end gap-1.5 whitespace-nowrap">
                                    <a href="{{ route('operator.siswa.edit', $s) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
                                    @include('operator.partials.aksi-akun', ['akun' => $s->user])
                                    <x-confirm :action="route('operator.siswa.destroy', $s)" message="Akun dan profil {{ $s->nama }} akan dihapus permanen.">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </x-confirm>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="7" pesan="Belum ada data siswa." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $siswa->links() }}</div>
    </div>
</x-layouts.app>
