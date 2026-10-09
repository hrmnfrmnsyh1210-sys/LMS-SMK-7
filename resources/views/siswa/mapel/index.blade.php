<x-layouts.app title="Mata Pelajaran">
    <x-page-header title="Mata Pelajaran" :subtitle="($kelas ? 'Kelas '.$kelas->nama_kelas.' · ' : '').($tahunAktif?->label ?? '-')" />

    @php
        $gradasi = ['from-violet-300 to-blue-400', 'from-pink-300 to-rose-300', 'from-sky-300 to-blue-400', 'from-emerald-300 to-teal-400', 'from-amber-300 to-orange-300', 'from-fuchsia-300 to-pink-400'];
    @endphp

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($pengampu as $p)
            <a href="{{ route('siswa.mapel.show', $p) }}" class="card group flex flex-col overflow-hidden transition hover:-translate-y-1 hover:shadow-lg">
                <div class="relative overflow-hidden bg-linear-to-br px-5 py-5 text-white {{ $gradasi[$loop->index % count($gradasi)] }}">
                    <x-icon name="book" class="pointer-events-none absolute -right-3 -bottom-4 h-24 w-24 text-white/20 transition group-hover:scale-110 group-hover:-rotate-6" />
                    <div class="relative text-lg font-bold">{{ $p->mapel->nama_mapel }}</div>
                    <div class="relative mt-0.5 flex items-center gap-1.5 text-sm text-white/90"><x-icon name="user" class="h-4 w-4" /> {{ $p->guru->nama }}</div>
                </div>
                <div class="grid grid-cols-3 divide-x divide-slate-100 py-3 text-center">
                    @foreach ([['Materi', $p->materi_count], ['Tugas', $p->tugas_count], ['Kuis', $p->kuis_count]] as [$label, $nilai])
                        <div>
                            <div class="text-lg font-bold text-slate-800">{{ $nilai }}</div>
                            <div class="text-xs text-slate-500">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-auto flex flex-wrap gap-x-3 gap-y-1 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                    @forelse ($p->jadwal as $j)
                        <span class="inline-flex items-center gap-1 whitespace-nowrap"><x-icon name="clock" class="h-3.5 w-3.5" /> {{ $j->hari }} {{ $j->jam }}</span>
                    @empty
                        Belum ada jadwal
                    @endforelse
                </div>
            </a>
        @empty
            <div class="card card-body col-span-full text-center text-sm text-slate-500">
                Kamu belum terdaftar di kelas mana pun pada tahun ajaran aktif. Hubungi operator sekolah.
            </div>
        @endforelse
    </div>
</x-layouts.app>
