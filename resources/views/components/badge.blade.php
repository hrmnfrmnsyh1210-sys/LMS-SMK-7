@props(['warna' => null, 'status' => null])

@php
    // Warna otomatis untuk status umum.
    $peta = [
        'aktif' => 'emerald', 'nonaktif' => 'slate', 'lulus' => 'sky',
        'hadir' => 'emerald', 'izin' => 'sky', 'sakit' => 'amber', 'alpa' => 'rose',
        'menunggu' => 'amber', 'disetujui' => 'emerald', 'ditolak' => 'rose',
        'ganjil' => 'violet', 'genap' => 'sky',
    ];
    $warna ??= $peta[$status] ?? 'slate';

    $kelas = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
    ][$warna] ?? 'bg-slate-100 text-slate-700 ring-slate-200';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {$kelas}"]) }}>
    {{ $slot->isEmpty() ? ucfirst((string) $status) : $slot }}
</span>
