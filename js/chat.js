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
        const fileInput = this.root.querySelector("#chatFiles");
        fileInput.addEventListener("change", () => this.renderChatFileList());
        if (this.pendingChatFiles?.length) {
            const transfer = new DataTransfer();
            this.pendingChatFiles.forEach(file => transfer.items.add(file));
            fileInput.files = transfer.files;
            this.pendingChatFiles = null;
            this.renderChatFileList();
        }
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
            const attachments = (message.adjuntos || []).map(attachment => {
                const url = `${this.api.baseUrl}?action=chat_attachment&id_adjunto=${encodeURIComponent(attachment.id_adjunto)}`;
                return `<a class="chat-attachment-link" href="${this.escape(url)}" download>
                    <span class="chat-attachment-file-icon" aria-hidden="true">↓</span>
                    <span><strong>${this.escape(attachment.nombre_original)}</strong><small>${this.formatChatFileSize(attachment.tamano)}</small></span>
                </a>`;
            }).join("");
            return this.moduleTemplate("chat", "chat-message", {
                messageSide: mine ? "mine" : "theirs",
                messageText: this.escape(message.mensaje),
                attachments: attachments ? `<div class="chat-message-attachments">${attachments}</div>` : "",
                author: this.escape(message.autor_nombre || message.autor_tipo),
                date: this.escape(message.fecha)
            });
        }))).join("");
        thread.scrollTop = thread.scrollHeight;
    },

    async sendChat(event, isClient) {
        event.preventDefault();
        const form = event.currentTarget;
        const formData = new FormData(form);
        const message = String(formData.get("mensaje") || "").trim();
        const files = Array.from(form.querySelector("#chatFiles").files);
        const messageBox = form.parentElement.querySelector(".message");
        if (!message && files.length === 0) {
            messageBox.textContent = "Escribí un mensaje o adjuntá al menos un archivo.";
            return;
        }
        if (files.length > 5) {
            messageBox.textContent = "Podés adjuntar hasta 5 archivos por mensaje.";
            return;
        }
        if (files.some(file => file.size > 10 * 1024 * 1024)) {
            messageBox.textContent = "Cada archivo debe pesar como máximo 10 MB.";
            return;
        }
        if (files.reduce((total, file) => total + file.size, 0) > 25 * 1024 * 1024) {
            messageBox.textContent = "El total de archivos por mensaje no puede superar 25 MB.";
            return;
        }
        formData.set("id_chat", this.activeChat || "");
        formData.set("id_usuario", isClient ? "" : this.session.user.id_usuario);
        formData.set("id_cliente", this.session.user.id_cliente || "");
        formData.set("autor_tipo", isClient ? "Cliente" : "Administracion");
        formData.set("asunto", formData.get("asunto") || "Consulta a administracion");
        try {
            const result = await this.api.postMultipart(isClient ? "chat_send" : "chat_reply", formData);
            if (!result.success) {
                if (result.authRequired) {
                    this.pendingChatDraft = {asunto: formData.get("asunto") || "", mensaje: message};
                    this.pendingChatFiles = files;
                    this.promptChatLogin();
                    return;
                }
                messageBox.textContent = result.message || "No se pudo enviar.";
                return;
            }
            this.activeChat = result.id_chat || this.activeChat;
            this.pendingChatFiles = null;
            await this.chatView();
        } catch (error) {
            console.error("No se pudo enviar el mensaje del chat:", error);
            messageBox.textContent = error.message || "No se pudo enviar el mensaje.";
        }
    },

    renderChatFileList() {
        const input = this.root.querySelector("#chatFiles");
        const list = this.root.querySelector("[data-chat-attachments]");
        if (!input || !list) return;
        list.replaceChildren();
        const files = Array.from(input.files);
        list.hidden = files.length === 0;
        files.forEach((file, index) => {
            const item = document.createElement("span");
            item.className = "chat-selected-file";
            const name = document.createElement("span");
            name.textContent = `${file.name} · ${this.formatChatFileSize(file.size)}`;
            const remove = document.createElement("button");
            remove.type = "button";
            remove.className = "chat-remove-file";
            remove.setAttribute("aria-label", `Quitar ${file.name}`);
            remove.textContent = "×";
            remove.addEventListener("click", () => {
                const transfer = new DataTransfer();
                files.forEach((selected, selectedIndex) => {
                    if (selectedIndex !== index) transfer.items.add(selected);
                });
                input.files = transfer.files;
                this.renderChatFileList();
            });
            item.append(name, remove);
            list.append(item);
        });
    },

    formatChatFileSize(size) {
        if (size < 1024 * 1024) return `${Math.max(1, Math.round(size / 1024))} KB`;
        return `${(size / (1024 * 1024)).toFixed(1)} MB`;
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
