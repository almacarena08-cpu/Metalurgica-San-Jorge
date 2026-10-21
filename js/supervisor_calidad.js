export const supervisorCalidadPanel = {
    async supervisorCalidadView() {
        const rows = await this.supervisorRows("calidad_list");
        this.renderSupervisorRecords([{
            title: "Controles de calidad",
            table: this.supervisorRecordsTable(rows, ["id_control", "id_orden", "razon_social", "resultado", "observaciones", "fecha"], "calidad", "id_control", "controles")
        }], "supervisor_calidad", () => this.supervisorCalidadView());
    }
};
