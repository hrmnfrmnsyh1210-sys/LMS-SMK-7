<x-layouts.app :title="'Presensi · '.$pengampu->label">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="presensi" />

    <x-page-header title="Presensi" subtitle="Input kehadiran per pertemuan. Surat izin yang disetujui wali kelas otomatis mengisi status izin/sakit.">
        <a href="{{ route('guru.presensi.form', $pengampu) }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Isi Pertemuan Baru</a>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="card lg:col-span-2">
            <div class="card-header"><h3 class="card-title">Pertemuan</h3></div>
            <div class="table-wrap px-2 pb-3 sm:px-4">
                <table class="table">
                    <thead><tr><th>Ke</th><th>Tanggal</th><th>H</th><th>I</th><th>S</th><th>A</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($pertemuan as $p)
                            <tr>
                                <td class="font-semibold">{{ $p->pertemuan_ke }}</td>
                                <td class="whitespace-nowrap">{{ tanggal_indo($p->tanggal, false) }}</td>
                                <td class="text-emerald-600">{{ (int) $p->hadir }}</td>
                                <td class="text-sky-600">{{ (int) $p->izin }}</td>
                                <td class="text-amber-600">{{ (int) $p->sakit }}</td>
                                <td class="text-rose-600">{{ (int) $p->alpa }}</td>
                                <td class="text-right"><a href="{{ route('guru.presensi.form', [$pengampu, 'pertemuan' => $p->pertemuan_ke]) }}" class="card-link">Ubah</a></td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="7" pesan="Belum ada presensi." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card lg:col-span-3">
            <div class="card-header"><h3 class="card-title">Rekap Semester</h3></div>
            <div class="table-wrap px-2 pb-3 sm:px-4">
                <table class="table">
                    <thead><tr><th>Siswa</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpa</th><th>Kehadiran</th></tr></thead>
                    <tbody>
                        @forelse ($rekap as $r)
                            <tr>
                                <td class="font-medium text-slate-800">{{ $r['siswa']->nama }}</td>
                                <td>{{ $r['hadir'] }}</td>
                                <td>{{ $r['izin'] }}</td>
                                <td>{{ $r['sakit'] }}</td>
                                <td>
                                    {{ $r['alpa'] }}
                                    @if ($r['alpa'] >= 3) <x-badge warna="rose">Sering alpa</x-badge> @endif
                                </td>
                                <td>
                                    @if ($r['persen'] !== null)
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-100">
                                                <div @class(['h-full rounded-full', 'bg-emerald-500' => $r['persen'] >= 80, 'bg-amber-500' => $r['persen'] < 80]) style="width: {{ $r['persen'] }}%"></div>
                                            </div>
                                            <span class="text-xs font-medium">{{ $r['persen'] }}%</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="6" pesan="Belum ada siswa." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.app>
