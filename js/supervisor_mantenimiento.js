export const supervisorMantenimientoPanel = {
    async supervisorMantenimientoView() {
        const rows = await this.supervisorRows("mantenimientos_list");
        this.renderSupervisorRecords([{
            title: "Intervenciones de mantenimiento",
            table: this.supervisorRecordsTable(rows, ["id_mantenimiento", "numero_identificacion", "tipo", "problema", "reparacion", "repuestos", "estado"], "mantenimiento", "id_mantenimiento", "intervenciones")
        }], "supervisor_mantenimiento", () => this.supervisorMantenimientoView());
    }
};
