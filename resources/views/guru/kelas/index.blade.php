@php
    $hariIni = \App\Models\Jadwal::HARI[now()->dayOfWeekIso - 1] ?? null;
    $jurusan = $pengampu->pluck('kelas.jurusan')->unique('id')->sortBy('kode')->values();
    // Aksen warna per jurusan agar kartu mudah dibedakan sekilas.
    $palet = [
        ['garis' => 'bg-amber-400', 'lencana' => 'bg-amber-400/15 text-amber-200 ring-amber-300/30', 'chip' => 'bg-amber-400'],
        ['garis' => 'bg-sky-400', 'lencana' => 'bg-sky-400/15 text-sky-200 ring-sky-300/30', 'chip' => 'bg-sky-400'],
        ['garis' => 'bg-emerald-400', 'lencana' => 'bg-emerald-400/15 text-emerald-200 ring-emerald-300/30', 'chip' => 'bg-emerald-400'],
        ['garis' => 'bg-rose-400', 'lencana' => 'bg-rose-400/15 text-rose-200 ring-rose-300/30', 'chip' => 'bg-rose-400'],
        ['garis' => 'bg-violet-400', 'lencana' => 'bg-violet-400/15 text-violet-200 ring-violet-300/30', 'chip' => 'bg-violet-400'],
    ];
    $warnaJurusan = $jurusan->mapWithKeys(fn ($j, $i) => [$j->id => $palet[$i % count($palet)]]);
    $kelasHariIni = $pengampu->filter(fn ($p) => $p->jadwal->contains('hari', $hariIni));
    $ringkasan = [
        ['label' => 'Kelas ajar', 'nilai' => $pengampu->count(), 'ikon' => 'book'],
        ['label' => 'Total siswa', 'nilai' => $pengampu->unique('kelas_id')->sum(fn ($p) => $jumlahSiswa[$p->kelas_id] ?? 0), 'ikon' => 'user-group'],
        ['label' => 'Mata pelajaran', 'nilai' => $pengampu->unique('mapel_id')->count(), 'ikon' => 'tag'],
        ['label' => 'Mengajar hari ini', 'nilai' => $kelasHariIni->count(), 'ikon' => 'clock'],
    ];
@endphp

