class ApiService {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
    }

    async get(action, params = {}) {
        const query = new URLSearchParams({action, ...params});
        const response = await fetch(`${this.baseUrl}?${query.toString()}`);
        return response.json();
    }

    async post(action, data = {}) {
        const response = await fetch(`${this.baseUrl}?action=${encodeURIComponent(action)}`, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"},
            body: new URLSearchParams(data)
        });
        return response.json();
    }
}

class SessionStore {
    constructor(key) {
        this.key = key;
        this.user = JSON.parse(localStorage.getItem(key) || "null");
    }

    set(user) {
        this.user = user;
        localStorage.setItem(this.key, JSON.stringify(user));
    }

    clear() {
        this.user = null;
        localStorage.removeItem(this.key);
    }
}

class App {
    constructor(root, api, session) {
        this.root = root;
        this.api = api;
        this.session = session;
        this.current = "dashboard";
        this.activeChat = null;
        this.chatTimer = null;
    }

    start() {
        this.session.user ? this.renderShell() : this.renderLanding();
    }

    renderLanding() {
        this.stopChatTimer();
        this.root.innerHTML = `
            <main class="landing-site">
                <header class="public-nav">
                    <a class="brand-lockup" href="#inicio" aria-label="Metalúrgica San Jorge, inicio">
                        <span class="brand-emblem">SJ</span>
                        <span class="brand-mark">METALÚRGICA<strong>SAN JORGE</strong></span>
                    </a>
                    <nav aria-label="Navegación principal">
                        <a href="#servicios">Servicios</a>
                        <a href="#empresa">Empresa</a>
                        <a href="#beneficios">Beneficios</a>
                        <a href="#contacto">Contacto</a>
                    </nav>
                    <div class="public-actions">
                        <button class="btn" data-auth="login">Ingresar</button>
                        <button class="btn primary" data-auth="register">Solicitar acceso</button>
                    </div>
                </header>
                <section class="public-hero" id="inicio">
                    <div class="hero-content">
                        <span class="eyebrow">Metalúrgica San Jorge · Gestión industrial</span>
                        <h1>Precisión en cada proceso.<br><span>Control en cada etapa.</span></h1>
                        <p>Pedidos, órdenes de trabajo y producción conectados en un solo lugar. La información de cada proyecto, siempre a mano.</p>
                        <div class="hero-actions">
                            <button class="btn primary" data-auth="login">Entrar al portal <span aria-hidden="true">&rarr;</span></button>
                            <a class="btn outline" href="#servicios">Conocer el sistema</a>
                        </div>
                        <div class="hero-index"><span>01</span><i></i> OPERACIÓN CONECTADA</div>
                    </div>
                    <div class="hero-side-note" aria-hidden="true"><span>MSJ</span><i></i> INDUSTRIA EN MOVIMIENTO</div>
                </section>
                <section class="quote-band">
                    <div><span class="eyebrow">Del taller a la entrega</span><h2>Información clara. Trabajo que avanza.</h2></div>
                    <button class="btn dark" data-auth="register">Solicitar acceso <span aria-hidden="true">&rarr;</span></button>
                </section>
                <section class="public-band services-band" id="servicios">
                    <div class="section-heading"><span class="eyebrow">Un sistema, cada sector</span><h2>La operación bajo control.</h2></div>
                    <div class="service-grid">
                        <article><span class="service-number">01 / PEDIDOS</span><strong>Cada pedido, en contexto.</strong><span>Reuní requerimientos, materiales y fechas de entrega para que el trabajo empiece con toda la información.</span></article>
                        <article><span class="service-number">02 / PRODUCCIÓN</span><strong>Avances visibles.</strong><span>Seguí órdenes de trabajo y avances de producción con trazabilidad entre los equipos.</span></article>
                        <article><span class="service-number">03 / CALIDAD</span><strong>Control hasta el final.</strong><span>Coordiná mantenimiento, depósito y controles de calidad desde un mismo espacio.</span></article>
                    </div>
                </section>
                <section class="public-band company-band split" id="empresa">
                    <div class="company-copy">
                        <span class="eyebrow">Metalúrgica San Jorge</span>
                        <h2>Una industria.<br><span>Un equipo conectado.</span></h2>
                        <p>MetalGest acompaña el trabajo diario de San Jorge: conecta administración con producción y cada sector operativo, para que los pedidos avancen con seguimiento y claridad.</p>
                        <button class="text-link" data-auth="login">Conocer el portal <span aria-hidden="true">&rarr;</span></button>
                    </div>
                    <div class="process-panel">
                        <span class="process-label">UN FLUJO, DE PRINCIPIO A FIN</span>
                        <div class="process-list">
                            <span><b>01</b> Pedido y evaluación</span>
                            <span><b>02</b> Orden de trabajo</span>
                            <span><b>03</b> Producción y seguimiento</span>
                            <span><b>04</b> Calidad y entrega</span>
                        </div>
                    </div>
                </section>
                <section class="benefits-band public-band" id="beneficios">
                    <div class="section-heading"><span class="eyebrow">Hecho para el trabajo real</span><h2>Una mejor forma de avanzar.</h2></div>
                    <div class="benefit-grid">
                        <article><span class="benefit-mark">A</span><h3>Todo conectado</h3><p>Pedidos, órdenes y sectores vinculados para reducir idas y vueltas.</p></article>
                        <article><span class="benefit-mark">B</span><h3>Seguimiento claro</h3><p>Consultá el estado y el avance de cada trabajo con información actualizada.</p></article>
                        <article><span class="benefit-mark">C</span><h3>Acceso por equipo</h3><p>Cada persona encuentra las herramientas de su sector y su rol.</p></article>
                    </div>
                </section>
                <section class="contact-band" id="contacto">
                    <div><span class="eyebrow">Contacto directo</span><h2>¿Empezamos con tu próximo trabajo?</h2><p>Escribinos desde el portal y mantené cada consulta en un solo lugar.</p></div>
                    <div class="contact-actions"><button class="btn primary" data-auth="register">Crear cuenta cliente <span aria-hidden="true">&rarr;</span></button><button class="text-link" data-auth="login">Ya tengo cuenta</button></div>
                </section>
                <footer class="public-footer">
                    <a class="brand-lockup" href="#inicio"><span class="brand-emblem">SJ</span><span class="brand-mark">METALÚRGICA<strong>SAN JORGE</strong></span></a>
                    <span>MetalGest · Gestión industrial</span>
                </footer>
            </main>
            <div class="auth-modal hidden" id="authModal">
                <div class="auth-card">
                    <button class="close-btn" data-close>×</button>
                    <div class="tabs">
                        <button class="tab active" data-tab="login">Ingresar</button>
                        <button class="tab" data-tab="register">Registrarse</button>
                    </div>
                    <div id="authContent"></div>
                </div>
            </div>
        `;
        this.root.querySelectorAll("[data-auth]").forEach(button => button.addEventListener("click", () => this.openAuth(button.dataset.auth)));
        this.root.querySelector("[data-close]").addEventListener("click", () => this.closeAuth());
        this.root.querySelectorAll("[data-tab]").forEach(tab => tab.addEventListener("click", () => this.openAuth(tab.dataset.tab)));
    }

