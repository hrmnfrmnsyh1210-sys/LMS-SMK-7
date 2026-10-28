<x-layouts.app title="Pengaturan Sekolah">
    <x-page-header title="Pengaturan Sekolah" subtitle="Profil sekolah ditampilkan di seluruh halaman dan laporan." />

    <form method="POST" action="{{ route('operator.pengaturan.update') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @method('PUT')

        <div class="card card-body space-y-4 lg:col-span-2">
            <h3 class="font-semibold">Profil sekolah</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field.input name="nama_sekolah" label="Nama sekolah" :value="$pengaturan['nama_sekolah']" required />
                <x-field.input name="npsn" label="NPSN" :value="$pengaturan['npsn']" />
                <x-field.input name="telepon_sekolah" label="Telepon" :value="$pengaturan['telepon_sekolah']" />
                <x-field.input name="email_sekolah" type="email" label="Email" :value="$pengaturan['email_sekolah']" />
            </div>
            <x-field.textarea name="alamat_sekolah" label="Alamat" :value="$pengaturan['alamat_sekolah']" />

            <h3 class="border-t border-slate-100 pt-4 font-semibold">Kebijakan</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field.input name="min_panjang_password" type="number" min="6" max="32" label="Panjang minimal password"
                               :value="$pengaturan['min_panjang_password']" required />
                <x-field.input name="kkm" type="number" min="0" max="100" label="KKM (Kriteria Ketuntasan Minimal)"
                               :value="$pengaturan['kkm']" required help="Dipakai untuk menandai siswa dengan nilai rendah." />
                <x-field.input name="toleransi_terlambat_guru" type="number" min="0" max="60" label="Toleransi terlambat guru (menit)"
                               :value="$pengaturan['toleransi_terlambat_guru']" required help="Guru yang absen masuk kelas melewati jam mulai + toleransi ditandai terlambat." />
            </div>
        </div>

        <div class="card card-body space-y-4" x-data="{ pratinjau: @js($pengaturan['logo'] ? file_url($pengaturan['logo']) : null) }">
            <h3 class="font-semibold">Logo sekolah</h3>
            <div class="flex h-36 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50">
                <template x-if="pratinjau"><img :src="pratinjau" alt="Logo" class="h-28 object-contain"></template>
                <template x-if="!pratinjau"><span class="text-sm text-slate-400">Belum ada logo</span></template>
            </div>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp"
                   @change="pratinjau = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : pratinjau"
                   class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-slate-200">
            <p class="text-xs text-slate-500">PNG/JPG/WEBP, maksimal 1 MB.</p>
            @error('logo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end lg:col-span-3">
            <button type="submit" class="btn-primary">Simpan Pengaturan</button>
        </div>
    </form>
</x-layouts.app>
