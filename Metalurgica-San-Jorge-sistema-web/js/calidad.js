export const calidadPanel = {
    async calidadView() {
        const [rows, ordenes] = await Promise.all([
            this.api.get("calidad_list"),
            this.api.get("ordenes_list")
        ]);
        this.content(await this.moduleTemplate("calidad", "calidad", {
            employeeName: this.escape(this.session.user.nombre || this.session.user.razon_social || "equipo"),
            orders: ordenes.map(o => `<option value="${this.escape(o.id_orden)}">#${this.escape(o.id_orden)} - ${this.escape(o.razon_social)}</option>`).join(""),
            userId: this.escape(this.session.user.id_usuario),
            today: new Date().toLocaleDateString(this.dateLocale(), {timeZone: "America/Argentina/Buenos_Aires", weekday: "long", day: "numeric", month: "long", year: "numeric"}),
            controlCount: rows.length,
            approvedCount: rows.filter(row => String(row.resultado || "").toLocaleLowerCase() === "aprobado").length,
            reviewCount: rows.filter(row => ["observado", "rechazado"].includes(String(row.resultado || "").toLocaleLowerCase())).length,
            qualityChart: this.moduleStatusDonutChart(rows, "resultado", "Controles de calidad", {
                "Aprobado": "#71b98a", "Observado": "#dfbb66", "Rechazado": "#d15e5e"
            }),
            qualityTable: this.canManageAllDepartmentRecords()
                ? this.supervisorRecordsTable(rows, ["id_control", "id_orden", "razon_social", "resultado", "observaciones", "fecha"], "calidad", "id_control", "controles")
                : this.simpleTable(rows, ["id_control", "id_orden", "razon_social", "resultado", "observaciones", "fecha"])
        }));
        this.bindModuleIndexNavigation();
        this.bindPostForm("#calidadForm", "calidad_register", () => this.calidadView());
        if (this.canManageAllDepartmentRecords()) this.bindSupervisorRecordActions(() => this.calidadView());
    }

};
