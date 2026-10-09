@props(['pengampu', 'aktif' => 'siswa'])

{{-- Kepala halaman kelas ajar: identitas kelas-mapel + tab menu pembelajaran. --}}
@php
    $pengampu->loadMissing(['mapel', 'kelas', 'jadwal']);
    $tab = [
        'siswa' => ['Siswa', 'guru.kelas.show', 'user-group'],
        'materi' => ['Materi', 'guru.materi.index', 'book'],
        'tugas' => ['Tugas', 'guru.tugas.index', 'document'],
        'kuis' => ['Kuis & Ujian', 'guru.kuis.index', 'list'],
        'presensi' => ['Presensi', 'guru.presensi.index', 'calendar'],
        'nilai' => ['Nilai', 'guru.nilai.index', 'chart'],
        'forum' => ['Forum', 'guru.forum.index', 'megaphone'],
        'monitoring' => ['Monitoring', 'guru.monitoring.index', 'star'],
    ];
@endphp

<div class="card overflow-hidden">
    <div class="flex flex-col gap-4 bg-linear-to-r from-blue-600 to-blue-500 px-5 py-5 text-white sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-center gap-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15">
                <x-icon name="book" class="h-6 w-6" />
            </span>
            <div>
                <div class="text-lg font-bold">{{ $pengampu->mapel->nama_mapel }}</div>
                <div class="text-sm text-blue-100">Kelas {{ $pengampu->kelas->nama_kelas }}</div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 text-xs">
            @forelse ($pengampu->jadwal->sortBy(fn ($j) => array_search($j->hari, \App\Models\Jadwal::HARI).$j->jam_mulai) as $j)
                <span class="rounded-lg bg-white/15 px-2.5 py-1">{{ $j->hari }}, {{ $j->jam }}@if ($j->ruang) · {{ $j->ruang }}@endif</span>
            @empty
                <span class="rounded-lg bg-white/15 px-2.5 py-1">Belum ada jadwal</span>
            @endforelse
        </div>
    </div>
    <nav class="flex gap-1 overflow-x-auto px-3 py-2 text-sm">
        @foreach ($tab as $kunci => [$label, $route, $ikon])
            <a href="{{ route($route, $pengampu) }}"
               @class([
                   'flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 font-medium transition',
                   'bg-blue-50 text-blue-700' => $aktif === $kunci,
                   'text-slate-600 hover:bg-slate-50 hover:text-slate-800' => $aktif !== $kunci,
               ])>
                <x-icon :name="$ikon" class="h-4 w-4" /> {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
