<x-layouts.app :title="'Forum · '.$topik->judul">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="forum" />

    <div class="card card-body space-y-3">
        <div class="flex items-start gap-3">
            <x-avatar :nama="$topik->user->nama" ukuran="h-11 w-11 text-sm" />
            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-semibold text-slate-800">{{ $topik->judul }}</h2>
                <div class="text-xs text-slate-500">{{ $topik->user->nama }} ({{ $topik->user->label_role }}) · {{ tanggal_indo($topik->created_at, false, true) }}</div>
            </div>
            <x-confirm :action="route('guru.forum.destroy', [$pengampu, $topik])" message="Topik beserta seluruh balasannya akan dihapus.">
                <x-icon name="trash" class="h-3.5 w-3.5" /> Hapus
            </x-confirm>
        </div>
        <p class="text-sm whitespace-pre-line text-slate-700">{{ $topik->isi }}</p>
    </div>

    <div class="space-y-3">
        @foreach ($topik->balasan as $b)
            <div @class(['card flex gap-3 p-4 sm:ml-10', 'border-blue-100 bg-blue-50/40' => $b->user->role === 'guru'])>
                <x-avatar :nama="$b->user->nama" />
                <div class="min-w-0 flex-1">
                    <div class="text-xs text-slate-500">
                        <span class="font-semibold text-slate-800">{{ $b->user->nama }}</span>
                        @if ($b->user->role === 'guru') <x-badge warna="sky">Guru</x-badge> @endif
                        · {{ $b->created_at->locale('id')->diffForHumans() }}
                    </div>
                    <p class="mt-1 text-sm whitespace-pre-line text-slate-700">{{ $b->isi }}</p>
                </div>
                <x-confirm :action="route('guru.forum.balasan.destroy', [$pengampu, $topik, $b])" message="Balasan ini akan dihapus."
                           button-class="h-fit rounded p-1 text-slate-400 hover:bg-red-50 hover:text-red-600">
                    <x-icon name="trash" class="h-4 w-4" />
                </x-confirm>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('guru.forum.balas', [$pengampu, $topik]) }}" class="card card-body space-y-3 sm:ml-10">
        @csrf
        <x-field.textarea name="isi" label="Tulis balasan" rows="3" required />
        <div class="flex justify-between">
            <a href="{{ route('guru.forum.index', $pengampu) }}" class="btn-secondary">Kembali</a>
            <button type="submit" class="btn-primary">Kirim Balasan</button>
        </div>
    </form>
</x-layouts.app>
