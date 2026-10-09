@php
    $edit = $ortu->exists;
    $terpilih = collect(old('siswa_ids', $ortu->anak?->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->values();
@endphp

<x-layouts.app :title="$edit ? 'Ubah Orang Tua' : 'Tambah Orang Tua'">
    <x-page-header :title="$edit ? 'Ubah Akun Orang Tua' : 'Tambah Akun Orang Tua'"
                   :subtitle="$edit ? null : 'Password awal = username. Wajib diganti saat login pertama.'" />

    <form method="POST" action="{{ $edit ? route('operator.orang-tua.update', $ortu) : route('operator.orang-tua.store') }}" class="grid gap-6 lg:grid-cols-5">
        @csrf
        @if ($edit) @method('PUT') @endif

        <div class="card card-body space-y-4 lg:col-span-3">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field.input name="nama" label="Nama lengkap" :value="$ortu->nama" required />
                <x-field.select name="hubungan" label="Hubungan dengan siswa" :options="\App\Models\OrangTua::HUBUNGAN" :value="$ortu->hubungan" required />
                <x-field.input name="username" label="Username" :value="$ortu->user?->username" help="Huruf, angka, titik, strip, atau garis bawah." required />
                <x-field.input name="email" type="email" label="Email" :value="$ortu->user?->email" />
                <x-field.input name="no_hp" label="Nomor HP / WhatsApp" :value="$ortu->no_hp" />
                <x-field.select name="status" label="Status akun" :options="['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif']"
                                :value="$ortu->user?->status ?? 'aktif'" required />
            </div>
            <x-field.textarea name="alamat" label="Alamat" :value="$ortu->alamat" />
        </div>

        {{-- Pilih anak --}}
        <div class="card card-body space-y-3 lg:col-span-2"
             x-data="{ q: '', siswa: @js($daftarSiswa), pilih: @js($terpilih),
                       get hasil() { const q = this.q.toLowerCase(); return this.siswa.filter(s => !this.pilih.includes(s.id) && (s.nama.toLowerCase().includes(q) || s.nis.includes(q))).slice(0, 8) },
                       data(id) { return this.siswa.find(s => s.id === id) } }">
            <div class="label">Anak yang dipantau <span class="text-red-500">*</span></div>

            <div class="flex flex-wrap gap-2">
                <template x-for="id in pilih" :key="id">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 py-1 pr-1.5 pl-3 text-xs font-medium text-blue-700">
                        <span x-text="(data(id)?.nama ?? 'Siswa #' + id) + ' · ' + (data(id)?.kelas ?? '-')"></span>
                        <button type="button" class="rounded-full p-0.5 hover:bg-blue-100" @click="pilih = pilih.filter(p => p !== id)" aria-label="Hapus"><x-icon name="x" class="h-3.5 w-3.5" /></button>
                        <input type="hidden" name="siswa_ids[]" :value="id">
                    </span>
                </template>
                <span x-show="pilih.length === 0" class="text-xs text-slate-400">Belum ada anak dipilih.</span>
            </div>

            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute top-2.5 left-3 h-4 w-4 text-slate-400" />
                <input type="search" x-model="q" placeholder="Cari nama atau NIS siswa…" class="input pl-9">
            </div>
            <ul class="divide-y divide-slate-100 rounded-lg border border-slate-100" x-show="q.length > 0" x-cloak>
                <template x-for="s in hasil" :key="s.id">
                    <li>
                        <button type="button" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm hover:bg-slate-50" @click="pilih.push(s.id); q = ''">
                            <span><span class="font-medium text-slate-800" x-text="s.nama"></span> <span class="text-xs text-slate-500" x-text="'NIS ' + s.nis"></span></span>
                            <span class="text-xs text-slate-400" x-text="s.kelas ?? '-'"></span>
                        </button>
                    </li>
                </template>
                <li x-show="hasil.length === 0" class="px-3 py-3 text-center text-xs text-slate-400">Tidak ditemukan.</li>
            </ul>
            @error('siswa_ids')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
            <p class="text-xs text-slate-500">Satu akun dapat memantau beberapa anak (kakak-adik).</p>
        </div>

        <div class="lg:col-span-5">
            <x-form-actions :batal="route('operator.orang-tua.index')" />
        </div>
    </form>
</x-layouts.app>
