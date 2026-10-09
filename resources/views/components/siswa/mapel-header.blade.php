@props(['pengampu', 'aktif' => 'materi'])

{{-- Kepala halaman mapel siswa: identitas mapel, guru, jadwal + tab menu. --}}
@php
    $pengampu->loadMissing(['mapel', 'kelas', 'guru', 'jadwal']);
    $tab = [
        'materi' => ['Materi', 'siswa.mapel.show', 'book'],
        'tugas' => ['Tugas', 'siswa.tugas.mapel', 'clipboard-check'],
        'kuis' => ['Kuis & Ujian', 'siswa.kuis.mapel', 'puzzle'],
        'forum' => ['Forum', 'siswa.forum.index', 'megaphone'],
    ];
@endphp

<div class="card overflow-hidden">
    <div class="relative overflow-hidden bg-linear-to-r from-blue-500 via-blue-400 to-pink-400 px-5 py-6 text-white sm:px-7">
        <x-icon name="sparkles" class="pointer-events-none absolute -top-4 right-6 h-28 w-28 text-white/15" />
        <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/20 backdrop-blur">
                    <x-icon name="book" class="h-7 w-7" />
                </span>
                <div>
                    <div class="text-xl font-bold">{{ $pengampu->mapel->nama_mapel }}</div>
                    <div class="text-sm text-white/85">{{ $pengampu->guru->nama }} · {{ $pengampu->kelas->nama_kelas }}</div>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                @forelse ($pengampu->jadwal->sortBy(fn ($j) => array_search($j->hari, \App\Models\Jadwal::HARI).$j->jam_mulai) as $j)
                    <span class="inline-flex items-center gap-1 rounded-full bg-white/20 px-3 py-1"><x-icon name="clock" class="h-3.5 w-3.5" /> {{ $j->hari }}, {{ $j->jam }}</span>
                @empty
                    <span class="rounded-full bg-white/20 px-3 py-1">Belum ada jadwal</span>
                @endforelse
            </div>
        </div>
    </div>
    <nav class="flex gap-1 overflow-x-auto px-3 py-2 text-sm">
        @foreach ($tab as $kunci => [$label, $route, $ikon])
            <a href="{{ route($route, $pengampu) }}"
               @class([
                   'flex shrink-0 items-center gap-2 rounded-full px-4 py-2 font-medium transition',
                   'bg-blue-50 text-blue-700' => $aktif === $kunci,
                   'text-slate-600 hover:bg-slate-50 hover:text-slate-800' => $aktif !== $kunci,
               ])>
                <x-icon :name="$ikon" class="h-4 w-4" /> {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
