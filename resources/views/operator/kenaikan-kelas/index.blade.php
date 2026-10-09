<x-layouts.app title="Kenaikan Kelas">
    <x-page-header title="Kenaikan Kelas" subtitle="Pindahkan siswa ke kelas pada tahun ajaran baru. Siswa kelas XII ditandai lulus dan akunnya dinonaktifkan." />

    {{-- Langkah 1: pilih tahun ajaran --}}
    <form method="GET" class="card card-body space-y-4">
        <h3 class="font-semibold"><span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-xs text-white">1</span>Pilih tahun ajaran</h3>
        <div class="grid gap-4 sm:grid-cols-3 sm:items-end">
            <x-field.select name="sumber_id" label="Dari (tahun ajaran lama)" :options="$daftarTahun->pluck('label', 'id')->all()"
                            :value="$sumber?->id ?? $tahunAktif?->id" required />
            <x-field.select name="tujuan_id" label="Ke (tahun ajaran baru)" :options="$daftarTahun->pluck('label', 'id')->all()"
                            :value="$tujuan?->id" placeholder="— Pilih —" required />
            <button type="submit" class="btn-primary">Lanjut</button>
        </div>
        <p class="text-xs text-slate-500">Tahun ajaran baru belum ada? Buat dulu di menu <a href="{{ route('operator.tahun-ajaran.create') }}" class="link">Tahun Ajaran</a> (umumnya semester ganjil).</p>
    </form>

    @if ($sumber && $tujuan && $sumber->id === $tujuan->id)
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Tahun ajaran tujuan harus berbeda dengan sumber.</div>
    @elseif ($sumber && $tujuan)
        {{-- Langkah 2: pemetaan --}}
        <div class="card">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="font-semibold"><span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-xs text-white">2</span>
                    Petakan kelas {{ $sumber->label }} → {{ $tujuan->label }}</h3>
                <form method="POST" action="{{ route('operator.kenaikan-kelas.buat-kelas') }}">
                    @csrf
                    <input type="hidden" name="sumber_id" value="{{ $sumber->id }}">
                    <input type="hidden" name="tujuan_id" value="{{ $tujuan->id }}">
                    <button type="submit" class="btn-secondary btn-sm"><x-icon name="plus" class="h-3.5 w-3.5" /> Buat otomatis kelas tujuan (X→XI, XI→XII)</button>
                </form>
            </div>

            <form method="POST" action="{{ route('operator.kenaikan-kelas.proses') }}">
                @csrf
                <input type="hidden" name="sumber_id" value="{{ $sumber->id }}">
                <input type="hidden" name="tujuan_id" value="{{ $tujuan->id }}">

                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Kelas lama</th><th>Siswa aktif</th><th class="w-80">Kelas tujuan</th></tr></thead>
                        <tbody>
                            @forelse ($kelasSumber as $k)
                                @php
                                    $usulan = $service->usulanNama($k);
                                    $default = $k->tingkat === 'XII'
                                        ? \App\Services\KenaikanKelasService::LULUS
                                        : $kelasTujuan->firstWhere('nama_kelas', $usulan)?->id;
                                @endphp
                                <tr>
                                    <td class="font-medium text-slate-800">{{ $k->nama_kelas }}</td>
                                    <td>{{ $k->siswa_count }}</td>
                                    <td>
                                        <select name="pemetaan[{{ $k->id }}]" class="input">
                                            <option value="">— Lewati —</option>
                                            <option value="lulus" @selected($default === 'lulus')>Lulus (nonaktifkan akun)</option>
                                            @foreach ($kelasTujuan as $kt)
                                                <option value="{{ $kt->id }}" @selected($default === $kt->id)>{{ $kt->nama_kelas }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <x-empty-row :colspan="3" pesan="Tidak ada kelas pada tahun ajaran sumber." />
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Langkah 3: proses --}}
                <div class="space-y-3 border-t border-slate-100 p-4">
                    <h3 class="font-semibold"><span class="mr-2 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-800 text-xs text-white">3</span>Proses</h3>
                    <p class="text-sm text-slate-600">Siswa tinggal kelas dapat dipindahkan manual setelah proses melalui menu Kelas → Siswa.</p>
                    <label class="flex items-start gap-2 text-sm">
                        <input type="checkbox" name="konfirmasi" value="1" class="mt-0.5 rounded border-slate-300">
                        <span>Saya sudah memeriksa pemetaan di atas.</span>
                    </label>
                    @error('konfirmasi') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" class="btn-primary" @disabled($kelasSumber->isEmpty())>
                        <x-icon name="arrow-up" class="h-4 w-4" /> Proses Kenaikan Kelas
                    </button>
                </div>
            </form>
        </div>
    @endif
</x-layouts.app>
