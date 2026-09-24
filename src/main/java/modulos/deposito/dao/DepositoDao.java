package modulos.deposito.dao;

import modulos.common.dao.ApiResult;
import modulos.common.dao.BaseApiDao;

import java.io.IOException;
import java.util.List;
import java.util.Map;

public class DepositoDao extends BaseApiDao {
    public List<Map<String, String>> listarMateriales() throws IOException {
        return getRows("materiales_list");
    }

    public List<Map<String, String>> listarMovimientos() throws IOException {
        return getRows("deposito_movimientos_list");
    }

    public ApiResult registrarMovimiento(Map<String, String> datos) throws IOException {
        return post("deposito_movimiento_create", datos);
    }
}
