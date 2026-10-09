<x-layouts.app :title="'Tugas · '.$pengampu->label">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="tugas" />

    <x-page-header title="Tugas" subtitle="Pengumpulan setelah deadline tetap diterima dan ditandai terlambat.">
        <a href="{{ route('guru.tugas.create', $pengampu) }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Buat Tugas</a>
    </x-page-header>

    <div class="card">
        <div class="table-wrap p-2 sm:p-4">
            <table class="table">
                <thead><tr><th>Judul</th><th>Deadline</th><th>Terkumpul</th><th>Dinilai</th><th>Nilai Maks</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($tugas as $t)
                        <tr>
                            <td>
                                <a href="{{ route('guru.tugas.show', [$pengampu, $t]) }}" class="font-semibold text-slate-800 hover:text-blue-700">{{ $t->judul }}</a>
                            </td>
                            <td class="whitespace-nowrap">
                                {{ tanggal_indo($t->deadline, false, true) }}
                                @if ($t->sudahLewatDeadline())
                                    <x-badge warna="slate">Ditutup</x-badge>
                                @else
                                    <x-badge warna="emerald">Berjalan</x-badge>
                                @endif
                            </td>
                            <td>{{ $t->pengumpulan_count }}/{{ $jumlahSiswa }}</td>
                            <td>
                                {{ $t->dinilai_count }}/{{ $t->pengumpulan_count }}
                                @if ($t->pengumpulan_count > $t->dinilai_count)
                                    <x-badge warna="amber">{{ $t->pengumpulan_count - $t->dinilai_count }} belum</x-badge>
                                @endif
                            </td>
                            <td>{{ $t->nilai_maks }}</td>
                            <td>
                                <div class="flex justify-end gap-1.5 whitespace-nowrap">
                                    <a href="{{ route('guru.tugas.show', [$pengampu, $t]) }}" class="btn-primary btn-sm">Pengumpulan</a>
                                    <a href="{{ route('guru.tugas.edit', [$pengampu, $t]) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /></a>
                                    <x-confirm :action="route('guru.tugas.destroy', [$pengampu, $t])" message="Tugas “{{ $t->judul }}” akan dihapus.">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </x-confirm>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="6" pesan="Belum ada tugas." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
