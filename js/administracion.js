export const administracionPanel = {
    async dashboardView() {
        clearInterval(this.dashboardTimer);
        this.dashboardTimer = null;
        const role = this.normalizeRole(this.session.user.rol || this.session.user.tipo || "");
        const [pedidos, ordenes, chat] = await Promise.all([
            this.api.get("pedidos_list"),
            this.api.get("ordenes_list"),
            ["cliente", "gerencia", "administracion", "administrador", "supervisor_administracion"].includes(role)
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

    async empleadosView() {
        const [employees, roles] = await Promise.all([
            this.api.get("empleados_list"),
            this.api.get("empleados_roles_list")
        ]);
        if (!Array.isArray(employees)) {
            throw new Error(employees.message || "No se pudo cargar el listado de empleados.");
        }
        if (!Array.isArray(roles)) {
            throw new Error(roles.message || "No se pudieron cargar los roles de empleados.");
        }
        const employeeRows = employees.length
            ? employees.map(employee => `
                <tr>
                    <td>${this.escape(employee.nombre)} ${this.escape(employee.apellido)}</td>
                    <td>${this.escape(employee.usuario)}</td>
                    <td>${this.escape(employee.rol)}</td>
                    <td class="employee-actions">
                        <button class="btn" type="button" data-edit-employee="${this.escape(employee.id_usuario)}"
                            data-nombre="${this.escape(employee.nombre)}" data-apellido="${this.escape(employee.apellido)}"
                            data-usuario="${this.escape(employee.usuario)}" data-id-rol="${this.escape(employee.id_rol)}">Editar</button>
                        <button class="btn warn" type="button" data-delete-employee="${this.escape(employee.id_usuario)}">Eliminar</button>
                    </td>
                </tr>`).join("")
            : '<tr><td colspan="4">Todavía no hay empleados registrados.</td></tr>';
        this.content(await this.moduleTemplate("administracion", "empleados", {
            adminNavigation: this.administrationNavigation("empleados"),
            roleOptions: roles.map(role => `<option value="${this.escape(role.id_rol)}">${this.escape(role.nombre)}</option>`).join(""),
            employeeRows
        }));
        this.bindAdministrationNavigation();

        const form = this.root.querySelector("#employeeForm");
        const message = this.root.querySelector("[data-employee-message]");
        const saveButton = form.querySelector("[type=submit]");
        const cancelButton = form.querySelector("[data-cancel-employee-edit]");
        const setFormMode = editing => {
            saveButton.textContent = editing ? "Guardar cambios" : "Crear empleado";
            form.querySelector("[data-password-label]").textContent = editing
                ? "Nueva contraseña (opcional)"
                : "Contraseña";
            form.querySelector('[name="contrasena"]').required = !editing;
            cancelButton.hidden = !editing;
        };
        const resetForm = () => {
            form.reset();
            form.elements.id_usuario.value = "";
            setFormMode(false);
        };

        form.addEventListener("submit", async event => {
            event.preventDefault();
            const data = Object.fromEntries(new FormData(form));
            const action = data.id_usuario ? "empleados_update" : "empleados_create";
            try {
                const result = await this.api.post(action, data);
                message.textContent = result.success
                    ? (data.id_usuario ? "Empleado actualizado." : "Empleado creado.")
                    : (result.message || "No se pudo guardar el empleado.");
                message.classList.toggle("is-error", !result.success);
                if (result.success) {
                    await this.empleadosView();
                    this.root.querySelector("[data-employee-message]").textContent = message.textContent;
                }
            } catch (error) {
                console.error("No se pudo guardar el empleado:", error);
                message.textContent = error.message || "No se pudo guardar el empleado.";
                message.classList.add("is-error");
            }
        });
        cancelButton.addEventListener("click", resetForm);
        this.root.querySelectorAll("[data-edit-employee]").forEach(button => {
            button.addEventListener("click", () => {
                form.elements.id_usuario.value = button.dataset.editEmployee;
                form.elements.nombre.value = button.dataset.nombre;
                form.elements.apellido.value = button.dataset.apellido;
                form.elements.usuario.value = button.dataset.usuario;
                form.elements.id_rol.value = button.dataset.idRol;
                form.elements.contrasena.value = "";
                message.textContent = `Editando a ${button.dataset.nombre} ${button.dataset.apellido}.`;
                message.classList.remove("is-error");
                setFormMode(true);
                form.scrollIntoView({behavior: "smooth", block: "start"});
            });
        });
        this.root.querySelectorAll("[data-delete-employee]").forEach(button => {
            button.addEventListener("click", async () => {
                const employee = employees.find(item => String(item.id_usuario) === button.dataset.deleteEmployee);
                const employeeName = employee ? `${employee.nombre} ${employee.apellido}`.trim() : "este empleado";
                if (!window.confirm(`¿Eliminar a ${employeeName}? Esta acción no se puede deshacer.`)) return;
                try {
                    const result = await this.api.post("empleados_delete", {id_usuario: button.dataset.deleteEmployee});
                    message.textContent = result.success
                        ? "Empleado eliminado."
                        : (result.message || "No se pudo eliminar el empleado.");
                    message.classList.toggle("is-error", !result.success);
                    if (result.success) {
                        await this.empleadosView();
                        this.root.querySelector("[data-employee-message]").textContent = "Empleado eliminado.";
                    }
                } catch (error) {
                    console.error("No se pudo eliminar el empleado:", error);
                    message.textContent = error.message || "No se pudo eliminar el empleado.";
                    message.classList.add("is-error");
                }
            });
        });
        setFormMode(false);
    },

    administrationNavigation(activeView) {
        const links = [
            ["dashboard", "Resumen"],
            ["pedidos", "Pedidos"],
            ["ordenes", "Órdenes"],
            ["clientes", "Clientes"]
        ];
        if (this.isGlobalAdministrator()) links.push(["empleados", "Empleados"]);
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
                    clientes: () => this.clientesView(),
                    empleados: () => this.empleadosView()
                };
                views[button.dataset.adminView]();
            });
        });
    }
};
