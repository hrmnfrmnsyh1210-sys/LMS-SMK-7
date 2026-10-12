<x-layouts.app title="Galeri">
    <x-page-header title="Galeri" subtitle="Foto kegiatan dan fasilitas sekolah. Urutan terkecil tampil lebih dulu.">
        <a href="{{ route('operator.galeri.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Foto</a>
    </x-page-header>

    @if ($galeri->isEmpty())
        <div class="card card-body flex flex-col items-center py-14 text-center text-slate-500">
            <x-icon name="photo" class="mb-2 h-10 w-10 text-slate-300" />
            Belum ada foto di galeri.
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($galeri as $g)
                <div class="card overflow-hidden">
                    <img src="{{ file_url($g->gambar) }}" alt="{{ $g->judul }}" class="aspect-4/3 w-full object-cover">
                    <div class="space-y-3 p-4">
                        <div>
                            <div class="truncate font-medium text-slate-800">{{ $g->judul }}</div>
                            <div class="text-xs text-slate-500">Urutan {{ $g->urutan }}</div>
                        </div>
                        <div class="flex gap-1.5">
                            <a href="{{ route('operator.galeri.edit', $g) }}" class="btn-secondary btn-sm flex-1"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
                            <x-confirm :action="route('operator.galeri.destroy', $g)" message="Foto &quot;{{ $g->judul }}&quot; akan dihapus.">
                                <x-icon name="trash" class="h-3.5 w-3.5" />
                            </x-confirm>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div>{{ $galeri->links() }}</div>
    @endif
</x-layouts.app>
