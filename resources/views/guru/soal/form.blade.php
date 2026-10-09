@php $edit = $soal->exists; @endphp

<x-layouts.app :title="($edit ? 'Ubah' : 'Tambah').' Soal'">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="kuis" />

    <form method="POST" action="{{ $edit ? route('guru.soal.update', [$pengampu, $kuis, $soal]) : route('guru.soal.store', [$pengampu, $kuis]) }}"
          class="card card-body max-w-3xl space-y-5" x-data="{ tipe: @js(old('tipe', $soal->tipe)) }">
        @csrf
        @if ($edit) @method('PUT') @endif

        <div>
            <h3 class="card-title">{{ $edit ? 'Ubah Soal' : 'Tambah Soal' }}</h3>
            <p class="text-sm text-slate-500">{{ $kuis->judul }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="label" for="tipe">Tipe soal</label>
                <select name="tipe" id="tipe" class="input" x-model="tipe">
                    <option value="pg">Pilihan ganda</option>
                    <option value="esai">Esai</option>
                </select>
            </div>
            <x-field.input name="bobot" type="number" min="1" label="Bobot (poin)" :value="$soal->bobot" required />
        </div>

        <x-field.textarea name="pertanyaan" label="Pertanyaan" :value="$soal->pertanyaan" rows="4" required />

        <div x-show="tipe === 'pg'" x-cloak class="space-y-3">
            @foreach (\App\Models\Soal::OPSI as $o)
                <div class="flex items-start gap-3">
                    <label class="mt-8 flex items-center gap-1 text-xs text-slate-500" title="Tandai sebagai kunci jawaban">
                        <input type="radio" name="kunci_jawaban" value="{{ $o }}" class="border-slate-300 text-emerald-600"
                               @checked(old('kunci_jawaban', $soal->kunci_jawaban) === $o)> Kunci
                    </label>
                    <div class="flex-1">
                        <x-field.input :name="'opsi_'.$o" :label="'Opsi '.strtoupper($o)" :value="$soal->{'opsi_'.$o}" />
                    </div>
                </div>
            @endforeach
            @error('kunci_jawaban') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <p x-show="tipe === 'esai'" x-cloak class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">Jawaban esai dikoreksi manual oleh guru di halaman hasil kuis.</p>

        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('guru.kuis.show', [$pengampu, $kuis]) }}" class="btn-secondary">Batal</a>
            @unless ($edit)
                <button type="submit" name="tambah_lagi" value="1" class="btn-secondary">Simpan & tambah lagi</button>
            @endunless
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-layouts.app>
