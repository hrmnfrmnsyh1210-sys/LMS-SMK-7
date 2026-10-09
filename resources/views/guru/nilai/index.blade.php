<x-layouts.app :title="'Nilai · '.$pengampu->label">
    <x-kelas-ajar-header :pengampu="$pengampu" aktif="nilai" />

    <x-page-header title="Rekap Nilai" subtitle="Nilai tugas & kuis dihitung otomatis (rata-rata). Isi UTS/UAS manual atau ambil dari kuis jenis UTS/UAS.">
        @if ($adaUjian)
            <form method="POST" action="{{ route('guru.nilai.ambil-ujian', $pengampu) }}">
                @csrf
                <button type="submit" class="btn-secondary"><x-icon name="refresh" class="h-4 w-4" /> Ambil UTS/UAS dari Kuis</button>
            </form>
        @endif
        <a href="{{ route('guru.nilai.ekspor', $pengampu) }}" class="btn-secondary"><x-icon name="download" class="h-4 w-4" /> Ekspor Excel</a>
    </x-page-header>

    {{-- Bobot --}}
    <form method="POST" action="{{ route('guru.nilai.bobot', $pengampu) }}" class="card card-body"
          x-data="{ b: { tugas: {{ old('bobot_tugas', $bobot->bobot_tugas) }}, kuis: {{ old('bobot_kuis', $bobot->bobot_kuis) }}, uts: {{ old('bobot_uts', $bobot->bobot_uts) }}, uas: {{ old('bobot_uas', $bobot->bobot_uas) }} } }">
        @csrf
        @method('PUT')
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="flex-1">
                <h3 class="card-title">Bobot Nilai Akhir</h3>
                <p class="text-xs text-slate-500">Nilai akhir = (tugas × bobot + kuis × bobot + UTS × bobot + UAS × bobot) / 100</p>
            </div>
            @foreach (['tugas' => 'Tugas', 'kuis' => 'Kuis', 'uts' => 'UTS', 'uas' => 'UAS'] as $k => $label)
                <div class="w-24">
                    <label class="label" for="bobot_{{ $k }}">{{ $label }} (%)</label>
                    <input type="number" min="0" max="100" name="bobot_{{ $k }}" id="bobot_{{ $k }}" class="input" value="{{ old('bobot_'.$k, $bobot->{'bobot_'.$k}) }}" x-model.number="b.{{ $k }}">
                </div>
            @endforeach
            <div class="text-sm">
                <div class="label">Total</div>
                <div class="py-2 font-bold" :class="(b.tugas + b.kuis + b.uts + b.uas) === 100 ? 'text-emerald-600' : 'text-rose-600'"
                     x-text="(b.tugas + b.kuis + b.uts + b.uas) + '%'">{{ $bobot->bobot_tugas + $bobot->bobot_kuis + $bobot->bobot_uts + $bobot->bobot_uas }}%</div>
            </div>
            <button type="submit" class="btn-primary" :disabled="(b.tugas + b.kuis + b.uts + b.uas) !== 100">Simpan Bobot</button>
        </div>
        @error('bobot_tugas') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
    </form>

    {{-- Tabel nilai --}}
    <form method="POST" action="{{ route('guru.nilai.simpan', $pengampu) }}" class="card">
        @csrf
        @method('PUT')
        <div class="table-wrap p-2 sm:p-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th><th>Siswa</th>
                        <th>Tugas <span class="font-normal">({{ $bobot->bobot_tugas }}%)</span></th>
                        <th>Kuis <span class="font-normal">({{ $bobot->bobot_kuis }}%)</span></th>
                        <th class="w-28">UTS <span class="font-normal">({{ $bobot->bobot_uts }}%)</span></th>
                        <th class="w-28">UAS <span class="font-normal">({{ $bobot->bobot_uas }}%)</span></th>
                        <th>Nilai Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @php $kkm = (float) $sekolah['kkm']; @endphp
                    @forelse ($nilai as $n)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="font-medium text-slate-800">{{ $n->siswa->nama }}</td>
                            <td>{{ $n->nilai_tugas !== null ? (float) $n->nilai_tugas : '-' }}</td>
                            <td>{{ $n->nilai_kuis !== null ? (float) $n->nilai_kuis : '-' }}</td>
                            @foreach (['nilai_uts', 'nilai_uas'] as $kolom)
                                <td>
                                    <input type="number" step="0.01" min="0" max="100" name="nilai[{{ $n->siswa_id }}][{{ $kolom }}]"
                                           value="{{ old("nilai.{$n->siswa_id}.{$kolom}", $n->{$kolom} !== null ? (float) $n->{$kolom} : null) }}"
                                           @class(['input py-1.5', 'input-error' => $errors->has("nilai.{$n->siswa_id}.{$kolom}")])>
                                </td>
                            @endforeach
                            <td>
                                @if ($n->nilai_akhir !== null)
                                    <span @class(['rounded-lg px-2.5 py-1 font-bold', 'bg-emerald-50 text-emerald-700' => $n->nilai_akhir >= $kkm, 'bg-rose-50 text-rose-700' => $n->nilai_akhir < $kkm])>{{ (float) $n->nilai_akhir }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-row :colspan="7" pesan="Belum ada siswa di kelas ini." />
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($errors->has('nilai.*'))
            <p class="px-6 text-sm text-red-600">Periksa kembali nilai UTS/UAS (0–100).</p>
        @endif
        <div class="flex flex-col gap-2 border-t border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-slate-500">KKM {{ $kkm }}. Nilai di bawah KKM ditandai merah.</p>
            <button type="submit" class="btn-primary" @disabled($nilai->isEmpty())>Simpan & Hitung Nilai Akhir</button>
        </div>
    </form>
</x-layouts.app>
