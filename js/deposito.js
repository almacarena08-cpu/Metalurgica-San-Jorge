export const depositoPanel = {
    async depositoView() {
        const [materiales, movimientos] = await Promise.all([this.api.get("materiales_list"), this.api.get("deposito_movimientos_list")]);
        this.content(await this.moduleTemplate("deposito", "deposito", {
            materials: materiales.map(m => `<option value="${this.escape(m.id_material)}">${this.escape(m.nombre)}</option>`).join(""),
            userId: this.escape(this.session.user.id_usuario),
            inventoryTable: this.simpleTable(materiales, ["id_material", "nombre", "tipo", "unidad", "stock", "stock_minimo", "stock_maximo"]),
            movementsTable: this.simpleTable(movimientos, ["id_movimiento", "material", "tipo", "cantidad", "motivo", "fecha"])
        }));
        this.refreshStockWarnings(materiales);
        this.bindPostForm("#movimientoForm", "deposito_movimiento_create", () => this.depositoView());
    }

};
