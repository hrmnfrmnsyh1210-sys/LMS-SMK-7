import Chart from 'chart.js/auto';

/*
 | Grafik bergaya "custom charts": batang gradasi biru→cyan, area berlapis bergradasi,
 | garis 2px, penanda bercincin putih, grid tipis, tooltip gelap.
 | Palet kategori (urutan tetap, lolos validasi CVD): biru, cyan, ungu, pink.
 */
export const WARNA = {
    biru: '#2f6fed',
    cyan: '#13b5cf',
    ungu: '#8b6cf0',
    pink: '#ec5f8e',
    teks: '#64748b',
    grid: '#eef2f7',
};

const URUTAN = [WARNA.biru, WARNA.cyan, WARNA.ungu, WARNA.pink];

Chart.defaults.font.family = 'Poppins, ui-sans-serif, system-ui, sans-serif';
Chart.defaults.font.size = 11;
Chart.defaults.color = WARNA.teks;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
Chart.defaults.plugins.legend.labels.boxWidth = 8;
Chart.defaults.plugins.legend.labels.boxHeight = 8;
Chart.defaults.plugins.legend.labels.padding = 16;
Object.assign(Chart.defaults.plugins.tooltip, {
    backgroundColor: '#0f172a',
    padding: 10,
    cornerRadius: 10,
    titleFont: { weight: '600' },
    boxPadding: 4,
    usePointStyle: true,
});

/** Gradasi vertikal mengikuti area grafik (dibuat ulang saat ukuran berubah). */
function gradasi(chart, atas, bawah) {
    const { ctx, chartArea } = chart;
    if (! chartArea) {
        return atas;
    }
    const g = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
    g.addColorStop(0, bawah);
    g.addColorStop(1, atas);

    return g;
}

