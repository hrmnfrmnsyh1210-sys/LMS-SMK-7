<x-layouts.app title="Presensi">
    <x-page-header title="Presensi" subtitle="Rekap kehadiranmu di setiap mata pelajaran pada tahun ajaran aktif." />

    {{-- Presensi Mandiri Hari Ini --}}
    @if ($jadwalHariIni->isNotEmpty())
        <div class="card p-5 space-y-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                        <x-icon name="calendar" class="h-5 w-5 text-blue-600" />
                        Presensi Hari Ini &mdash; {{ \App\Models\Jadwal::hariDari(today()) }}, {{ tanggal_indo(today()) }}
                    </h3>
                    <p class="text-xs text-slate-500">Lakukan absensi mandiri untuk setiap mata pelajaran yang berlangsung hari ini.</p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($jadwalHariIni as $j)
                    @php $p = $presensiHariIni->get($j->pengampu_id); @endphp
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-slate-50/60 p-4 transition hover:border-blue-100 hover:bg-blue-50/20">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                    <x-icon name="clock" class="h-3.5 w-3.5" />
                                    {{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}
                                </span>
                                @if ($p)
                                    <x-badge :status="$p->status">{{ ucfirst($p->status) }}</x-badge>
                                @endif
                            </div>
                            <h4 class="mt-2.5 text-sm font-semibold text-slate-800">{{ $j->pengampu->mapel->nama_mapel }}</h4>
                            <p class="text-xs text-slate-500">{{ $j->pengampu->guru->nama }}@if ($j->ruang) · {{ $j->ruang }}@endif</p>
                            @if ($p && $p->catatan)
                                <p class="mt-1.5 text-xs text-slate-500 italic">"{{ $p->catatan }}"</p>
                            @endif
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-200/60 flex items-center justify-between">
                            @if ($p)
                                <span class="text-xs text-emerald-600 font-medium flex items-center gap-1">
                                    <x-icon name="check-badge" class="h-4 w-4" /> Sudah Tercatat
                                </span>
                                @if ($p->waktu_absen)
                                    <span class="text-[11px] text-slate-400">Pukul {{ $p->waktu_absen->format('H:i') }}</span>
                                @endif
                            @else
                                <span class="text-xs text-amber-600 font-medium flex items-center gap-1">
                                    <x-icon name="info" class="h-3.5 w-3.5" /> Belum absen
                                </span>
                                <button type="button"
                                        @click="$dispatch('buka-presensi', { id: {{ $j->pengampu_id }}, mapel: '{{ addslashes($j->pengampu->mapel->nama_mapel) }}', guru: '{{ addslashes($j->pengampu->guru->nama) }}' })"
                                        class="btn-primary btn-sm">
                                    Absen Sekarang
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="card p-4 flex items-center gap-3 bg-slate-50/80 text-slate-600 text-xs">
            <x-icon name="info" class="h-4 w-4 text-slate-400 shrink-0" />
            <span>Tidak ada jadwal pelajaran untuk kelasmu hari ini ({{ \App\Models\Jadwal::hariDari(today()) ?? 'Libur' }}). Absensi mandiri hanya dapat dilakukan pada mata pelajaran yang berlangsung sesuai jadwal hari ini.</span>
        </div>
    @endif

    <x-akademik.presensi :rekap="$rekap" :total="$total" :riwayat="$riwayat" />

    <x-siswa.modal-presensi />
</x-layouts.app>
