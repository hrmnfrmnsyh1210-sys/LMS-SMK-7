<x-layouts.web title="Galeri" latar-akhir="#ffffff" description="Galeri foto kegiatan dan fasilitas {{ $sekolah['nama_sekolah'] }}.">
    <section class="bg-navy-800 pt-32 pb-14 text-white lg:pt-36">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <h1 class="font-display text-3xl font-bold sm:text-5xl">Galeri</h1>
            <p class="mt-2 text-white/75">Dokumentasi kegiatan dan fasilitas {{ $sekolah['nama_sekolah'] }}.</p>
        </div>
    </section>

    <section class="py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            @if ($galeri->isEmpty())
                <div class="rounded-xl bg-latar py-16 text-center text-slate-400">
                    <x-icon name="photo" class="mx-auto h-10 w-10" />
                    <p class="mt-2">Belum ada foto di galeri.</p>
                </div>
            @else
                @include('web.galeri-grid', ['galeri' => $galeri])
                <div class="mt-10">{{ $galeri->links() }}</div>
            @endif
        </div>
    </section>
</x-layouts.web>
