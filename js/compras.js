export const comprasPanel = {
    async comprasView() {
        const [proveedores, materiales, compras] = await Promise.all([this.api.get("proveedores_list"), this.api.get("materiales_list"), this.api.get("compras_list")]);
        this.content(await this.moduleTemplate("compras", "compras", {
            suppliers: proveedores.map(p => `<option value="${this.escape(p.id_proveedor)}">${this.escape(p.razon_social)}</option>`).join(""),
            materials: materiales.map(m => `<option value="${this.escape(m.id_material)}">${this.escape(m.nombre)}</option>`).join(""),
            materialsTable: this.inventoryTable(materiales),
            purchasesTable: this.simpleTable(compras, ["id_compra", "proveedor", "detalle", "total", "estado"])
        }));
        this.refreshStockWarnings(materiales);
        this.bindPostForm("#proveedorForm", "proveedores_create", () => this.comprasView());
        this.bindPostForm("#compraForm", "compras_create", () => this.comprasView());
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
        return `<div class="table-wrap"><table class="inventory-table">
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
        </table></div>`;
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
