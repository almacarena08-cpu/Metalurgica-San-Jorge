import {translateElement} from "./idioma.js?v=language-settings-20261021-1";

const supervisorRecordFields = {
    cliente: [
        {key: "razon_social", label: "Razón social", type: "text"},
        {key: "cuit", label: "CUIT", type: "text"},
        {key: "email", label: "Email", type: "email"},
        {key: "direccion", label: "Dirección", type: "text"}
    ],
    pedido: [
        {key: "descripcion", label: "Descripción", type: "textarea"},
        {key: "cantidad", label: "Cantidad", type: "number", min: 1},
        {key: "material", label: "Material", type: "text"},
        {key: "fecha_entrega", label: "Fecha de entrega", type: "date", required: false}
    ],
    orden: [
        {key: "fecha_prevista", label: "Fecha prevista", type: "date", required: false},
        {key: "prioridad", label: "Prioridad", type: "select", options: ["Baja", "Media", "Alta", "Urgente"]},
        {key: "observaciones", label: "Observaciones", type: "textarea"}
    ],
    produccion: [
        {key: "cantidad_producida", label: "Cantidad producida", type: "number", min: 0},
        {key: "avance", label: "Avance (%)", type: "number", min: 0, max: 100},
        {key: "observaciones", label: "Observaciones", type: "textarea"}
    ],
    mantenimiento: [
        {key: "problema", label: "Problema", type: "textarea"},
        {key: "reparacion", label: "Reparación", type: "textarea"},
        {key: "repuestos", label: "Repuestos", type: "textarea"},
        {key: "estado", label: "Estado", type: "select", options: ["Pendiente", "En proceso", "Resuelta"]}
    ],
    material: [
        {key: "nombre", label: "Nombre", type: "text"},
        {key: "tipo", label: "Tipo", type: "text"},
        {key: "unidad", label: "Unidad", type: "text"},
        {key: "espesor", label: "Espesor", type: "text"},
        {key: "stock_minimo", label: "Stock mínimo", type: "number", min: 0, step: "0.01"},
        {key: "stock_maximo", label: "Stock máximo", type: "number", min: 0, step: "0.01"}
    ],
    movimiento: [
        {key: "tipo", label: "Tipo", type: "select", options: ["Ingreso", "Salida"]},
        {key: "cantidad", label: "Cantidad", type: "number", min: 0.01, step: "0.01"},
        {key: "motivo", label: "Motivo", type: "textarea"}
    ],
    proveedor: [
        {key: "razon_social", label: "Razón social", type: "text"},
        {key: "cuit", label: "CUIT", type: "text"},
        {key: "telefono", label: "Teléfono", type: "text"},
        {key: "email", label: "Email", type: "email"}
    ],
    compra: [
        {key: "fecha", label: "Fecha", type: "date"},
        {key: "estado", label: "Estado", type: "select", options: ["Solicitada", "Recibida", "Cancelada"]}
    ],
    calidad: [
        {key: "fecha", label: "Fecha", type: "date"},
        {key: "resultado", label: "Resultado", type: "select", options: ["Aprobado", "Observado", "Rechazado"]},
        {key: "observaciones", label: "Observaciones", type: "textarea"}
    ]
};

const supervisorRecordIdFields = {
    cliente: "id_cliente",
    pedido: "id_pedido",
    orden: "id_orden",
    produccion: "id_produccion",
    mantenimiento: "id_mantenimiento",
    material: "id_material",
    movimiento: "id_movimiento",
    proveedor: "id_proveedor",
    compra: "id_compra",
    calidad: "id_control"
};

const supervisorRecordLabels = {
    cliente: "cliente",
    pedido: "pedido",
    orden: "orden de trabajo",
    produccion: "avance de producción",
    mantenimiento: "intervención de mantenimiento",
    material: "material",
    movimiento: "movimiento de stock",
    proveedor: "proveedor",
    compra: "compra",
    calidad: "control de calidad"
};

