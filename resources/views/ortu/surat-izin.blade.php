<x-layouts.app title="Surat Izin">
    <x-page-header title="Surat Izin" subtitle="Ajukan izin atau sakit untuk anak Anda. Surat dikirim ke wali kelas untuk disetujui." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @forelse ($surat as $s)
                <div class="card flex gap-4 p-5">
                    <span @class([
                        'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl',
                        'bg-amber-50 text-amber-600' => $s->jenis === 'sakit',
                        'bg-sky-50 text-sky-600' => $s->jenis === 'izin',
                    ])><x-icon name="document" class="h-5 w-5" /></span>
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
                        <p class="text-sm whitespace-pre-line text-slate-600">{{ $s->keterangan }}</p>
                        @if ($s->file_path)
                            <a href="{{ file_url($s->file_path) }}" target="_blank" class="link inline-flex items-center gap-1 text-sm"><x-icon name="document" class="h-4 w-4" /> Lihat bukti</a>
                        @endif
                        @if ($s->status !== 'menunggu')
                            <p class="text-xs text-slate-400">Diproses {{ $s->pemroses?->nama }}{{ $s->catatan ? ' · Catatan: '.$s->catatan : '' }}</p>
                        @endif
                        <p class="text-xs text-slate-400">Diajukan {{ $s->pengaju_user_id === auth()->id() ? 'oleh Anda' : 'oleh '.($s->pengaju?->nama ?? $s->siswa->nama) }} · {{ tanggal_indo($s->created_at, false, true) }}</p>
                    </div>
                    @if ($s->status === 'menunggu' && $s->pengaju_user_id === auth()->id())
                        <x-confirm :action="route('ortu.surat-izin.destroy', $s)" title="Batalkan surat?" message="Surat izin ini akan dibatalkan."
                                   button-class="h-fit rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600">
                            <x-icon name="trash" class="h-4 w-4" />
                        </x-confirm>
                    @endif
                </div>
            @empty
                <div class="card card-body text-center text-sm text-slate-500">Belum ada surat izin.</div>
            @endforelse
            {{ $surat->links() }}
        </div>

        <form method="POST" action="{{ route('ortu.surat-izin.store') }}" enctype="multipart/form-data" class="card card-body h-fit space-y-4">
            @csrf
            <h3 class="card-title">Ajukan Surat</h3>
            <x-field.select name="siswa_id" label="Anak" :options="$pilihanAnak" :value="$anakDipilih" required />
            <x-field.select name="jenis" label="Jenis" :options="['izin' => 'Izin', 'sakit' => 'Sakit']" required />
            <div class="grid grid-cols-2 gap-3">
                <x-field.input type="date" name="tanggal_mulai" label="Dari" :value="now()->toDateString()" required />
                <x-field.input type="date" name="tanggal_selesai" label="Sampai" :value="now()->toDateString()" required />
            </div>
            <x-field.textarea name="keterangan" label="Keterangan" rows="3" required />
            <x-field.file name="file" label="Bukti (opsional)" help="Surat dokter atau surat orang tua. PDF/JPG/PNG maksimal 5 MB." accept=".pdf,.jpg,.jpeg,.png" />
            <button type="submit" class="btn-primary w-full" @disabled(empty($pilihanAnak))>Kirim ke Wali Kelas</button>
            <p class="text-xs text-slate-500">Jika disetujui, presensi pada rentang tanggal tersebut otomatis menjadi izin/sakit.</p>
        </form>
    </div>
</x-layouts.app>
