package modulos.common;

import javax.swing.BorderFactory;
import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JPasswordField;
import javax.swing.JTextField;
import java.awt.BorderLayout;
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
    private final JComboBox<String> idioma = new JComboBox<>(new String[]{"Español", "English"});
    private final JLabel subtitle = new JLabel();
    private final JLabel usuarioLabel = new JLabel();
    private final JLabel contrasenaLabel = new JLabel();
    private final JButton ingresar = new JButton();

    public ModuleLoginPanel(Consumer<EmployeeSession> onSuccess) {
        setLayout(new GridBagLayout());
        setBackground(UiTheme.BACKGROUND);

        JPanel card = new JPanel(new GridBagLayout());
        card.setBackground(UiTheme.WHITE);
        card.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(UiTheme.LINE),
            BorderFactory.createEmptyBorder(20, 22, 20, 22)));
        GridBagConstraints c = new GridBagConstraints();
        c.gridx = 0;
        c.fill = GridBagConstraints.HORIZONTAL;
        c.weightx = 1;
        c.insets = new Insets(6, 0, 6, 0);

        JLabel title = new JLabel("MetalGest");
        title.setForeground(UiTheme.ORANGE);
        title.setFont(new Font("SansSerif", Font.BOLD, 28));
        addToCard(card, new IndustrialBanner(), c, 0);
        addToCard(card, title, c, 1);
        subtitle.setForeground(UiTheme.MUTED);
        addToCard(card, subtitle, c, 2);
        usuarioLabel.setForeground(UiTheme.INK);
        addToCard(card, usuarioLabel, c, 3);
        UiTheme.styleInput(nombre);
        addToCard(card, nombre, c, 4);
        contrasenaLabel.setForeground(UiTheme.INK);
        addToCard(card, contrasenaLabel, c, 5);
        UiTheme.styleInput(contrasena);
        addToCard(card, contrasena, c, 6);
        UiTheme.styleButton(ingresar, true);
        JLabel idiomaLabel = new JLabel();
        idiomaLabel.setForeground(UiTheme.INK);
        addToCard(card, idiomaLabel, c, 7);
        addToCard(card, idioma, c, 8);
        addToCard(card, ingresar, c, 9);
        estado.setForeground(UiTheme.YELLOW);
        addToCard(card, estado, c, 10);
        add(card, new GridBagConstraints());

        idioma.addActionListener(event -> {
            boolean ingles = idioma.getSelectedIndex() == 1;
            idiomaLabel.setText(ingles ? "Language" : "Idioma");
            subtitle.setText(ingles ? "Secure system access" : "Acceso seguro al sistema");
            usuarioLabel.setText(ingles ? "Username" : "Usuario");
            contrasenaLabel.setText(ingles ? "Password" : "Contraseña");
            ingresar.setText(ingles ? "Sign in" : "Ingresar");
            estado.setText(" ");
        });
        idioma.setSelectedIndex(AppLanguage.isEnglish() ? 1 : 0);
        AppLanguage.setEnglish(idioma.getSelectedIndex() == 1);

        ingresar.addActionListener(event -> autenticar(onSuccess));
        contrasena.addActionListener(event -> autenticar(onSuccess));
    }

    private void addToCard(JPanel card, java.awt.Component component, GridBagConstraints base, int row) {
        GridBagConstraints c = (GridBagConstraints) base.clone();
        c.gridy = row;
        card.add(component, c);
    }

    private void autenticar(Consumer<EmployeeSession> onSuccess) {
            boolean ingles = idioma.getSelectedIndex() == 1;
            AppLanguage.setEnglish(ingles);
        String nombreIngresado = nombre.getText().trim();
        String clave = new String(contrasena.getPassword());
        if (nombreIngresado.isEmpty() || clave.isEmpty()) {
            estado.setText(ingles ? "Enter your username and password." : "Completa nombre y contraseña.");
            return;
        }
        try {
            Map<String, String> datos = new LinkedHashMap<>();
            datos.put("usuario", nombreIngresado);
            datos.put("contrasena", clave);
            String cuerpo = ApiClient.post("login", datos);
            if (cuerpo == null || !cuerpo.trim().startsWith("{")) {
                estado.setText(ingles ? "Could not validate access with the API." : "No se pudo validar el acceso con la API.");
                return;
            }
            Map<String, String> respuesta = JsonUtil.parseObject(cuerpo);
            if (!"true".equals(respuesta.get("success"))) {
                String mensaje = respuesta.get("message");
                if (mensaje == null || mensaje.isEmpty() || "Credenciales incorrectas".equalsIgnoreCase(mensaje)) {
                    estado.setText(ingles ? "Incorrect username or password." : "Nombre o contraseña incorrectos.");
                } else {
                    estado.setText(mensaje);
                }
                return;
            }
            Map<String, String> usuario = extraerUsuario(cuerpo);
            if ("Cliente".equalsIgnoreCase(usuario.getOrDefault("rol", ""))) {
                estado.setText(ingles ? "Customer access is unavailable in this application." : "El acceso de clientes no está disponible en esta aplicación.");
                return;
            }
            try {
                onSuccess.accept(new EmployeeSession(
                        parseInt(usuario.get("id_usuario")),
                        usuario.getOrDefault("nombre", nombreIngresado),
                        usuario.getOrDefault("apellido", ""),
                        usuario.getOrDefault("rol", ""),
                        usuario.getOrDefault("usuario", nombreIngresado)));
            } catch (RuntimeException error) {
                error.printStackTrace();
                estado.setText(ingles ? "Access was validated, but the module could not be opened ("
                        + error.getClass().getSimpleName() + "). Check the console." : "El acceso fue validado, pero no se pudo abrir el módulo ("
                        + error.getClass().getSimpleName() + "). Revisá la consola.");
            }
        } catch (IOException error) {
            estado.setText(ingles ? "Could not connect to the API." : "No se pudo conectar con la API.");
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
