<x-layouts.app title="Laporan">
    <x-page-header title="Laporan" subtitle="Laporan akademik, kesiswaan, kehadiran, dan nilai per semester. Ekspor ke PDF atau Excel.">
        @if ($laporan)
            <a href="{{ route($prefix.'.laporan.ekspor', request()->query() + ['format' => 'pdf']) }}" class="btn-secondary"><x-icon name="document" class="h-4 w-4" /> PDF</a>
            <a href="{{ route($prefix.'.laporan.ekspor', request()->query() + ['format' => 'excel']) }}" class="btn-primary"><x-icon name="download" class="h-4 w-4" /> Excel</a>
        @endif
    </x-page-header>

    <form method="GET" class="card card-body grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end" x-data="{ jenis: @js($jenis) }">
        <div>
            <label for="jenis" class="label">Jenis laporan</label>
            <select name="jenis" id="jenis" class="input" x-model="jenis">
                @foreach (\App\Services\LaporanService::JENIS_SEKOLAH as $nilai => $label)
                    <option value="{{ $nilai }}" @selected($jenis === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="tahun_ajaran_id" class="label">Tahun ajaran</label>
            <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="input" onchange="this.form.kelas_id && (this.form.kelas_id.value = ''); this.form.submit()">
                @foreach ($daftarTahun as $t)
                    <option value="{{ $t->id }}" @selected($tahun?->id === $t->id)>{{ $t->label }}{{ $t->status_aktif ? ' (aktif)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="['kehadiran', 'nilai'].includes(jenis)" x-cloak>
            <label for="kelas_id" class="label">Kelas</label>
            <select name="kelas_id" id="kelas_id" class="input" :disabled="! ['kehadiran', 'nilai'].includes(jenis)">
                <option value="">Pilih kelas</option>
                @foreach ($daftarKelas as $k)
                    <option value="{{ $k->id }}" @selected($kelas?->id === $k->id)>{{ $k->nama_kelas }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="btn-primary w-full sm:w-auto"><x-icon name="eye" class="h-4 w-4" /> Tampilkan</button>
        </div>
    </form>

    @if ($laporan)
        <x-laporan.analisis-ai :url="route($prefix.'.laporan.analisis')" :aktif="$aiAktif" />
        <x-laporan.tabel :laporan="$laporan" />
    @else
        <div class="card card-body text-center text-sm text-slate-500">
            {{ $tahun ? 'Pilih kelas untuk menampilkan laporan ini.' : 'Belum ada tahun ajaran.' }}
        </div>
    @endif
</x-layouts.app>
