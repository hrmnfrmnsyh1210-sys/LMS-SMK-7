<x-layouts.app title="Keaktifan Guru">
    <x-page-header title="Keaktifan Guru" :subtitle="'Jumlah konten dan pertemuan yang dicatat guru pada '.($tahunAktif?->label ?? '-')" />

    <div class="card">
        <div class="border-b border-slate-100 p-4">
            <x-search placeholder="Cari nama atau NIP" :value="$cari" />
        </div>
        <div class="table-wrap px-2 pb-3 sm:px-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Guru</th>
                        <th class="text-center">Kelas Ajar</th>
                        <th class="text-center">Materi</th>
                        <th class="text-center">Tugas</th>
                        <th class="text-center">Kuis</th>
                        <th class="text-center">Pertemuan</th>
                        <th>Login Terakhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rekap as $r)
                        @php $pasif = $r['kelas'] > 0 && $r['materi'] + $r['tugas'] + $r['kuis'] === 0; @endphp
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :nama="$r['guru']->nama" />
                                    <div>
                                        <div class="font-medium text-slate-800">{{ $r['guru']->nama }}</div>
                                        <div class="text-xs text-slate-500">{{ $r['guru']->nip ? 'NIP '.$r['guru']->nip : '-' }}</div>
                                    </div>
                                    @if ($pasif)
                                        <x-badge warna="amber">Belum ada konten</x-badge>
                                    @endif
                                </div>
                            </td>
                            <td class="text-center">{{ $r['kelas'] }}</td>
                            <td class="text-center">{{ $r['materi'] }}</td>
                            <td class="text-center">{{ $r['tugas'] }}</td>
                            <td class="text-center">{{ $r['kuis'] }}</td>
                            <td class="text-center">{{ $r['pertemuan'] }}</td>
                            <td class="text-xs whitespace-nowrap text-slate-500">{{ $r['guru']->user?->last_login?->locale('id')->diffForHumans() ?? 'Belum pernah' }}</td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="7" pesan="Tidak ada data guru." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
