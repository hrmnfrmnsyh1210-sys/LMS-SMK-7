<x-layouts.app :title="'Presensi Pertemuan '.$pertemuan">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="presensi" />

    @php
        $warna = ['hadir' => 'peer-checked:bg-emerald-600', 'izin' => 'peer-checked:bg-sky-600', 'sakit' => 'peer-checked:bg-amber-500', 'alpa' => 'peer-checked:bg-rose-600'];
    @endphp

    <form method="POST" action="{{ route('guru.presensi.store', $pengampu) }}" class="card"
          x-data="{ semua(status) { $root.querySelectorAll('input[type=radio][value=' + status + ']').forEach(r => r.checked = true) } }">
        @csrf
        <div class="grid gap-4 border-b border-slate-100 p-4 sm:grid-cols-4 sm:p-6">
            <x-field.input name="pertemuan_ke" type="number" min="1" label="Pertemuan ke" :value="$pertemuan" required />
            <x-field.input name="tanggal" type="date" label="Tanggal" :value="$tanggal" :max="now()->toDateString()" required />
            <div class="sm:col-span-2 sm:self-end">
                <span class="label">Tandai semua</span>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Services\PresensiService::STATUS as $k => $label)
                        <button type="button" class="btn-secondary btn-sm" @click="semua('{{ $k }}')">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        @error('status') <p class="px-6 pt-3 text-sm text-red-600">{{ $message }}</p> @enderror

        <div class="table-wrap p-2 sm:p-4">
            <table class="table">
                <thead><tr><th>#</th><th>Siswa</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($siswa as $s)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="font-medium text-slate-800">{{ $s->nama }}</div>
                                @if (isset($izin[$s->id]))
                                    <span class="text-xs text-sky-600">Surat {{ $izin[$s->id] }} disetujui</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach (\App\Services\PresensiService::STATUS as $k => $label)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="status[{{ $s->id }}]" value="{{ $k }}" class="peer sr-only"
                                                   @checked(old("status.{$s->id}", $status[$s->id]) === $k)>
                                            <span class="inline-block rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition peer-checked:border-transparent peer-checked:text-white {{ $warna[$k] }}">{{ $label }}</span>
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
            <button type="submit" class="btn-primary" @disabled($siswa->isEmpty())>Simpan Presensi</button>
        </div>
    </form>
</x-layouts.app>
