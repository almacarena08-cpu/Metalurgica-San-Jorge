package modulos.common.dao;

public class ApiResult {
    private final boolean success;
    private final String body;

    public ApiResult(boolean success, String body) {
        this.success = success;
        this.body = body;
    }

    public boolean isSuccess() {
        return success;
    }

    public String getBody() {
        return body;
    }
}
