<x-layouts.app title="Dashboard">
    <x-banner-sapaan :nama="$user->nama" pesan="Tetap semangat mengikuti perjalanan akademikmu." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="card card-body lg:col-span-2">
            <h3 class="card-title">Informasi</h3>
            <p class="mt-2 text-sm text-slate-600">Anda masuk sebagai <strong>{{ $user->label_role }}</strong>. Fitur untuk role ini sedang dikembangkan dan akan segera tersedia.</p>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">Kalender Akademik</h3></div>
            <x-kalender class="px-4 pb-5 sm:px-6" />
        </div>
    </div>
</x-layouts.app>
