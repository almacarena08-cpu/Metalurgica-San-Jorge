package modulos.mantenimiento.dao;

import modulos.common.dao.ApiResult;
import modulos.common.dao.BaseApiDao;

import java.io.IOException;
import java.util.List;
import java.util.Map;

public class MantenimientoDao extends BaseApiDao {
    public List<Map<String, String>> listar() throws IOException {
        return getRows("mantenimientos_list");
    }

    public ApiResult crearPreventivo(Map<String, String> datos) throws IOException {
        return post("mantenimiento_preventivo_create", datos);
    }

    public ApiResult actualizar(Map<String, String> datos) throws IOException {
        return post("mantenimiento_update", datos);
    }
}
