<x-layouts.app title="Pergantian Tahun Ajaran">
    <x-page-header title="Pergantian Tahun Ajaran / Semester"
                   subtitle="Mengganti tahun ajaran aktif. Seluruh data operasional (kelas, pengampu, jadwal) akan mengikuti tahun ajaran baru." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-body space-y-2 text-sm">
            <h3 class="font-semibold">Panduan</h3>
            <ul class="list-inside list-disc space-y-1 text-slate-600">
                <li><strong>Ganjil → Genap (tahun yang sama):</strong> pilih semester genap dan centang <em>salin data</em>. Kelas, anggota kelas, pengampu & jadwal disalin apa adanya.</li>
                <li><strong>Tahun ajaran baru:</strong> gunakan menu <a href="{{ route('operator.kenaikan-kelas.index') }}" class="link">Kenaikan Kelas</a> terlebih dahulu, lalu aktifkan tahun ajaran baru di sini tanpa menyalin data.</li>
                <li>Buat tahun ajaran tujuan lebih dulu di menu <a href="{{ route('operator.tahun-ajaran.index') }}" class="link">Tahun Ajaran</a>.</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('operator.pergantian-tahun-ajaran.store') }}" class="card card-body space-y-4 lg:col-span-2">
            @csrf
            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm">
                Tahun ajaran aktif saat ini: <strong>{{ $aktif?->label ?? 'Belum ada' }}</strong>
            </div>

            <x-field.select name="tujuan_id" label="Tahun ajaran tujuan" placeholder="— Pilih —" required
                            :options="$daftarTahun->reject(fn ($t) => $t->status_aktif)->mapWithKeys(fn ($t) => [$t->id => $t->label.' ('.$t->kelas_count.' kelas)'])->all()" />

            <label class="flex items-start gap-2 text-sm">
                <input type="hidden" name="salin_data" value="0">
                <input type="checkbox" name="salin_data" value="1" class="mt-0.5 rounded border-slate-300" @checked(old('salin_data'))>
                <span>Salin kelas, anggota kelas, pengampu & jadwal dari <strong>{{ $aktif?->label ?? '-' }}</strong> (data yang sudah ada di tujuan tidak ditimpa).</span>
            </label>

            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="konfirmasi" value="1" class="mt-0.5 rounded border-slate-300">
                <span>Saya memahami bahwa seluruh pengguna akan bekerja pada tahun ajaran tujuan setelah ini.</span>
            </label>
            @error('konfirmasi') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

            <div class="flex justify-end border-t border-slate-100 pt-4">
                <button type="submit" class="btn-primary"><x-icon name="refresh" class="h-4 w-4" /> Ganti Tahun Ajaran</button>
            </div>
        </form>
    </div>
</x-layouts.app>
