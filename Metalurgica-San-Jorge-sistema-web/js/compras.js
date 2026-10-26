export const comprasPanel = {
    async comprasView() {
        const [proveedores, materiales, compras, ordenes] = await Promise.all([
            this.api.get("proveedores_list"),
            this.api.get("materiales_list"),
            this.api.get("compras_list"),
            this.api.get("ordenes_list")
        ]);
        const activePurchases = compras.filter(purchase => String(purchase.estado || "").toLocaleLowerCase() !== "cancelada");
        const purchaseTotal = activePurchases.reduce((total, purchase) => {
            const amount = Number(purchase.total);
            return total + (Number.isFinite(amount) ? amount : 0);
        }, 0);
        this.content(await this.moduleTemplate("compras", "compras", {
            employeeName: this.escape(this.session.user.nombre || this.session.user.razon_social || "equipo"),
            suppliers: proveedores.map(p => `<option value="${this.escape(p.id_proveedor)}">${this.escape(p.razon_social)}</option>`).join(""),
            materials: materiales.map(m => `<option value="${this.escape(m.id_material)}">${this.escape(m.nombre)}</option>`).join(""),
            orders: ordenes.filter(o => !["Finalizada", "Entregada"].includes(o.estado)).map(o => `<option value="${this.escape(o.id_pedido)}">#${this.escape(o.id_pedido)} · ${this.escape(o.razon_social)} · ${this.escape(o.descripcion)}</option>`).join(""),
            today: new Date().toLocaleDateString(this.dateLocale(), {timeZone: "America/Argentina/Buenos_Aires", weekday: "long", day: "numeric", month: "long", year: "numeric"}),
            supplierCount: proveedores.length,
            purchaseCount: compras.length,
            purchaseTotal: this.formatChartCurrency(purchaseTotal),
            lowStockCount: materiales.filter(material => {
                const stock = Number(material.stock);
                const minimum = Number(material.stock_minimo);
                return Number.isFinite(stock) && Number.isFinite(minimum) && minimum > 0 && stock <= minimum * 1.1;
            }).length,
            purchaseTrendChart: this.moduleMonthlyChart(activePurchases, "fecha", "total", "Inversión mensual en compras", true),
            purchaseStatusChart: this.moduleStatusDonutChart(compras, "estado", "Órdenes de compra", {
                "Solicitada": "#e8753e", "Recibida": "#71b98a", "Cancelada": "#d15e5e"
            }),
            suppliersTable: this.canManageAllDepartmentRecords()
                ? this.supervisorRecordsTable(proveedores, ["id_proveedor", "razon_social", "cuit", "telefono", "email"], "proveedor", "id_proveedor", "proveedores")
                : this.simpleTable(proveedores, ["id_proveedor", "razon_social", "cuit", "telefono", "email"]),
            materialsTable: this.inventoryTable(materiales),
            purchasesTable: this.canManageAllDepartmentRecords()
                ? this.supervisorRecordsTable(compras, ["id_compra", "fecha", "proveedor", "detalle", "total", "estado"], "compra", "id_compra", "compras")
                : this.simpleTable(compras, ["id_compra", "fecha", "proveedor", "detalle", "total", "estado"])
        }));
        this.bindModuleIndexNavigation();
        this.refreshStockWarnings(materiales);
        this.bindPostForm("#proveedorForm", "proveedores_create", () => this.comprasView());
        this.bindPostForm("#compraForm", "compras_create", () => this.comprasView());
        this.bindPostForm("#materialForm", "materiales_create", () => this.comprasView());
        if (this.canManageAllDepartmentRecords()) this.bindSupervisorRecordActions(() => this.comprasView());
    },

    inventoryTable(materiales) {
        if (!materiales.length) return '<p class="muted">No hay materiales para mostrar.</p>';
        const columns = [
            ["id_material", "ID material"],
            ["nombre", "Material"],
            ["stock", "Stock actual"],
            ["stock_minimo", "Mínimo"],
            ["stock_maximo", "Máximo"]
        ];
        const table = `<div class="table-wrap"><table class="inventory-table">
            <thead><tr>${columns.map(([, label]) => `<th>${label}</th>`).join("")}</tr></thead>
            <tbody>${materiales.map(material => {
                const stock = Number(material.stock);
                const minimum = Number(material.stock_minimo);
                const isNearMinimum = Number.isFinite(stock) && Number.isFinite(minimum)
                    && minimum > 0 && stock <= minimum * 1.1;
                return `<tr class="${isNearMinimum ? "stock-warning-row" : ""}">
                    <td>${this.escape(material.id_material)}</td>
                    <td>${this.escape(material.nombre)}${isNearMinimum ? '<span class="stock-warning-tag">Stock bajo</span>' : ""}</td>
                    <td>${this.escape(material.stock)}</td>
                    <td>${this.escape(material.stock_minimo)}</td>
                    <td>${this.escape(material.stock_maximo)}</td>
                </tr>`;
            }).join("")}</tbody>
        </table><p class="table-no-results" data-table-empty hidden>No se encontraron materiales con ese criterio.</p></div>`;
        if (this.canManageAllDepartmentRecords()) {
            return this.supervisorRecordsTable(materiales, ["id_material", "nombre", "tipo", "unidad", "stock", "stock_minimo", "stock_maximo"], "material", "id_material", "materiales");
        }
        return this.searchableTable(table, materiales.length, "materiales");
    },

    async refreshStockWarnings(materials = null) {
        const panel = this.root.querySelector("[data-stock-alerts]");
        if (!panel) return;
        try {
            const inventory = materials || await this.api.get("materiales_list");
            if (!Array.isArray(inventory)) throw new Error("La respuesta de inventario no es válida.");
            const lowStock = inventory.filter(material => {
                const stock = Number(material.stock);
                const minimum = Number(material.stock_minimo);
                return Number.isFinite(stock) && Number.isFinite(minimum)
                    && minimum > 0 && stock <= minimum * 1.1;
            });
            const button = panel.querySelector("[data-stock-warning]");
            const details = panel.querySelector("[data-stock-warning-details]");
            button.setAttribute("aria-label", `${lowStock.length} materiales cerca o por debajo del stock mínimo. Abrir Compras.`);
            details.innerHTML = lowStock.length
                ? `<span class="stock-alert-count">${lowStock.length}</span><span class="stock-alert-copy"><strong>Stock bajo</strong><small>${lowStock.slice(0, 2).map(item => this.escape(item.nombre)).join(", ")}${lowStock.length > 2 ? ` y ${lowStock.length - 2} más` : ""}</small></span><span class="stock-alert-chevron" aria-hidden="true">›</span>`
                : '<span class="stock-alert-clear"><span aria-hidden="true">✓</span> Inventario sin alertas</span>';
            panel.hidden = false;
            panel.classList.remove("stock-alert-error");
            panel.classList.toggle("has-stock-alerts", lowStock.length > 0);
        } catch (error) {
            console.error("No se pudieron actualizar las alertas de inventario:", error);
            panel.hidden = false;
            panel.classList.add("stock-alert-error");
            panel.querySelector("[data-stock-warning-details]").textContent = "No se pudieron cargar las alertas de stock.";
        }
    }

};
