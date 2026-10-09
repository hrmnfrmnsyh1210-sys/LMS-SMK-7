<x-layouts.app title="Log Aktivitas">
    <x-page-header title="Log Aktivitas" subtitle="Riwayat aksi penting seluruh pengguna." />

    <div class="card">
        <div class="border-b border-slate-100 p-4">
            <x-search placeholder="Cari username" :value="$cari">
                <select name="role" class="input sm:w-36">
                    <option value="">Semua role</option>
                    @foreach (\App\Models\User::LABEL_ROLE as $role => $label)
                        <option value="{{ $role }}" @selected(request('role') === $role)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="aksi" class="input sm:w-44">
                    <option value="">Semua aksi</option>
                    @foreach ($daftarAksi as $aksi)
                        <option value="{{ $aksi }}" @selected(request('aksi') === $aksi)>{{ $aksi }}</option>
                    @endforeach
                </select>
                <input type="date" name="dari" value="{{ request('dari') }}" class="input sm:w-40" title="Dari tanggal">
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="input sm:w-40" title="Sampai tanggal">
            </x-search>
            @error('sampai') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Keterangan</th><th>IP</th></tr></thead>
                <tbody>
                    @forelse ($log as $l)
                        <tr>
                            <td class="whitespace-nowrap">{{ tanggal_indo($l->created_at, false, true) }}</td>
                            <td>
                                @if ($l->user)
                                    <div class="font-medium text-slate-800">{{ $l->user->nama }}</div>
                                    <div class="text-xs text-slate-500">{{ $l->user->username }} · {{ $l->user->label_role }}</div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td><x-badge>{{ $l->aksi }}</x-badge></td>
                            <td>{{ $l->keterangan }}</td>
                            <td class="text-xs text-slate-500">{{ $l->ip_address }}</td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="5" pesan="Tidak ada log yang cocok." />
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $log->links() }}</div>
    </div>
</x-layouts.app>
