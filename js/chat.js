export const chatPanel = {
    async chatView(startNew = false) {
        const isClient = (this.session.user.rol || this.session.user.tipo || "").toLowerCase() === "cliente";
        const chats = await this.api.post("chat_list", isClient ? {id_cliente: this.session.user.id_cliente} : {});
        if (!Array.isArray(chats)) {
            if (chats.authRequired) {
                this.promptChatLogin();
                return;
            }
            this.content(await this.moduleTemplate("chat", "chat-error", {
                error: this.escape(chats.message || "Iniciá sesión para acceder al chat.")
            }));
            return;
        }
        this.activeChat = startNew ? null : (this.activeChat || chats[0]?.id_chat || null);
        const selectedChat = chats.find(chat => String(chat.id_chat) === String(this.activeChat));
        const contactName = selectedChat
            ? (isClient ? "Administración San Jorge" : selectedChat.nombre)
            : (isClient ? "Atención comercial" : "Bandeja de conversaciones");
        const contactSubtitle = selectedChat
            ? (isClient ? selectedChat.asunto : `${selectedChat.email || "Cliente"} · ${selectedChat.asunto}`)
            : (isClient ? "Te respondemos por este medio" : "Seleccioná una conversación para responder");
        const chatItems = chats.length
            ? (await Promise.all(chats.map(chat => {
                const title = isClient ? "Administración San Jorge" : chat.nombre;
                return this.moduleTemplate("chat", "chat-item", {
                    activeClass: String(chat.id_chat) === String(this.activeChat) ? "active" : "",
                    chatId: this.escape(chat.id_chat),
                    initials: this.escape(String(title || "SJ").trim().slice(0, 2).toUpperCase()),
                    title: this.escape(title),
                    preview: this.escape(chat.ultimo_mensaje || chat.asunto),
                    stateClass: chat.estado === "Abierto" ? "is-open" : "",
                    state: this.escape(chat.estado)
                });
            }))).join("")
            : await this.moduleTemplate("chat", "chat-empty-list");
        this.content(await this.moduleTemplate("chat", "chat", {
            clientSiteButton: isClient ? '<button class="chat-back-button" type="button" data-client-site>Volver al sitio</button>' : "",
            newChatButton: isClient ? '<button class="chat-new-button" data-new-chat aria-label="Iniciar nueva consulta" title="Iniciar nueva consulta"><span aria-hidden="true">+</span><span>Nueva</span></button>' : "",
            chatCount: chats.length,
            chatItems,
            contactInitials: this.escape(String(contactName || "SJ").trim().slice(0, 2).toUpperCase()),
            contactName: this.escape(contactName),
            contactSubtitle: this.escape(contactSubtitle),
            chatStatus: selectedChat ? this.escape(selectedChat.estado) : (isClient ? "En línea" : "Chat interno"),
            subjectField: isClient && !this.activeChat ? '<label class="chat-subject-label" for="chatSubject">ASUNTO DE LA CONSULTA</label><input id="chatSubject" name="asunto" placeholder="Por ejemplo: consulta de presupuesto">' : "",
            messageLabel: isClient ? "TU MENSAJE" : "RESPUESTA",
            messagePlaceholder: isClient ? "Escribí tu consulta con el mayor detalle posible..." : "Escribí una respuesta...",
            message_disabled: !isClient && !selectedChat ? "disabled" : "",
            composeHint: isClient ? "Nuestro equipo va a responderte por este chat." : "Tu respuesta se enviará al cliente de esta conversación.",
            send_disabled: !isClient && !selectedChat ? "disabled" : ""
        }));
        this.root.querySelectorAll("[data-chat]").forEach(button => {
            button.addEventListener("click", async () => {
                this.activeChat = button.dataset.chat;
                await this.chatView();
            });
        });
        const clientSiteButton = this.root.querySelector(".chat-toolbar [data-client-site]");
        if (clientSiteButton) clientSiteButton.addEventListener("click", () => this.renderLanding());
        const newChat = this.root.querySelector("[data-new-chat]");
        if (newChat) {
            newChat.addEventListener("click", async () => {
                this.activeChat = null;
                await this.chatView(true);
            });
        }
        this.root.querySelector("#chatForm").addEventListener("submit", event => this.sendChat(event, isClient));
        if (this.pendingChatDraft) {
            const form = this.root.querySelector("#chatForm");
            if (form.elements.asunto) form.elements.asunto.value = this.pendingChatDraft.asunto;
            form.elements.mensaje.value = this.pendingChatDraft.mensaje;
            form.parentElement.querySelector(".message").textContent = "Tu mensaje quedó guardado. Presioná Enviar para mandarlo ahora.";
            this.pendingChatDraft = null;
        }
        await this.loadMessages();
        this.chatTimer = setInterval(() => this.loadMessages(), 5000);
    },

    async loadMessages() {
        const thread = this.root.querySelector("#chatThread");
        if (!thread) return;
        if (!this.activeChat) {
            const isClient = (this.session.user.rol || this.session.user.tipo || "").toLowerCase() === "cliente";
            thread.innerHTML = await this.moduleTemplate("chat", isClient ? "chat-welcome-client" : "chat-welcome-staff");
            return;
        }
        const messages = await this.api.post("chat_messages", {id_chat: this.activeChat});
        if (!Array.isArray(messages)) {
            if (messages.authRequired) {
                this.promptChatLogin();
                return;
            }
            thread.textContent = messages.message || "No se pudieron cargar los mensajes.";
            return;
        }
        const role = (this.session.user.rol || this.session.user.tipo || "").toLowerCase();
        thread.innerHTML = (await Promise.all(messages.map(message => {
            const mine = role === "cliente" ? message.autor_tipo === "Cliente" : message.autor_tipo === "Administracion";
            return this.moduleTemplate("chat", "chat-message", {
                messageSide: mine ? "mine" : "theirs",
                messageText: this.escape(message.mensaje),
                author: this.escape(message.autor_nombre || message.autor_tipo),
                date: this.escape(message.fecha)
            });
        }))).join("");
        thread.scrollTop = thread.scrollHeight;
    },

    async sendChat(event, isClient) {
        event.preventDefault();
        const form = event.currentTarget;
        const data = Object.fromEntries(new FormData(form));
        const payload = {
            id_chat: this.activeChat || "",
            id_usuario: isClient ? "" : this.session.user.id_usuario,
            id_cliente: this.session.user.id_cliente || "",
            autor_tipo: isClient ? "Cliente" : "Administracion",
            asunto: data.asunto || "Consulta a administracion",
            mensaje: data.mensaje
        };
        const result = await this.api.post(isClient ? "chat_send" : "chat_reply", payload);
        if (!result.success) {
            if (result.authRequired) {
                this.pendingChatDraft = {asunto: data.asunto || "", mensaje: data.mensaje};
                this.promptChatLogin();
                return;
            }
            form.parentElement.querySelector(".message").textContent = result.message || "No se pudo enviar.";
            return;
        }
        this.activeChat = result.id_chat || this.activeChat;
        form.reset();
        await this.chatView();
    },

    promptChatLogin() {
        this.openChatAfterClientLogin = true;
        this.renderLanding();
        this.openAuth("login");
        const message = this.root.querySelector("#authContent .message");
        if (message) message.textContent = "La sesión del chat venció o no está activa. Ingresá de nuevo para enviar el mensaje.";
    },

    stopChatTimer() {
        if (this.chatTimer) {
            clearInterval(this.chatTimer);
            this.chatTimer = null;
        }
    }
};
