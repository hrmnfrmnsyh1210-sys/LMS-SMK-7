<x-layouts.app title="Berita & Agenda">
    <x-page-header title="Berita & Agenda" subtitle="Berita, prestasi, dan agenda sekolah yang tampil di website.">
        <a href="{{ route('operator.berita.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Tulis Berita</a>
    </x-page-header>

    <div class="card">
        <div class="border-b border-slate-100 p-4">
            <x-search placeholder="Cari judul berita" :value="$cari">
                <select name="kategori" class="input sm:w-44">
                    <option value="">Semua kategori</option>
                    @foreach (\App\Models\Berita::KATEGORI as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($kategori === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-search>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Judul</th><th>Kategori</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                <tbody>
                    @forelse ($berita as $b)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($b->gambar)
                                        <img src="{{ file_url($b->gambar) }}" alt="" class="h-11 w-16 shrink-0 rounded-lg object-cover">
                                    @else
                                        <span class="flex h-11 w-16 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400"><x-icon name="photo" class="h-5 w-5" /></span>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-medium text-slate-800">{{ $b->judul }}</div>
                                        <div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($b->cuplikan(), 80) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><x-badge warna="blue">{{ $b->labelKategori() }}</x-badge></td>
                            <td class="whitespace-nowrap">
                                @if ($b->sudahTerbit())
                                    <x-badge warna="emerald">Terbit</x-badge>
                                    <div class="mt-1 text-xs text-slate-500">{{ $b->diterbitkan_pada->translatedFormat('d M Y') }}</div>
                                @elseif ($b->diterbitkan_pada)
                                    <x-badge warna="amber">Terjadwal</x-badge>
                                    <div class="mt-1 text-xs text-slate-500">{{ $b->diterbitkan_pada->translatedFormat('d M Y H:i') }}</div>
                                @else
                                    <x-badge>Draf</x-badge>
                                @endif
                            </td>
                            <td>
                                <div class="flex justify-end gap-1.5">
                                    @if ($b->sudahTerbit())
                                        <a href="{{ route('web.berita.show', $b) }}" target="_blank" class="btn-secondary btn-sm" title="Lihat"><x-icon name="eye" class="h-3.5 w-3.5" /></a>
                                    @endif
                                    <a href="{{ route('operator.berita.edit', $b) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-3.5 w-3.5" /> Ubah</a>
                                    <x-confirm :action="route('operator.berita.destroy', $b)" message="Berita &quot;{{ $b->judul }}&quot; akan dihapus.">
                                        <x-icon name="trash" class="h-3.5 w-3.5" />
                                    </x-confirm>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="4" pesan="Belum ada berita." />
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $berita->links() }}</div>
    </div>
</x-layouts.app>
