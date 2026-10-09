<x-layouts.app title="Surat Izin">
    <x-page-header :title="'Surat Izin Kelas '.$kelas->nama_kelas" subtitle="Surat yang disetujui otomatis mengubah presensi pada rentang tanggalnya menjadi izin/sakit.">
        <a href="{{ route('guru.wali.rekap') }}" class="btn-secondary">Rekap Kelas</a>
    </x-page-header>

    <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 text-sm">
        @foreach (['menunggu' => 'Menunggu ('.$jumlahMenunggu.')', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $k => $label)
            <a href="{{ route('guru.wali.surat-izin', ['status' => $k]) }}"
               @class(['rounded-md px-3 py-1.5', 'bg-blue-600 text-white' => $status === $k, 'text-slate-600 hover:bg-slate-50' => $status !== $k])>{{ $label }}</a>
        @endforeach
    </div>

    <div class="space-y-4">
        @forelse ($surat as $s)
            <div class="card p-5" x-data="{ tolak: false }">
                <div class="flex flex-col gap-4 md:flex-row">
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-slate-800">{{ $s->siswa->nama }}</span>
                            <x-badge :warna="$s->jenis === 'sakit' ? 'amber' : 'sky'">{{ ucfirst($s->jenis) }}</x-badge>
                            <x-badge :status="$s->status" />
                        </div>
                        <div class="text-sm text-slate-600">
                            {{ tanggal_indo($s->tanggal_mulai) }}
                            @unless ($s->tanggal_mulai->equalTo($s->tanggal_selesai)) s.d. {{ tanggal_indo($s->tanggal_selesai) }} @endunless
                        </div>
                        <p class="text-sm whitespace-pre-line text-slate-700">{{ $s->keterangan }}</p>
                        @if ($s->file_path)
                            <a href="{{ file_url($s->file_path) }}" target="_blank" class="link text-sm">📎 Lihat bukti</a>
                        @endif
                        @if ($s->status !== 'menunggu')
                            <p class="text-xs text-slate-400">Diproses {{ $s->pemroses?->nama }}{{ $s->catatan ? ' · Catatan: '.$s->catatan : '' }}</p>
                        @endif
                        <p class="text-xs text-slate-400">Diajukan {{ tanggal_indo($s->created_at, false, true) }}</p>
                    </div>

                    @if ($s->status === 'menunggu')
                        <div class="flex shrink-0 flex-col gap-2 md:w-64">
                            <form method="POST" action="{{ route('guru.wali.surat-izin.proses', $s) }}" x-show="!tolak">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="disetujui">
                                <button type="submit" class="btn-success w-full"><x-icon name="check" class="h-4 w-4" /> Setujui</button>
                            </form>
                            <button type="button" class="btn-secondary w-full" x-show="!tolak" @click="tolak = true"><x-icon name="x" class="h-4 w-4" /> Tolak</button>

                            <form method="POST" action="{{ route('guru.wali.surat-izin.proses', $s) }}" x-show="tolak" x-cloak class="space-y-2">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="ditolak">
                                <textarea name="catatan" rows="2" class="input" placeholder="Alasan penolakan" required></textarea>
                                <div class="flex gap-2">
                                    <button type="button" class="btn-secondary flex-1" @click="tolak = false">Batal</button>
                                    <button type="submit" class="btn-danger flex-1">Tolak</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="card card-body text-center text-sm text-slate-500">Tidak ada surat izin.</div>
        @endforelse
        {{ $surat->links() }}
    </div>
</x-layouts.app>
