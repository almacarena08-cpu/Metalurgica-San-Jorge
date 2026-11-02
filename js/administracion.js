export const administracionPanel = {
    async dashboardView() {
        clearInterval(this.dashboardTimer);
        this.dashboardTimer = null;
        const role = this.normalizeRole(this.session.user.rol || this.session.user.tipo || "");
        const [pedidos, ordenes, chat] = await Promise.all([
            this.api.get("pedidos_list"),
            this.api.get("ordenes_list"),
            ["cliente", "gerencia", "administracion", "administrador", "administrador_global", "supervisor_administracion"].includes(role)
                ? this.api.post("chat_list")
                : Promise.resolve([])
        ]);
        if (this.current !== "administracion") return;
        for (const [rows, label] of [[pedidos, "los pedidos"], [ordenes, "las órdenes de trabajo"]]) {
            if (!Array.isArray(rows)) {
                const error = new Error(rows?.message || `No se pudieron cargar ${label}.`);
                error.authRequired = Boolean(rows?.authRequired);
                throw error;
            }
        }
        const openChats = Array.isArray(chat) ? chat.filter(item => item.estado === "Abierto").length : 0;
        this.content(await this.moduleTemplate("administracion", "dashboard", {
            adminNavigation: this.administrationNavigation("dashboard"),
            employeeName: this.escape(this.session.user.nombre || this.session.user.razon_social || "equipo"),
            pedidosCount: pedidos.length,
            ordenesCount: ordenes.length,
            chatsCount: openChats,
            today: new Date().toLocaleDateString(this.dateLocale(), {timeZone: "America/Argentina/Buenos_Aires"}),
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
            orders: this.canManageAllDepartmentRecords()
                ? this.supervisorRecordsTable(rows, ["id_pedido", "razon_social", "descripcion", "cantidad", "material", "fecha_entrega", "estado"], "pedido", "id_pedido", "pedidos")
                : this.simpleTable(rows, ["id_pedido", "razon_social", "descripcion", "cantidad", "material", "fecha_entrega", "estado"])
        }));
        this.bindAdministrationNavigation();
        this.bindPostForm("#pedidoForm", "pedidos_create", () => this.pedidosView());
        if (this.canManageAllDepartmentRecords()) this.bindSupervisorRecordActions(() => this.pedidosView());
    },

    async facturacionView() {
        const invoices = await this.api.get('facturas_list');
        if (!Array.isArray(invoices)) throw new Error(invoices?.message || 'No se pudieron cargar las facturas.');
        const invoiceRows = invoices.map(invoice => {
            const total = Number(invoice.total || 0).toLocaleString('es-AR', {style: 'currency', currency: 'ARS'});
            const invoiceData = this.escape(JSON.stringify(invoice));
            return `<tr><td>${this.escape(invoice.numero)}</td><td>${this.escape(invoice.razon_social)}</td><td>${this.escape(invoice.asunto_chat || '—')}</td><td>${invoice.id_pedido ? `#${this.escape(invoice.id_pedido)}` : '—'}</td><td>${this.escape(invoice.fecha)}</td><td>${this.escape(total)}</td><td>${this.escape(invoice.estado)}</td><td><div class="invoice-row-actions"><button class="btn" type="button" data-edit-invoice="${invoiceData}">Editar</button><button class="btn" type="button" data-send-invoice="${this.escape(invoice.id_factura)}">PDF al chat</button><button class="btn warn" type="button" data-delete-invoice="${this.escape(invoice.id_factura)}">Eliminar</button></div></td></tr>`;
        }).join('');
        const invoiceTable = invoices.length
            ? this.searchableTable(`<div class="table-wrap"><table class="invoice-table">
                <thead><tr><th>Número</th><th>Cliente</th><th>Conversación</th><th>Pedido</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>${invoiceRows}</tbody>
            </table><p class="table-no-results" data-table-empty hidden>No se encontraron facturas con ese criterio.</p></div>`, invoices.length, 'facturas')
            : '<p class="muted">Todavía no hay facturas. Creá una desde esta pantalla o desde una conversación del chat.</p>';
        this.content(await this.moduleTemplate('administracion', 'facturacion', {
            adminNavigation: this.administrationNavigation('facturacion'),
            invoiceTable
        }));
        this.bindAdministrationNavigation();
        const feedback = this.root.querySelector('[data-invoice-message]');
        this.root.querySelector('[data-create-invoice]').addEventListener('click', () => this.openInvoiceDialog());
        this.root.querySelectorAll('[data-edit-invoice]').forEach(button => button.addEventListener('click', () => {
            try { this.openInvoiceDialog(null, JSON.parse(button.dataset.editInvoice)); }
            catch (error) { console.error('No se pudo abrir la factura para editarla:', error); }
        }));
        this.root.querySelectorAll('[data-send-invoice]').forEach(button => button.addEventListener('click', async () => {
            button.disabled = true;
            try {
                const result = await this.api.post('facturas_enviar_chat', {id_factura: button.dataset.sendInvoice});
                feedback.textContent = result.success ? 'PDF generado y enviado a la conversación.' : (result.message || 'No se pudo enviar el PDF.');
                feedback.classList.toggle('is-error', !result.success);
                if (result.success) await this.facturacionView();
            } catch (error) {
                feedback.textContent = error.message || 'No se pudo enviar el PDF.';
                feedback.classList.add('is-error');
            }
        }));
        this.root.querySelectorAll('[data-delete-invoice]').forEach(button => button.addEventListener('click', async () => {
            if (!window.confirm('¿Eliminar esta factura?')) return;
            try {
                const result = await this.api.post('facturas_delete', {id_factura: button.dataset.deleteInvoice});
                if (!result.success) throw new Error(result.message || 'No se pudo eliminar la factura.');
                await this.facturacionView();
                const message = this.root.querySelector('[data-invoice-message]');
                if (message) message.textContent = 'Factura eliminada.';
            } catch (error) {
                feedback.textContent = error.message || 'No se pudo eliminar la factura.';
                feedback.classList.add('is-error');
            }
        }));
    },

    async openInvoiceDialog(prefilledChat = null, invoice = null) {
        let chats = [];
        try { chats = await this.api.post('chat_list'); }
        catch (error) { window.alert(error.message || 'No se pudieron cargar las conversaciones.'); return; }
        if (!Array.isArray(chats)) { window.alert(chats?.message || 'No se pudieron cargar las conversaciones.'); return; }
        const dialog = document.createElement('dialog');
        dialog.className = 'invoice-dialog';
        dialog.setAttribute('aria-labelledby', 'invoiceDialogTitle');
        const options = chats.map(chat => `<option value="${this.escape(chat.id_chat)}" data-client="${this.escape(chat.id_cliente)}" data-client-name="${this.escape(chat.nombre)}" data-order="${this.escape(chat.id_pedido || '')}">${this.escape(chat.asunto)} · ${this.escape(chat.nombre)}</option>`).join('');
        dialog.innerHTML = `<form class="invoice-form" data-invoice-form>
            <header class="invoice-dialog-head"><div><span class="module-kicker">DOCUMENTO INTERNO</span><h3 id="invoiceDialogTitle">${invoice ? 'Editar factura' : 'Crear factura'}</h3></div><button class="invoice-close" type="button" data-close aria-label="Cerrar">×</button></header>
            <p class="invoice-dialog-note">Borrador interno: no válido como factura fiscal sin autorización CAE de ARCA.</p>
            <input type="hidden" name="id_factura"><input type="hidden" name="id_cliente">
            <div class="invoice-form-grid">
                <label class="field full">Conversación (asunto)<select name="id_chat" required><option value="">Seleccionar conversación...</option>${options}</select></label>
                <div class="invoice-context"><span>Cliente</span><strong data-invoice-client>Seleccioná una conversación</strong></div><div class="invoice-context"><span>Pedido vinculado</span><strong data-invoice-order>Sin pedido vinculado</strong></div>
                <label>Número<input name="numero" maxlength="30" placeholder="Ej.: B-00001-00000123" required></label><label>Fecha<input name="fecha" type="date" required></label>
                <label>Tipo de comprobante<select name="tipo_comprobante"><option>A</option><option selected>B</option><option>C</option></select></label>
                <label>Condición de venta<select name="condicion_venta"><option>Cuenta corriente</option><option>Transferencia</option><option>Contado</option><option>Otro</option></select></label>
                <label>IVA (%)<select name="iva_alicuota"><option>21</option><option>10.5</option><option>27</option><option>0</option></select></label>
                <label>Total final (IVA incluido)<input name="total" type="number" min="0" step="0.01" required></label>
                <label>Estado<select name="estado"><option>Pendiente</option><option>Pagada</option><option>Vencida</option><option>Anulada</option></select></label>
            </div><p class="message" data-form-message aria-live="polite"></p>
            <footer class="invoice-dialog-actions"><button class="btn" type="button" data-close>Cancelar</button><button class="btn" type="submit">Guardar</button><button class="btn primary" type="button" data-save-send>Guardar y enviar PDF al chat</button></footer>
        </form>`;
        document.body.append(dialog);
        const form = dialog.querySelector('[data-invoice-form]');
        const select = form.elements.id_chat;
        const updateChatContext = () => {
            const option = select.selectedOptions[0];
            form.elements.id_cliente.value = option?.dataset.client || '';
            dialog.querySelector('[data-invoice-client]').textContent = option?.dataset.clientName || 'Seleccioná una conversación';
            dialog.querySelector('[data-invoice-order]').textContent = option?.dataset.order ? `Pedido #${option.dataset.order}` : 'Sin pedido vinculado';
        };
        select.addEventListener('change', updateChatContext);
        const close = () => { if (dialog.open) dialog.close(); dialog.remove(); };
        dialog.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', close));
        dialog.addEventListener('click', event => { if (event.target === dialog) close(); });
        dialog.addEventListener('close', () => dialog.remove(), {once: true});
        if (invoice) {
            for (const field of ['id_factura','fecha','numero','total','tipo_comprobante','condicion_venta','iva_alicuota','estado']) {
                if (form.elements[field]) form.elements[field].value = invoice[field] ?? '';
            }
            select.value = String(invoice.id_chat || '');
        } else {
            const localDate = new Intl.DateTimeFormat('en-CA', {timeZone: 'America/Argentina/Buenos_Aires', year: 'numeric', month: '2-digit', day: '2-digit'}).formatToParts(new Date());
            const dateParts = Object.fromEntries(localDate.map(part => [part.type, part.value]));
            form.elements.fecha.value = `${dateParts.year}-${dateParts.month}-${dateParts.day}`;
            if (prefilledChat?.id_chat) select.value = String(prefilledChat.id_chat);
        }
        updateChatContext();
        const saveInvoice = async sendAfterSave => {
            const message = dialog.querySelector('[data-form-message]');
            const data = Object.fromEntries(new FormData(form));
            if (!data.id_cliente || !data.id_chat) { message.textContent = 'Seleccioná la conversación del cliente.'; message.classList.add('is-error'); return; }
            const buttons = [...dialog.querySelectorAll('button')]; buttons.forEach(button => button.disabled = true);
            try {
                const editing = Boolean(data.id_factura);
                const saved = await this.api.post(editing ? 'facturas_update' : 'facturas_create', data);
                if (!saved.success) throw new Error(saved.message || 'No se pudo guardar la factura.');
                const idFactura = data.id_factura || saved.id_factura;
                if (sendAfterSave) {
                    const sent = await this.api.post('facturas_enviar_chat', {id_factura: idFactura});
                    if (!sent.success) throw new Error(sent.message || 'La factura quedó guardada, pero no se pudo enviar el PDF.');
                }
                close();
                if (this.current === 'administracion') await this.facturacionView();
                else await this.chatView();
                const notice = this.root.querySelector('[data-invoice-message]') || this.root.querySelector('.chat-window > .message');
                if (notice) notice.textContent = sendAfterSave ? 'Factura guardada y PDF enviado al chat.' : 'Factura guardada.';
            } catch (error) {
                message.textContent = error.message || 'No se pudo guardar la factura.';
                message.classList.add('is-error');
                buttons.forEach(button => button.disabled = false);
            }
        };
        form.addEventListener('submit', event => { event.preventDefault(); saveInvoice(false); });
        dialog.querySelector('[data-save-send]').addEventListener('click', () => saveInvoice(true));
        dialog.showModal();
        form.elements.id_chat.focus();
    },

    async ordenesView() {
        const [ordenes, pedidos] = await Promise.all([this.api.get("ordenes_list"), this.api.get("pedidos_list")]);
        this.content(await this.moduleTemplate("administracion", "ordenes", {
            adminNavigation: this.administrationNavigation("ordenes"),
            ordersForSelect: pedidos.map(p => `<option value="${this.escape(p.id_pedido)}">#${this.escape(p.id_pedido)} - ${this.escape(p.razon_social)} - ${this.escape(p.descripcion)}</option>`).join(""),
            userId: this.escape(this.session.user.id_usuario),
            ordersTable: this.canManageAllDepartmentRecords()
                ? this.supervisorRecordsTable(ordenes, ["id_orden", "id_pedido", "razon_social", "material", "prioridad", "estado", "avance", "fecha_prevista", "observaciones"], "orden", "id_orden", "órdenes")
                : this.simpleTable(ordenes, ["id_orden", "id_pedido", "razon_social", "material", "prioridad", "estado", "avance", "fecha_prevista"])
        }));
        this.bindAdministrationNavigation();
        this.bindPostForm("#ordenForm", "ordenes_create", () => this.ordenesView());
        this.bindPostForm("#estadoOrdenForm", "orden_update_status", () => this.ordenesView());
        if (this.canManageAllDepartmentRecords()) this.bindSupervisorRecordActions(() => this.ordenesView());
    },

    async clientesView() {
        const rows = await this.api.get("clientes_list");
        this.content(await this.moduleTemplate("administracion", "clientes", {
            adminNavigation: this.administrationNavigation("clientes"),
            clientsTable: this.canManageAllDepartmentRecords()
                ? this.supervisorRecordsTable(rows, ["id_cliente", "razon_social", "cuit", "email", "direccion"], "cliente", "id_cliente", "clientes")
                : this.simpleTable(rows, ["id_cliente", "razon_social", "cuit", "email", "direccion"])
        }));
        this.bindAdministrationNavigation();
        if (this.canManageAllDepartmentRecords()) this.bindSupervisorRecordActions(() => this.clientesView());
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
            ? employees.map(employee => {
                const protectedRole = ["administrador", "administrador_global", "gerencia"].includes(this.normalizeRole(employee.rol));
                const canManageEmployee = this.isGlobalAdministrator() || !protectedRole;
                return `
                <tr>
                    <td>${this.escape(employee.nombre)} ${this.escape(employee.apellido)}</td>
                    <td>${this.escape(employee.usuario)}</td>
                    <td>${this.escape(employee.rol)}</td>
                    <td>
                        <div class="employee-actions">
                        ${canManageEmployee ? `
                        <button class="btn" type="button" data-edit-employee="${this.escape(employee.id_usuario)}"
                            data-nombre="${this.escape(employee.nombre)}" data-apellido="${this.escape(employee.apellido)}"
                            data-usuario="${this.escape(employee.usuario)}" data-id-rol="${this.escape(employee.id_rol)}">Editar</button>
                        <button class="btn warn" type="button" data-delete-employee="${this.escape(employee.id_usuario)}">Eliminar</button>
                        ` : '<span class="muted">Protegido</span>'}
                        </div>
                    </td>
                </tr>`;
            }).join("")
            : '<tr><td colspan="4">Todavía no hay empleados registrados.</td></tr>';
        const employeesTable = `
            <table class="employee-table">
                <thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Acciones</th></tr></thead>
                <tbody>${employeeRows}</tbody>
            </table>
        `;
        this.content(await this.moduleTemplate("administracion", "empleados", {
            adminNavigation: this.administrationNavigation("empleados"),
            roleOptions: roles.map(role => `<option value="${this.escape(role.id_rol)}">${this.escape(role.nombre)}</option>`).join(""),
            employeesTable
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
            ["facturacion", "Facturación"],
            ["clientes", "Clientes"]
        ];
        if (this.canManageEmployees()) links.push(["empleados", "Empleados"]);
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
                    facturacion: () => this.facturacionView(),
                    clientes: () => this.clientesView(),
                    empleados: () => this.empleadosView()
                };
                views[button.dataset.adminView]();
            });
        });
    }
};
