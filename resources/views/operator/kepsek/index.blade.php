<x-layouts.app title="Kepala Sekolah">
    <x-page-header title="Akun Kepala Sekolah">
        <a href="{{ route('operator.kepsek.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Akun</a>
    </x-page-header>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>NIP</th>
                        <th>Username</th>
                        <th>No. HP</th>
                        <th>Login terakhir</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kepsek as $k)
                        <tr>
                            <td class="font-medium text-slate-800">{{ $k->nama }}</td>
                            <td>{{ $k->nip ?: '-' }}</td>
                            <td>{{ $k->user->username }}</td>
                            <td>{{ $k->no_hp ?: '-' }}</td>
                            <td>{{ $k->user->last_login ? tanggal_indo($k->user->last_login, false, true) : 'Belum pernah' }}</td>
                            <td><x-badge :status="$k->user->status" /></td>
                            <td>
                                <div class="flex justify-end gap-1.5 whitespace-nowrap">
                                    <a href="{{ route('operator.kepsek.edit', $k) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
                                    @include('operator.partials.aksi-akun', ['akun' => $k->user])
                                    <x-confirm :action="route('operator.kepsek.destroy', $k)" message="Akun {{ $k->nama }} akan dihapus permanen.">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </x-confirm>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="7" pesan="Belum ada akun kepala sekolah." />
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $kepsek->links() }}</div>
    </div>
</x-layouts.app>
