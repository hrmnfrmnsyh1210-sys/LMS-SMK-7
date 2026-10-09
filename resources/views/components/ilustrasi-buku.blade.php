{{-- Ilustrasi tumpukan buku + tanaman (SVG inline, tanpa file eksternal). --}}
<svg {{ $attributes->merge(['class' => 'w-64']) }} viewBox="0 0 320 200" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <ellipse cx="190" cy="186" rx="120" ry="10" fill="#42568d" opacity=".12" />

    {{-- Pot & tanaman --}}
    <path d="M58 150h40l-5 34H63z" fill="#f8fafc" />
    <path d="M58 150h40l-1 6H59z" fill="#e2e8f0" />
    <path d="M78 150c0-30 2-60 8-88" stroke="#5f9e7f" stroke-width="3" stroke-linecap="round" />
    <path d="M80 120c-18-6-26-20-24-36 14 4 24 18 24 36z" fill="#86cfa9" />
    <path d="M81 104c16-8 22-24 18-40-14 6-20 22-18 40z" fill="#6dba93" />
    <path d="M79 140c-16-2-26-12-28-26 14 0 26 10 28 26z" fill="#a6dfc0" />
    <path d="M82 132c14-6 20-18 18-30-12 4-18 16-18 30z" fill="#86cfa9" />
    <path d="M85 80c-10-10-10-24-4-34 10 8 12 22 4 34z" fill="#a6dfc0" />

    {{-- Buku bawah (oranye) --}}
    <rect x="118" y="150" width="170" height="34" rx="6" fill="#f2c27b" />
    <rect x="124" y="156" width="160" height="22" rx="4" fill="#fffbeb" />
    <rect x="118" y="150" width="14" height="34" rx="4" fill="#e0a95f" />

    {{-- Buku tengah (biru tua) --}}
    <rect x="130" y="118" width="160" height="32" rx="6" fill="#7290d6" />
    <rect x="136" y="124" width="150" height="20" rx="4" fill="#eff6ff" />
    <rect x="130" y="118" width="14" height="32" rx="4" fill="#5b78c6" />

    {{-- Buku atas (biru muda, miring) --}}
    <g transform="rotate(-6 210 100)">
        <rect x="140" y="86" width="150" height="30" rx="6" fill="#a3b9ea" />
        <rect x="146" y="92" width="140" height="18" rx="4" fill="#f8fafc" />
        <rect x="140" y="86" width="14" height="30" rx="4" fill="#8ea8e0" />
    </g>

    {{-- Burung kecil --}}
    <path d="M270 34l6 4 6-4M290 50l5 3 5-3" stroke="#42568d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
</svg>
