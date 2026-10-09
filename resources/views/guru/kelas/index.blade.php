<x-layouts.app title="Kelas Ajar">
    <x-page-header title="Kelas Ajar" :subtitle="'Kelas dan mata pelajaran yang Anda ampu pada '.($tahunAktif?->label ?? '-')" />

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($pengampu as $p)
            <a href="{{ route('guru.kelas.show', $p) }}" class="card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="bg-linear-to-r from-blue-600 to-blue-500 px-5 py-4 text-white">
                    <div class="text-xs text-blue-100">{{ $p->kelas->jurusan->nama }}</div>
                    <div class="text-lg font-bold">{{ $p->mapel->nama_mapel }}</div>
                    <div class="text-sm">{{ $p->kelas->nama_kelas }}</div>
                </div>
                <div class="grid grid-cols-4 divide-x divide-slate-100 py-3 text-center">
                    @foreach ([['Siswa', $jumlahSiswa[$p->kelas_id] ?? 0], ['Materi', $p->materi_count], ['Tugas', $p->tugas_count], ['Kuis', $p->kuis_count]] as [$label, $nilai])
                        <div>
                            <div class="text-lg font-bold text-slate-800">{{ $nilai }}</div>
                            <div class="text-xs text-slate-500">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-auto border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                    @forelse ($p->jadwal as $j)
                        <span class="mr-2 whitespace-nowrap">{{ $j->hari }} {{ $j->jam }}</span>
                    @empty
                        Belum ada jadwal
                    @endforelse
                </div>
            </a>
        @empty
            <div class="card card-body col-span-full text-center text-sm text-slate-500">
                Anda belum memiliki kelas ajar pada tahun ajaran aktif. Hubungi operator untuk plotting pengampu.
            </div>
        @endforelse
    </div>
</x-layouts.app>
