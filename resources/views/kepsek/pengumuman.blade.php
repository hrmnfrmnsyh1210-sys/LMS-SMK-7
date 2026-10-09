<x-layouts.app title="Pengumuman">
    <x-page-header title="Pengumuman" subtitle="Seluruh pengumuman sekolah dan kelas. Anda dapat menerbitkan pengumuman untuk semua warga sekolah, guru, siswa, atau orang tua." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
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
                        <div class="mt-2 text-xs text-slate-400">{{ $p->pembuat?->nama }} ({{ $p->pembuat?->label_role }}) · {{ tanggal_indo($p->created_at, true, true) }}</div>
                    </div>
                    @if ($p->pembuat_user_id === auth()->id())
                        <x-confirm :action="route('kepsek.pengumuman.destroy', $p)" message="Pengumuman ini akan dihapus."
                                   button-class="h-fit rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600">
                            <x-icon name="trash" class="h-4 w-4" />
                        </x-confirm>
                    @endif
                </div>
            @empty
                <div class="card card-body text-center text-sm text-slate-500">Belum ada pengumuman.</div>
            @endforelse
            {{ $pengumuman->links() }}
        </div>

        <form method="POST" action="{{ route('kepsek.pengumuman.store') }}" class="card card-body h-fit space-y-4">
            @csrf
            <h3 class="card-title">Buat Pengumuman Sekolah</h3>
            <x-field.select name="target" label="Tujuan" :options="['semua' => 'Semua (guru, siswa & orang tua)', 'guru' => 'Guru', 'siswa' => 'Siswa', 'ortu' => 'Orang tua']" required />
            <x-field.input name="judul" label="Judul" required />
            <x-field.textarea name="isi" label="Isi pengumuman" rows="5" required />
            <button type="submit" class="btn-primary w-full">Terbitkan</button>
            <p class="text-xs text-slate-500">Penerima akan mendapat notifikasi di dashboard.</p>
        </form>
    </div>
</x-layouts.app>
