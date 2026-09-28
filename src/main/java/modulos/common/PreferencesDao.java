package modulos.common;

import java.io.IOException;
import java.util.LinkedHashMap;
import java.util.Map;

public class PreferencesDao {
    public UserPreferences load(int userId) throws IOException {
        Map<String, String> data = new LinkedHashMap<>();
        data.put("id_usuario", String.valueOf(userId));
        Map<String, String> response = JsonUtil.parseObject(ApiClient.post("preferences_get", data));
        return new UserPreferences(parseSize(response.get("tamano_texto")));
    }

    public boolean save(int userId, int textSize) throws IOException {
        Map<String, String> data = new LinkedHashMap<>();
        data.put("id_usuario", String.valueOf(userId));
        data.put("modo_oscuro", "1");
        data.put("tamano_texto", String.valueOf(textSize));
        return ApiClient.isSuccess(ApiClient.post("preferences_save", data));
    }

    private int parseSize(String value) {
        try {
            return Integer.parseInt(value);
        } catch (Exception error) {
            return 12;
        }
    }
}
