package modulos.compras.dao;

import modulos.common.dao.ApiResult;
import modulos.common.dao.BaseApiDao;

import java.io.IOException;
import java.util.List;
import java.util.Map;

public class ComprasDao extends BaseApiDao {
    public List<Map<String, String>> listarMateriales() throws IOException {
        return getRows("materiales_list");
    }

    public List<Map<String, String>> listarProveedores() throws IOException {
        return getRows("proveedores_list");
    }

    public List<Map<String, String>> listarCompras() throws IOException {
        return getRows("compras_list");
    }

    public ApiResult crearProveedor(Map<String, String> datos) throws IOException {
        return post("proveedores_create", datos);
    }

    public ApiResult crearCompra(Map<String, String> datos) throws IOException {
        return post("compras_create", datos);
    }

    public ApiResult crearMaterial(Map<String, String> datos) throws IOException {
        return post("materiales_create", datos);
    }

    public ApiResult actualizarMaterial(Map<String, String> datos) throws IOException {
        return post("materiales_update", datos);
    }

    public ApiResult eliminarMaterial(Map<String, String> datos) throws IOException {
        return post("materiales_delete", datos);
    }
}
