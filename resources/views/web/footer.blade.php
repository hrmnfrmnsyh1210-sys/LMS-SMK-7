{{-- Footer website publik. Disertakan dari layouts/web: memakai $sekolah, $menu, $sosial, $logo, $bagian, $latarAkhir. --}}
@php
    $ikonSosial = ['Instagram' => 'instagram', 'Facebook' => 'facebook', 'YouTube' => 'youtube', 'TikTok' => 'tiktok'];
    $wa = $sekolah['web_whatsapp'] ? preg_replace('/^0/', '62', preg_replace('/\D/', '', $sekolah['web_whatsapp'])) : null;
    $kontak = array_values(array_filter([
        $sekolah['alamat_sekolah'] ? ['ikon' => 'map-pin', 'label' => 'Alamat', 'teks' => $sekolah['alamat_sekolah'], 'url' => null] : null,
        $sekolah['telepon_sekolah'] ? ['ikon' => 'phone', 'label' => 'Telepon', 'teks' => $sekolah['telepon_sekolah'], 'url' => 'tel:'.preg_replace('/[^0-9+]/', '', $sekolah['telepon_sekolah'])] : null,
        $sekolah['email_sekolah'] ? ['ikon' => 'envelope', 'label' => 'Email', 'teks' => $sekolah['email_sekolah'], 'url' => 'mailto:'.$sekolah['email_sekolah']] : null,
        $wa ? ['ikon' => 'chat', 'label' => 'WhatsApp', 'teks' => $sekolah['web_whatsapp'], 'url' => 'https://wa.me/'.$wa] : null,
    ]));
@endphp

