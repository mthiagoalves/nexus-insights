<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps<{
    series: Array<{ date: string; spend: number; revenue: number }>;
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
let chart: Chart | null = null;

const buildChart = () => {
    if (!canvasRef.value) return;

    const labels = props.series.map(s => s.date);
    const spendData = props.series.map(s => s.spend);
    const revenueData = props.series.map(s => s.revenue);

    const ctx = canvasRef.value.getContext('2d');
    if (!ctx) return;

    if (chart) {
        chart.destroy();
    }

    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Gostos',
                    data: spendData,
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y',
                },
                {
                    label: 'Receita',
                    data: revenueData,
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y',
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false },
            },
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: {
                    ticks: {
                        autoSkip: true,
                        maxTicksLimit: 10,
                    },
                },
                y: {
                    position: 'left',
                    beginAtZero: true,
                },
            },
        },
    });
};

onMounted(() => {
    buildChart();
});

watch(() => props.series, () => {
    buildChart();
}, { deep: true });
</script>

<template>
    <div>
        <canvas ref="canvasRef"></canvas>
    </div>
</template>
