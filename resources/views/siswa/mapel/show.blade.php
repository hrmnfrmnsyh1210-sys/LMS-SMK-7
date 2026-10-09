<x-layouts.app :title="'Materi · '.$pengampu->mapel->nama_mapel">
    <x-siswa.mapel-header :pengampu="$pengampu" aktif="materi" />

    @forelse ($materi as $pertemuan => $daftar)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title inline-flex items-center gap-2">
                    <span class="flex h-7 min-w-7 items-center justify-center rounded-full bg-blue-50 px-2 text-xs font-bold text-blue-700">{{ $pertemuan }}</span>
                    Pertemuan {{ $pertemuan }}
                </h3>
            </div>
            <ul class="divide-y divide-slate-100 px-4 pb-2 sm:px-6">
                @foreach ($daftar as $m)
                    <li class="flex gap-3 py-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br from-blue-100 to-pink-100 text-blue-600">
                            <x-icon :name="$m->tautan && ! $m->file_path ? 'link' : 'document'" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-slate-800">{{ $m->judul }}</div>
                            @if ($m->deskripsi)
                                <p class="mt-0.5 text-sm whitespace-pre-line text-slate-600">{{ $m->deskripsi }}</p>
                            @endif
                            <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                @if ($m->file_path)
                                    <a href="{{ file_url($m->file_path) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 font-medium text-blue-700 hover:bg-blue-100">
                                        <x-icon name="download" class="h-3.5 w-3.5" /> {{ \Illuminate\Support\Str::limit(basename($m->file_path), 40) }}
                                    </a>
                                @endif
                                @if ($m->tautan)
                                    <a href="{{ $m->tautan }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-full bg-pink-50 px-3 py-1 font-medium text-pink-600 hover:bg-pink-100">
                                        <x-icon name="link" class="h-3.5 w-3.5" /> Buka tautan
                                    </a>
                                @endif
                                <span class="self-center text-slate-400">{{ tanggal_indo($m->created_at, false) }}</span>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <div class="card card-body text-center text-sm text-slate-500">Guru belum mengunggah materi untuk mapel ini.</div>
    @endforelse
</x-layouts.app>
