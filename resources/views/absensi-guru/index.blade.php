@php
    $labelStatus = [
        'hadir' => ['emerald', 'Tepat waktu'],
        'terlambat' => ['amber', 'Terlambat'],
        'belum' => ['slate', 'Belum masuk'],
        'tidak_hadir' => ['rose', 'Tidak hadir'],
    ];
@endphp

<x-layouts.app title="Absensi Guru">
    <x-page-header title="Absensi Guru" :subtitle="'Kehadiran guru di setiap sesi kelas sesuai jadwal. Masuk lewat '.$toleransi.' menit dari jam mulai ditandai terlambat.'">
        <div class="inline-flex rounded-lg bg-slate-100 p-1 text-sm font-medium">
            @foreach (['harian' => 'Harian', 'bulanan' => 'Rekap Bulanan'] as $nilai => $label)
                <a href="{{ route($prefix.'.absensi-guru.index', ['mode' => $nilai]) }}"
                   @class(['rounded-md px-3 py-1.5 transition', 'bg-white text-slate-900 shadow-sm' => $mode === $nilai, 'text-slate-500 hover:text-slate-800' => $mode !== $nilai])>{{ $label }}</a>
            @endforeach
        </div>
    </x-page-header>

    @if ($mode === 'harian')
        @php $jumlah = $rekap->countBy('status'); @endphp
        <div class="grid grid-cols-1 gap-4 min-[480px]:grid-cols-2 lg:grid-cols-4">
            <x-card-stat label="Sesi Mengajar" :value="$rekap->count()" ikon="clock" warna="blue" :keterangan="$tanggal->locale('id')->translatedFormat('l, j F Y')" />
            <x-card-stat label="Tepat Waktu" :value="$jumlah['hadir'] ?? 0" ikon="check-badge" warna="emerald" />
            <x-card-stat label="Terlambat" :value="$jumlah['terlambat'] ?? 0" ikon="warning" warna="amber" />
            <x-card-stat label="Tidak Hadir" :value="$jumlah['tidak_hadir'] ?? 0" ikon="x" warna="rose" :keterangan="($jumlah['belum'] ?? 0).' sesi belum dimulai'" />
        </div>
    @endif

    <div class="card">
        <div class="border-b border-slate-100 p-4">
            <x-search placeholder="Cari nama atau NIP guru" :value="$cari">
                <input type="hidden" name="mode" value="{{ $mode }}">
                @if ($mode === 'harian')
                    <input type="date" name="tanggal" value="{{ $tanggal->toDateString() }}" max="{{ today()->toDateString() }}" class="input sm:w-44" aria-label="Tanggal">
                @else
                    <input type="month" name="bulan" value="{{ $bulan->format('Y-m') }}" max="{{ now()->format('Y-m') }}" class="input sm:w-44" aria-label="Bulan">
                @endif
            </x-search>
        </div>
        <div class="table-wrap px-2 pb-3 sm:px-4">
            @if ($mode === 'harian')
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jadwal</th>
                            <th>Guru</th>
                            <th>Kelas & Mapel</th>
                            <th class="text-center">Masuk</th>
                            <th class="text-center">Keluar</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekap as $r)
                            @php
                                $j = $r['jadwal'];
                                $a = $r['absensi'];
                                [$warna, $label] = $labelStatus[$r['status']];
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap">{{ $j->jam }}</td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar :nama="$j->pengampu->guru->nama" />
                                        <div>
                                            <div class="font-medium text-slate-800">{{ $j->pengampu->guru->nama }}</div>
                                            <div class="text-xs text-slate-500">{{ $j->pengampu->guru->nip ? 'NIP '.$j->pengampu->guru->nip : '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-medium text-slate-800">{{ $j->pengampu->kelas->nama_kelas }}</div>
                                    <div class="text-xs text-slate-500">{{ $j->pengampu->mapel->nama_mapel }}@if ($j->ruang) · {{ $j->ruang }}@endif</div>
                                </td>
                                <td class="text-center">{{ $a ? substr($a->jam_masuk, 0, 5) : '-' }}</td>
                                <td class="text-center">{{ $a?->jam_keluar ? substr($a->jam_keluar, 0, 5) : '-' }}</td>
                                <td><x-badge :warna="$warna">{{ $label }}</x-badge></td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="6" :pesan="\App\Models\Jadwal::hariDari($tanggal) ? 'Tidak ada jadwal mengajar pada tanggal ini.' : 'Hari Minggu tidak ada jadwal mengajar.'" />
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Guru</th>
                            <th class="text-center">Sesi Terjadwal</th>
                            <th class="text-center">Hadir</th>
                            <th class="text-center">Tepat Waktu</th>
                            <th class="text-center">Terlambat</th>
                            <th class="text-center">Tidak Hadir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekap as $r)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-avatar :nama="$r['guru']->nama" />
                                        <div>
                                            <div class="font-medium text-slate-800">{{ $r['guru']->nama }}</div>
                                            <div class="text-xs text-slate-500">{{ $r['guru']->nip ? 'NIP '.$r['guru']->nip : '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">{{ $r['terjadwal'] }}</td>
                                <td class="text-center font-semibold">{{ $r['hadir'] }}</td>
                                <td class="text-center">{{ $r['tepat_waktu'] }}</td>
                                <td class="text-center">{{ $r['terlambat'] }}</td>
                                <td @class(['text-center', 'font-semibold text-rose-600' => $r['tidak_hadir'] > 0])>{{ $r['tidak_hadir'] }}</td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="6" pesan="Tidak ada data guru." />
                        @endforelse
                    </tbody>
                </table>
                <p class="px-2 pt-3 text-xs text-slate-500">Sesi terjadwal dihitung dari jadwal yang berlaku saat ini untuk sesi yang sudah berakhir.</p>
            @endif
        </div>
    </div>
</x-layouts.app>
