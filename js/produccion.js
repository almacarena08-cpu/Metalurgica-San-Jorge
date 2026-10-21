export const produccionPanel = {
    async produccionView() {
        const [produccion, ordenes, maquinas] = await Promise.all([
            this.api.get("produccion_list"),
            this.api.get("ordenes_list"),
            this.api.get("maquinas_list")
        ]);
        this.content(await this.moduleTemplate("produccion", "produccion", {
            employeeName: this.escape(this.session.user.nombre || this.session.user.razon_social || "equipo"),
            orders: ordenes.map(o => `<option value="${this.escape(o.id_orden)}">#${this.escape(o.id_orden)} - ${this.escape(o.razon_social)}</option>`).join(""),
            affectedOrders: ordenes.filter(o => !["Finalizada", "Entregada"].includes(o.estado)).map(o => `<option value="${this.escape(o.id_orden)}">#${this.escape(o.id_orden)} - ${this.escape(o.razon_social)} · ${this.escape(o.descripcion)}</option>`).join(""),
            machines: maquinas.map(m => `<option value="${this.escape(m.id_maquina)}">${this.escape(`${m.marca} ${m.modelo} - ${m.numero_identificacion}`)}</option>`).join(""),
            userId: this.escape(this.session.user.id_usuario),
            today: new Date().toLocaleDateString(this.dateLocale(), {timeZone: "America/Argentina/Buenos_Aires", weekday: "long", day: "numeric", month: "long", year: "numeric"}),
            productionCount: produccion.length,
            activeOrders: ordenes.filter(order => String(order.estado || "").toLocaleLowerCase() !== "finalizada").length,
            machineCount: maquinas.length,
            productionChart: this.moduleProductionProgressChart(produccion),
            productionTable: this.canManageAllDepartmentRecords()
                ? this.supervisorRecordsTable(produccion, ["id_produccion", "id_orden", "razon_social", "material", "cantidad_producida", "avance", "observaciones"], "produccion", "id_produccion", "avances")
                : this.simpleTable(produccion, ["id_produccion", "id_orden", "razon_social", "material", "cantidad_producida", "avance", "observaciones"])
        }));
        this.bindModuleIndexNavigation();
        this.bindPostForm("#produccionForm", "produccion_register", () => this.produccionView());
        this.bindPostForm("#fallaForm", "mantenimiento_report", () => this.produccionView());
        if (this.canManageAllDepartmentRecords()) this.bindSupervisorRecordActions(() => this.produccionView());
    }

};
