import {administracionPanel} from "./administracion.js?v=supervisor-admin-full-20261021-1";
import {produccionPanel} from "./produccion.js?v=supervisors-module-20261021-1";
import {mantenimientoPanel} from "./mantenimiento.js?v=supervisors-module-20261021-1";
import {depositoPanel} from "./deposito.js?v=supervisors-module-20261021-1";
import {comprasPanel} from "./compras.js?v=supervisors-module-20261021-1";
import {calidadPanel} from "./calidad.js?v=supervisors-module-20261021-1";
import {chatPanel} from "./chat.js?v=pedido-chat-flow-20261019-3";
import {chartsPanel} from "./charts.js?v=module-index-20261017-3";
import {seguimientoPanel} from "./seguimiento.js?v=pedido-chat-flow-20261019-3";
import {supervisoresPanel} from "./supervisores.js?v=supervisors-module-20261021-1";
import {supervisorProduccionPanel} from "./supervisor_produccion.js?v=supervisors-module-20261021-1";
import {supervisorMantenimientoPanel} from "./supervisor_mantenimiento.js?v=supervisors-module-20261021-1";
import {supervisorDepositoPanel} from "./supervisor_deposito.js?v=supervisors-module-20261021-1";
import {supervisorComprasPanel} from "./supervisor_compras.js?v=supervisors-module-20261021-1";
import {supervisorCalidadPanel} from "./supervisor_calidad.js?v=supervisors-module-20261021-1";

const panelModules = [chartsPanel, comprasPanel, depositoPanel, calidadPanel, mantenimientoPanel, administracionPanel, produccionPanel, seguimientoPanel, supervisoresPanel, supervisorProduccionPanel, supervisorMantenimientoPanel, supervisorDepositoPanel, supervisorComprasPanel, supervisorCalidadPanel];

