export const calidadPanel = {
    async calidadView() {
        const [rows, ordenes] = await Promise.all([this.api.get("calidad_list"), this.api.get("ordenes_list")]);
        this.content(await this.moduleTemplate("calidad", "calidad", {
            orders: ordenes.map(o => `<option value="${this.escape(o.id_orden)}">#${this.escape(o.id_orden)} - ${this.escape(o.razon_social)}</option>`).join(""),
            userId: this.escape(this.session.user.id_usuario),
            qualityTable: this.simpleTable(rows, ["id_control", "id_orden", "razon_social", "resultado", "observaciones", "fecha"])
        }));
        this.bindPostForm("#calidadForm", "calidad_register", () => this.calidadView());
    }

};