    openAuth(mode) {
        this.root.querySelector("#authModal").classList.remove("hidden");
        this.root.querySelectorAll("[data-tab]").forEach(tab => tab.classList.toggle("active", tab.dataset.tab === mode));
        mode === "register" ? this.renderRegisterForm() : this.renderLoginForm();
    }

    closeAuth() {
        this.root.querySelector("#authModal").classList.add("hidden");
    }

    renderLoginForm() {
        this.root.querySelector("#authContent").innerHTML = `
            <h2>Ingreso al portal</h2>
            <p class="muted">El mismo acceso sirve para empleados y clientes.</p>
            <form id="loginForm" class="form-grid">
                <label class="field full">Usuario<input name="nombre" autocomplete="username" required></label>
                <label class="field full">Contrasena<input name="contrasena" type="password" autocomplete="current-password" required></label>
                <button class="btn primary field full">Ingresar</button>
            </form>
            <div class="message"></div>
        `;
        this.root.querySelector("#loginForm").addEventListener("submit", event => this.login(event));
    }

    renderRegisterForm() {
        this.root.querySelector("#authContent").innerHTML = `
            <h2>Registro de cliente</h2>
            <p class="muted">El registro crea una cuenta con rol Cliente.</p>
            <form id="registerForm" class="form-grid">
                <label>Empresa / nombre<input name="razon_social" required></label>
                <label>CUIT<input name="cuit"></label>
                <label>Email<input name="email" type="email" required></label>
                <label>Telefono<input name="telefono"></label>
                <label class="field full">Direccion<input name="direccion"></label>
                <label>Usuario<input name="usuario" required></label>
                <label>Contrasena<input name="contrasena" type="password" required></label>
                <button class="btn primary field full">Crear cuenta</button>
            </form>
            <div class="message"></div>
        `;
        this.root.querySelector("#registerForm").addEventListener("submit", event => this.register(event));
    }

