export const supervisorDepositoPanel = {
    async supervisorDepositoView() {
        const [materials, movements] = await Promise.all([
            this.supervisorRows("materiales_list"),
            this.supervisorRows("deposito_movimientos_list")
        ]);
        this.renderSupervisorRecords([
            {
                title: "Inventario de materiales",
                table: this.supervisorRecordsTable(materials, ["id_material", "nombre", "tipo", "unidad", "stock", "stock_minimo", "stock_maximo"], "material", "id_material", "materiales")
            },
            {
                title: "Movimientos de stock",
                table: this.supervisorRecordsTable(movements, ["id_movimiento", "material", "tipo", "cantidad", "motivo", "fecha"], "movimiento", "id_movimiento", "movimientos")
            }
        ], "supervisor_deposito", () => this.supervisorDepositoView());
    }
};
