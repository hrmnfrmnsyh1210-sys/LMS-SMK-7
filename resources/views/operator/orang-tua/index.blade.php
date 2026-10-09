<x-layouts.app title="Orang Tua">
    <x-page-header title="Akun Orang Tua" subtitle="Orang tua/wali dapat memantau nilai, presensi, tugas, dan jadwal anaknya.">
        <a href="{{ route('operator.orang-tua.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Akun</a>
    </x-page-header>

    <form method="POST" action="{{ route('operator.orang-tua.massal') }}" class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        @csrf
        <div class="flex-1">
            <x-field.select name="kelas_id" label="Buat akun massal per kelas" :options="$daftarKelas->all()" placeholder="— Pilih kelas —" required />
            <p class="mt-1 text-xs text-slate-500">Dibuat untuk siswa yang belum punya akun orang tua. Username & password awal: <code>ortu</code> + NIS, nama dari data “nama orang tua” siswa.</p>
        </div>
        <button type="submit" class="btn-secondary sm:mb-6"><x-icon name="user-group" class="h-4 w-4" /> Buat Akun Massal</button>
    </form>

    <div class="card">
        <div class="border-b border-slate-100 p-4">
            <x-search placeholder="Cari nama, username, atau nama/NIS anak" :value="$cari" />
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Username</th>
                        <th>Anak</th>
                        <th>No. HP</th>
                        <th>Login terakhir</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ortu as $o)
                        <tr>
                            <td>
                                <div class="font-medium text-slate-800">{{ $o->nama }}</div>
                                <div class="text-xs text-slate-500">{{ $o->label_hubungan }}</div>
                            </td>
                            <td>{{ $o->user->username }}</td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($o->anak as $a)
                                        <x-badge warna="blue">{{ $a->nama }} · {{ $a->nis }}</x-badge>
                                    @empty
                                        <span class="text-xs text-rose-500">Belum terhubung</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>{{ $o->no_hp ?: '-' }}</td>
                            <td class="whitespace-nowrap">{{ $o->user->last_login ? tanggal_indo($o->user->last_login, false, true) : 'Belum pernah' }}</td>
                            <td><x-badge :status="$o->user->status" /></td>
                            <td>
                                <div class="flex justify-end gap-1.5 whitespace-nowrap">
                                    <a href="{{ route('operator.orang-tua.edit', $o) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
                                    @include('operator.partials.aksi-akun', ['akun' => $o->user])
                                    <x-confirm :action="route('operator.orang-tua.destroy', $o)" message="Akun {{ $o->nama }} akan dihapus permanen. Data anak tidak terpengaruh.">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </x-confirm>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="7" pesan="Belum ada akun orang tua." />
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $ortu->links() }}</div>
    </div>
</x-layouts.app>
