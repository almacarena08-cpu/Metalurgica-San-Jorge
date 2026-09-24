package modulos.common;

import javax.swing.BorderFactory;
import javax.swing.JButton;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JPasswordField;
import javax.swing.JTextField;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Font;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Insets;
import java.io.IOException;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.function.Consumer;

public class ModuleLoginPanel extends JPanel {
    private final JTextField nombre = new JTextField(22);
    private final JPasswordField contrasena = new JPasswordField(22);
    private final JLabel estado = new JLabel(" ");

    public ModuleLoginPanel(Consumer<EmployeeSession> onSuccess) {
        setLayout(new GridBagLayout());
        setBackground(UiTheme.BACKGROUND);

        JPanel card = new JPanel(new GridBagLayout());
        card.setBackground(Color.WHITE);
        card.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(UiTheme.LINE),
            BorderFactory.createEmptyBorder(20, 22, 20, 22)));
        GridBagConstraints c = new GridBagConstraints();
        c.gridx = 0;
        c.fill = GridBagConstraints.HORIZONTAL;
        c.weightx = 1;
        c.insets = new Insets(6, 0, 6, 0);

        JLabel title = new JLabel("MetalGest");
        title.setForeground(UiTheme.NAVY);
        title.setFont(new Font("SansSerif", Font.BOLD, 28));
        addToCard(card, new IndustrialBanner(), c, 0);
        addToCard(card, title, c, 1);
        JLabel subtitle = new JLabel("Acceso seguro al sistema");
        subtitle.setForeground(UiTheme.MUTED);
        addToCard(card, subtitle, c, 2);
        addToCard(card, new JLabel("Usuario"), c, 3);
        UiTheme.styleInput(nombre);
        addToCard(card, nombre, c, 4);
        addToCard(card, new JLabel("Contrasena"), c, 5);
        UiTheme.styleInput(contrasena);
        addToCard(card, contrasena, c, 6);
        JButton ingresar = new JButton("Ingresar");
        UiTheme.styleButton(ingresar, true);
        addToCard(card, ingresar, c, 7);
        estado.setForeground(new Color(190, 55, 65));
        addToCard(card, estado, c, 8);
        add(card, new GridBagConstraints());

        ingresar.addActionListener(event -> autenticar(onSuccess));
        contrasena.addActionListener(event -> autenticar(onSuccess));
    }

    private void addToCard(JPanel card, java.awt.Component component, GridBagConstraints base, int row) {
        GridBagConstraints c = (GridBagConstraints) base.clone();
        c.gridy = row;
        card.add(component, c);
    }

    private void autenticar(Consumer<EmployeeSession> onSuccess) {
        String nombreIngresado = nombre.getText().trim();
        String clave = new String(contrasena.getPassword());
        if (nombreIngresado.isEmpty() || clave.isEmpty()) {
            estado.setText("Completa nombre y contraseña.");
            return;
        }
        try {
            Map<String, String> datos = new LinkedHashMap<>();
            datos.put("nombre", nombreIngresado);
            datos.put("contrasena", clave);
            String cuerpo = ApiClient.post("login", datos);
            if (cuerpo == null || !cuerpo.trim().startsWith("{")) {
                estado.setText("No se pudo validar el acceso con la API.");
                return;
            }
            Map<String, String> respuesta = JsonUtil.parseObject(cuerpo);
            if (!"true".equals(respuesta.get("success"))) {
                estado.setText("Nombre o contraseña incorrectos.");
                return;
            }
            Map<String, String> usuario = extraerUsuario(cuerpo);
            onSuccess.accept(new EmployeeSession(
                    parseInt(usuario.get("id_usuario")),
                    usuario.getOrDefault("nombre", nombreIngresado),
                    usuario.getOrDefault("apellido", ""),
                    usuario.getOrDefault("rol", "")));
        } catch (IOException error) {
            estado.setText("No se pudo conectar con la API.");
        }
    }

    private Map<String, String> extraerUsuario(String respuesta) {
        int inicio = respuesta.indexOf("\"usuario\":");
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

    private int parseInt(String valor) {
        try {
            return Integer.parseInt(valor);
        } catch (Exception error) {
            return 0;
        }
    }
}
