export const mantenimientoPanel = {
    async mantenimientoView() {
        const [rows, maquinas] = await Promise.all([this.api.get("mantenimientos_list"), this.api.get("maquinas_list")]);
        this.content(await this.moduleTemplate("mantenimiento", "mantenimiento", {
            machines: maquinas.map(m => `<option value="${this.escape(m.id_maquina)}">${this.escape(`${m.marca} ${m.modelo}`)}</option>`).join(""),
            userId: this.escape(this.session.user.id_usuario),
            maintenanceTable: this.simpleTable(rows, ["id_mantenimiento", "numero_identificacion", "tipo", "problema", "reparacion", "repuestos", "estado"])
        }));
        this.bindPostForm("#preventivoForm", "mantenimiento_preventivo_create", () => this.mantenimientoView());
        this.bindPostForm("#mantenimientoForm", "mantenimiento_update", () => this.mantenimientoView());
    }

};
