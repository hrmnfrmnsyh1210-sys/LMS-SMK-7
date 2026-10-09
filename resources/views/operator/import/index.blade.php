<x-layouts.app title="Import Excel">
    <x-page-header title="Import Guru & Siswa" subtitle="Unduh template, isi data, lalu unggah kembali. Baris yang valid tetap disimpan walaupun ada baris lain yang gagal." />

    @php $hasil = session('hasil_import'); @endphp
    @if ($hasil)
        <div class="card">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-4 py-3 sm:px-6">
                <h3 class="font-semibold">Hasil import {{ $hasil['jenis'] }}</h3>
                <x-badge warna="emerald">{{ $hasil['berhasil'] }} berhasil</x-badge>
                <x-badge :warna="count($hasil['gagal']) ? 'rose' : 'slate'">{{ count($hasil['gagal']) }} gagal</x-badge>
            </div>
            @if (count($hasil['gagal']))
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Baris</th><th>Data</th><th>Alasan gagal</th></tr></thead>
                        <tbody>
                            @foreach ($hasil['gagal'] as $g)
                                <tr>
                                    <td>{{ $g['baris'] }}</td>
                                    <td class="whitespace-nowrap">{{ $g['data'] }}</td>
                                    <td>
                                        <ul class="list-inside list-disc text-red-700">
                                            @foreach ($g['alasan'] as $alasan)
                                                <li>{{ $alasan }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="px-6 py-4 text-sm text-emerald-700">Semua baris berhasil diimpor.</p>
            @endif
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach (['guru' => 'Guru', 'siswa' => 'Siswa'] as $jenis => $label)
            <div class="card card-body space-y-4">
                <div>
                    <h3 class="font-semibold">Import {{ $label }}</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Kolom: <code class="rounded bg-slate-100 px-1 text-xs">{{ implode(', ', $kolom[$jenis]) }}</code>
                    </p>
                    <ul class="mt-2 list-inside list-disc text-xs text-slate-500">
                        @if ($jenis === 'guru')
                            <li>Username boleh kosong → memakai NIP. Jika NIP kosong, username wajib diisi.</li>
                            <li>Password awal = NIP / username.</li>
                        @else
                            <li>Username & password awal = NIS.</li>
                            <li>Kolom <em>kelas</em> diisi nama kelas pada tahun ajaran aktif (mis. X RPL 1), boleh kosong.</li>
                            <li>Tanggal lahir format YYYY-MM-DD.</li>
                        @endif
                        <li>Jenis kelamin diisi L atau P.</li>
                    </ul>
                </div>

                <a href="{{ route('operator.import.template', $jenis) }}" class="btn-secondary w-full sm:w-auto">
                    <x-icon name="download" class="h-4 w-4" /> Unduh template {{ strtolower($label) }}
                </a>

                <form method="POST" action="{{ route('operator.import.store', $jenis) }}" enctype="multipart/form-data"
                      class="space-y-3 border-t border-slate-100 pt-4" x-data="{ proses: false }" @submit="proses = true">
                    @csrf
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                           class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-slate-200">
                    @error('file') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" class="btn-primary" :disabled="proses">
                        <x-icon name="upload" class="h-4 w-4" />
                        <span x-text="proses ? 'Memproses...' : 'Import {{ strtolower($label) }}'">Import {{ strtolower($label) }}</span>
                    </button>
                </form>
            </div>
        @endforeach
    </div>
</x-layouts.app>
