import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

/**
 * Alpine.js Chart.js wrapper component
 * Usage: x-data="chartComponent({ type: 'line', data: {...}, options: {...} })"
 */
window.chartComponent = function (config = {}) {
    return {
        chart: null,
        type: config.type || 'line',
        chartData: config.data || { labels: [], datasets: [] },
        chartOptions: config.options || {},

        init() {
            this.$nextTick(() => {
                this.createChart();
            });

            // Listen for Livewire updates
            if (config.wireModel) {
                Livewire.on('chartDataUpdated', (data) => {
                    this.updateChart(data);
                });
            }
        },

        createChart() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Default options based on type
            const defaults = this.getDefaultOptions();

            this.chart = new Chart(ctx, {
                type: this.type,
                data: this.chartData,
                options: {
                    ...defaults,
                    ...this.chartOptions,
                },
            });
        },

        updateChart(newData) {
            if (!this.chart) return;

            if (newData.labels) {
                this.chart.data.labels = newData.labels;
            }
            if (newData.datasets) {
                this.chart.data.datasets = newData.datasets;
            }
            this.chart.update('active');
        },

        getDefaultOptions() {
            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(148, 163, 184, 0.08)' : 'rgba(148, 163, 184, 0.15)';
            const textColor = isDark ? '#94a3b8' : '#64748b';

            const base = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#fff',
                        titleColor: isDark ? '#f1f5f9' : '#0f172a',
                        bodyColor: isDark ? '#cbd5e1' : '#475569',
                        borderColor: isDark ? 'rgba(148, 163, 184, 0.2)' : 'rgba(148, 163, 184, 0.3)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 12,
                        titleFont: { size: 13, weight: '600' },
                        bodyFont: { size: 12 },
                        displayColors: true,
                        boxPadding: 4,
                    },
                },
            };

            if (this.type === 'line' || this.type === 'bar') {
                base.scales = {
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor, font: { size: 11 } },
                        border: { display: false },
                    },
                    y: {
                        grid: { color: gridColor },
                        ticks: { color: textColor, font: { size: 11 } },
                        border: { display: false },
                        beginAtZero: true,
                    },
                };
            }

            if (this.type === 'doughnut' || this.type === 'pie') {
                base.cutout = this.type === 'doughnut' ? '70%' : 0;
                base.plugins.legend = {
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: textColor,
                        padding: 16,
                        usePointStyle: true,
                        pointStyleWidth: 8,
                        font: { size: 12 },
                    },
                };
            }

            return base;
        },

        destroy() {
            if (this.chart) {
                this.chart.destroy();
                this.chart = null;
            }
        },
    };
};