class ApiService {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;
    }

    async get(action, params = {}) {
        const query = new URLSearchParams({action, ...params});
        const response = await fetch(`${this.baseUrl}?${query.toString()}`, {credentials: "include"});
        return response.json();
    }

    async post(action, data = {}) {
        const response = await fetch(`${this.baseUrl}?action=${encodeURIComponent(action)}`, {
            method: "POST",
            credentials: "include",
            headers: {"Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"},
            body: new URLSearchParams(data)
        });
        const contentType = response.headers.get("content-type") || "sin Content-Type";
        const body = await response.text();
        try {
            const result = JSON.parse(body);
            if (response.status === 401 && result.success === false) {
                result.authRequired = true;
            }
            return result;
        } catch (error) {
            throw new Error(`La respuesta de ${response.url} fue HTTP ${response.status} ${response.statusText} sin JSON (${contentType}).`);
        }
    }

    async postMultipart(action, formData) {
        const response = await fetch(`${this.baseUrl}?action=${encodeURIComponent(action)}`, {
            method: "POST",
            credentials: "include",
            body: formData
        });
        const contentType = response.headers.get("content-type") || "sin Content-Type";
        const body = await response.text();
        try {
            const result = JSON.parse(body);
            if (response.status === 401 && result.success === false) {
                result.authRequired = true;
            }
            return result;
        } catch (error) {
            throw new Error(`La respuesta de ${response.url} fue HTTP ${response.status} ${response.statusText} sin JSON (${contentType}).`);
        }
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
        this.current = "administracion";
        this.activeChat = null;
        this.chatTimer = null;
        this.presenceTimer = null;
        this.dashboardTimer = null;
        this.trackingTimer = null;
        this.openChatAfterClientLogin = false;
        this.pendingChatDraft = null;
        this.previousBodyOverflow = null;
        this.moduleTemplates = new Map();
    }

    async start() {
        try {
            const moduleNames = ["inicio", "administracion", "produccion", "mantenimiento", "deposito", "compras", "calidad", "chat", "seguimiento", "supervisor_produccion", "supervisor_mantenimiento", "supervisor_deposito", "supervisor_compras", "supervisor_calidad"];
            await Promise.all(moduleNames.map(async moduleName => {
                const response = await fetch(`html/${moduleName}.html?v=supervisors-module-20261021-1`);
                if (!response.ok) throw new Error(`No se pudo cargar html/${moduleName}.html: HTTP ${response.status}.`);
                this.moduleTemplates.set(moduleName, new DOMParser().parseFromString(await response.text(), "text/html"));
            }));
        } catch (error) {
            this.root.innerHTML = `<p class="message">${this.escape(error.message || "No se pudieron cargar las vistas.")}</p>`;
            return;
        }
        const params = new URLSearchParams(window.location.search);
        const resetLinkToken = params.get("restablecer_cliente");
        if (resetLinkToken) {
            window.history.replaceState({}, document.title, window.location.pathname + window.location.hash);
            this.renderLanding();
            this.openAuth("login");
            this.renderClientPasswordLinkForm(resetLinkToken);
            return;
        }
        const verificationToken = params.get("verificar_cliente");
        if (verificationToken) {
            window.history.replaceState({}, document.title, window.location.pathname + window.location.hash);
            this.renderLanding();
            this.openAuth("register");
            this.verifyClientEmail(verificationToken);
            return;
        }
        this.session.user ? this.renderShell() : this.renderLanding();
    }


    renderShell() {
        this.restorePageScroll();
        const modules = this.modulesForRole();
        this.root.innerHTML = `
            <div class="app-shell ${this.session.user.tipo === "cliente" ? "client-chat-shell" : ""}">
                <aside class="sidebar">
                    <div class="brand">METALURGICA<br>SAN JORGE</div>
                    <nav class="nav">${modules.map(module => `<button data-module="${module.id}">${module.label}</button>`).join("")}</nav>
                    ${modules.some(module => module.id === "compras")
                        ? '<section class="sidebar-stock-alerts" data-stock-alerts hidden aria-label="Alertas de inventario"><button class="sidebar-stock-warning" type="button" data-stock-warning><span data-stock-warning-details></span></button></section>'
                        : ""}
                    ${this.session.user.tipo === "cliente"
                        ? '<button class="btn" data-client-site>Volver al sitio</button>'
                        : '<button class="btn" data-logout>Salir</button>'}
                </aside>
                <main class="main">
                    <div class="topbar">
                        <div>
                            <h2 id="moduleTitle"></h2>
                            <span class="muted">Hola, ${this.escape(this.session.user.tipo === "cliente" ? this.session.user.razon_social : `${this.session.user.nombre} ${this.session.user.apellido || ""}`)} - ${this.escape(this.session.user.rol || "Cliente")}</span>
                        </div>
                        <button class="btn" data-refresh>Actualizar</button>
                    </div>
                    <div id="content"></div>
                </main>
            </div>
        `;
        const logoutButton = this.root.querySelector("[data-logout]");
        if (logoutButton) logoutButton.addEventListener("click", () => this.logout());
        this.root.querySelector("[data-refresh]").addEventListener("click", () => this.openModule(this.current));
        const clientSiteButton = this.root.querySelector("[data-client-site]");
        if (clientSiteButton) clientSiteButton.addEventListener("click", () => this.returnToSite());
        this.root.querySelectorAll("[data-module]").forEach(button => button.addEventListener("click", () => this.openModule(button.dataset.module)));
        const stockWarningButton = this.root.querySelector("[data-stock-warning]");
        if (stockWarningButton) {
            stockWarningButton.addEventListener("click", () => this.openModule("compras"));
            this.refreshStockWarnings();
        }
        const initialRole = this.normalizeRole(this.session.user.rol || "");
        const initialModule = ["gerencia", "administrador", "administrador_global", "supervisor_administracion"].includes(initialRole)
            ? "administracion"
            : modules[0].id;
        this.openModule(initialModule);
    }

    normalizeRole(role) {
        return String(role).normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .toLowerCase().replace(/[^a-z0-9]+/g, "_").replace(/^_+|_+$/g, "");
    }

    isGlobalAdministrator() {
        return ["administrador", "administrador_global", "gerencia"].includes(this.normalizeRole(this.session.user.rol || ""));
    }

    canManageEmployees() {
        return this.isGlobalAdministrator() || this.normalizeRole(this.session.user.rol || "") === "supervisor_administracion";
    }

    canManageAllDepartmentRecords() {
        return this.normalizeRole(this.session.user.rol || "") === "supervisor_administracion";
    }

    isDepartmentSupervisor() {
        const role = this.normalizeRole(this.session.user.rol || "");
        return role.startsWith("supervisor_") && role !== "supervisor_administracion";
    }

    moduleForRole(role) {
        const normalizedRole = this.normalizeRole(role);
        const supervisorModules = {
            supervisor_administracion: "administracion",
            supervisor_produccion: "produccion",
            supervisor_mantenimiento: "mantenimiento",
            supervisor_deposito: "deposito",
            supervisor_compras: "compras",
            supervisor_calidad: "calidad"
        };
        if (supervisorModules[normalizedRole]) return supervisorModules[normalizedRole];
        if (["gerencia", "administrador", "administrador_global", "administracion"].includes(normalizedRole)) {
            return "administracion";
        }
        return ["produccion", "mantenimiento", "deposito", "compras", "calidad"].includes(normalizedRole)
            ? normalizedRole
            : null;
    }

    modulesForRole() {
        const role = this.normalizeRole(this.session.user.rol || this.session.user.tipo || "");
        const all = [
            {id: "compras", label: "Compras"},
            {id: "deposito", label: "Depósito"},
            {id: "calidad", label: "Calidad"},
            {id: "mantenimiento", label: "Mantenimiento"},
            {id: "administracion", label: "Administración"},
            {id: "produccion", label: "Producción"},
            {id: "chat", label: "Chat"}
        ];
        if (role === "cliente") return [{id: "chat", label: "Mensajes"}, {id: "seguimiento", label: "Mis pedidos"}];
        if (["gerencia", "administrador", "administrador_global", "supervisor_administracion"].includes(role)) return all;
        const module = this.moduleForRole(role);
        if (module === "administracion") {
            return all.filter(module => ["administracion", "chat"].includes(module.id));
        }
        if (module) return all.filter(item => item.id === module);
        return [{id: "administracion", label: "Administración"}];
    }

    async openModule(id) {
        this.stopChatTimer();
        clearInterval(this.dashboardTimer);
        clearInterval(this.trackingTimer);
        this.dashboardTimer = null;
        this.trackingTimer = null;
        this.current = id;
        this.root.querySelector(".app-shell")?.classList.toggle("is-chat-shell", id === "chat");
        this.root.querySelector("#content").dataset.module = id;
        this.root.querySelectorAll("[data-module]").forEach(button => button.classList.toggle("active", button.dataset.module === id));
        const label = this.modulesForRole().find(module => module.id === id)?.label || "Inicio";
        this.root.querySelector("#moduleTitle").textContent = label;
        const views = {
            administracion: () => this.dashboardView(),
            produccion: () => this.produccionView(),
            mantenimiento: () => this.mantenimientoView(),
            deposito: () => this.depositoView(),
            compras: () => this.comprasView(),
            calidad: () => this.calidadView(),
            chat: () => this.chatView(),
            seguimiento: () => this.seguimientoView()
        };
        try {
            await views[id]();
        } catch (error) {
            console.error(`No se pudo abrir el módulo ${id}:`, error);
            this.content(`<section class="section-panel"><div class="message">${this.escape(error.message || "No se pudo cargar esta pantalla. Actualizá e intentá de nuevo.")}</div></section>`);
        }
    }

    async logout() {
        try {
            const result = await this.api.post("logout");
            if (!result.success) {
                throw new Error(result.message || "No se pudo cerrar la sesión.");
            }
            this.stopChatTimer();
            clearInterval(this.dashboardTimer);
            clearInterval(this.trackingTimer);
            this.dashboardTimer = null;
            this.trackingTimer = null;
            this.session.clear();
            this.renderLanding();
        } catch (error) {
            const content = this.root.querySelector("#content");
            const clientGreeting = this.root.querySelector(".client-greeting");
            if (content) {
                content.innerHTML = `<div class="message">${this.escape(error.message || "No se pudo cerrar la sesión.")}</div>`;
            } else if (clientGreeting) {
                clientGreeting.textContent = error.message || "No se pudo cerrar la sesión.";
            }
        }
    }

    content(html) {
        const content = this.root.querySelector("#content");
        content.innerHTML = html;
        content.querySelectorAll("[data-table-search]").forEach(input => {
            const container = input.closest("[data-searchable-table]");
            if (!container) return;
            const rows = [...container.querySelectorAll("tbody tr")];
            const limit = Number(container.dataset.tableLimit) || 8;
            const updateResults = () => {
                const query = this.normalizeSearchText(input.value.trim());
                const matchingRows = rows.filter(row =>
                    !query || this.normalizeSearchText(row.textContent).includes(query)
                );
                const visibleRows = matchingRows.slice(0, limit);
                const visibleSet = new Set(visibleRows);
                rows.forEach(row => {
                    row.hidden = !visibleSet.has(row);
                });
                const count = container.querySelector("[data-table-count]");
                const empty = container.querySelector("[data-table-empty]");
                const noun = matchingRows.length === 1
                    ? container.dataset.tableSingular
                    : container.dataset.tablePlural;
                count.textContent = query
                    ? `Mostrando ${visibleRows.length} de ${matchingRows.length} resultados`
                    : `Mostrando ${visibleRows.length} de ${rows.length} ${noun}`;
                empty.hidden = matchingRows.length !== 0;
            };
            input.addEventListener("input", updateResults);
            updateResults();
        });
    }

    moduleTemplate(moduleName, viewName, values = {}) {
        const documentTemplate = this.moduleTemplates.get(moduleName);
        if (!documentTemplate) throw new Error(`No se cargaron las vistas de ${moduleName}.`);
        const template = documentTemplate.querySelector(`template[data-view="${viewName}"]`);
        if (!template) {
            throw new Error(`No existe la vista "${viewName}" en html/${moduleName}.html.`);
        }
        return template.innerHTML.replace(/\{\{([A-Za-z0-9_]+)\}\}/g, (token, key) => {
            if (!Object.hasOwn(values, key)) {
                throw new Error(`Falta completar ${token} en html/${moduleName}.html.`);
            }
            return String(values[key]);
        });
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
        const table = `
            <div class="table-wrap">
                <table>
                    <thead><tr>${keys.map(key => `<th>${this.label(key)}</th>`).join("")}</tr></thead>
                    <tbody>${rows.map(row => `<tr>${keys.map(key => `<td>${this.formatCell(key, row[key])}</td>`).join("")}</tr>`).join("")}</tbody>
                </table>
                <p class="table-no-results" data-table-empty hidden>No se encontraron registros con ese criterio.</p>
            </div>
        `;
        return this.searchableTable(table, rows.length);
    }

    searchableTable(table, count, noun = "registros") {
        const singular = noun === "materiales" ? "material" : noun.replace(/s$/, "");
        return `
            <div class="data-table" data-searchable-table data-table-limit="8" data-table-singular="${this.escape(singular)}" data-table-plural="${this.escape(noun)}">
                <div class="table-search">
                    <label class="table-search-label">
                        <span>Buscar en este listado · máximo 8 resultados visibles</span>
                        <input type="search" data-table-search placeholder="Buscar por cualquier dato..." autocomplete="off">
                    </label>
                    <span class="table-search-count" data-table-count aria-live="polite">Mostrando ${Math.min(count, 8)} de ${count} ${this.escape(count === 1 ? singular : noun)}</span>
                </div>
                ${table}
            </div>
        `;
    }

    normalizeSearchText(value) {
        return String(value ?? "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLocaleLowerCase("es");
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

class PublicModule {
    renderLanding() {
        this.stopChatTimer();
        const client = this.session.user?.tipo === "cliente" ? this.session.user : null;
        this.root.innerHTML = this.moduleTemplate("inicio", "landing", {
            publicActions: client
                ? `<details class="client-profile">
                    <summary class="client-profile-toggle" aria-label="Perfil de ${this.escape(client.razon_social)}">
                        <span class="client-greeting">Hola, <strong>${this.escape(client.razon_social)}</strong></span>
                        <span class="client-profile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <circle cx="12" cy="8" r="3.5"></circle>
                                <path d="M4.8 20c.5-3.5 3.4-5.5 7.2-5.5s6.7 2 7.2 5.5"></path>
                            </svg>
                        </span>
                    </summary>
                    <div class="client-profile-dropdown">
                        <button class="client-profile-action" type="button" data-client-action="desactivar">Desactivar cuenta</button>
                    </div>
                </details>`
                : '<button class="btn" data-auth="login">Ingresar</button><button class="btn primary" data-auth="register">Solicitar acceso</button>',
            menuActions: "",
            heroAccess: client
                ? '<button class="btn outline" data-client-chat>Acceso al portal</button>'
                : '<button class="btn outline" data-auth="login">Acceso al portal</button>',
            quoteAccess: client
                ? '<button class="btn dark" data-client-chat>Acceso al portal <span aria-hidden="true">&rarr;</span></button>'
                : '<button class="btn dark" data-auth="login">Acceso al portal <span aria-hidden="true">&rarr;</span></button>',
            companyAccess: client
                ? '<button class="text-link" data-client-chat>Ingresar a MetalGest <span aria-hidden="true">&rarr;</span></button>'
                : '<button class="text-link" data-auth="login">Ingresar a MetalGest <span aria-hidden="true">&rarr;</span></button>',
            contactAccess: client
                ? '<button class="text-link" data-client-chat>Iniciar sesión</button>'
                : '<button class="text-link" data-auth="login">Iniciar sesión</button>'
        });
        this.root.querySelectorAll("[data-auth]").forEach(button =>
            button.addEventListener("click", () => {
                this.closePublicMenu();
                this.openChatAfterClientLogin = false;
                this.openAuth(button.dataset.auth);
            })
        );
        const menuToggle = this.root.querySelector("[data-menu-toggle]");
        menuToggle.addEventListener("click", () => this.togglePublicMenu(menuToggle));
        this.root.querySelectorAll(".public-menu nav a").forEach(link =>
            link.addEventListener("click", () => this.closePublicMenu())
        );
        this.root.querySelector("[data-consultation]").addEventListener("click", () => this.startConsultation());
        this.root.querySelector("[data-floating-chat]").addEventListener("click", () => this.startConsultation());
        this.root.querySelectorAll("[data-client-chat]").forEach(button =>
            button.addEventListener("click", () => this.renderShell())
        );
        this.root.querySelectorAll("[data-client-action]").forEach(button =>
            button.addEventListener("click", () => this.clientAccountAction(button.dataset.clientAction))
        );
        this.root.querySelector("[data-close]").addEventListener("click", () => this.closeAuth());
        this.root.querySelectorAll("[data-tab]").forEach(tab =>
            tab.addEventListener("click", () => this.openAuth(tab.dataset.tab))
        );
    }

    togglePublicMenu(button) {
        const isOpen = button.getAttribute("aria-expanded") === "true";
        button.setAttribute("aria-expanded", String(!isOpen));
        button.setAttribute("aria-label", isOpen ? "Abrir menú" : "Cerrar menú");
        this.root.querySelector(".public-nav").classList.toggle("menu-open", !isOpen);
    }

    closePublicMenu() {
        const button = this.root.querySelector("[data-menu-toggle]");
        if (!button) return;
        button.setAttribute("aria-expanded", "false");
        button.setAttribute("aria-label", "Abrir menú");
        this.root.querySelector(".public-nav").classList.remove("menu-open");
    }

    startConsultation() {
        if (this.session.user?.tipo === "cliente") {
            this.renderShell();
            return;
        }
        this.openChatAfterClientLogin = true;
        this.openAuth("login");
    }
}

class AuthModule {
    openAuth(mode) {
        const modal = this.root.querySelector("#authModal");
        if (modal.classList.contains("hidden")) {
            this.previousBodyOverflow = document.body.style.overflow;
            document.body.style.overflow = "hidden";
        }
        modal.classList.remove("hidden");
        const tabMode = mode === "register" ? "register" : "login";
        this.root.querySelectorAll("[data-tab]").forEach(tab => {
            const active = tab.dataset.tab === tabMode;
            tab.classList.toggle("active", active);
            tab.setAttribute("aria-selected", String(active));
            tab.tabIndex = active ? 0 : -1;
        });
        if (mode === "register") this.renderRegisterForm();
        else this.renderLoginForm();
    }

    closeAuth() {
        this.root.querySelector("#authModal").classList.add("hidden");
        this.restorePageScroll();
    }

    restorePageScroll() {
        if (this.previousBodyOverflow === null) return;
        document.body.style.overflow = this.previousBodyOverflow;
        this.previousBodyOverflow = null;
    }

    renderLoginForm(initialMessage = "", email = "") {
        this.root.querySelector("#authContent").innerHTML = this.moduleTemplate("inicio", "login");
        const identifier = this.root.querySelector('#loginForm input[name="identificador"]');
        identifier.value = email;
        this.root.querySelector("#authContent .message").textContent = initialMessage;
        this.root.querySelector("#loginForm").addEventListener("submit", event => this.login(event));
        this.root.querySelector("[data-client-forgot]").addEventListener("click", () => this.renderClientPasswordRequestForm());
    }

    renderClientPasswordRequestForm() {
        this.clearCodeResendCooldown();
        this.root.querySelector("#authContent").innerHTML = this.moduleTemplate("inicio", "password-request");
        this.root.querySelector("#clientPasswordRequestForm").addEventListener("submit", event => this.requestClientPasswordCode(event));
        this.root.querySelector("[data-client-login-back]").addEventListener("click", () => this.renderLoginForm());
    }

    renderClientPasswordLinkForm(token) {
        this.clearCodeResendCooldown();
        this.root.querySelector("#authContent").innerHTML = this.moduleTemplate("inicio", "password-link");
        this.root.querySelector("#clientPasswordLinkForm").addEventListener("submit", event => this.resetClientPasswordFromLink(event, token));
    }

    async resetClientPasswordFromLink(event, token) {
        event.preventDefault();
        const form = event.currentTarget;
        const data = Object.fromEntries(new FormData(form));
        const message = form.parentElement.querySelector(".message");
        if (data.contrasena !== data.confirmar) {
            message.textContent = "Las contraseñas no coinciden.";
            return;
        }
        const result = await this.api.post("cliente_password_reset_link", {token, codigo: data.codigo, contrasena: data.contrasena});
        if (!result.success) {
            message.textContent = result.message || "No se pudo cambiar la contraseña.";
            return;
        }
        this.renderLoginForm();
        this.root.querySelector("#authContent .message").textContent = result.message;
    }

    async requestClientPasswordCode(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const email = form.elements.email.value.trim();
        const submitButton = form.querySelector('button[type="submit"], button:not([type])');
        const message = form.parentElement.querySelector(".message");
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = "Enviando código...";
        message.textContent = "Estamos enviando el código. Puede tardar unos segundos.";
        try {
            const result = await this.api.post("cliente_password_request", {email});
            if (!result.success) {
                message.textContent = result.message || "No se pudo enviar el código.";
                return;
            }
            this.renderClientPasswordResetForm(email, result.message);
        } catch (error) {
            console.error("Error al solicitar recuperación de contraseña:", error);
            message.textContent = error.message || "No se pudo conectar con el servidor.";
        } finally {
            if (submitButton.isConnected) {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        }
    }

    renderClientPasswordResetForm(email, initialMessage = "") {
        this.root.querySelector("#authContent").innerHTML = this.moduleTemplate("inicio", "password-reset", {
            email: this.escape(email),
            message: this.escape(initialMessage)
        });
        this.root.querySelector("#clientPasswordResetForm").addEventListener("submit", event => this.resetClientPassword(event));
        this.root.querySelector("[data-resend-code]").addEventListener("click", event => this.resendClientCode(event.currentTarget));
        this.startCodeResendCooldown(this.root.querySelector("[data-resend-code]"));
    }

    startCodeResendCooldown(button, seconds = 30) {
        this.clearCodeResendCooldown();
        const unlockAt = Date.now() + seconds * 1000;
        const updateButton = () => {
            const remaining = Math.max(0, Math.ceil((unlockAt - Date.now()) / 1000));
            button.disabled = remaining > 0;
            button.textContent = remaining ? `Reenviar código en ${remaining}s` : "Reenviar código";
            if (!remaining) {
                clearInterval(this.codeResendTimer);
                this.codeResendTimer = null;
            }
        };
        updateButton();
        if (seconds > 0) this.codeResendTimer = setInterval(updateButton, 1000);
    }

    clearCodeResendCooldown() {
        if (!this.codeResendTimer) return;
        clearInterval(this.codeResendTimer);
        this.codeResendTimer = null;
    }

    async resendClientCode(button) {
        const form = button.parentElement.querySelector("form");
        const message = button.parentElement.querySelector(".message");
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = "Enviando...";
        try {
            const result = await this.api.post("cliente_resend_code", {
                email: form.elements.email.value.trim(),
                proposito: button.dataset.purpose
            });
            message.textContent = result.message || (result.success ? "Revisá tu correo." : "No se pudo reenviar el código.");
            if (result.success) {
                this.startCodeResendCooldown(button);
            } else if (result.retry_after) {
                this.startCodeResendCooldown(button, Number(result.retry_after));
            } else {
                button.disabled = false;
                button.textContent = originalText;
            }
        } catch (error) {
            message.textContent = error.message || "No se pudo conectar con el servidor.";
            button.disabled = false;
            button.textContent = originalText;
        }
    }

    async resetClientPassword(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const data = Object.fromEntries(new FormData(form));
        const message = form.parentElement.querySelector(".message");
        if (data.contrasena !== data.confirmar) {
            message.textContent = "Las contraseñas no coinciden.";
            return;
        }
        delete data.confirmar;
        const result = await this.api.post("cliente_password_reset", data);
        message.textContent = result.message || (result.success ? "Contraseña actualizada." : "No se pudo cambiar la contraseña.");
        if (result.success) {
            this.renderLoginForm();
            this.root.querySelector("#authContent .message").textContent = result.message;
        }
    }

    renderRegisterForm() {
        this.clearCodeResendCooldown();
        this.root.querySelector("#authContent").innerHTML = this.moduleTemplate("inicio", "register");
        this.root.querySelector("#registerForm").addEventListener("submit", event => this.register(event));
    }

    async login(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const identifier = form.elements.identificador.value.trim();
        const contrasena = form.elements.contrasena.value;
        const isClient = identifier.includes("@");
        const message = form.parentElement.querySelector(".message");
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = "Ingresando...";
        message.textContent = "";
        try {
            const result = await this.api.post(
                isClient ? "cliente_login" : "login",
                isClient ? {email: identifier, contrasena} : {usuario: identifier, contrasena}
            );
            if (!result.success) {
                message.textContent = result.message || "No se pudo ingresar.";
                return;
            }
            if (isClient) {
                this.session.set(result.cliente);
                this.openChatAfterClientLogin = false;
                this.closeAuth();
                this.renderShell();
                return;
            }
            this.openChatAfterClientLogin = false;
            this.session.set(result.usuario);
            this.renderShell();
        } catch (error) {
            console.error("Error al iniciar sesión:", error);
            message.textContent = error.message || "No se pudo conectar con el servidor.";
        } finally {
            if (submitButton.isConnected) {
                submitButton.disabled = false;
                submitButton.textContent = "Ingresar";
            }
        }
    }

    async clientAccountAction(action) {
        const deleting = action === "eliminar";
        this.root.insertAdjacentHTML("beforeend", this.moduleTemplate("inicio", "client-account-dialog", {
            actionTitle: deleting ? "Eliminar cuenta" : "Desactivar cuenta",
            actionDescription: deleting
                ? "Se quitará el acceso. Se conserva el historial de pedidos y chats."
                : "La cuenta y tus credenciales se conservarán, pero no podrás iniciar sesión mientras esté desactivada. Contactá a la empresa para volver a habilitar el acceso.",
            buttonClass: deleting ? "warn" : "primary",
            submitLabel: deleting ? "Eliminar acceso" : "Desactivar cuenta"
        }));
        const dialog = this.root.querySelector(".client-account-dialog");
        const form = dialog.querySelector("[data-client-account-confirm]");
        dialog.showModal();
        dialog.querySelector("[data-account-cancel]").addEventListener("click", () => dialog.close());
        dialog.addEventListener("close", () => dialog.remove(), {once: true});
        form.addEventListener("submit", async event => {
            event.preventDefault();
            const submitButton = form.querySelector('button[type="submit"]');
            const message = form.querySelector(".message");
            submitButton.disabled = true;
            message.textContent = "Procesando...";
            try {
                const result = await this.api.post("cliente_account_action", {
                    id_cliente: this.session.user.id_cliente,
                    accion: action,
                    contrasena: form.elements.contrasena.value
                });
                if (!result.success) {
                    message.textContent = result.message || "No se pudo completar la acción.";
                    submitButton.disabled = false;
                    return;
                }
                this.session.clear();
                dialog.close();
                this.renderLanding();
            } catch (error) {
                message.textContent = error.message || "No se pudo conectar con el servidor.";
                submitButton.disabled = false;
            }
        });
        dialog.querySelector('input[name="contrasena"]').focus();
    }

    async register(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const data = Object.fromEntries(new FormData(form));
        const message = form.parentElement.querySelector(".message");
        const submitButton = form.querySelector('button[type="submit"], button:not([type])');
        if (data.contrasena !== data.confirmar) {
            message.textContent = "Las contraseñas no coinciden.";
            return;
        }
        delete data.confirmar;
        submitButton.disabled = true;
        submitButton.textContent = "Registrando...";
        try {
            const result = await this.api.post("cliente_register", data);
            if (!result.success) {
                message.textContent = result.message || "No se pudo registrar la empresa.";
                return;
            }
            this.renderLoginForm(result.message, data.email);
        } catch (error) {
            console.error("Error al registrar cliente:", error);
            message.textContent = error.message || "No se pudo conectar con el servidor.";
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = "Registrar cliente";
        }
    }

    renderClientVerificationForm(email, initialMessage = "") {
        this.root.querySelector("#authContent").innerHTML = this.moduleTemplate("inicio", "verification", {
            email: this.escape(email),
            message: this.escape(initialMessage)
        });
        this.root.querySelector("#clientVerificationForm").addEventListener("submit", event => this.verifyClientCode(event));
        this.root.querySelector("[data-resend-code]").addEventListener("click", event => this.resendClientCode(event.currentTarget));
        this.startCodeResendCooldown(this.root.querySelector("[data-resend-code]"));
    }

    async verifyClientCode(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const message = form.parentElement.querySelector(".message");
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = "Verificando...";
        try {
            const result = await this.api.post("cliente_verify_code", Object.fromEntries(new FormData(form)));
            if (!result.success) {
                message.textContent = result.message || "El código no es válido.";
                return;
            }
            this.renderLoginForm();
            this.root.querySelector("#authContent .message").textContent = result.message;
        } catch (error) {
            console.error("Error al verificar el correo:", error);
            message.textContent = error.message || "No se pudo conectar con el servidor.";
        } finally {
            if (submitButton.isConnected) {
                submitButton.disabled = false;
                submitButton.textContent = "Verificar correo";
            }
        }
    }

    async verifyClientEmail(token) {
        const result = await this.api.post("cliente_verify", {token});
        const message = this.root.querySelector("#authContent .message");
        if (message) message.textContent = result.message || "No se pudo verificar el correo.";
    }
}

for (const module of [...panelModules, chatPanel, PublicModule.prototype, AuthModule.prototype]) {
    const descriptors = Object.getOwnPropertyDescriptors(module);
    delete descriptors.constructor;
    Object.defineProperties(App.prototype, descriptors);
}

const app = new App(
    document.querySelector("#app"),
    new ApiService(["5500", "5501"].includes(window.location.port)
        ? `${window.location.protocol}//${window.location.hostname}/MetalGest/clases/php/api.php`
        : "clases/php/api.php"),
    new SessionStore("metalgest_web_user")
);
app.start();
