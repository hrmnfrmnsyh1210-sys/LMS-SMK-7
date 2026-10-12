import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import { grafikArea, grafikBatang } from './grafik';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.data('grafikBatang', grafikBatang);
Alpine.data('grafikArea', grafikArea);

Alpine.start();
