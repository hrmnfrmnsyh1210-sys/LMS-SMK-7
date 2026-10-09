<x-layouts.app title="Data Guru">
    <x-page-header title="Data Guru" subtitle="Kelola akun dan profil guru.">
        <a href="{{ route('operator.import.index') }}" class="btn-secondary"><x-icon name="upload" class="h-4 w-4" /> Import Excel</a>
        <a href="{{ route('operator.guru.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Guru</a>
    </x-page-header>

    <div class="card">
        <div class="border-b border-slate-100 p-4">
            <x-search placeholder="Cari nama, NIP, username..." :value="$cari">
                <select name="status" class="input sm:w-40">
                    <option value="">Semua status</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
                </select>
            </x-search>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>NIP / Username</th>
                        <th>L/P</th>
                        <th>No. HP</th>
                        <th>Mengajar</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($guru as $g)
                        <tr>
                            <td>{{ $guru->firstItem() + $loop->index }}</td>
                            <td class="font-medium text-slate-800">{{ $g->nama }}</td>
                            <td>
                                <div>{{ $g->nip ?: '-' }}</div>
                                <div class="text-xs text-slate-500">{{ $g->user->username }}</div>
                            </td>
                            <td>{{ $g->jenis_kelamin }}</td>
                            <td>{{ $g->no_hp ?: '-' }}</td>
                            <td class="whitespace-nowrap">{{ $g->pengampu_count }} kelas-mapel</td>
                            <td><x-badge :status="$g->user->status" /></td>
                            <td>
                                <div class="flex justify-end gap-1.5 whitespace-nowrap">
                                    <a href="{{ route('operator.guru.edit', $g) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
                                    @include('operator.partials.aksi-akun', ['akun' => $g->user])
                                    <x-confirm :action="route('operator.guru.destroy', $g)" message="Akun dan profil {{ $g->nama }} akan dihapus permanen.">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </x-confirm>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="8" pesan="Belum ada data guru." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4">{{ $guru->links() }}</div>
    </div>
</x-layouts.app>
