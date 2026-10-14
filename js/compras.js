export const comprasPanel = {
    async comprasView() {
        const [proveedores, materiales, compras] = await Promise.all([this.api.get("proveedores_list"), this.api.get("materiales_list"), this.api.get("compras_list")]);
        this.content(await this.moduleTemplate("compras", "compras", {
            suppliers: proveedores.map(p => `<option value="${this.escape(p.id_proveedor)}">${this.escape(p.razon_social)}</option>`).join(""),
            materials: materiales.map(m => `<option value="${this.escape(m.id_material)}">${this.escape(m.nombre)}</option>`).join(""),
            materialsTable: this.simpleTable(materiales, ["id_material", "nombre", "stock", "stock_minimo", "stock_maximo"]),
            purchasesTable: this.simpleTable(compras, ["id_compra", "proveedor", "detalle", "total", "estado"])
        }));
        this.bindPostForm("#proveedorForm", "proveedores_create", () => this.comprasView());
        this.bindPostForm("#compraForm", "compras_create", () => this.comprasView());
    }

};