export const supervisoresPanel = {
    async supervisorRows(action) {
        const rows = await this.api.get(action);
        if (!Array.isArray(rows)) {
            const message = rows && typeof rows === "object" ? rows.message : null;
            throw new Error(message || `No se pudieron cargar los registros (${action}).`);
        }
        return rows;
    },

    renderSupervisorRecords(sections, moduleName, refresh) {
        this.content(this.supervisorRecordsPage(sections, moduleName));
        this.bindSupervisorRecordActions(refresh);
    },

    supervisorRecordsTable(rows, keys, entity, idField, noun) {
        if (!rows.length) return `<p class="muted">No hay ${this.escape(noun)} para mostrar.</p>`;
        const table = `
            <div class="table-wrap">
                <table>
                    <thead><tr>${keys.map(key => `<th>${this.label(key)}</th>`).join("")}<th>Acciones</th></tr></thead>
                    <tbody>${rows.map(row => `
                        <tr class="supervisor-record-row" title="Doble clic para editar este registro">
                            ${keys.map(key => `<td>${this.formatCell(key, row[key])}</td>`).join("")}
                            <td class="employee-actions">
                                <button class="btn" type="button" data-supervisor-edit="${this.escape(row[idField])}"
                                    data-entity="${this.escape(entity)}" data-record="${this.escape(JSON.stringify(row))}">Editar</button>
                                <button class="btn warn" type="button" data-supervisor-delete="${this.escape(row[idField])}"
                                    data-entity="${this.escape(entity)}">Eliminar</button>
                            </td>
                        </tr>`).join("")}
                    </tbody>
                </table>
                <p class="table-no-results" data-table-empty hidden>No se encontraron registros con ese criterio.</p>
            </div>
        `;
        return this.searchableTable(table, rows.length, noun);
    },

    supervisorRecordsPage(sections, moduleName) {
        const tabs = sections.length > 1 ? `
            <nav class="supervisor-records-tabs" role="tablist" aria-label="Categorías de registros">
                ${sections.map((section, index) => `
                    <button class="supervisor-record-tab${index === 0 ? " active" : ""}" id="supervisor-record-tab-${index}"
                        type="button" role="tab" aria-controls="supervisor-record-panel-${index}"
                        aria-selected="${index === 0}" tabindex="${index === 0 ? "0" : "-1"}" data-supervisor-tab="${index}">
                        ${this.escape(section.title)}
                    </button>
                `).join("")}
            </nav>
        ` : "";
        const panels = sections.map((section, index) => `
            <section class="section-panel supervisor-records-section" id="supervisor-record-panel-${index}"
                ${sections.length > 1 ? `role="tabpanel" aria-labelledby="supervisor-record-tab-${index}"${index === 0 ? "" : " hidden"}` : ""}>
                <div class="module-panel-heading">
                    <div><span class="module-kicker">REGISTROS</span><h3>${this.escape(section.title)}</h3></div>
                </div>
                ${section.table}
            </section>
        `).join("");
        return this.moduleTemplate(moduleName, "records", {
            name: this.escape(this.session.user.nombre || "Supervisor"),
            role: this.escape(this.session.user.rol || "Supervisor"),
            tabs,
            panels
        });
    },

    bindSupervisorRecordActions(refresh) {
        this.root.querySelectorAll("[data-supervisor-tab]").forEach(tab => {
            tab.addEventListener("click", () => {
                const selectedIndex = tab.dataset.supervisorTab;
                this.root.querySelectorAll("[data-supervisor-tab]").forEach(otherTab => {
                    const active = otherTab === tab;
                    otherTab.classList.toggle("active", active);
                    otherTab.setAttribute("aria-selected", String(active));
                    otherTab.tabIndex = active ? 0 : -1;
                });
                this.root.querySelectorAll(".supervisor-records-section[role='tabpanel']").forEach(panel => {
                    panel.hidden = panel.id !== `supervisor-record-panel-${selectedIndex}`;
                });
            });
        });
        this.root.querySelectorAll("[data-supervisor-edit]").forEach(button => {
            const openEditor = () => {
                const entity = button.dataset.entity;
                const fields = supervisorRecordFields[entity];
                if (!fields) throw new Error(`No existe un formulario de edición para ${entity}.`);
                const record = JSON.parse(button.dataset.record);
                const recordId = this.escape(record[supervisorRecordIdFields[entity]]);
                const dialog = document.createElement("dialog");
                dialog.className = "supervisor-record-dialog";
                dialog.setAttribute("aria-labelledby", "supervisorRecordTitle");
                dialog.innerHTML = `
                    <form class="supervisor-record-form" data-supervisor-record-form>
                        <header class="supervisor-dialog-header">
                            <div>
                                <span class="supervisor-dialog-kicker">MODIFICAR REGISTRO</span>
                                <h2 id="supervisorRecordTitle">Editar ${this.escape(supervisorRecordLabels[entity])}</h2>
                                <p>Registro <span>#${recordId}</span> · Revisá los datos antes de guardar.</p>
                            </div>
                            <button class="supervisor-dialog-close" type="button" data-close-dialog aria-label="Cerrar">×</button>
                        </header>
                        <div class="supervisor-dialog-fields">
                            ${fields.map(field => {
                                const value = this.escape(record[field.key] ?? "");
                                if (field.type === "textarea") {
                                    return `<label class="supervisor-dialog-field full">${field.label}<textarea name="${field.key}">${value}</textarea></label>`;
                                }
                                if (field.type === "select") {
                                    return `<label class="supervisor-dialog-field">${field.label}<select name="${field.key}">${field.options.map(option =>
                                        `<option${record[field.key] === option ? " selected" : ""}>${option}</option>`
                                    ).join("")}</select></label>`;
                                }
                                return `<label class="supervisor-dialog-field">${field.label}<input name="${field.key}" type="${field.type}" value="${value}"${field.min !== undefined ? ` min="${field.min}"` : ""}${field.max !== undefined ? ` max="${field.max}"` : ""}${field.step ? ` step="${field.step}"` : ""}${field.required === false ? "" : " required"}></label>`;
                            }).join("")}
                        </div>
                        <p class="supervisor-dialog-message" data-record-message aria-live="polite" hidden></p>
                        <footer class="supervisor-dialog-actions">
                            <button class="btn" type="button" data-close-dialog>Cancelar</button>
                            <button class="btn primary" type="submit">Guardar cambios</button>
                        </footer>
                    </form>
                `;
                this.root.append(dialog);
                translateElement(dialog, this.language);
                dialog.querySelectorAll("[data-close-dialog]").forEach(closeButton => closeButton.addEventListener("click", () => dialog.close()));
                dialog.addEventListener("close", () => dialog.remove(), {once: true});
                dialog.querySelector("form").addEventListener("submit", async event => {
                    event.preventDefault();
                    const form = event.currentTarget;
                    const message = dialog.querySelector("[data-record-message]");
                    const submitButton = form.querySelector("[type='submit']");
                    const data = Object.fromEntries(new FormData(form));
                    data.entity = entity;
                    data.id = record[supervisorRecordIdFields[entity]];
                    submitButton.disabled = true;
                    submitButton.textContent = "Guardando...";
                    try {
                        const result = await this.api.post("supervisor_record_update", data);
                        if (!result.success) throw new Error(result.message || "No se pudo editar el registro.");
                        dialog.close();
                        await refresh();
                    } catch (error) {
                        message.textContent = error.message || "No se pudo editar el registro.";
                        message.hidden = false;
                    } finally {
                        if (dialog.open) {
                            submitButton.disabled = false;
                            submitButton.textContent = "Guardar cambios";
                        }
                    }
                });
                dialog.showModal();
                dialog.querySelector("input, select, textarea")?.focus();
            };
            button.addEventListener("click", openEditor);
            const row = button.closest("tr");
            if (row) {
                row.addEventListener("dblclick", event => {
                    if (event.target.closest("button, a, input, select, textarea")) return;
                    openEditor();
                });
            }
        });
        this.root.querySelectorAll("[data-supervisor-delete]").forEach(button => {
            button.addEventListener("click", async () => {
                if (!window.confirm("¿Eliminar este registro? Esta acción no se puede deshacer.")) return;
                try {
                    const result = await this.api.post("supervisor_record_delete", {
                        entity: button.dataset.entity,
                        id: button.dataset.supervisorDelete
                    });
                    if (!result.success) throw new Error(result.message || "No se pudo eliminar el registro.");
                    await refresh();
                } catch (error) {
                    window.alert(error.message || "No se pudo eliminar el registro.");
                }
            });
        });
    }
};
