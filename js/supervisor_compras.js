export const supervisorComprasPanel = {
    async supervisorComprasView() {
        const [suppliers, purchases, materials] = await Promise.all([
            this.supervisorRows("proveedores_list"),
            this.supervisorRows("compras_list"),
            this.supervisorRows("materiales_list")
        ]);
        this.renderSupervisorRecords([
            {
                title: "Proveedores",
                table: this.supervisorRecordsTable(suppliers, ["id_proveedor", "razon_social", "cuit", "telefono", "email"], "proveedor", "id_proveedor", "proveedores")
            },
            {
                title: "Compras",
                table: this.supervisorRecordsTable(purchases, ["id_compra", "fecha", "proveedor", "detalle", "total", "estado"], "compra", "id_compra", "compras")
            },
            {
                title: "Inventario de materiales",
                table: this.supervisorRecordsTable(materials, ["id_material", "nombre", "tipo", "unidad", "stock", "stock_minimo", "stock_maximo"], "material", "id_material", "materiales")
            }
        ], "supervisor_compras", () => this.supervisorComprasView());
    }
};
