package modulos.produccion.dao;

import modulos.common.dao.ApiResult;
import modulos.common.dao.BaseApiDao;

import java.io.IOException;
import java.util.List;
import java.util.Map;

public class ProduccionDao extends BaseApiDao {
    public List<Map<String, String>> listarOrdenes() throws IOException {
        return getRows("ordenes_list");
    }

    public List<Map<String, String>> listarAvances() throws IOException {
        return getRows("produccion_list");
    }

    public ApiResult registrarAvance(Map<String, String> datos) throws IOException {
        return post("produccion_register", datos);
    }

    public List<Map<String, String>> listarMaquinas() throws IOException {
        return getRows("maquinas_list");
    }

    public ApiResult reportarFalla(Map<String, String> datos) throws IOException {
        return post("mantenimiento_report", datos);
    }
}