    async login(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const result = await this.api.post("login", Object.fromEntries(new FormData(form)));
        if (!result.success) {
            form.parentElement.querySelector(".message").textContent = result.message || "No se pudo ingresar.";
            return;
        }
        this.session.set(result.usuario);
        this.renderShell();
    }

    async register(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const result = await this.api.post("cliente_register", Object.fromEntries(new FormData(form)));
        if (!result.success) {
            form.parentElement.querySelector(".message").textContent = result.message || "No se pudo registrar.";
            return;
        }
        this.session.set(result.usuario);
        this.renderShell();
    }

    renderShell() {
        const modules = this.modulesForRole();
        this.root.innerHTML = `
            <div class="app-shell">
                <aside class="sidebar">
                    <div class="brand">METALURGICA<br>SAN JORGE</div>
                    <nav class="nav">${modules.map(module => `<button data-module="${module.id}">${module.label}</button>`).join("")}</nav>
                    <button class="btn" data-logout>Salir</button>
                </aside>
                <main class="main">
                    <div class="topbar">
                        <div>
                            <h2 id="moduleTitle"></h2>
                            <span class="muted">Hola, ${this.escape(`${this.session.user.nombre} ${this.session.user.apellido || ""}`)} - ${this.escape(this.session.user.rol)}</span>
                        </div>
                        <button class="btn" data-refresh>Actualizar</button>
                    </div>
                    <div id="content"></div>
                </main>
            </div>
        `;
        this.root.querySelector("[data-logout]").addEventListener("click", () => {
            this.stopChatTimer();
            this.session.clear();
            this.renderLanding();
        });
        this.root.querySelector("[data-refresh]").addEventListener("click", () => this.openModule(this.current));
        this.root.querySelectorAll("[data-module]").forEach(button => button.addEventListener("click", () => this.openModule(button.dataset.module)));
        this.openModule(modules[0].id);
    }

    modulesForRole() {
        const role = (this.session.user.rol || "").toLowerCase();
        const all = [
            {id: "dashboard", label: "Inicio"},
            {id: "pedidos", label: "Pedidos"},
            {id: "ordenes", label: "Ordenes"},
            {id: "produccion", label: "Produccion"},
            {id: "mantenimiento", label: "Mantenimiento"},
            {id: "deposito", label: "Deposito"},
            {id: "compras", label: "Compras"},
            {id: "calidad", label: "Calidad"},
            {id: "clientes", label: "Clientes"},
            {id: "chat", label: "Chat"}
        ];
        if (role === "cliente") return [{id: "chat", label: "Chat"}];
        if (role === "gerencia") return all;
        if (role === "administracion") return all.filter(m => ["dashboard", "pedidos", "ordenes", "clientes", "chat"].includes(m.id));
        if (role === "produccion") return all.filter(m => ["dashboard", "produccion"].includes(m.id));
        if (role === "mantenimiento") return all.filter(m => ["dashboard", "mantenimiento"].includes(m.id));
        if (role === "deposito") return all.filter(m => ["dashboard", "deposito"].includes(m.id));
        if (role === "compras") return all.filter(m => ["dashboard", "compras"].includes(m.id));
        if (role === "calidad") return all.filter(m => ["dashboard", "calidad"].includes(m.id));
        return [{id: "dashboard", label: "Inicio"}];
    }

