package modulos.common;

import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JPasswordField;
import javax.swing.JTextField;
import java.awt.GridLayout;
import java.io.IOException;
import java.util.LinkedHashMap;
import java.util.Map;

public final class ModuleLogin {
    private ModuleLogin() {
    }

    public static EmployeeSession authenticate(String moduleName, String expectedRole) {
        JTextField nombre = new JTextField(18);
        JPasswordField contrasena = new JPasswordField(18);
        JPanel formulario = new JPanel(new GridLayout(0, 1, 7, 7));
        formulario.add(new JLabel("Nombre del empleado"));
        formulario.add(nombre);
        formulario.add(new JLabel("Contraseña"));
        formulario.add(contrasena);

        int opcion = JOptionPane.showConfirmDialog(
                null,
                formulario,
                "Acceso - " + moduleName,
                JOptionPane.OK_CANCEL_OPTION,
                JOptionPane.PLAIN_MESSAGE);
        if (opcion != JOptionPane.OK_OPTION) {
            return null;
        }
        if (nombre.getText().trim().isEmpty() || contrasena.getPassword().length == 0) {
            JOptionPane.showMessageDialog(null, "Completa el nombre y la contraseña.", "Acceso", JOptionPane.WARNING_MESSAGE);
            return authenticate(moduleName, expectedRole);
        }

        try {
            Map<String, String> datos = new LinkedHashMap<>();
            datos.put("nombre", nombre.getText().trim());
            datos.put("contrasena", new String(contrasena.getPassword()));
            if (expectedRole != null && !expectedRole.isEmpty()) {
                datos.put("rol", expectedRole);
            }
            String cuerpo = ApiClient.post("login", datos);
            Map<String, String> respuesta = JsonUtil.parseObject(cuerpo);
            if (!"true".equals(respuesta.get("success"))) {
                JOptionPane.showMessageDialog(null, "Nombre, contraseña o rol incorrectos.", "Acceso denegado", JOptionPane.ERROR_MESSAGE);
                return authenticate(moduleName, expectedRole);
            }
            Map<String, String> usuario = extraerUsuario(cuerpo);
            return new EmployeeSession(
                    parseInt(usuario.get("id_usuario")),
                    usuario.getOrDefault("nombre", nombre.getText().trim()),
                    usuario.getOrDefault("apellido", ""),
                    usuario.getOrDefault("rol", expectedRole == null ? "" : expectedRole));
        } catch (IOException e) {
            JOptionPane.showMessageDialog(null, "No se pudo conectar con la API: " + e.getMessage(), "Conexion", JOptionPane.ERROR_MESSAGE);
            return null;
        }
    }

    public static EmployeeSession authenticateAllModules() {
        return authenticate("MetalGest", null);
    }

    private static Map<String, String> extraerUsuario(String respuesta) {
        int inicio = respuesta.indexOf("\"usuario\":");
        if (inicio < 0) {
            return new LinkedHashMap<>();
        }
        int objetoInicio = respuesta.indexOf('{', inicio);
        int profundidad = 0;
        boolean cadena = false;
        boolean escape = false;
        for (int i = objetoInicio; i < respuesta.length(); i++) {
            char caracter = respuesta.charAt(i);
            if (escape) {
                escape = false;
            } else if (caracter == '\\' && cadena) {
                escape = true;
            } else if (caracter == '"') {
                cadena = !cadena;
            } else if (!cadena && caracter == '{') {
                profundidad++;
            } else if (!cadena && caracter == '}' && --profundidad == 0) {
                return JsonUtil.parseObject(respuesta.substring(objetoInicio, i + 1));
            }
        }
        return new LinkedHashMap<>();
    }

    private static int parseInt(String valor) {
        try {
            return Integer.parseInt(valor);
        } catch (Exception e) {
            return 0;
        }
    }
}
