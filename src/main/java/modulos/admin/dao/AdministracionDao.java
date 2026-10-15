package modulos.admin.dao;

import modulos.common.dao.ApiResult;
import modulos.common.dao.BaseApiDao;
import modulos.common.ApiClient;
import modulos.common.JsonUtil;

import java.io.IOException;
import java.util.List;
import java.util.Map;
import java.util.ArrayList;
import java.io.File;

public class AdministracionDao extends BaseApiDao {
    private static final String WEB_API = System.getProperty(
            "metalgest.web.api.url",
            ApiClient.API_BASE);
    private volatile int chatUserId;

    public String getChatWebApi() {
        return WEB_API;
    }

    public List<Map<String, String>> listarPedidos() throws IOException {
        return getRows("pedidos_list");
    }

    public List<Map<String, String>> listarClientes() throws IOException {
        return getRows("clientes_list");
    }

    public List<Map<String, String>> listarChats() throws IOException {
        return getRows("chat_list");
    }

    public List<Map<String, String>> listarMateriales() throws IOException {
        return getRows("materiales_list");
    }

    public ApiResult crearPedido(Map<String, String> datos) throws IOException {
        return post("pedidos_create", datos);
    }

    public List<Map<String, String>> listarOrdenes() throws IOException {
        return getRows("ordenes_list");
    }

    public ApiResult crearOrden(Map<String, String> datos) throws IOException {
        return post("ordenes_create", datos);
    }

    public ApiResult actualizarOrden(Map<String, String> datos) throws IOException {
        return post("orden_update_status", datos);
    }

    public ApiResult responderChat(Map<String, String> datos) throws IOException {
        return responderChat(datos, java.util.Collections.emptyList());
    }

    public ApiResult responderChat(Map<String, String> datos, List<File> adjuntos) throws IOException {
        datos.putAll(chatIdentity());
        String body = adjuntos.isEmpty()
                ? ApiClient.postTo(WEB_API, "chat_reply", datos)
                : ApiClient.postMultipart("chat_reply", datos, adjuntos);
        return new ApiResult(ApiClient.isSuccess(body), body);
    }

    public ApiResult iniciarSesionChat(String usuario, String contrasena) throws IOException {
        Map<String, String> datos = new java.util.LinkedHashMap<>();
        datos.put("usuario", usuario);
        datos.put("contrasena", contrasena);
        return postToWeb("chat_login", datos);
    }

    public ApiResult iniciarSesionChat(int idUsuario, String rol) throws IOException {
        this.chatUserId = idUsuario;
        Map<String, String> datos = new java.util.LinkedHashMap<>();
        datos.put("id_usuario", String.valueOf(idUsuario));
        datos.put("rol", rol);
        return postToWeb("chat_login_app", datos);
    }

    public ApiResult cerrarSesionChat() throws IOException {
        chatUserId = 0;
        return postToWeb("chat_logout", java.util.Collections.emptyMap());
    }

    public boolean sesionChatActiva() throws IOException {
        Map<String, String> data = JsonUtil.parseObject(ApiClient.postTo(WEB_API, "chat_auth_check", chatIdentity()));
        return "true".equals(data.get("success"));
    }

    public List<Map<String, String>> listarChatsWeb() throws IOException {
        String result = ApiClient.postTo(WEB_API, "chat_list", chatIdentity());
        List<Map<String, String>> rows = JsonUtil.parseArray(result);
        if (!rows.isEmpty() || result.trim().startsWith("[")) return rows;
        if (result.contains("\"success\":false")) throw new IOException(errorMessage(result));
        String fallback = ApiClient.getFrom(ApiClient.API_BASE, "chat_list");
        List<Map<String, String>> localRows = new ArrayList<>();
        for (Map<String, String> row : JsonUtil.parseArray(fallback)) {
            row.put("_chat_source", "web");
            localRows.add(row);
        }
        if (localRows.isEmpty() && fallback.contains("\"success\":false")) throw new IOException(errorMessage(fallback));
        return localRows;
    }

    public List<Map<String, String>> listarMensajesChat(int idChat) throws IOException {
        return listarMensajesChat(idChat, "web");
    }

    public List<Map<String, String>> listarMensajesChat(int idChat, String source) throws IOException {
        Map<String, String> data = new java.util.LinkedHashMap<>();
        data.put("id_chat", String.valueOf(idChat));
        data.putAll(chatIdentity());
        if ("local".equals(source)) {
            String localResult = ApiClient.post("chat_messages", data);
            List<Map<String, String>> localRows = JsonUtil.parseArray(localResult);
            if (!localRows.isEmpty() || localResult.trim().startsWith("[")) return localRows;
        }
        String result = ApiClient.postTo(WEB_API, "chat_messages", data);
        List<Map<String, String>> rows = JsonUtil.parseArray(result);
        if (!rows.isEmpty() || result.trim().startsWith("[")) return rows;
        if (result.contains("\"success\":false")) throw new IOException(errorMessage(result));
        String fallback = ApiClient.post("chat_messages", data);
        rows = JsonUtil.parseArray(fallback);
        if (rows.isEmpty() && fallback.contains("\"success\":false")) throw new IOException(errorMessage(fallback));
        return rows;
    }

    public List<Map<String, String>> listarAdjuntosChat(int idChat) throws IOException {
        Map<String, String> data = new java.util.LinkedHashMap<>();
        data.put("id_chat", String.valueOf(idChat));
        data.putAll(chatIdentity());
        String result = ApiClient.postTo(WEB_API, "chat_attachments_list", data);
        List<Map<String, String>> rows = JsonUtil.parseArray(result);
        if (!rows.isEmpty() || result.trim().startsWith("[")) return rows;
        throw new IOException(errorMessage(result));
    }

    public byte[] descargarAdjunto(int idAdjunto) throws IOException {
        Map<String, String> data = chatIdentity();
        data.put("id_adjunto", String.valueOf(idAdjunto));
        return ApiClient.download("chat_attachment", data);
    }

    private Map<String, String> chatIdentity() {
        Map<String, String> data = new java.util.LinkedHashMap<>();
        if (chatUserId > 0) data.put("id_usuario", String.valueOf(chatUserId));
        return data;
    }

    private String errorMessage(String json) {
        String message = JsonUtil.parseObject(json).get("message");
        return message == null || message.isEmpty() ? "La API no devolvió una lista de chats." : message;
    }

    private ApiResult postToWeb(String action, Map<String, String> datos) throws IOException {
        String body = ApiClient.postTo(WEB_API, action, datos);
        return new ApiResult(ApiClient.isSuccess(body), body);
    }

}
