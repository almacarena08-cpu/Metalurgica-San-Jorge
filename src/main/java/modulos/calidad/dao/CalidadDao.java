package modulos.calidad.dao;

import modulos.common.dao.ApiResult;
import modulos.common.dao.BaseApiDao;

import java.io.IOException;
import java.util.List;
import java.util.Map;

public class CalidadDao extends BaseApiDao {
    public List<Map<String, String>> listarOrdenes() throws IOException {
        return getRows("ordenes_list");
    }

    public List<Map<String, String>> listarControles() throws IOException {
        return getRows("calidad_list");
    }

    public ApiResult registrarControl(Map<String, String> datos) throws IOException {
        return post("calidad_register", datos);
    }
}
