<x-layouts.app :title="'Anggota Kelas '.$kelas->nama_kelas">
    <x-page-header :title="'Anggota Kelas '.$kelas->nama_kelas"
                   :subtitle="$kelas->jurusan->nama.' · '.$kelas->tahunAjaran->label.' · Wali kelas: '.($kelas->waliKelas?->nama ?? '-')">
        <a href="{{ route('operator.kelas.index', ['tahun_ajaran_id' => $kelas->tahun_ajaran_id]) }}" class="btn-secondary">Kembali</a>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Anggota saat ini --}}
        <div class="card">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h3 class="font-semibold">Siswa di kelas ini</h3>
                <x-badge>{{ $anggota->count() }} siswa</x-badge>
            </div>
            <div class="table-wrap max-h-[32rem] overflow-y-auto">
                <table class="table">
                    <thead><tr><th>#</th><th>Nama</th><th>NIS</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($anggota as $s)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="font-medium text-slate-800">{{ $s->nama }}</td>
                                <td>{{ $s->nis }}</td>
                                <td class="text-right">
                                    <x-confirm :action="route('operator.kelas.siswa.destroy', [$kelas, $s])" title="Keluarkan siswa?"
                                               message="{{ $s->nama }} akan dikeluarkan dari kelas {{ $kelas->nama_kelas }}." confirm-label="Ya, keluarkan">
                                        Keluarkan
                                    </x-confirm>
                                </td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="4" pesan="Belum ada siswa di kelas ini." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tambah siswa (pilih banyak) --}}
        <div class="card" x-data="{ semua: false }">
            <div class="border-b border-slate-100 px-4 py-3">
                <h3 class="font-semibold">Tambahkan siswa</h3>
                <p class="text-xs text-slate-500">Hanya menampilkan siswa aktif yang belum punya kelas di {{ $kelas->tahunAjaran->label }}.</p>
            </div>
            <div class="border-b border-slate-100 p-4">
                <x-search placeholder="Cari nama atau NIS" :value="$cari" />
            </div>

            <form method="POST" action="{{ route('operator.kelas.siswa.store', $kelas) }}">
                @csrf
                @error('siswa_id') <p class="px-4 pt-3 text-sm text-red-600">{{ $message }}</p> @enderror
                @error('siswa_id.*') <p class="px-4 pt-3 text-sm text-red-600">{{ $message }}</p> @enderror

                <div class="table-wrap max-h-[28rem] overflow-y-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="w-10">
                                    <input type="checkbox" class="rounded border-slate-300" x-model="semua"
                                           @change="$root.querySelectorAll('.pilih-siswa').forEach(c => c.checked = semua)">
                                </th>
                                <th>Nama</th><th>NIS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($kandidat as $s)
                                <tr>
                                    <td><input type="checkbox" name="siswa_id[]" value="{{ $s->id }}" class="pilih-siswa rounded border-slate-300"></td>
                                    <td>{{ $s->nama }}</td>
                                    <td>{{ $s->nis }}</td>
                                </tr>
                            @empty
                                <x-empty-row :colspan="3" pesan="Tidak ada siswa yang bisa ditambahkan." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>{{ $kandidat->links() }}</div>
                    <button type="submit" class="btn-primary" @disabled($kandidat->isEmpty())>Masukkan ke kelas</button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
