package modulos.common.dao;

import modulos.common.ApiClient;
import modulos.common.JsonUtil;

import java.io.IOException;
import java.util.Collections;
import java.util.List;
import java.util.Map;

public abstract class BaseApiDao {
    protected List<Map<String, String>> getRows(String action) throws IOException {
        List<Map<String, String>> rows = JsonUtil.parseArray(ApiClient.get(action));
        return rows == null ? Collections.emptyList() : rows;
    }

    protected ApiResult post(String action, Map<String, String> data) throws IOException {
        String body = ApiClient.post(action, data);
        return new ApiResult(ApiClient.isSuccess(body), body);
    }
}
