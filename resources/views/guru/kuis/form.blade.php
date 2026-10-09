@php $edit = $kuis->exists; @endphp

<x-layouts.app :title="($edit ? 'Ubah' : 'Buat').' Kuis'">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="kuis" />

    <form method="POST" action="{{ $edit ? route('guru.kuis.update', [$pengampu, $kuis]) : route('guru.kuis.store', $pengampu) }}"
          class="card card-body max-w-3xl space-y-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <h3 class="card-title">{{ $edit ? 'Pengaturan Kuis' : 'Buat Kuis' }}</h3>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <x-field.input name="judul" label="Judul" :value="$kuis->judul" placeholder="Kuis Bab 1" required />
            </div>
            <x-field.select name="jenis" label="Jenis" :options="\App\Models\Kuis::JENIS" :value="$kuis->jenis" required />
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <x-field.input name="waktu_mulai" type="datetime-local" label="Dibuka" :value="$kuis->waktu_mulai?->format('Y-m-d\TH:i')" required />
            <x-field.input name="waktu_selesai" type="datetime-local" label="Ditutup" :value="$kuis->waktu_selesai?->format('Y-m-d\TH:i')" required />
            <x-field.input name="durasi_menit" type="number" min="1" label="Durasi (menit)" :value="$kuis->durasi_menit" required />
        </div>
        <p class="-mt-2 text-xs text-slate-500">Siswa hanya bisa mulai di antara waktu dibuka dan ditutup, satu kali percobaan. Batas waktu = waktu mulai siswa + durasi, maksimal waktu ditutup.</p>

        <div class="space-y-2">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="acak_soal" value="1" class="rounded border-slate-300" @checked(old('acak_soal', $kuis->acak_soal))>
                Acak urutan soal untuk setiap siswa
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="tampilkan_hasil" value="1" class="rounded border-slate-300" @checked(old('tampilkan_hasil', $kuis->tampilkan_hasil))>
                Tampilkan hasil/skor kepada siswa setelah selesai
            </label>
        </div>

        <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:justify-between">
            @if ($edit)
                <x-confirm :action="route('guru.kuis.destroy', [$pengampu, $kuis])" message="Kuis beserta seluruh soalnya akan dihapus." button-class="btn-danger">
                    <x-icon name="trash" class="h-4 w-4" /> Hapus Kuis
                </x-confirm>
            @else
                <span></span>
            @endif
            <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <a href="{{ $edit ? route('guru.kuis.show', [$pengampu, $kuis]) : route('guru.kuis.index', $pengampu) }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </div>
    </form>
</x-layouts.app>
