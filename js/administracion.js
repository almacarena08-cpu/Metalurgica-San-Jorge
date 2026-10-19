export const administracionPanel = {
    async dashboardView() {
        clearInterval(this.dashboardTimer);
        this.dashboardTimer = null;
        const role = (this.session.user.rol || this.session.user.tipo || "").toLowerCase();
        const [pedidos, ordenes, chat] = await Promise.all([
            this.api.get("pedidos_list"),
            this.api.get("ordenes_list"),
            ["cliente", "gerencia", "administracion"].includes(role)
                ? this.api.post("chat_list")
                : Promise.resolve([])
        ]);
        if (this.current !== "administracion") return;
        const openChats = Array.isArray(chat) ? chat.filter(item => item.estado === "Abierto").length : 0;
        this.content(await this.moduleTemplate("administracion", "dashboard", {
            adminNavigation: this.administrationNavigation("dashboard"),
            employeeName: this.escape(this.session.user.nombre || this.session.user.razon_social || "equipo"),
            pedidosCount: pedidos.length,
            ordenesCount: ordenes.length,
            chatsCount: openChats,
            today: new Date().toLocaleDateString("es-AR", {timeZone: "America/Argentina/Buenos_Aires"}),
            pedidosChart: this.moduleMonthlyChart(pedidos, "fecha", null, "Pedidos registrados por mes"),
            ordenesChart: this.moduleStatusDonutChart(ordenes, "estado", "Órdenes de trabajo"),
            recentOrders: this.simpleTable(pedidos.slice(0, 6), ["id_pedido", "razon_social", "descripcion", "estado"])
        }));
        this.bindAdministrationNavigation();
        this.dashboardTimer = setInterval(() => {
            if (this.current !== "administracion" || !this.root.querySelector("#content .admin-overview")) return;
            this.dashboardView().catch(error => {
                console.error("No se pudo actualizar el dashboard automáticamente:", error);
                const status = this.root.querySelector("[data-dashboard-sync]");
                if (status) {
                    status.textContent = "No se pudo actualizar. Se reintentará en un minuto.";
                    status.classList.add("is-error");
                }
            });
        }, 60000);
    },

    async pedidosView() {
        const [rows, clientes, materiales] = await Promise.all([
            this.api.get("pedidos_list"), this.api.get("clientes_list"), this.api.get("materiales_list")
        ]);
        this.content(await this.moduleTemplate("administracion", "pedidos", {
            adminNavigation: this.administrationNavigation("pedidos"),
            clients: clientes.map(c => `<option value="${this.escape(c.id_cliente)}">${this.escape(c.razon_social)}</option>`).join(""),
            materials: materiales.map(m => `<option value="${this.escape(m.nombre)}">${this.escape(m.nombre)}</option>`).join(""),
            orders: this.simpleTable(rows, ["id_pedido", "razon_social", "descripcion", "cantidad", "material", "fecha_entrega", "estado"])
        }));
        this.bindAdministrationNavigation();
        this.bindPostForm("#pedidoForm", "pedidos_create", () => this.pedidosView());
    },

    async ordenesView() {
        const [ordenes, pedidos] = await Promise.all([this.api.get("ordenes_list"), this.api.get("pedidos_list")]);
        this.content(await this.moduleTemplate("administracion", "ordenes", {
            adminNavigation: this.administrationNavigation("ordenes"),
            ordersForSelect: pedidos.map(p => `<option value="${this.escape(p.id_pedido)}">#${this.escape(p.id_pedido)} - ${this.escape(p.razon_social)} - ${this.escape(p.descripcion)}</option>`).join(""),
            userId: this.escape(this.session.user.id_usuario),
            ordersTable: this.simpleTable(ordenes, ["id_orden", "id_pedido", "razon_social", "material", "prioridad", "estado", "avance", "fecha_prevista"])
        }));
        this.bindAdministrationNavigation();
        this.bindPostForm("#ordenForm", "ordenes_create", () => this.ordenesView());
        this.bindPostForm("#estadoOrdenForm", "orden_update_status", () => this.ordenesView());
    },

    async clientesView() {
        const rows = await this.api.get("clientes_list");
        this.content(await this.moduleTemplate("administracion", "clientes", {
            adminNavigation: this.administrationNavigation("clientes"),
            clientsTable: this.simpleTable(rows, ["id_cliente", "razon_social", "cuit", "email", "direccion"])
        }));
        this.bindAdministrationNavigation();
        this.root.querySelector("#clienteForm").addEventListener("submit", event => {
            event.preventDefault();
            const data = Object.fromEntries(new FormData(event.currentTarget));
            this.postAndRefresh(data.id_cliente ? "clientes_update" : "clientes_create", data, () => this.clientesView(), event.currentTarget);
        });
        this.bindPostForm("#deleteClienteForm", "clientes_delete", () => this.clientesView());
    },

    administrationNavigation(activeView) {
        const links = [
            ["dashboard", "Resumen"],
            ["pedidos", "Pedidos"],
            ["ordenes", "Órdenes"],
            ["clientes", "Clientes"]
        ];
        return `<nav class="admin-subnav" aria-label="Secciones de administración">${links.map(([view, label]) =>
            `<button class="btn ${view === activeView ? "primary" : ""}" type="button" data-admin-view="${view}">${label}</button>`
        ).join("")}</nav>`;
    },

    bindAdministrationNavigation() {
        this.root.querySelectorAll("[data-admin-view]").forEach(button => {
            button.addEventListener("click", () => {
                const views = {
                    dashboard: () => this.dashboardView(),
                    pedidos: () => this.pedidosView(),
                    ordenes: () => this.ordenesView(),
                    clientes: () => this.clientesView()
                };
                views[button.dataset.adminView]();
            });
        });
    }
};
