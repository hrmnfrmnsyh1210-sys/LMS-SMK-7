<x-layouts.web title="Berita & Agenda" description="Berita, prestasi, dan agenda terbaru dari {{ $sekolah['nama_sekolah'] }}.">
    <section class="bg-navy-800 pt-32 pb-14 text-white lg:pt-36">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <h1 class="font-display text-3xl font-bold sm:text-5xl">Berita & Agenda</h1>
            <p class="mt-2 text-white/75">Kabar terbaru, prestasi, dan agenda kegiatan {{ $sekolah['nama_sekolah'] }}.</p>
        </div>
    </section>

    <section class="bg-latar py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('web.berita.index') }}" @class(['rounded-full px-4 py-2 text-sm font-medium transition', 'bg-navy-800 text-white' => ! $kategori, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-navy-700' => $kategori])>Semua</a>
                @foreach (\App\Models\Berita::KATEGORI as $nilai => $label)
                    <a href="{{ route('web.berita.index', ['kategori' => $nilai]) }}" @class(['rounded-full px-4 py-2 text-sm font-medium transition', 'bg-navy-800 text-white' => $kategori === $nilai, 'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-navy-700' => $kategori !== $nilai])>{{ $label }}</a>
                @endforeach
            </div>

            @if ($berita->isEmpty())
                <div class="mt-10 rounded-xl bg-white py-16 text-center text-slate-400 ring-1 ring-slate-100">
                    <x-icon name="megaphone" class="mx-auto h-10 w-10" />
                    <p class="mt-2">Belum ada berita yang diterbitkan.</p>
                </div>
            @else
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($berita as $b)
                        @include('web.berita.kartu', ['berita' => $b])
                    @endforeach
                </div>
                <div class="mt-10">{{ $berita->links() }}</div>
            @endif
        </div>
    </section>
</x-layouts.web>
