export const depositoPanel = {
    async depositoView() {
        const [materiales, movimientos, ordenes] = await Promise.all([
            this.api.get("materiales_list"),
            this.api.get("deposito_movimientos_list"),
            this.api.get("ordenes_list")
        ]);
        this.content(await this.moduleTemplate("deposito", "deposito", {
            employeeName: this.escape(this.session.user.nombre || this.session.user.razon_social || "equipo"),
            materials: materiales.map(m => `<option value="${this.escape(m.id_material)}">${this.escape(m.nombre)}</option>`).join(""),
            orders: ordenes.filter(o => !["Finalizada", "Entregada"].includes(o.estado)).map(o => `<option value="${this.escape(o.id_pedido)}">#${this.escape(o.id_pedido)} · ${this.escape(o.razon_social)} · ${this.escape(o.descripcion)}</option>`).join(""),
            userId: this.escape(this.session.user.id_usuario),
            today: new Date().toLocaleDateString(this.dateLocale(), {timeZone: "America/Argentina/Buenos_Aires", weekday: "long", day: "numeric", month: "long", year: "numeric"}),
            materialCount: materiales.length,
            lowStockCount: materiales.filter(material => {
                const stock = Number(material.stock);
                const minimum = Number(material.stock_minimo);
                return Number.isFinite(stock) && Number.isFinite(minimum) && minimum > 0 && stock <= minimum * 1.1;
            }).length,
            movementCount: movimientos.length,
            inventoryChart: this.moduleValueBarChart(materiales, "nombre", "stock", "Existencias por material", {
                referenceField: "stock_minimo", color: "#7d9fe5"
            }),
            inventoryTable: this.canManageAllDepartmentRecords()
                ? this.supervisorRecordsTable(materiales, ["id_material", "nombre", "tipo", "unidad", "stock", "stock_minimo", "stock_maximo"], "material", "id_material", "materiales")
                : this.simpleTable(materiales, ["id_material", "nombre", "tipo", "unidad", "stock", "stock_minimo", "stock_maximo"]),
            movementsTable: this.canManageAllDepartmentRecords()
                ? this.supervisorRecordsTable(movimientos, ["id_movimiento", "material", "tipo", "cantidad", "motivo", "fecha"], "movimiento", "id_movimiento", "movimientos")
                : this.simpleTable(movimientos, ["id_movimiento", "material", "tipo", "cantidad", "motivo", "fecha"])
        }));
        this.bindModuleIndexNavigation();
        this.refreshStockWarnings(materiales);
        this.bindPostForm("#movimientoForm", "deposito_movimiento_create", () => this.depositoView());
        if (this.canManageAllDepartmentRecords()) this.bindSupervisorRecordActions(() => this.depositoView());
    }

};