    async openModule(id) {
        this.stopChatTimer();
        this.current = id;
        this.root.querySelectorAll("[data-module]").forEach(button => button.classList.toggle("active", button.dataset.module === id));
        const label = this.modulesForRole().find(module => module.id === id)?.label || "Inicio";
        this.root.querySelector("#moduleTitle").textContent = label;
        const views = {
            dashboard: () => this.dashboardView(),
            pedidos: () => this.pedidosView(),
            ordenes: () => this.ordenesView(),
            produccion: () => this.produccionView(),
            mantenimiento: () => this.mantenimientoView(),
            deposito: () => this.depositoView(),
            compras: () => this.comprasView(),
            calidad: () => this.calidadView(),
            clientes: () => this.clientesView(),
            chat: () => this.chatView()
        };
        await views[id]();
    }

    content(html) {
        this.root.querySelector("#content").innerHTML = html;
    }

    async dashboardView() {
        const [pedidos, ordenes, chat] = await Promise.all([
            this.api.get("pedidos_list"),
            this.api.get("ordenes_list"),
            this.api.post("chat_list")
        ]);
        this.content(`
            <section class="cards">
                <div class="card"><strong>${pedidos.length}</strong><span class="muted">Pedidos</span></div>
                <div class="card"><strong>${ordenes.length}</strong><span class="muted">Ordenes</span></div>
                <div class="card"><strong>${chat.filter(c => c.estado === "Abierto").length}</strong><span class="muted">Chats abiertos</span></div>
                <div class="card"><strong>${new Date().toLocaleDateString("es-AR")}</strong><span class="muted">Fecha de trabajo</span></div>
            </section>
            <section class="section-panel"><h3>Actividad reciente</h3>${this.simpleTable(pedidos.slice(0, 6), ["id_pedido", "razon_social", "descripcion", "estado"])}</section>
        `);
    }

    async pedidosView() {
        const [rows, clientes, materiales] = await Promise.all([
            this.api.get("pedidos_list"), this.api.get("clientes_list"), this.api.get("materiales_list")
        ]);
        this.content(`
            <section class="section-panel">
                <h3>Nuevo pedido</h3>
                <form id="pedidoForm" class="form-grid">
                    <label>Cliente<select name="id_cliente">${clientes.map(c => `<option value="${c.id_cliente}">${this.escape(c.razon_social)}</option>`).join("")}</select></label>
                    <label>Material<select name="material">${materiales.map(m => `<option value="${this.escape(m.nombre)}">${this.escape(m.nombre)}</option>`).join("")}</select></label>
                    <label class="field full">Descripcion<input name="descripcion" required></label>
                    <label>Cantidad<input name="cantidad" type="number" min="1" value="1"></label>
                    <label>Entrega<input name="fecha_entrega" type="date"></label>
                    <button class="btn primary">Crear pedido</button>
                </form>
                <div class="message"></div>
            </section>
            <section class="section-panel">${this.simpleTable(rows, ["id_pedido", "razon_social", "descripcion", "cantidad", "material", "fecha_entrega", "estado"])}</section>
        `);
        this.bindPostForm("#pedidoForm", "pedidos_create", () => this.pedidosView());
    }

