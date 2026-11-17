<x-layouts.app :title="'Presensi Pertemuan '.$pertemuan">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="presensi" />

    @php
        $warna = ['hadir' => 'peer-checked:bg-emerald-600', 'izin' => 'peer-checked:bg-sky-600', 'sakit' => 'peer-checked:bg-amber-500', 'alpa' => 'peer-checked:bg-rose-600'];
    @endphp

    <form method="POST" action="{{ route('guru.presensi.store', $pengampu) }}" enctype="multipart/form-data" class="space-y-4"
          x-data="{ semua(status) { $root.querySelectorAll('input[type=radio][value=' + status + ']').forEach(r => r.checked = true) } }">
        @csrf

        {{-- Info Pertemuan (readonly) --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Informasi Pertemuan</h3>
                <x-badge warna="blue">Pertemuan ke-{{ $pertemuan }}</x-badge>
            </div>
            <div class="grid gap-4 border-b border-slate-100 p-4 sm:grid-cols-3 sm:p-6">
                {{-- Pertemuan ke dikunci, tidak bisa diubah guru --}}
                <div>
                    <span class="label">Pertemuan ke</span>
                    <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                        <span class="font-bold text-slate-800 text-lg">{{ $pertemuan }}</span>
                        <span class="text-xs text-slate-500">(otomatis)</span>
                    </div>
                    <input type="hidden" name="pertemuan_ke" value="{{ $pertemuan }}">
                </div>
                <x-field.input name="tanggal" type="date" label="Tanggal" :value="$tanggal" :max="now()->toDateString()" required />
                <div class="sm:self-end">
                    <span class="label">Tandai semua</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Services\PresensiService::STATUS as $k => $label)
                            <button type="button" class="btn-secondary btn-sm" @click="semua('{{ $k }}')">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
            {{-- Jurnal Pembelajaran: Materi, Tugas, Catatan & Bukti Dokumentasi --}}
            <div class="grid gap-4 p-4 sm:p-6 lg:grid-cols-2 border-b border-slate-100">
                <div>
                    <label for="materi" class="label">Materi yang Diajarkan Hari Ini <span class="text-rose-500">*</span></label>
                    <textarea name="materi" id="materi" rows="2" maxlength="1000"
                              class="input w-full resize-none"
                              placeholder="Tuliskan pokok materi / kompetensi yang diajarkan hari ini…">{{ old('materi', $materi) }}</textarea>
                    @error('materi') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tugas" class="label">Tugas yang Diberikan ke Siswa <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea name="tugas" id="tugas" rows="2" maxlength="1000"
                              class="input w-full resize-none"
                              placeholder="Tuliskan penugasan / latihan yang diberikan ke siswa hari ini…">{{ old('tugas', $tugas) }}</textarea>
                    @error('tugas') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="catatan" class="label">Catatan Tambahan / Kendala Kelas <span class="text-slate-400 font-normal">(opsional)</span></label>
                    <textarea name="catatan" id="catatan" rows="2" maxlength="1000"
                              class="input w-full resize-none"
                              placeholder="Catatan interaksi siswa, kendala, atau hal penting lainnya…">{{ old('catatan', $catatan) }}</textarea>
                    @error('catatan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <x-field.file name="dokumentasi"
                                  label="Foto Bukti Dokumentasi Pembelajaran (Opsional)"
                                  :path-lama="$dokumentasi"
                                  help="Unggah foto kegiatan belajar mengajar di kelas. JPG/PNG maksimal 5 MB."
                                  accept="image/*,.pdf" />
                </div>
            </div>
        </div>

        {{-- Daftar Siswa --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Daftar Kehadiran Siswa</h3></div>

            @error('status') <p class="px-6 pt-3 text-sm text-red-600">{{ $message }}</p> @enderror

            <div class="table-wrap p-2 sm:p-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-10">#</th>
                            <th>Nama Siswa</th>
                            <th>Status Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswa as $s)
                            <tr>
                                <td class="text-center text-slate-500">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="font-medium text-slate-800">{{ $s->nama }}</div>
                                    @if (isset($izin[$s->id]))
                                        <span class="inline-flex items-center gap-1 text-xs text-sky-600">
                                            <x-icon name="document" class="h-3 w-3" /> Surat {{ $izin[$s->id] }} disetujui
                                        </span>
                                    @endif
                                    @if (isset($presensiTersimpan[$s->id]) && $presensiTersimpan[$s->id]->diabsen_oleh === 'siswa')
                                        <span class="inline-flex items-center gap-1 text-xs text-violet-600 font-medium">
                                            <x-icon name="user" class="h-3 w-3" /> Absen mandiri
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach (\App\Services\PresensiService::STATUS as $k => $label)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="status[{{ $s->id }}]" value="{{ $k }}" class="peer sr-only"
                                                       @checked(old("status.{$s->id}", $status[$s->id]) === $k)>
                                                <span class="inline-flex items-center rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition peer-checked:border-transparent peer-checked:text-white {{ $warna[$k] }}">
                                                    {{ $label }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-row :colspan="3" pesan="Belum ada siswa di kelas ini." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-100 p-4 sm:flex-row sm:justify-end">
                <a href="{{ route('guru.presensi.index', $pengampu) }}" class="btn-secondary">Batal</a>
                <button type="submit" class="btn-primary" @disabled($siswa->isEmpty())>
                    <x-icon name="check" class="h-4 w-4" /> Simpan Presensi
                </button>
            </div>
        </div>
    </form>
</x-layouts.app>
