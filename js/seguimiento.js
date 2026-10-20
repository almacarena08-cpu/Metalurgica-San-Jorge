export const seguimientoPanel = {
    async seguimientoView() {
        clearInterval(this.trackingTimer);
        this.trackingTimer = null;
        this.trackingSignature = null;
        this.content(await this.moduleTemplate("seguimiento", "seguimiento", {orders: "Cargando tus pedidos…"}));
        await this.refreshSeguimiento(true);
        this.trackingTimer = setInterval(() => {
            if (this.current === "seguimiento" && document.visibilityState !== "hidden") {
                this.refreshSeguimiento().catch(error => {
                    console.error("No se pudo actualizar el seguimiento:", error);
                    const status = this.root.querySelector("[data-tracking-sync]");
                    if (status) status.textContent = "No se pudo actualizar ahora. Se volverá a intentar automáticamente.";
                });
            }
        }, 7000);
    },

    async refreshSeguimiento(force = false) {
        const pedidos = await this.api.get("pedidos_tracking");
        if (!Array.isArray(pedidos)) {
            const message = pedidos.message || "No se pudo cargar el seguimiento de pedidos.";
            const container = this.root.querySelector("[data-tracking-orders]");
            if (container) container.innerHTML = `<div class="tracking-empty"><strong>Seguimiento no disponible</strong><span>${this.escape(message)}</span></div>`;
            return;
        }
        const signature = JSON.stringify(pedidos);
        if (!force && signature === this.trackingSignature) return;
        this.trackingSignature = signature;
        const container = this.root.querySelector("[data-tracking-orders]");
        if (!container) return;
        if (!pedidos.length) {
            container.innerHTML = '<div class="tracking-empty"><strong>Todavía no hay pedidos</strong><span>Cuando Administración registre un pedido desde una conversación, aparecerá acá.</span><button type="button" class="btn primary" data-open-client-chat>Ir a mis mensajes</button></div>';
            container.querySelector("[data-open-client-chat]")?.addEventListener("click", () => this.openModule("chat"));
            return;
        }
        container.innerHTML = pedidos.map(pedido => {
            const updates = pedido.seguimiento || [];
            const timeline = updates.length
                ? updates.slice().reverse().map(update => `<li class="tracking-event">
                    <span class="tracking-event-dot"></span>
                    <div><strong>${this.escape(update.estado)}</strong><span>${this.escape(update.descripcion)}</span>
                    <small>${this.escape(update.area)} · ${this.formatTrackingDate(update.fecha)}</small></div>
                </li>`).join("")
                : `<li class="tracking-event"><span class="tracking-event-dot"></span><div><strong>${this.escape(pedido.estado || "Estado actual")}</strong><span>Este es el estado actual guardado. La base disponible no conserva un historial de cambios.</span></div></li>`;
            const expected = pedido.fecha_entrega
                ? `Entrega estimada: ${this.formatTrackingDate(pedido.fecha_entrega, true)}`
                : "Fecha de entrega a confirmar";
            const progress = Number(pedido.avance || 0);
            return `<article class="tracking-order">
                <header class="tracking-order-head">
                    <div><span class="tracking-order-kicker">PEDIDO #${this.escape(pedido.id_pedido)}</span><h4>${this.escape(pedido.descripcion || "Pedido")}</h4></div>
                    <span class="tracking-state">${this.escape(pedido.estado || "Pedido registrado")}</span>
                </header>
                <div class="tracking-order-facts"><span>${this.escape(pedido.cantidad || "—")} unidades</span><span>${this.escape(pedido.material || "Material a confirmar")}</span><span>${this.escape(expected)}</span>${pedido.id_orden ? `<span>Orden de trabajo #${this.escape(pedido.id_orden)}</span>` : ""}</div>
                ${pedido.id_orden ? `<div class="tracking-progress"><span>Avance de producción</span><span>${this.escape(progress)}%</span><div><i style="width:${Math.max(0, Math.min(100, progress))}%"></i></div></div>` : ""}
                <ol class="tracking-timeline">${timeline}</ol>
            </article>`;
        }).join("");
        const status = this.root.querySelector("[data-tracking-sync]");
        if (status) status.textContent = `Estado actualizado ${new Date().toLocaleTimeString("es-AR", {hour: "2-digit", minute: "2-digit"})} · Se revisa automáticamente cada 7 segundos.`;
    },

    formatTrackingDate(value, dateOnly = false) {
        if (!value) return "";
        const date = new Date(String(value).replace(" ", "T"));
        if (Number.isNaN(date.getTime())) return this.escape(value);
        return date.toLocaleString("es-AR", dateOnly
            ? {timeZone: "America/Argentina/Buenos_Aires", day: "numeric", month: "short", year: "numeric"}
            : {timeZone: "America/Argentina/Buenos_Aires", day: "numeric", month: "short", hour: "2-digit", minute: "2-digit"});
    }
};