<x-layouts.app title="Kelas Ajar">
    <x-page-header title="Kelas Ajar" :subtitle="'Kelas dan mata pelajaran yang Anda ampu pada '.($tahunAktif?->label ?? '-')" />

    @if ($pengampu->isEmpty())
        <div class="card card-body flex flex-col items-center py-14 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600"><x-icon name="book" class="h-7 w-7" /></span>
            <p class="mt-3 font-medium text-slate-700">Belum ada kelas ajar pada tahun ajaran aktif.</p>
            <p class="text-sm text-slate-500">Hubungi operator untuk plotting pengampu.</p>
        </div>
    @else
        {{-- Ringkasan --}}
        <div class="card grid grid-cols-2 divide-slate-100 lg:grid-cols-4 lg:divide-x">
            @foreach ($ringkasan as $r)
                <div class="flex items-center gap-3 p-4 sm:p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700"><x-icon :name="$r['ikon']" class="h-5 w-5" /></span>
                    <div>
                        <div class="font-display text-2xl leading-none font-bold text-slate-900">{{ $r['nilai'] }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $r['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="space-y-5"
             x-data="{
                 jurusan: 'semua',
                 cari: '',
                 kelas: @js($pengampu->map(fn ($p) => ['j' => (string) $p->kelas->jurusan_id, 'k' => mb_strtolower($p->mapel->nama_mapel.' '.$p->kelas->nama_kelas.' '.$p->kelas->jurusan->nama)])),
                 cocok(j, k) {
                     return (this.jurusan === 'semua' || this.jurusan === j) && k.includes(this.cari.toLowerCase().trim());
                 },
             }">
            {{-- Saringan --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="jurusan = 'semua'"
                            class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                            :class="jurusan === 'semua' ? 'bg-blue-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-blue-700'">
                        Semua <span class="opacity-60">{{ $pengampu->count() }}</span>
                    </button>
                    @foreach ($jurusan as $j)
                        <button type="button" @click="jurusan = '{{ $j->id }}'"
                                class="inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-medium transition"
                                :class="jurusan === '{{ $j->id }}' ? 'bg-blue-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-blue-700'"
                                title="{{ $j->nama }}">
                            <span class="h-2 w-2 rounded-full {{ $warnaJurusan[$j->id]['chip'] }}"></span>
                            {{ $j->kode }} <span class="opacity-60">{{ $pengampu->where('kelas.jurusan_id', $j->id)->count() }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="relative md:w-72">
                    <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-slate-400" />
                    <input type="search" x-model="cari" placeholder="Cari mapel atau kelas..." class="input pl-9">
                </div>
            </div>

            {{-- Kartu kelas --}}
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($pengampu as $p)
                    @php
                        $warna = $warnaJurusan[$p->kelas->jurusan_id];
                        $mengajarHariIni = $kelasHariIni->contains($p);
                    @endphp
                    <a href="{{ route('guru.kelas.show', $p) }}"
                       x-show="cocok(kelas[{{ $loop->index }}].j, kelas[{{ $loop->index }}].k)"
                       class="card group flex flex-col overflow-hidden transition hover:-translate-y-1 hover:shadow-lg hover:shadow-slate-900/5">
                        {{-- Kepala kartu --}}
                        <div class="relative isolate overflow-hidden bg-sidebar px-5 pt-5 pb-6 text-white">
                            <span class="absolute inset-y-0 left-0 w-1 {{ $warna['garis'] }}"></span>
                            <span aria-hidden="true" class="pointer-events-none absolute -right-2 -bottom-5 -z-10 font-display text-7xl font-bold text-white/5 select-none">{{ $p->mapel->kode }}</span>

                            <div class="flex items-start justify-between gap-3">
                                <span class="rounded-md px-2 py-0.5 text-[11px] font-semibold tracking-wide ring-1 ring-inset {{ $warna['lencana'] }}">{{ $p->kelas->jurusan->kode }}</span>
                                @if ($mengajarHariIni)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-aksen px-2.5 py-0.5 text-[11px] font-semibold text-slate-900">
                                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-slate-900"></span> Hari ini
                                    </span>
                                @endif
                            </div>
                            <h3 class="mt-3 font-display text-xl leading-snug font-bold">{{ $p->mapel->nama_mapel }}</h3>
                            <div class="mt-1 flex items-center gap-1.5 text-sm text-white/70">
                                <x-icon name="building" class="h-4 w-4" /> Kelas {{ $p->kelas->nama_kelas }}
                            </div>
                        </div>

                        {{-- Statistik --}}
                        <div class="grid grid-cols-4 gap-1 px-3 py-4">
                            @foreach ([['Siswa', $jumlahSiswa[$p->kelas_id] ?? 0, 'user-group'], ['Materi', $p->materi_count, 'document'], ['Tugas', $p->tugas_count, 'clipboard-check'], ['Kuis', $p->kuis_count, 'puzzle']] as [$label, $nilai, $ikon])
                                <div class="flex flex-col items-center rounded-lg py-1.5 transition group-hover:bg-slate-50">
                                    <x-icon :name="$ikon" class="h-4 w-4 text-slate-400" />
                                    <div class="mt-1 text-lg leading-none font-bold text-slate-800">{{ $nilai }}</div>
                                    <div class="mt-1 text-[11px] text-slate-500">{{ $label }}</div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Jadwal --}}
                        <div class="mt-auto flex items-center gap-3 border-t border-slate-100 px-5 py-3.5">
                            <div class="flex min-w-0 flex-1 flex-wrap gap-1.5">
                                @forelse ($p->jadwal as $j)
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium whitespace-nowrap',
                                        'bg-amber-50 text-amber-800 ring-1 ring-amber-200' => $j->hari === $hariIni,
                                        'bg-slate-100 text-slate-600' => $j->hari !== $hariIni,
                                    ])>
                                        <x-icon name="clock" class="h-3.5 w-3.5" /> {{ $j->hari }}, {{ $j->jam }}
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-400">Belum ada jadwal</span>
                                @endforelse
                            </div>
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition group-hover:bg-blue-700 group-hover:text-white">
                                <x-icon name="arrow-right" class="h-4 w-4" />
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            <p x-cloak x-show="! kelas.some(k => cocok(k.j, k.k))" class="card card-body text-center text-sm text-slate-500">
                Tidak ada kelas yang cocok dengan pencarian.
            </p>
        </div>
    @endif
</x-layouts.app>