    async ordenesView() {
        const [ordenes, pedidos] = await Promise.all([this.api.get("ordenes_list"), this.api.get("pedidos_list")]);
        this.content(`
            <section class="section-panel">
                <h3>Generar orden de trabajo</h3>
                <form id="ordenForm" class="form-grid">
                    <label class="field full">Pedido<select name="id_pedido">${pedidos.map(p => `<option value="${p.id_pedido}">#${p.id_pedido} - ${this.escape(p.razon_social)} - ${this.escape(p.descripcion)}</option>`).join("")}</select></label>
                    <label>Inicio<input name="fecha_inicio" type="date"></label>
                    <label>Fecha prevista<input name="fecha_prevista" type="date"></label>
                    <label>Prioridad<select name="prioridad"><option>Media</option><option>Alta</option><option>Baja</option></select></label>
                    <label class="field full">Observaciones<textarea name="observaciones"></textarea></label>
                    <input type="hidden" name="id_usuario" value="${this.session.user.id_usuario}">
                    <button class="btn primary">Generar orden</button>
                </form>
                <div class="message"></div>
            </section>
            <section class="section-panel">
                <h3>Actualizar estado</h3>
                <form id="estadoOrdenForm" class="toolbar">
                    <label>ID orden<input name="id_orden" type="number" required></label>
                    <label>Estado<select name="estado"><option>Pendiente</option><option>En produccion</option><option>Pausada</option><option>Finalizada</option></select></label>
                    <label>Observacion<input name="observaciones"></label>
                    <button class="btn primary">Actualizar</button>
                </form>
                <div class="message"></div>
            </section>
            <section class="section-panel">${this.simpleTable(ordenes, ["id_orden", "id_pedido", "razon_social", "material", "prioridad", "estado", "avance", "fecha_prevista"])}</section>
        `);
        this.bindPostForm("#ordenForm", "ordenes_create", () => this.ordenesView());
        this.bindPostForm("#estadoOrdenForm", "orden_update_status", () => this.ordenesView());
    }

    async produccionView() {
        const [produccion, ordenes, maquinas] = await Promise.all([this.api.get("produccion_list"), this.api.get("ordenes_list"), this.api.get("maquinas_list")]);
        this.content(`
            <section class="section-panel">
                <h3>Registrar avance</h3>
                <form id="produccionForm" class="form-grid">
                    <label class="field full">Orden<select name="id_orden">${ordenes.map(o => `<option value="${o.id_orden}">#${o.id_orden} - ${this.escape(o.razon_social)}</option>`).join("")}</select></label>
                    <label>Cantidad producida<input name="cantidad_producida" type="number" min="0" value="0"></label>
                    <label>Avance %<input name="avance" type="number" min="0" max="100" value="0"></label>
                    <label class="field full">Observaciones<textarea name="observaciones"></textarea></label>
                    <button class="btn primary">Registrar</button>
                </form>
                <div class="message"></div>
            </section>
            <section class="section-panel">
                <h3>Aviso a mantenimiento</h3>
                <form id="fallaForm" class="form-grid">
                    <label>Maquina<select name="id_maquina">${maquinas.map(m => `<option value="${m.id_maquina}">${this.escape(`${m.marca} ${m.modelo} - ${m.numero_identificacion}`)}</option>`).join("")}</select></label>
                    <label>Tipo<select name="tipo"><option>Correctivo</option><option>Preventivo</option></select></label>
                    <label class="field full">Problema<textarea name="problema" required></textarea></label>
                    <input type="hidden" name="id_usuario" value="${this.session.user.id_usuario}">
                    <button class="btn primary">Reportar falla</button>
                </form>
                <div class="message"></div>
            </section>
            <section class="section-panel">${this.simpleTable(produccion, ["id_produccion", "id_orden", "razon_social", "material", "cantidad_producida", "avance", "observaciones"])}</section>
        `);
        this.bindPostForm("#produccionForm", "produccion_register", () => this.produccionView());
        this.bindPostForm("#fallaForm", "mantenimiento_report", () => this.produccionView());
    }

    async mantenimientoView() {
        const [rows, maquinas] = await Promise.all([this.api.get("mantenimientos_list"), this.api.get("maquinas_list")]);
        this.content(`
            <section class="section-panel">
                <h3>Preventivo</h3>
                <form id="preventivoForm" class="form-grid">
                    <label>Maquina<select name="id_maquina">${maquinas.map(m => `<option value="${m.id_maquina}">${this.escape(`${m.marca} ${m.modelo}`)}</option>`).join("")}</select></label>
                    <label>Fecha<input name="fecha" type="date"></label>
                    <label class="field full">Tarea / problema<textarea name="problema" required></textarea></label>
                    <input type="hidden" name="id_usuario" value="${this.session.user.id_usuario}">
                    <button class="btn primary">Crear preventivo</button>
                </form>
                <div class="message"></div>
            </section>
            <section class="section-panel">
                <h3>Actualizar mantenimiento</h3>
                <form id="mantenimientoForm" class="form-grid">
                    <label>ID mantenimiento<input name="id_mantenimiento" type="number" required></label>
                    <label>Estado<select name="estado"><option>Pendiente</option><option>En proceso</option><option>Resuelta</option></select></label>
                    <label class="field full">Reparacion<textarea name="reparacion" required></textarea></label>
                    <label class="field full">Repuestos<input name="repuestos"></label>
                    <button class="btn primary">Actualizar</button>
                </form>
                <div class="message"></div>
            </section>
            <section class="section-panel">${this.simpleTable(rows, ["id_mantenimiento", "numero_identificacion", "tipo", "problema", "reparacion", "repuestos", "estado"])}</section>
        `);
        this.bindPostForm("#preventivoForm", "mantenimiento_preventivo_create", () => this.mantenimientoView());
        this.bindPostForm("#mantenimientoForm", "mantenimiento_update", () => this.mantenimientoView());
    }

    async depositoView() {
        const [materiales, movimientos] = await Promise.all([this.api.get("materiales_list"), this.api.get("deposito_movimientos_list")]);
        this.content(`
            <section class="section-panel">
                <h3>Movimiento de stock</h3>
                <form id="movimientoForm" class="form-grid">
                    <label>Material<select name="id_material">${materiales.map(m => `<option value="${m.id_material}">${this.escape(m.nombre)}</option>`).join("")}</select></label>
                    <label>Tipo<select name="tipo"><option>Ingreso</option><option>Salida</option></select></label>
                    <label>Cantidad<input name="cantidad" type="number" min="0.01" step="0.01"></label>
                    <label>Motivo<input name="motivo"></label>
                    <input type="hidden" name="id_usuario" value="${this.session.user.id_usuario}">
                    <button class="btn primary">Registrar</button>
                </form>
                <div class="message"></div>
            </section>
            <section class="section-panel"><h3>Inventario</h3>${this.simpleTable(materiales, ["id_material", "nombre", "tipo", "unidad", "stock", "stock_minimo", "stock_maximo"])}</section>
            <section class="section-panel"><h3>Movimientos</h3>${this.simpleTable(movimientos, ["id_movimiento", "material", "tipo", "cantidad", "motivo", "fecha"])}</section>
        `);
        this.bindPostForm("#movimientoForm", "deposito_movimiento_create", () => this.depositoView());
    }

    async comprasView() {
        const [proveedores, materiales, compras] = await Promise.all([this.api.get("proveedores_list"), this.api.get("materiales_list"), this.api.get("compras_list")]);
        this.content(`
            <section class="section-panel">
                <h3>Proveedor</h3>
                <form id="proveedorForm" class="form-grid">
                    <label>Razon social<input name="razon_social" required></label>
                    <label>CUIT<input name="cuit"></label>
                    <label>Telefono<input name="telefono"></label>
                    <label>Email<input name="email"></label>
                    <button class="btn primary">Crear proveedor</button>
                </form>
                <div class="message"></div>
            </section>
            <section class="section-panel">
                <h3>Orden de compra</h3>
                <form id="compraForm" class="form-grid">
                    <label>Proveedor<select name="id_proveedor">${proveedores.map(p => `<option value="${p.id_proveedor}">${this.escape(p.razon_social)}</option>`).join("")}</select></label>
                    <label>Material<select name="id_material">${materiales.map(m => `<option value="${m.id_material}">${this.escape(m.nombre)}</option>`).join("")}</select></label>
                    <label>Cantidad<input name="cantidad" type="number" min="0.01" step="0.01"></label>
                    <label>Precio<input name="precio" type="number" min="0" step="0.01"></label>
                    <label>Fecha<input name="fecha" type="date"></label>
                    <label>Estado<select name="estado"><option>Solicitada</option><option>Recibida</option><option>Cancelada</option></select></label>
                    <button class="btn primary">Crear compra</button>
                </form>
                <div class="message"></div>
            </section>
            <section class="section-panel"><h3>Materiales</h3>${this.simpleTable(materiales, ["id_material", "nombre", "stock", "stock_minimo", "stock_maximo"])}</section>
            <section class="section-panel"><h3>Compras</h3>${this.simpleTable(compras, ["id_compra", "proveedor", "detalle", "total", "estado"])}</section>
        `);
        this.bindPostForm("#proveedorForm", "proveedores_create", () => this.comprasView());
        this.bindPostForm("#compraForm", "compras_create", () => this.comprasView());
    }

    async calidadView() {
        const [rows, ordenes] = await Promise.all([this.api.get("calidad_list"), this.api.get("ordenes_list")]);
        this.content(`
            <section class="section-panel">
                <h3>Registrar control</h3>
                <form id="calidadForm" class="form-grid">
                    <label>Orden<select name="id_orden">${ordenes.map(o => `<option value="${o.id_orden}">#${o.id_orden} - ${this.escape(o.razon_social)}</option>`).join("")}</select></label>
                    <label>Fecha<input name="fecha" type="date"></label>
                    <label>Resultado<select name="resultado"><option>Aprobado</option><option>Observado</option><option>Rechazado</option></select></label>
                    <label class="field full">Observaciones<textarea name="observaciones"></textarea></label>
                    <input type="hidden" name="id_usuario" value="${this.session.user.id_usuario}">
                    <button class="btn primary">Registrar control</button>
                </form>
                <div class="message"></div>
            </section>
            <section class="section-panel">${this.simpleTable(rows, ["id_control", "id_orden", "razon_social", "resultado", "observaciones", "fecha"])}</section>
        `);
        this.bindPostForm("#calidadForm", "calidad_register", () => this.calidadView());
    }

    async clientesView() {
        const rows = await this.api.get("clientes_list");
        this.content(`
            <section class="section-panel">
                <h3>Modulo clientes</h3>
                <form id="clienteForm" class="form-grid">
                    <label>ID para editar<input name="id_cliente" type="number" placeholder="Solo si editas"></label>
                    <label>Razon social<input name="razon_social" required></label>
                    <label>CUIT<input name="cuit"></label>
                    <label>Telefono<input name="telefono"></label>
                    <label>Email<input name="email" type="email"></label>
                    <label>Direccion<input name="direccion"></label>
                    <button class="btn primary">Guardar cliente</button>
                </form>
                <form id="deleteClienteForm" class="toolbar">
                    <label>ID cliente a eliminar<input name="id_cliente" type="number" required></label>
                    <button class="btn warn">Eliminar</button>
                </form>
                <div class="message"></div>
            </section>
            <section class="section-panel">${this.simpleTable(rows, ["id_cliente", "razon_social", "cuit", "telefono", "email", "direccion"])}</section>
        `);
        this.root.querySelector("#clienteForm").addEventListener("submit", event => {
            event.preventDefault();
            const data = Object.fromEntries(new FormData(event.currentTarget));
            this.postAndRefresh(data.id_cliente ? "clientes_update" : "clientes_create", data, () => this.clientesView(), event.currentTarget);
        });
        this.bindPostForm("#deleteClienteForm", "clientes_delete", () => this.clientesView());
    }

    async chatView() {
        const isClient = (this.session.user.rol || "").toLowerCase() === "cliente";
        const chats = await this.api.post("chat_list", isClient ? {id_cliente: this.session.user.id_cliente} : {});
        this.activeChat = this.activeChat || chats[0]?.id_chat || null;
        this.content(`
            <section class="chat-layout">
                <aside class="chat-list">
                    <div class="chat-list-head">
                        <h3>Conversaciones</h3>
                        ${isClient ? '<button class="btn primary" data-new-chat>Nueva</button>' : ""}
                    </div>
                    ${chats.length ? chats.map(chat => `
                        <button class="chat-item ${String(chat.id_chat) === String(this.activeChat) ? "active" : ""}" data-chat="${chat.id_chat}">
                            <strong>${this.escape(chat.nombre)}</strong>
                            <span>${this.escape(chat.ultimo_mensaje || chat.asunto)}</span>
                        </button>
                    `).join("") : '<p class="muted">Todavia no hay conversaciones.</p>'}
                </aside>
                <section class="chat-window">
                    <div class="chat-thread" id="chatThread"></div>
                    <form id="chatForm" class="chat-compose">
                        ${isClient && !this.activeChat ? '<input name="asunto" placeholder="Asunto de la consulta">' : ""}
                        <input name="mensaje" placeholder="Escribir mensaje..." autocomplete="off" required>
                        <button class="btn primary">Enviar</button>
                    </form>
                    <div class="message"></div>
                </section>
            </section>
        `);
        this.root.querySelectorAll("[data-chat]").forEach(button => {
            button.addEventListener("click", async () => {
                this.activeChat = button.dataset.chat;
                await this.chatView();
            });
        });
        const newChat = this.root.querySelector("[data-new-chat]");
        if (newChat) {
            newChat.addEventListener("click", async () => {
                this.activeChat = null;
                await this.chatView();
            });
        }
        this.root.querySelector("#chatForm").addEventListener("submit", event => this.sendChat(event, isClient));
        await this.loadMessages();
        this.chatTimer = setInterval(() => this.loadMessages(), 5000);
    }

    async loadMessages() {
        const thread = this.root.querySelector("#chatThread");
        if (!thread) return;
        if (!this.activeChat) {
            thread.innerHTML = '<div class="empty-chat">Escribi tu primer mensaje para abrir una conversacion con Administracion San Jorge.</div>';
            return;
        }
        const messages = await this.api.post("chat_messages", {id_chat: this.activeChat});
        const role = (this.session.user.rol || "").toLowerCase();
        thread.innerHTML = messages.map(message => {
            const mine = role === "cliente" ? message.autor_tipo === "Cliente" : message.autor_tipo === "Administracion";
            return `
                <div class="bubble-row ${mine ? "mine" : "theirs"}">
                    <div class="bubble">
                        <span>${this.escape(message.mensaje)}</span>
                        <small>${this.escape(message.autor_nombre || message.autor_tipo)} · ${this.escape(message.fecha)}</small>
                    </div>
                </div>
            `;
        }).join("");
        thread.scrollTop = thread.scrollHeight;
    }

    async sendChat(event, isClient) {
        event.preventDefault();
        const form = event.currentTarget;
        const data = Object.fromEntries(new FormData(form));
        const payload = {
            id_chat: this.activeChat || "",
            id_usuario: this.session.user.id_usuario,
            id_cliente: this.session.user.id_cliente || "",
            autor_tipo: isClient ? "Cliente" : "Administracion",
            asunto: data.asunto || "Consulta a administracion",
            mensaje: data.mensaje
        };
        const result = await this.api.post(isClient ? "chat_send" : "chat_reply", payload);
        if (!result.success) {
            form.parentElement.querySelector(".message").textContent = result.message || "No se pudo enviar.";
            return;
        }
        this.activeChat = result.id_chat || this.activeChat;
        form.reset();
        await this.chatView();
    }

    stopChatTimer() {
        if (this.chatTimer) {
            clearInterval(this.chatTimer);
            this.chatTimer = null;
        }
    }

    bindPostForm(selector, action, refresh) {
        const form = this.root.querySelector(selector);
        if (!form) return;
        form.addEventListener("submit", event => {
            event.preventDefault();
            this.postAndRefresh(action, Object.fromEntries(new FormData(form)), refresh, form);
        });
    }

    async postAndRefresh(action, data, refresh, form) {
        const message = form.parentElement.querySelector(".message") || this.root.querySelector(".message");
        const result = await this.api.post(action, data);
        message.textContent = result.success ? "Operacion realizada." : (result.message || "No se pudo completar.");
        if (result.success) {
            form.reset();
            await refresh();
        }
    }

    simpleTable(rows, keys) {
        if (!rows || rows.length === 0) return `<p class="muted">No hay registros para mostrar.</p>`;
        return `
            <div class="table-wrap">
                <table>
                    <thead><tr>${keys.map(key => `<th>${this.label(key)}</th>`).join("")}</tr></thead>
                    <tbody>${rows.map(row => `<tr>${keys.map(key => `<td>${this.formatCell(key, row[key])}</td>`).join("")}</tr>`).join("")}</tbody>
                </table>
            </div>
        `;
    }

    label(key) {
        return key.replace(/^id_/, "ID ").replaceAll("_", " ").replace(/\b\w/g, letter => letter.toUpperCase());
    }

    formatCell(key, value) {
        const safe = this.escape(value);
        if (key === "estado") {
            const cls = safe === "Abierto" || safe === "Pendiente" ? "pending" : safe === "Respondido" || safe === "Finalizada" ? "done" : "";
            return `<span class="pill ${cls}">${safe}</span>`;
        }
        return safe;
    }

    escape(value) {
        return String(value ?? "").replace(/[&<>"']/g, char => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
        }[char]));
    }
}

const app = new App(
    document.querySelector("#app"),
    new ApiService("../clases/php/api.php"),
    new SessionStore("metalgest_web_user")
);
app.start();
