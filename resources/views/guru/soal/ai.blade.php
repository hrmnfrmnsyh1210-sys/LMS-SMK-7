<x-layouts.app title="Draf Soal AI">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="kuis" />

    <x-page-header title="Tinjau Draf Soal AI" :subtitle="$kuis->label_jenis.' '.$kuis->judul.' · '.count($soal).' draf. Periksa kebenaran isi dan kunci jawaban sebelum disimpan.'">
        <a href="{{ route('guru.kuis.show', [$pengampu, $kuis]) }}" class="btn-secondary">Batal</a>
    </x-page-header>

    @error('soal')
        <div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    <form method="POST" action="{{ route('guru.soal.ai.simpan', [$pengampu, $kuis]) }}" class="space-y-4"
          x-data="{ jumlah: 0 }" x-init="jumlah = $el.querySelectorAll('[data-pilih]:checked').length" @change="jumlah = $el.querySelectorAll('[data-pilih]:checked').length">
        @csrf

        @foreach ($soal as $i => $s)
            @php
                $tipe = old("soal.$i.tipe", $s['tipe']);
                $dipilih = ! session()->hasOldInput() || old("soal.$i") !== null;
            @endphp
            {{-- Fieldset yang tidak dicentang dinonaktifkan sehingga tidak ikut terkirim. --}}
            <div class="card p-4 sm:p-5" x-data="{ pilih: @js($dipilih) }" :class="pilih ? '' : 'opacity-50'">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <input type="checkbox" x-model="pilih" data-pilih class="rounded border-slate-300 text-blue-600"> Soal {{ $loop->iteration }}
                    </label>
                    <x-badge :warna="$tipe === 'pg' ? 'sky' : 'amber'">{{ $tipe === 'pg' ? 'Pilihan ganda' : 'Esai' }}</x-badge>
                </div>
                <fieldset :disabled="! pilih" class="space-y-3">
                    <input type="hidden" name="soal[{{ $i }}][tipe]" value="{{ $tipe }}">
                    <div class="grid gap-3 sm:grid-cols-[1fr_8rem]">
                        <x-field.textarea name="soal[{{ $i }}][pertanyaan]" id="soal_{{ $i }}_pertanyaan" label="Pertanyaan" rows="3"
                                          :value="old('soal.'.$i.'.pertanyaan', $s['pertanyaan'])" />
                        <x-field.input name="soal[{{ $i }}][bobot]" type="number" min="1" max="100" label="Bobot" :value="$s['bobot']" />
                    </div>
                    @if ($tipe === 'pg')
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach (\App\Models\Soal::OPSI as $o)
                                <div class="flex items-start gap-2">
                                    <label class="mt-8 flex items-center gap-1 text-xs text-slate-500" title="Kunci jawaban">
                                        <input type="radio" name="soal[{{ $i }}][kunci_jawaban]" value="{{ $o }}" class="border-slate-300 text-emerald-600"
                                               @checked(old("soal.$i.kunci_jawaban", $s['kunci_jawaban']) === $o)>
                                    </label>
                                    <div class="flex-1">
                                        <x-field.input :name="'soal['.$i.'][opsi_'.$o.']'" :label="'Opsi '.strtoupper($o)" :value="$s['opsi_'.$o]" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error("soal.$i.kunci_jawaban") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @endif
                </fieldset>
            </div>
        @endforeach

        <div class="card sticky bottom-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-600"><strong x-text="jumlah"></strong> soal dipilih. Radio di kiri opsi menandai kunci jawaban.</p>
            <button type="submit" class="btn-primary" :disabled="jumlah === 0"><x-icon name="check" class="h-4 w-4" /> Simpan ke Bank Soal</button>
        </div>
    </form>
</x-layouts.app>
