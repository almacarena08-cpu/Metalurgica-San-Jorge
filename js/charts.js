export const chartsPanel = {
    bindModuleIndexNavigation() {
        const content = this.root.querySelector("#content");
        const buttons = [...content.querySelectorAll("[data-module-index]")];
        const panels = [...content.querySelectorAll("[data-module-panel]")];
        buttons.forEach(button => {
            button.addEventListener("click", () => {
                const selected = button.dataset.moduleIndex;
                const activePanel = panels.find(panel => panel.dataset.modulePanel === selected);
                if (!activePanel) {
                    console.error(`No existe una sección para el índice "${selected}".`);
                    return;
                }
                buttons.forEach(item => {
                    const active = item === button;
                    item.classList.toggle("active", active);
                    item.setAttribute("aria-pressed", String(active));
                });
                panels.forEach(panel => {
                    panel.hidden = panel === activePanel ? false : true;
                });
                const main = content.closest(".main");
                if (main) main.scrollTo({top: 0, behavior: "smooth"});
            });
        });
    },

    formatChartCurrency(amount) {
        return new Intl.NumberFormat("es-AR", {
            style: "currency",
            currency: "ARS",
            maximumFractionDigits: amount >= 1000000 ? 1 : 0,
            notation: amount >= 1000000 ? "compact" : "standard"
        }).format(amount);
    },

    chartEmpty(message) {
        return `<p class="module-chart-empty">${this.escape(message)}</p>`;
    },

    moduleMonthlyChart(rows, dateField, valueField, title, currency = false) {
        const timeZone = "America/Argentina/Buenos_Aires";
        const monthParts = date => Object.fromEntries(new Intl.DateTimeFormat("en", {
            timeZone, year: "numeric", month: "2-digit"
        }).formatToParts(date).filter(part => part.type !== "literal").map(part => [part.type, part.value]));
        const current = monthParts(new Date());
        const months = [];
        for (let offset = 5; offset >= 0; offset -= 1) {
            const date = new Date(Date.UTC(Number(current.year), Number(current.month) - 1 - offset, 1, 12));
            const parts = monthParts(date);
            months.push({
                key: `${parts.year}-${parts.month}`,
                label: date.toLocaleDateString("es-AR", {month: "short", timeZone})
            });
        }
        const totals = new Map(months.map(month => [month.key, 0]));
        rows.forEach(row => {
            const key = String(row[dateField] || "").slice(0, 7);
            if (!totals.has(key)) return;
            const amount = Number(valueField ? row[valueField] : 1);
            if (Number.isFinite(amount)) totals.set(key, totals.get(key) + amount);
        });
        const values = months.map(month => totals.get(month.key));
        const max = Math.max(...values, 0);
        const rawStep = max > 0 ? max / 4 : 1;
        const magnitude = 10 ** Math.floor(Math.log10(rawStep));
        const step = Math.ceil(rawStep / magnitude) * magnitude;
        const axisMax = step * 4;
        const plot = {left: 64, top: 18, width: 620, height: 184};
        const points = values.map((value, index) => ({
            x: plot.left + index * (plot.width / (values.length - 1)),
            y: plot.top + plot.height - (value / axisMax) * plot.height,
            value
        }));
        const line = points.map(point => `${point.x},${point.y}`).join(" ");
        const area = `${plot.left},${plot.top + plot.height} ${line} ${plot.left + plot.width},${plot.top + plot.height}`;
        const grid = Array.from({length: 5}, (_, index) => {
            const value = axisMax * (4 - index) / 4;
            const y = plot.top + index * plot.height / 4;
            const label = currency ? this.formatChartCurrency(value) : String(value);
            return `<g><line x1="${plot.left}" y1="${y}" x2="${plot.left + plot.width}" y2="${y}" class="module-chart-grid-line"/><text x="${plot.left - 10}" y="${y + 4}" text-anchor="end" class="module-chart-axis-label">${this.escape(label)}</text></g>`;
        }).join("");
        const xLabels = points.map((point, index) =>
            `<text x="${point.x}" y="${plot.top + plot.height + 25}" text-anchor="middle" class="module-chart-axis-label">${this.escape(months[index].label)}</text>`
        ).join("");
        const markers = points.map((point, index) =>
            `<circle cx="${point.x}" cy="${point.y}" r="5" class="module-chart-line-marker"><title>${this.escape(months[index].label)}: ${this.escape(currency ? this.formatChartCurrency(point.value) : point.value)}</title></circle>`
        ).join("");
        const total = values.reduce((sum, value) => sum + value, 0);
        const summary = currency ? this.formatChartCurrency(total) : String(total);
        const emptyMessage = currency
            ? "No hay compras en los últimos seis meses."
            : "No hay registros en los últimos seis meses.";
        return `
            <div class="module-chart-summary"><strong>${this.escape(summary)}</strong><span>en los últimos 6 meses</span></div>
            <div class="module-chart-trend">
                <svg viewBox="0 0 720 250" role="img" aria-label="${this.escape(title)}">
                    <defs><linearGradient id="moduleChartFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#eb713b" stop-opacity=".28"/><stop offset="100%" stop-color="#eb713b" stop-opacity="0"/></linearGradient></defs>
                    ${grid}<polygon points="${area}" class="module-chart-area"/><polyline points="${line}" class="module-chart-line"/>${markers}${xLabels}
                </svg>
            </div>
            ${values.some(value => value > 0) ? "" : this.chartEmpty(emptyMessage)}
        `;
    },

    moduleStatusDonutChart(rows, field, title, colors = {}) {
        const counts = new Map();
        rows.forEach(row => {
            const status = String(row[field] || "Sin estado");
            counts.set(status, (counts.get(status) || 0) + 1);
        });
        if (!counts.size) return this.chartEmpty(`Todavía no hay ${title.toLocaleLowerCase("es")} para analizar.`);
        const palette = ["#e8753e", "#71b98a", "#7d9fe5", "#c39ae8", "#dfbb66"];
        const total = rows.length;
        const radius = 58;
        const circumference = 2 * Math.PI * radius;
        let offset = 0;
        const slices = [...counts.entries()].map(([status, count], index) => {
            const length = circumference * count / total;
            const color = colors[status] || palette[index % palette.length];
            const circle = `<circle cx="80" cy="80" r="${radius}" fill="none" stroke="${color}" stroke-width="17" stroke-dasharray="${length} ${circumference - length}" stroke-dashoffset="${-offset}" transform="rotate(-90 80 80)"><title>${this.escape(status)}: ${count}</title></circle>`;
            offset += length;
            return {status, count, color, circle};
        });
        const legend = slices.map(slice =>
            `<li><span class="module-chart-legend-dot" style="--legend-color:${slice.color}"></span><span>${this.escape(slice.status)}</span><strong>${slice.count}</strong></li>`
        ).join("");
        return `
            <div class="module-chart-donut-layout">
                <div class="module-chart-donut">
                    <svg viewBox="0 0 160 160" role="img" aria-label="${this.escape(title)}">
                        <circle cx="80" cy="80" r="${radius}" fill="none" stroke="#292f32" stroke-width="17"/>
                        ${slices.map(slice => slice.circle).join("")}
                        <text x="80" y="76" text-anchor="middle" class="module-chart-donut-total">${total}</text>
                        <text x="80" y="95" text-anchor="middle" class="module-chart-donut-caption">registros</text>
                    </svg>
                </div>
                <ul class="module-chart-legend">${legend}</ul>
            </div>
        `;
    },

    moduleStatusBarChart(rows, field, title) {
        const counts = new Map();
        rows.forEach(row => {
            const status = String(row[field] || "Sin estado");
            counts.set(status, (counts.get(status) || 0) + 1);
        });
        if (!counts.size) return this.chartEmpty(`Todavía no hay ${title.toLocaleLowerCase("es")} para analizar.`);
        const maximum = Math.max(...counts.values());
        const colors = ["#e8753e", "#71b98a", "#7d9fe5", "#c39ae8"];
        const items = [...counts.entries()].sort((left, right) => right[1] - left[1]).map(([status, count], index) => `
            <li>
                <div class="module-chart-bar-label"><span>${this.escape(status)}</span><strong>${count}</strong></div>
                <div class="module-chart-bar-track"><span style="--bar-width:${Math.max(count / maximum * 100, 4)}%;--bar-color:${colors[index % colors.length]}"></span></div>
            </li>
        `).join("");
        return `<ul class="module-chart-bars" aria-label="${this.escape(title)}">${items}</ul>`;
    },

    moduleValueBarChart(rows, labelField, valueField, title, options = {}) {
        const items = rows.map(row => ({
            label: String(row[labelField] || "Sin nombre"),
            value: Number(row[valueField]),
            reference: options.referenceField ? Number(row[options.referenceField]) : null
        })).filter(item => Number.isFinite(item.value)).slice(0, 8);
        if (!items.length) return this.chartEmpty(`Todavía no hay ${title.toLocaleLowerCase("es")} para analizar.`);
        const maximum = Math.max(...items.flatMap(item => [item.value, item.reference || 0]), 0) || 1;
        const bars = items.map(item => {
            const width = Math.max(item.value / maximum * 100, item.value > 0 ? 2 : 0);
            const reference = item.reference === null ? "" : `<i class="module-chart-reference" style="--reference-position:${item.reference / maximum * 100}%" title="Mínimo: ${this.escape(item.reference)}"></i>`;
            return `
                <li>
                    <div class="module-chart-bar-label"><span>${this.escape(item.label)}</span><strong>${this.escape(item.value)}${this.escape(options.suffix || "")}</strong></div>
                    <div class="module-chart-bar-track"><span style="--bar-width:${width}%;--bar-color:${options.color || "#e8753e"}"></span>${reference}</div>
                </li>
            `;
        }).join("");
        return `<ul class="module-chart-bars" aria-label="${this.escape(title)}">${bars}</ul>`;
    },

    moduleProductionProgressChart(rows) {
        const latestByOrder = new Map();
        rows.forEach(row => {
            const key = String(row.id_orden);
            if (!latestByOrder.has(key)) latestByOrder.set(key, row);
        });
        const latest = [...latestByOrder.values()].slice(0, 8).reverse();
        return this.moduleValueBarChart(latest, "id_orden", "avance", "Avance de producción por orden", {
            suffix: "%", color: "#71b98a"
        });
    }
};
