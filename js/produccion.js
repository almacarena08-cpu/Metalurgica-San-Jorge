export const produccionPanel = {
    async produccionView() {
        const [produccion, ordenes, maquinas] = await Promise.all([this.api.get("produccion_list"), this.api.get("ordenes_list"), this.api.get("maquinas_list")]);
        this.content(await this.moduleTemplate("produccion", "produccion", {
            orders: ordenes.map(o => `<option value="${this.escape(o.id_orden)}">#${this.escape(o.id_orden)} - ${this.escape(o.razon_social)}</option>`).join(""),
            machines: maquinas.map(m => `<option value="${this.escape(m.id_maquina)}">${this.escape(`${m.marca} ${m.modelo} - ${m.numero_identificacion}`)}</option>`).join(""),
            userId: this.escape(this.session.user.id_usuario),
            productionTable: this.simpleTable(produccion, ["id_produccion", "id_orden", "razon_social", "material", "cantidad_producida", "avance", "observaciones"])
        }));
        this.bindPostForm("#produccionForm", "produccion_register", () => this.produccionView());
        this.bindPostForm("#fallaForm", "mantenimiento_report", () => this.produccionView());
    }

};
