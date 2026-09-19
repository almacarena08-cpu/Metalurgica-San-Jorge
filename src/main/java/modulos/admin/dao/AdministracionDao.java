package modulos.admin.dao;

import modulos.common.dao.ApiResult;
import modulos.common.dao.BaseApiDao;

import java.io.IOException;
import java.util.List;
import java.util.Map;

public class AdministracionDao extends BaseApiDao {
    public List<Map<String, String>> listarPedidos() throws IOException {
        return getRows("pedidos_list");
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

}
