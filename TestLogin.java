import java.util.LinkedHashMap;
import java.util.Map;
import modulos.common.ApiClient;

public class TestLogin {
    public static void main(String[] args) throws Exception {
        Map<String, String> data = new LinkedHashMap<>();
        data.put("nombre", "alma");
        data.put("contrasena", "mantenimiento");
        data.put("rol", "Mantenimiento");
        System.out.println(ApiClient.post("login", data));
    }
}
