<x-layouts.app title="Wali Kelas">
    <x-page-header title="Penetapan Wali Kelas" :subtitle="'Tahun ajaran '.($tahunAktif?->label ?? '-').'. Satu guru hanya menjadi wali satu kelas.'" />

    <form method="POST" action="{{ route('operator.wali-kelas.update') }}" class="card">
        @csrf
        @method('PUT')
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Kelas</th><th>Jurusan</th><th>Jumlah siswa</th><th class="w-80">Wali kelas</th></tr></thead>
                <tbody>
                    @forelse ($kelas as $k)
                        <tr>
                            <td class="font-medium text-slate-800">{{ $k->nama_kelas }}</td>
                            <td>{{ $k->jurusan->kode }}</td>
                            <td>{{ $k->siswa_count }}</td>
                            <td>
                                <select name="wali[{{ $k->id }}]" class="input">
                                    <option value="">— Belum ditentukan —</option>
                                    @foreach ($daftarGuru as $g)
                                        <option value="{{ $g->id }}" @selected((string) old("wali.{$k->id}", $k->wali_kelas_id) === (string) $g->id)>{{ $g->nama }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="4" pesan="Belum ada kelas pada tahun ajaran aktif." />
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($kelas->isNotEmpty())
            <div class="flex justify-end border-t border-slate-100 p-4">
                <button type="submit" class="btn-primary">Simpan Wali Kelas</button>
            </div>
        @endif
    </form>
</x-layouts.app>
