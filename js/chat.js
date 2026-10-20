export const chatPanel = {
    async chatView(startNew = false) {
        this.stopChatTimer();
        const isClient = (this.session.user.rol || this.session.user.tipo || "").toLowerCase() === "cliente";
        const chats = await this.api.post("chat_list", isClient ? {id_cliente: this.session.user.id_cliente} : {});
        if (!Array.isArray(chats)) {
            if (chats.authRequired) {
                this.promptChatLogin();
                return;
            }
            this.content(`<section class="section-panel"><div class="message">${this.escape(chats.message || "Iniciá sesión para acceder al chat.")}</div></section>`);
            return;
        }
        const activeChatExists = chats.some(chat => String(chat.id_chat) === String(this.activeChat));
        this.activeChat = startNew ? null : (activeChatExists ? this.activeChat : (isClient ? chats[0]?.id_chat || null : null));
        const selectedChat = chats.find(chat => String(chat.id_chat) === String(this.activeChat));
        const contactName = selectedChat
            ? (isClient ? "Administración San Jorge" : selectedChat.nombre)
            : (isClient ? "Atención comercial" : "Bandeja de conversaciones");
        const contactSubtitle = selectedChat
            ? (isClient ? selectedChat.asunto : `${selectedChat.email || "Cliente"} · ${selectedChat.asunto}`)
            : (isClient ? "Te respondemos por este medio" : "Seleccioná una conversación para responder");
        const orderControls = !isClient && selectedChat
            ? '<span class="chat-order-badge" title="Usá Administración > Pedidos para registrar pedidos; el esquema actual no permite vincularlos con esta conversación">Vínculo con pedido no disponible</span>'
            : "";
        const orderDialog = "";
        const chatItems = chats.length
            ? (await Promise.all(chats.map(chat => {
                const title = isClient ? "Administración San Jorge" : chat.nombre;
                const activeClass = String(chat.id_chat) === String(this.activeChat) ? "active" : "";
                const initials = this.escape(String(title || "SJ").trim().slice(0, 2).toUpperCase());
                const preview = this.escape(chat.ultimo_mensaje || chat.asunto);
                const metadata = this.escape(isClient ? chat.asunto : [chat.asunto, chat.email].filter(Boolean).join(" · "));
                const searchText = this.escape([title, chat.email, chat.asunto, chat.ultimo_mensaje, chat.estado].filter(Boolean).join(" "));
                const stateClass = chat.estado === "Abierto" ? "is-open" : "";
                return `<button class="chat-item ${activeClass}" data-chat="${this.escape(chat.id_chat)}" data-chat-search-text="${searchText}">
                    <span class="chat-avatar">${initials}</span>
                    <span class="chat-item-copy"><span class="chat-item-title">${this.escape(title)}</span><span class="chat-item-preview">${preview}</span><span class="chat-item-meta">${metadata}</span></span>
                    <span class="chat-item-state ${stateClass}" title="${this.escape(chat.estado)}"></span>
                </button>`;
            }))).join("")
            : '<div class="chat-list-empty"><strong>Sin conversaciones</strong><span>Cuando inicies una consulta, aparecerá acá.</span></div>';
        this.content(await this.moduleTemplate("chat", "chat", {
            layoutClass: !isClient && !selectedChat ? "is-staff-inbox" : "",
            chatBrandSubtitle: isClient ? "METALGEST" : "ATENCIÓN A CLIENTES",
            chatBrandTitle: isClient ? "Mensajes" : "Bandeja de entrada",
            conversationLabel: isClient ? "CONVERSACIONES" : "CLIENTES QUE ESCRIBIERON",
            clientSiteButton: isClient ? '<button class="chat-back-button" type="button" data-client-site>Volver al inicio</button>' : "",
            newChatButton: isClient ? '<button class="chat-new-button" data-new-chat aria-label="Iniciar nueva consulta" title="Iniciar nueva consulta"><span aria-hidden="true">+</span><span>Nueva</span></button>' : "",
            chatCount: chats.length,
            chatSearch: chats.length ? '<div class="chat-list-search"><label><span>Buscar conversaciones</span><input type="search" data-chat-search placeholder="Nombre, asunto o mensaje..." autocomplete="off"></label><span class="chat-list-search-count" data-chat-search-count aria-live="polite"></span></div>' : "",
            chatItems,
            contactInitials: this.escape(String(contactName || "SJ").trim().slice(0, 2).toUpperCase()),
            contactName: this.escape(contactName),
            contactSubtitle: this.escape(contactSubtitle),
            chatStatus: selectedChat ? this.escape(selectedChat.estado) : (isClient ? "En línea" : "Chat interno"),
            orderControls,
            orderDialog,
            subjectField: isClient && !this.activeChat ? '<label class="chat-subject-label" for="chatSubject">ASUNTO DE LA CONSULTA</label><input id="chatSubject" name="asunto" placeholder="Por ejemplo: consulta de presupuesto">' : "",
            messageLabel: isClient ? "TU MENSAJE" : "RESPUESTA",
            messagePlaceholder: isClient ? "Escribí tu consulta con el mayor detalle posible..." : "Escribí una respuesta...",
            message_disabled: !isClient && !selectedChat ? "disabled" : "",
            composeHint: isClient ? "Nuestro equipo va a responderte por este chat." : "Tu respuesta se enviará al cliente de esta conversación.",
            send_disabled: !isClient && !selectedChat ? "disabled" : ""
        }));
        const fileInput = this.root.querySelector("#chatFiles");
        fileInput.addEventListener("change", () => this.renderChatFileList());
        const chatMenu = this.root.querySelector("[data-chat-menu]");
        if (chatMenu) {
            chatMenu.addEventListener("click", () => {
                const layout = this.root.querySelector(".chat-layout");
                const isOpen = layout.classList.toggle("is-list-open");
                chatMenu.setAttribute("aria-expanded", String(isOpen));
                const listName = isClient ? "conversaciones" : "lista de clientes";
                chatMenu.setAttribute("aria-label", isOpen ? `Cerrar ${listName}` : `Ver ${listName}`);
            });
            this.root.querySelector(".chat-layout").addEventListener("click", event => {
                const layout = event.currentTarget;
                if (!layout.classList.contains("is-list-open") || event.target !== layout) return;
                layout.classList.remove("is-list-open");
                chatMenu.setAttribute("aria-expanded", "false");
                chatMenu.setAttribute("aria-label", isClient ? "Ver conversaciones" : "Ver lista de clientes");
            });
        }
        const messageInput = this.root.querySelector("#chatMessage");
        if (messageInput) {
            const resizeMessage = () => {
                messageInput.style.height = "auto";
                messageInput.style.height = `${Math.min(messageInput.scrollHeight, 180)}px`;
            };
            messageInput.addEventListener("input", resizeMessage);
            messageInput.addEventListener("keydown", event => {
                if (event.key !== "Enter" || event.shiftKey || event.isComposing) return;
                event.preventDefault();
                if (messageInput.value.trim() || this.root.querySelector("#chatFiles").files.length) {
                    this.root.querySelector("#chatForm").requestSubmit();
                }
            });
        }
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
        const chatSearch = this.root.querySelector("[data-chat-search]");
        if (chatSearch) {
            const chatSearchCount = this.root.querySelector("[data-chat-search-count]");
            const chatItemsContainer = this.root.querySelector(".chat-items");
            const noResults = document.createElement("div");
            noResults.className = "chat-list-empty chat-search-empty";
            noResults.hidden = true;
            noResults.innerHTML = "<strong>No se encontraron conversaciones</strong><span>Probá con otro nombre, asunto o mensaje.</span>";
            chatItemsContainer.append(noResults);
            chatSearch.addEventListener("input", () => {
                const query = this.normalizeSearchText(chatSearch.value.trim());
                let visibleCount = 0;
                this.root.querySelectorAll(".chat-item").forEach(item => {
                    const matches = !query || this.normalizeSearchText(item.dataset.chatSearchText).includes(query);
                    item.hidden = !matches;
                    if (matches) visibleCount += 1;
                });
                chatSearchCount.textContent = query
                    ? `${visibleCount} de ${chats.length} conversaciones`
                    : `${chats.length} ${chats.length === 1 ? "conversación" : "conversaciones"}`;
                noResults.hidden = visibleCount !== 0;
            });
            chatSearchCount.textContent = `${chats.length} ${chats.length === 1 ? "conversación" : "conversaciones"}`;
        }
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
            form.parentElement.querySelector(".message").textContent = "Conservé tu borrador. Presioná Enviar para mandarlo ahora.";
            this.pendingChatDraft = null;
        }
        await this.loadMessages();
        this.root.querySelectorAll("[data-chat-suggestion]").forEach(button => {
            button.addEventListener("click", () => {
                const input = this.root.querySelector("#chatMessage");
                input.value = button.dataset.chatSuggestion;
                input.dispatchEvent(new Event("input", {bubbles: true}));
                input.focus();
            });
        });
        this.chatTimer = setInterval(() => {
            if (document.visibilityState !== "hidden") {
                this.loadMessages().catch(error => console.error("No se pudieron actualizar los mensajes:", error));
            }
        }, 5000);
    },

    async loadMessages() {
        const thread = this.root.querySelector("#chatThread");
        if (!thread) return;
        if (!this.activeChat) {
            const isClient = (this.session.user.rol || this.session.user.tipo || "").toLowerCase() === "cliente";
            thread.innerHTML = isClient
                ? `<div class="chat-welcome">
                    <span class="chat-welcome-mark" aria-hidden="true">SJ</span>
                    <span class="chat-welcome-eyebrow">ATENCIÓN PERSONALIZADA</span>
                    <h3>¿En qué podemos ayudarte?</h3>
                    <p>Escribinos tu consulta. El equipo de Metalúrgica San Jorge te responderá por este medio.</p>
                    <div class="chat-suggestions" aria-label="Consultas frecuentes">
                        <button type="button" data-chat-suggestion="Quisiera solicitar un presupuesto.">Solicitar un presupuesto</button>
                        <button type="button" data-chat-suggestion="Quisiera consultar el estado de mi pedido.">Consultar un pedido</button>
                        <button type="button" data-chat-suggestion="Necesito hablar con administración.">Hablar con administración</button>
                    </div>
                </div>`
                : '<div class="chat-welcome chat-welcome-empty"><span class="chat-welcome-mark" aria-hidden="true">SJ</span><span class="chat-welcome-eyebrow">BANDEJA DE CLIENTES</span><h3>Elegí una conversación</h3><p>Seleccioná un cliente de la lista para ver sus mensajes y responder.</p></div>';
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
            return `<div class="bubble-row ${mine ? "mine" : "theirs"}"><div class="bubble">
                <span>${this.escape(message.mensaje)}</span>
                ${attachments ? `<div class="chat-message-attachments">${attachments}</div>` : ""}
                <small>${this.escape(message.autor_nombre || message.autor_tipo)} · ${this.escape(message.fecha)}</small>
            </div></div>`;
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
        if (this.chatSendPending) return;
        this.chatSendPending = true;
        const sendButton = form.querySelector('[type="submit"]');
        sendButton.disabled = true;
        let messageSaved = false;
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
            messageSaved = true;
            this.activeChat = result.id_chat || this.activeChat;
            this.pendingChatFiles = null;
            await this.chatView();
            const feedback = this.root.querySelector(".chat-window > .message");
            if (feedback) {
                feedback.textContent = isClient
                    ? "Mensaje enviado."
                    : result.notificacion_enviada
                        ? "Respuesta enviada y aviso por correo enviado al cliente."
                        : "Respuesta guardada, pero no se pudo enviar el aviso por correo.";
            }
        } catch (error) {
            console.error("No se pudo enviar el mensaje del chat:", error);
            const feedback = this.root.querySelector(".chat-window > .message") || messageBox;
            feedback.textContent = messageSaved
                ? "El mensaje quedó guardado, pero no se pudo actualizar la conversación."
                : error.message || "No se pudo enviar el mensaje.";
        } finally {
            this.chatSendPending = false;
            if (sendButton.isConnected) sendButton.disabled = false;
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
        this.stopChatTimer();
        this.openChatAfterClientLogin = true;
        let modal = this.root.querySelector("#authModal");
        if (!modal) {
            const landing = this.moduleTemplates.get("inicio")?.querySelector('template[data-view="landing"]');
            const authModal = landing?.content.querySelector("#authModal");
            if (!authModal) throw new Error("No se pudo abrir el acceso sin salir del chat.");
            modal = authModal.cloneNode(true);
            this.root.append(modal);
            modal.querySelector("[data-close]").addEventListener("click", () => this.closeAuth());
            modal.querySelectorAll("[data-tab]").forEach(tab =>
                tab.addEventListener("click", () => this.openAuth(tab.dataset.tab))
            );
        }
        this.openAuth("login");
        const message = this.root.querySelector("#authContent .message");
        if (message) message.textContent = "La sesión venció. Iniciá sesión para enviar tu mensaje; conservé el texto y los archivos adjuntos.";
    },

    stopChatTimer() {
        if (this.chatTimer) {
            clearInterval(this.chatTimer);
            this.chatTimer = null;
        }
    }
};
