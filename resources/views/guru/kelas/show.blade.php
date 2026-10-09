<x-layouts.app :title="$pengampu->label">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="siswa" />

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Siswa <span class="text-sm font-normal text-slate-500">({{ $siswa->count() }})</span></h3>
            <span class="text-xs text-slate-500">Wali kelas: {{ $pengampu->kelas->waliKelas?->nama ?? '-' }}</span>
        </div>
        <div class="table-wrap px-2 pb-3 sm:px-4">
            <table class="table">
                <thead><tr><th>#</th><th>Nama</th><th>NIS</th><th>NISN</th><th>L/P</th><th class="text-right">Perkembangan</th></tr></thead>
                <tbody>
                    @forelse ($siswa as $s)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="font-medium text-slate-800">{{ $s->nama }}</td>
                            <td>{{ $s->nis }}</td>
                            <td>{{ $s->nisn }}</td>
                            <td>{{ $s->jenis_kelamin }}</td>
                            <td class="text-right"><a href="{{ route('guru.monitoring.siswa', [$pengampu, $s]) }}" class="card-link">Lihat</a></td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="6" pesan="Belum ada siswa di kelas ini." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
