export const supervisorProduccionPanel = {
    async supervisorProduccionView() {
        const rows = await this.supervisorRows("produccion_list");
        this.renderSupervisorRecords([{
            title: "Avances de producción",
            table: this.supervisorRecordsTable(rows, ["id_produccion", "id_orden", "razon_social", "material", "cantidad_producida", "avance", "observaciones"], "produccion", "id_produccion", "avances")
        }], "supervisor_produccion", () => this.supervisorProduccionView());
    }
};
