<x-layouts.app title="Pengumuman">
    <x-page-header title="Pengumuman" subtitle="Pengumuman sekolah untuk orang tua dan pengumuman kelas anak Anda." />

    <div class="space-y-4">
        @forelse ($pengumuman as $p)
            <div class="card flex gap-4 p-5">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"><x-icon name="megaphone" class="h-5 w-5" /></span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="font-semibold text-slate-800">{{ $p->judul }}</h3>
                        @if ($p->target === 'kelas')
                            <x-badge warna="violet">Kelas {{ $p->kelas?->nama_kelas }}</x-badge>
                        @else
                            <x-badge warna="sky">{{ \App\Models\Pengumuman::TARGET[$p->target] }}</x-badge>
                        @endif
                    </div>
                    <p class="mt-1 text-sm whitespace-pre-line text-slate-600">{{ $p->isi }}</p>
                    <div class="mt-2 text-xs text-slate-400">{{ $p->pembuat?->nama }} · {{ tanggal_indo($p->created_at, true, true) }}</div>
                </div>
            </div>
        @empty
            <div class="card card-body text-center text-sm text-slate-500">Belum ada pengumuman.</div>
        @endforelse
        {{ $pengumuman->links() }}
    </div>
</x-layouts.app>
