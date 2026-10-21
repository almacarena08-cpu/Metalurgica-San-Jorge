export const mantenimientoPanel = {
    async mantenimientoView() {
        const [rows, maquinas] = await Promise.all([
            this.api.get("mantenimientos_list"),
            this.api.get("maquinas_list")
        ]);
        this.content(await this.moduleTemplate("mantenimiento", "mantenimiento", {
            employeeName: this.escape(this.session.user.nombre || this.session.user.razon_social || "equipo"),
            machines: maquinas.map(m => `<option value="${this.escape(m.id_maquina)}">${this.escape(`${m.marca} ${m.modelo}`)}</option>`).join(""),
            userId: this.escape(this.session.user.id_usuario),
            today: new Date().toLocaleDateString(this.dateLocale(), {timeZone: "America/Argentina/Buenos_Aires", weekday: "long", day: "numeric", month: "long", year: "numeric"}),
            maintenanceCount: rows.length,
            openMaintenance: rows.filter(row => ["pendiente", "en proceso"].includes(String(row.estado || "").toLocaleLowerCase())).length,
            machineCount: maquinas.length,
            maintenanceChart: this.moduleStatusDonutChart(rows, "estado", "Intervenciones de mantenimiento", {
                "Pendiente": "#dfbb66", "En proceso": "#7d9fe5", "Resuelta": "#71b98a"
            }),
            maintenanceTable: this.canManageAllDepartmentRecords()
                ? this.supervisorRecordsTable(rows, ["id_mantenimiento", "numero_identificacion", "tipo", "problema", "reparacion", "repuestos", "estado"], "mantenimiento", "id_mantenimiento", "intervenciones")
                : this.simpleTable(rows, ["id_mantenimiento", "numero_identificacion", "tipo", "problema", "reparacion", "repuestos", "estado"])
        }));
        this.bindModuleIndexNavigation();
        this.bindPostForm("#preventivoForm", "mantenimiento_preventivo_create", () => this.mantenimientoView());
        this.bindPostForm("#mantenimientoForm", "mantenimiento_update", () => this.mantenimientoView());
        if (this.canManageAllDepartmentRecords()) this.bindSupervisorRecordActions(() => this.mantenimientoView());
    }

};