<footer class="relative isolate overflow-hidden bg-navy-950 text-slate-400">
    {{-- Gelombang biru berlapis menyambung dari section terakhir halaman. --}}
    <div class="relative h-10 sm:h-14 lg:h-16" style="background: {{ $latarAkhir }}">
        <x-web.gelombang isi="#051633" tinggi="h-full" />
    </div>

    {{-- Latar: cahaya biru, cincin konsentris, dan pola titik. --}}
    <div aria-hidden="true" class="pointer-events-none absolute top-20 -right-40 -z-10 h-80 w-80 rounded-full bg-[#2f6fed]/25 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 -left-32 -z-10 h-72 w-72 rounded-full bg-[#13b5cf]/15 blur-3xl"></div>
    <svg aria-hidden="true" viewBox="0 0 400 400" class="pointer-events-none absolute -right-20 -bottom-16 -z-10 h-80 w-80 opacity-30">
        @foreach ([190, 150, 110, 70] as $r)
            <circle cx="200" cy="200" r="{{ $r }}" fill="none" stroke="#5b9bff" stroke-width="1.5" @if ($loop->odd) stroke-dasharray="4 8" @endif />
        @endforeach
    </svg>
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 opacity-[0.07] [background-image:radial-gradient(#ffffff_1px,transparent_1px)] [background-size:22px_22px] [mask-image:linear-gradient(to_bottom,transparent,black_30%,black_70%,transparent)]"></div>

    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        {{-- Kartu ajakan --}}
        <div class="relative isolate mt-2 overflow-hidden rounded-2xl bg-linear-to-r from-[#2f6fed] via-[#2a8de0] to-[#13b5cf] px-6 py-5 text-white shadow-xl shadow-[#2f6fed]/20 sm:px-8 sm:py-6">
            <div aria-hidden="true" class="absolute -top-20 -right-10 -z-10 h-64 w-64 rounded-full bg-white/10"></div>
            <div aria-hidden="true" class="absolute -top-8 right-24 -z-10 h-40 w-40 rounded-full bg-white/10"></div>
            <div aria-hidden="true" class="absolute -bottom-24 left-1/3 -z-10 h-56 w-56 rounded-full border-2 border-dashed border-white/20"></div>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                                        <h2 class="font-display text-lg font-bold sm:text-xl">Bagian dari keluarga besar {{ $sekolah['nama_sekolah'] }}?</h2>
                    <p class="mt-1 text-sm text-white/80">Akses materi, tugas, kuis, dan nilai kapan saja melalui LMS sekolah.</p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-3">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-navy-900 shadow-lg transition hover:-translate-y-0.5 hover:bg-kuning-300">
                        Masuk LMS <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                    <a href="{{ $bagian('kontak') }}" class="inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold text-white ring-1 ring-white/60 transition hover:bg-white/10">
                        Hubungi kami
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-8 py-10 md:grid-cols-2 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <div class="flex items-center gap-3">
                    @if ($logo)
                        <img src="{{ $logo }}" alt="" class="h-10 w-10 rounded-xl bg-white object-contain p-0.5">
                    @else
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-linear-to-br from-[#2f6fed] to-[#13b5cf] text-white shadow-lg shadow-[#2f6fed]/30"><x-icon name="academic-cap" class="h-6 w-6" /></span>
                    @endif
                    <div>
                        <div class="font-display text-lg font-bold text-white">{{ $sekolah['nama_sekolah'] }}</div>
                        @if ($sekolah['npsn'])
                            <div class="text-xs text-slate-400">NPSN {{ $sekolah['npsn'] }}</div>
                        @endif
                    </div>
                </div>
                @if ($sekolah['web_hero_subjudul'])
                    <p class="mt-3 max-w-md text-sm leading-relaxed">{{ $sekolah['web_hero_subjudul'] }}</p>
                @endif
                @if ($sosial)
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($sosial as $nama => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $nama }}" title="{{ $nama }}"
                               class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/5 text-slate-300 ring-1 ring-white/10 transition hover:-translate-y-0.5 hover:bg-[#2f6fed] hover:text-white hover:ring-transparent">
                                <x-icon :name="$ikonSosial[$nama]" class="h-5 w-5" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="lg:col-span-3">
                <h3 class="flex items-center gap-2 text-sm font-semibold tracking-wide text-white uppercase">
                    <span class="h-0.5 w-5 rounded-full bg-linear-to-r from-[#2f6fed] to-[#13b5cf]"></span> Tautan
                </h3>
                <ul class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2.5 text-sm">
                    @foreach ([...$menu, ['label' => 'Masuk LMS', 'url' => route('login')]] as $item)
                        <li>
                            <a href="{{ $item['url'] }}" class="group inline-flex items-center gap-2 transition hover:text-white">
                                <x-icon name="chevron-right" class="h-3.5 w-3.5 text-[#5b9bff] transition group-hover:translate-x-0.5" /> {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="md:col-span-2 lg:col-span-4">
                <h3 class="flex items-center gap-2 text-sm font-semibold tracking-wide text-white uppercase">
                    <span class="h-0.5 w-5 rounded-full bg-linear-to-r from-[#2f6fed] to-[#13b5cf]"></span> Kontak
                </h3>
                <ul class="mt-4 space-y-2 text-sm">
                    @forelse ($kontak as $k)
                        <li>
                            @php $tag = $k['url'] ? 'a' : 'div'; @endphp
                            <{{ $tag }} @if ($k['url']) href="{{ $k['url'] }}" @if (str_starts_with($k['url'], 'http')) target="_blank" rel="noopener" @endif @endif
                                class="group flex items-center gap-3 rounded-xl bg-white/[0.03] px-3 py-2 ring-1 ring-white/5 transition hover:bg-white/[0.06]">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#2f6fed]/15 text-[#7fb0ff]"><x-icon :name="$k['ikon']" class="h-4.5 w-4.5" /></span>
                                <span class="min-w-0">
                                                                        <span class="block break-words text-slate-200 group-hover:text-white">{{ $k['teks'] }}</span>
                                </span>
                            </{{ $tag }}>
                        </li>
                    @empty
                        <li class="text-slate-500">Informasi kontak akan segera tersedia.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="border-t border-white/10 bg-black/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-3.5 text-xs sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <span>&copy; {{ date('Y') }} {{ $sekolah['nama_sekolah'] }}. Hak cipta dilindungi.</span>
            <a href="#" @click.prevent="window.scrollTo({ top: 0, behavior: 'smooth' })" class="group inline-flex items-center gap-2 self-start font-medium text-slate-300 hover:text-white sm:self-auto">
                Kembali ke atas
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white/5 ring-1 ring-white/10 transition group-hover:bg-[#2f6fed]"><x-icon name="arrow-up" class="h-4 w-4" /></span>
            </a>
        </div>
    </div>
</footer>