function transparan(hex, alfa) {
    const n = parseInt(hex.slice(1), 16);

    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alfa})`;
}

const sumbu = (satuan, maks) => ({
    x: { grid: { display: false }, border: { display: false }, ticks: { maxRotation: 0, autoSkip: true } },
    y: {
        beginAtZero: true,
        max: maks ?? undefined,
        grace: maks ? 0 : '8%',
        border: { display: false },
        grid: { color: WARNA.grid },
        ticks: { maxTicksLimit: 5, callback: (v) => v + (satuan ?? '') },
    },
});

/**
 * Batang bergradasi. Bila ada `garis` (mis. KKM), batang di bawah garis diberi gradasi ungu–pink
 * dan garis ditampilkan sebagai garis acuan putus-putus (satu sumbu yang sama).
 *
 * @param {{label: string[], nilai: (number|null)[], nama?: string, satuan?: string, maks?: number, garis?: {label: string, nilai: number}}} d
 */
export function grafikBatang(d) {
    return {
        init() {
            const kanvas = document.createElement('canvas');
            kanvas.setAttribute('role', 'img');
            kanvas.setAttribute('aria-label', d.nama ?? 'Grafik batang');
            this.$el.appendChild(kanvas);

            const diBawah = (i) => d.garis && d.nilai[i] !== null && d.nilai[i] < d.garis.nilai;
            const dataset = [{
                type: 'bar',
                label: d.nama ?? 'Nilai',
                data: d.nilai,
                backgroundColor: (c) => diBawah(c.dataIndex)
                    ? gradasi(c.chart, WARNA.ungu, transparan(WARNA.ungu, 0.45))
                    : gradasi(c.chart, WARNA.biru, transparan(WARNA.cyan, 0.85)),
                hoverBackgroundColor: (c) => (diBawah(c.dataIndex) ? WARNA.ungu : WARNA.biru),
                borderRadius: { topLeft: 6, topRight: 6 },
                borderSkipped: 'bottom',
                maxBarThickness: 24,
                categoryPercentage: 0.7,
                barPercentage: 0.8,
                order: 2,
            }];

            if (d.garis) {
                dataset.push({
                    type: 'line',
                    label: d.garis.label,
                    data: d.label.map(() => d.garis.nilai),
                    borderColor: WARNA.pink,
                    borderWidth: 2,
                    borderDash: [6, 5],
                    pointRadius: 0,
                    pointHoverRadius: 0,
                    order: 1,
                });
            }

            new Chart(kanvas, {
                data: { labels: d.label, datasets: dataset },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: sumbu(d.satuan, d.maks),
                    plugins: {
                        legend: {
                            display: !! d.garis,
                            position: 'bottom',
                            align: 'end',
                            onClick: () => {},
                            // Warna legenda eksplisit: isi batang memakai gradasi sehingga tidak bisa dipakai untuk swatch.
                            labels: {
                                generateLabels: () => (! d.garis ? [] : [
                                    { text: d.nama ?? 'Nilai', fillStyle: WARNA.biru, strokeStyle: WARNA.biru, pointStyle: 'circle', lineWidth: 0 },
                                    ...(d.nilai.some((n, i) => diBawah(i))
                                        ? [{ text: `Di bawah ${d.garis.label}`, fillStyle: WARNA.ungu, strokeStyle: WARNA.ungu, pointStyle: 'circle', lineWidth: 0 }]
                                        : []),
                                    { text: `${d.garis.label} (${d.garis.nilai})`, fillStyle: WARNA.pink, strokeStyle: WARNA.pink, pointStyle: 'line', lineWidth: 2 },
                                ]),
                            },
                        },
                        tooltip: {
                            filter: (item) => item.dataset.type === 'bar',
                            callbacks: {
                                label: (c) => ` ${c.dataset.label}: ${c.parsed.y ?? '-'}${d.satuan ?? ''}`,
                                afterLabel: (c) => (diBawah(c.dataIndex) ? ` Di bawah ${d.garis.label} (${d.garis.nilai})` : ''),
                            },
                        },
                    },
                },
            });
        },
    };
}

/**
 * Area berlapis bergradasi dengan penanda bercincin (maks. 4 seri, warna berurutan tetap).
 *
 * @param {{label: string[], seri: {nama: string, nilai: (number|null)[]}[], satuan?: string, maks?: number}} d
 */
export function grafikArea(d) {
    return {
        init() {
            const kanvas = document.createElement('canvas');
            kanvas.setAttribute('role', 'img');
            kanvas.setAttribute('aria-label', d.seri.map((s) => s.nama).join(', '));
            this.$el.appendChild(kanvas);

            new Chart(kanvas, {
                type: 'line',
                data: {
                    labels: d.label,
                    datasets: d.seri.map((s, i) => {
                        const warna = URUTAN[i];

                        return {
                            label: s.nama,
                            data: s.nilai,
                            borderColor: warna,
                            borderWidth: 2,
                            borderCapStyle: 'round',
                            borderJoinStyle: 'round',
                            tension: 0.42,
                            cubicInterpolationMode: 'monotone',
                            fill: true,
                            backgroundColor: (c) => gradasi(c.chart, transparan(warna, 0.28), transparan(warna, 0.02)),
                            pointRadius: d.label.length > 14 ? 0 : 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: warna,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHitRadius: 14,
                            spanGaps: true,
                        };
                    }),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: sumbu(d.satuan, d.maks),
                    plugins: {
                        legend: { display: d.seri.length > 1, position: 'bottom', align: 'end' },
                        tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${c.parsed.y ?? '-'}${d.satuan ?? ''}` } },
                    },
                },
                plugins: [garisBidik],
            });
        },
    };
}

/** Garis bidik vertikal tipis saat kursor berada di atas grafik garis. */
const garisBidik = {
    id: 'garisBidik',
    afterDatasetsDraw(chart) {
        const aktif = chart.tooltip?.getActiveElements?.() ?? [];
        if (! aktif.length) {
            return;
        }
        const { ctx, chartArea } = chart;
        const x = aktif[0].element.x;
        ctx.save();
        ctx.strokeStyle = '#cbd5e1';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(x, chartArea.top);
        ctx.lineTo(x, chartArea.bottom);
        ctx.stroke();
        ctx.restore();
    },
};
