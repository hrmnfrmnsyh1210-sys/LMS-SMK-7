<x-layouts.app title="Backup & Restore">
    <x-page-header title="Backup & Restore Database" />

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card card-body space-y-4">
            <div>
                <h3 class="font-semibold">Backup</h3>
                <p class="mt-1 text-sm text-slate-600">Unduh salinan seluruh database dalam format <code>.sql</code>. Simpan file di tempat yang aman. Lakukan backup secara rutin, terutama sebelum kenaikan kelas atau pergantian tahun ajaran.</p>
            </div>
            <a href="{{ route('operator.backup.download') }}" class="btn-primary w-full sm:w-auto"><x-icon name="download" class="h-4 w-4" /> Unduh Backup</a>
            <p class="text-xs text-slate-500">File yang diunggah pengguna (materi, tugas, logo) tidak termasuk; salin folder <code>storage/app/public</code> secara terpisah.</p>
        </div>

        <form method="POST" action="{{ route('operator.backup.restore') }}" enctype="multipart/form-data"
              class="card card-body space-y-4 border-red-200" x-data="{ yakin: false }">
            @csrf
            <div>
                <h3 class="font-semibold text-red-700">Restore</h3>
                <p class="mt-1 text-sm text-slate-600">Mengembalikan database dari file backup. <strong>Seluruh data saat ini akan diganti</strong> dan Anda akan diminta login ulang.</p>
            </div>

            <div>
                <label class="label" for="file">File backup (.sql)</label>
                <input type="file" name="file" id="file" accept=".sql" required
                       class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-slate-200">
                @error('file') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <x-field.input name="konfirmasi_teks" label='Ketik "RESTORE" untuk konfirmasi' autocomplete="off" required />
            <x-field.input name="password" type="password" label="Password akun Anda" required autocomplete="current-password" />

            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" x-model="yakin" class="mt-0.5 rounded border-slate-300">
                <span>Saya yakin ingin mengganti seluruh data dengan isi file backup.</span>
            </label>

            <button type="submit" class="btn-danger" :disabled="!yakin">Restore Database</button>
        </form>
    </div>
</x-layouts.app>
