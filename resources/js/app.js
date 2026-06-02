import './bootstrap';

/**
 * Alpine-компонент для математичного звіту аналогів.
 * Визначений тут (а не у Blade-скрипті) щоб гарантувати виконання
 * незалежно від Livewire DOM-оновлень.
 *
 * @param {string[]} labels
 * @param {number[]} smart
 * @param {number[]} exact
 * @param {number[]} fuzzy
 * @param {number[]} symptoms
 * @param {string[]} colors
 */
window.farmaReport = function(labels, smart, exact, fuzzy, symptoms, colors) {
    return {
        show: false,
        _charts: [],
        labels, smart, exact, fuzzy, symptoms, colors,

        toggle() {
            this.show = !this.show;
            if (this.show) {
                this.$nextTick(() => this._render());
            } else {
                this._destroy();
            }
        },

        _render() {
            this._destroy();

            const font      = { family: 'Figtree, sans-serif', size: 11 };
            const gridColor = 'rgba(156,163,175,0.2)';

            // Діаграма 1 — Інтелектуальний бал (горизонтальні стовпці)
            const c1 = document.getElementById('chart-smart');
            if (c1 && window.Chart) {
                this._charts.push(new Chart(c1, {
                    type: 'bar',
                    data: {
                        labels: this.labels,
                        datasets: [{
                            label: 'Інтелектуальний бал (%)',
                            data: this.smart,
                            backgroundColor: this.colors,
                            borderRadius: 6,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.x + '%' } }
                        },
                        scales: {
                            x: { min: 0, max: 100, ticks: { callback: v => v + '%', font }, grid: { color: gridColor } },
                            y: { ticks: { font } }
                        }
                    }
                }));
            }

            // Діаграма 2 — Три метрики збігу (групований)
            const c2 = document.getElementById('chart-metrics');
            if (c2 && window.Chart) {
                this._charts.push(new Chart(c2, {
                    type: 'bar',
                    data: {
                        labels: this.labels,
                        datasets: [
                            { label: '🧪 Точні речовини', data: this.exact,    backgroundColor: 'rgba(99,102,241,0.75)', borderRadius: 4 },
                            { label: '🔬 Схожі речовини', data: this.fuzzy,    backgroundColor: 'rgba(139,92,246,0.75)', borderRadius: 4 },
                            { label: '🩺 Симптоми',        data: this.symptoms, backgroundColor: 'rgba(59,130,246,0.75)',  borderRadius: 4 },
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom', labels: { font, boxWidth: 12 } },
                            tooltip: { callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + '%' } }
                        },
                        scales: {
                            y: { min: 0, max: 100, ticks: { callback: v => v + '%', font }, grid: { color: gridColor } },
                            x: { ticks: { font } }
                        }
                    }
                }));
            }
        },

        _destroy() {
            this._charts.forEach(c => c.destroy());
            this._charts = [];
        }
    };
};
