<x-layouts.app :title="'Materi · '.$pengampu->label">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="materi" />

    <x-page-header title="Materi Pembelajaran" subtitle="Dikelompokkan per pertemuan. Dapat berupa file (PDF, PPT, video) atau tautan.">
        <a href="{{ route('guru.materi.create', $pengampu) }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tambah Materi</a>
    </x-page-header>

    @forelse ($materi as $pertemuan => $daftar)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pertemuan {{ $pertemuan }}</h3>
            </div>
            <ul class="divide-y divide-slate-100 px-4 pb-2 sm:px-6">
                @foreach ($daftar as $m)
                    <li class="flex flex-col gap-3 py-3 sm:flex-row sm:items-start">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                            <x-icon :name="$m->tautan && ! $m->file_path ? 'link' : 'document'" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-slate-800">{{ $m->judul }}</div>
                            @if ($m->deskripsi)
                                <p class="mt-0.5 text-sm whitespace-pre-line text-slate-600">{{ $m->deskripsi }}</p>
                            @endif
                            <div class="mt-1 flex flex-wrap gap-3 text-xs">
                                @if ($m->file_path)
                                    <a href="{{ file_url($m->file_path) }}" target="_blank" class="link">📎 {{ basename($m->file_path) }}</a>
                                @endif
                                @if ($m->tautan)
                                    <a href="{{ $m->tautan }}" target="_blank" rel="noopener" class="link">🔗 {{ \Illuminate\Support\Str::limit($m->tautan, 50) }}</a>
                                @endif
                                <span class="text-slate-400">{{ tanggal_indo($m->created_at, false) }}</span>
                            </div>
                        </div>
                        <div class="flex gap-1.5">
                            <a href="{{ route('guru.materi.edit', [$pengampu, $m]) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
                            <x-confirm :action="route('guru.materi.destroy', [$pengampu, $m])" message="Materi “{{ $m->judul }}” beserta file-nya akan dihapus.">
                                <x-icon name="trash" class="h-3.5 w-3.5" />
                            </x-confirm>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <div class="card card-body text-center text-sm text-slate-500">Belum ada materi. Tambahkan materi pertemuan pertama.</div>
    @endforelse
</x-layouts.app>
