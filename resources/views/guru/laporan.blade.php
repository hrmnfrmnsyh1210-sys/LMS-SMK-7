<x-layouts.app title="Laporan">
    <x-page-header title="Laporan" subtitle="Laporan nilai dan kehadiran mapel yang Anda ampu{{ $daftarKelas->isNotEmpty() ? ' serta kelas perwalian' : '' }}. Ekspor ke PDF atau Excel.">
        @if ($laporan)
            <a href="{{ route('guru.laporan.ekspor', request()->query() + ['format' => 'pdf']) }}" class="btn-secondary"><x-icon name="document" class="h-4 w-4" /> PDF</a>
            <a href="{{ route('guru.laporan.ekspor', request()->query() + ['format' => 'excel']) }}" class="btn-primary"><x-icon name="download" class="h-4 w-4" /> Excel</a>
        @endif
    </x-page-header>

    @php
        $jenisTersedia = collect(\App\Services\LaporanService::JENIS_GURU)
            ->when($daftarKelas->isEmpty(), fn ($j) => $j->only(['nilai_mapel', 'kehadiran_mapel']));
    @endphp

    <form method="GET" class="card card-body grid gap-3 sm:grid-cols-2 lg:grid-cols-3 lg:items-end" x-data="{ jenis: @js($jenis) }">
        <div>
            <label for="jenis" class="label">Jenis laporan</label>
            <select name="jenis" id="jenis" class="input" x-model="jenis">
                @foreach ($jenisTersedia as $nilai => $label)
                    <option value="{{ $nilai }}" @selected($jenis === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="jenis.endsWith('_mapel')">
            <label for="pengampu_id" class="label">Mapel & kelas</label>
            <select name="pengampu_id" id="pengampu_id" class="input" :disabled="! jenis.endsWith('_mapel')">
                @forelse ($daftarPengampu as $id => $label)
                    <option value="{{ $id }}" @selected($pengampu?->id === $id)>{{ $label }}</option>
                @empty
                    <option value="">Belum ada kelas ajar</option>
                @endforelse
            </select>
        </div>
        <div x-show="jenis.endsWith('_kelas')" x-cloak>
            <label for="kelas_id" class="label">Kelas perwalian</label>
            <select name="kelas_id" id="kelas_id" class="input" :disabled="! jenis.endsWith('_kelas')">
                @foreach ($daftarKelas as $id => $label)
                    <option value="{{ $id }}" @selected($kelas?->id === $id)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="btn-primary w-full sm:w-auto"><x-icon name="eye" class="h-4 w-4" /> Tampilkan</button>
        </div>
    </form>

    @if ($laporan)
        <x-laporan.analisis-ai :url="route('guru.laporan.analisis')" :aktif="$aiAktif" />
        <x-laporan.tabel :laporan="$laporan" />
    @else
        <div class="card card-body text-center text-sm text-slate-500">Belum ada data yang bisa dilaporkan.</div>
    @endif
</x-layouts.app>
