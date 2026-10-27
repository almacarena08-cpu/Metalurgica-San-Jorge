package modulos.admin.dao;

import modulos.common.dao.ApiResult;
import modulos.common.dao.BaseApiDao;
import modulos.common.ApiClient;
import modulos.common.JsonUtil;

import java.io.IOException;
import java.util.List;
import java.util.Map;
import java.io.File;

public class AdministracionDao extends BaseApiDao {
    private volatile int chatUserId;
    public List<Map<String,String>> listarEmpleados(int adminId) throws IOException { Map<String,String>d=new java.util.LinkedHashMap<>(); d.put("id_usuario",String.valueOf(adminId)); return getRows("empleados_list",d); }
    public ApiResult crearEmpleado(Map<String,String> d) throws IOException { return post("empleados_create",d); }
    public ApiResult actualizarEmpleado(Map<String,String> d) throws IOException { return post("empleados_update",d); }

    public String getChatApi() {
        return ApiClient.API_BASE;
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
                ? ApiClient.post("chat_reply", datos)
                : ApiClient.postMultipart("chat_reply", datos, adjuntos);
        return new ApiResult(ApiClient.isSuccess(body), body);
    }

    public ApiResult iniciarSesionChat(String usuario, String contrasena) throws IOException {
        Map<String, String> datos = new java.util.LinkedHashMap<>();
        datos.put("usuario", usuario);
        datos.put("contrasena", contrasena);
        String login = ApiClient.post("login", datos);
        if (!ApiClient.isSuccess(login)) return new ApiResult(false, login);
        Map<String, String> user = JsonUtil.parseObject(JsonUtil.parseObject(login).get("usuario"));
        try {
            chatUserId = Integer.parseInt(user.getOrDefault("id_usuario", "0"));
        } catch (NumberFormatException error) {
            chatUserId = 0;
        }
        return iniciarSesionChat(chatUserId, user.getOrDefault("rol", ""));
    }

    public ApiResult iniciarSesionChat(int idUsuario, String rol) throws IOException {
        this.chatUserId = idUsuario;
        Map<String, String> data = new java.util.LinkedHashMap<>();
        data.put("id_usuario", String.valueOf(idUsuario));
        data.put("rol", rol);
        String response = ApiClient.post("chat_login_app", data);
        return new ApiResult(ApiClient.isSuccess(response), response);
    }

    public ApiResult cerrarSesionChat() throws IOException {
        chatUserId = 0;
        String response = ApiClient.post("chat_logout", java.util.Collections.emptyMap());
        return new ApiResult(ApiClient.isSuccess(response), response);
    }

    public boolean sesionChatActiva() throws IOException {
        String response = ApiClient.post("chat_auth_check", chatIdentity());
        return "true".equals(JsonUtil.parseObject(response).get("success"));
    }

    public List<Map<String, String>> listarChatsApp() throws IOException {
        String result = ApiClient.post("chat_list", chatIdentity());
        List<Map<String, String>> rows = JsonUtil.parseArray(result);
        if (!rows.isEmpty() || result.trim().startsWith("[")) return rows;
        throw new IOException(errorMessage(result));
    }

    public List<Map<String, String>> listarMensajesChat(int idChat) throws IOException {
        Map<String, String> data = new java.util.LinkedHashMap<>();
        data.put("id_chat", String.valueOf(idChat));
        data.putAll(chatIdentity());
        String result = ApiClient.post("chat_messages", data);
        List<Map<String, String>> rows = JsonUtil.parseArray(result);
        if (!rows.isEmpty() || result.trim().startsWith("[")) return rows;
        throw new IOException(errorMessage(result));
    }

    public List<Map<String, String>> listarAdjuntosChat(int idChat) throws IOException {
        Map<String, String> data = new java.util.LinkedHashMap<>();
        data.put("id_chat", String.valueOf(idChat));
        data.putAll(chatIdentity());
        String result = ApiClient.post("chat_attachments_list", data);
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

}
