<x-layouts.app :title="'Forum · '.$pengampu->mapel->nama_mapel">
    <x-siswa.mapel-header :pengampu="$pengampu" aktif="forum" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="border-b border-slate-100 p-4">
                <x-search placeholder="Cari judul topik" :value="$cari" />
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($topik as $t)
                    <li>
                        <a href="{{ route('siswa.forum.show', [$pengampu, $t]) }}" class="flex gap-3 px-4 py-4 hover:bg-slate-50 sm:px-6">
                            <x-avatar :nama="$t->user->nama" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-slate-800">{{ $t->judul }}</span>
                                    @if ($t->user->role === 'guru')
                                        <x-badge warna="blue">Guru</x-badge>
                                    @endif
                                </div>
                                <div class="line-clamp-1 text-sm text-slate-500">{{ $t->isi }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $t->user->nama }} · {{ $t->created_at->locale('id')->diffForHumans() }}</div>
                            </div>
                            <span class="inline-flex items-center gap-1 self-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-blue-700">
                                <x-icon name="megaphone" class="h-3.5 w-3.5" /> {{ $t->balasan_count }}
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="px-6 py-10 text-center text-sm text-slate-500">Belum ada diskusi. Jadilah yang pertama bertanya!</li>
                @endforelse
            </ul>
            <div class="p-4">{{ $topik->links() }}</div>
        </div>

        <form method="POST" action="{{ route('siswa.forum.store', $pengampu) }}" class="card card-body h-fit space-y-4">
            @csrf
            <h3 class="card-title inline-flex items-center gap-2"><x-icon name="light-bulb" class="h-5 w-5 text-amber-500" /> Mulai Diskusi</h3>
            <x-field.input name="judul" label="Judul topik" required />
            <x-field.textarea name="isi" label="Pertanyaan atau ide kamu" rows="5" required />
            <button type="submit" class="btn-primary w-full">Kirim Topik</button>
        </form>
    </div>
</x-layouts.app>
