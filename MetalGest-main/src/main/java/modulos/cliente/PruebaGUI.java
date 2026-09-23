package modulos.cliente;

import javax.swing.*;
import javax.swing.table.DefaultTableModel;
import java.awt.BorderLayout;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.io.*;
import java.net.*;
import java.util.regex.*;

public class PruebaGUI {

    private static final String API_BASE = "http://localhost/MetalGest/index.php";

    public static void recargarTabla(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        try {
            URL url = new URL(API_BASE + "?action=list_client");
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("GET");
            conn.setConnectTimeout(3000);
            conn.setReadTimeout(3000);

            int status = conn.getResponseCode();
            InputStream is = (status >= 200 && status < 400) ? conn.getInputStream() : conn.getErrorStream();
            BufferedReader br = new BufferedReader(new InputStreamReader(is, "UTF-8"));
            StringBuilder sb = new StringBuilder();
            String line;
            while ((line = br.readLine()) != null) sb.append(line);
            br.close();
            String json = sb.toString().trim();

            if (json.startsWith("[")) {
                Pattern p = Pattern.compile("\\{[^}]*\\}");
                Matcher m = p.matcher(json);
                Pattern fields = Pattern.compile("\"id\"\s*:\s*(\\d+).*?\"nombre\"\s*:\s*\"([^\"]*)\".*?\"cantidad\"\s*:\s*(\\d+)", Pattern.DOTALL);
                while (m.find()) {
                    String obj = m.group();
                    Matcher fm = fields.matcher(obj);
                    if (fm.find()) {
                        int id = Integer.parseInt(fm.group(1));
                        String nombre = fm.group(2);
                        int cantidad = Integer.parseInt(fm.group(3));
                        Object[] fila = {id, nombre, cantidad};
                        modelo.addRow(fila);
                    }
                }
            }
        } catch (IOException e) {
            JOptionPane.showMessageDialog(null, "Error al cargar datos desde la API: " + e.getMessage(), "Error HTTP", JOptionPane.ERROR_MESSAGE);
        }
    }

    public static boolean solicitarLibroEnBD(String nombre, int cantidad) {
        try {
            URL url = new URL(API_BASE + "?action=request");
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setDoOutput(true);
            conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");

            String post = "nombre=" + URLEncoder.encode(nombre, "UTF-8") + "&cantidad=" + URLEncoder.encode(String.valueOf(cantidad), "UTF-8");
            try (OutputStream os = conn.getOutputStream()) {
                os.write(post.getBytes("UTF-8"));
            }

            int status = conn.getResponseCode();
            InputStream is = (status >= 200 && status < 400) ? conn.getInputStream() : conn.getErrorStream();
            BufferedReader br = new BufferedReader(new InputStreamReader(is, "UTF-8"));
            StringBuilder sb = new StringBuilder();
            String line;
            while ((line = br.readLine()) != null) sb.append(line);
            br.close();
            String resp = sb.toString().trim();
            return resp.contains("\"success\":true");
        } catch (IOException e) {
            JOptionPane.showMessageDialog(null, "Error al solicitar vía API: " + e.getMessage(), "Error HTTP", JOptionPane.ERROR_MESSAGE);
            return false;
        }
    }

    public static void cargarMain() {
        JFrame vent = new JFrame("Cliente - Gestión de Biblioteca");
        vent.setSize(600, 400);
        vent.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        vent.setLocationRelativeTo(null);

        JPanel panelPrincipal = new JPanel(new BorderLayout());
        vent.add(panelPrincipal);

        JPanel panelEntrada = new JPanel();

        JLabel etiNombre = new JLabel("Nombre:");
        panelEntrada.add(etiNombre);

        JTextField txtNombre = new JTextField(12);
        panelEntrada.add(txtNombre);

        JLabel etiCantidad = new JLabel("Cantidad:");
        panelEntrada.add(etiCantidad);

        JTextField txtCantidad = new JTextField(5);
        panelEntrada.add(txtCantidad);

        JButton btnAnadir = new JButton("Solicitar");
        panelEntrada.add(btnAnadir);

        panelPrincipal.add(panelEntrada, BorderLayout.NORTH);

        String[] columnas = {"ID", "Nombre del Libro", "Cantidad disponible"};

        DefaultTableModel modelo = new DefaultTableModel(columnas, 0) {
            @Override
            public boolean isCellEditable(int row, int column) {
                return false;
            }
        };

        JTable tabla = new JTable(modelo);
        JScrollPane scrollPane = new JScrollPane(tabla);
        panelPrincipal.add(scrollPane, BorderLayout.CENTER);

        JPanel panelInferior = new JPanel();
        JButton btnRecargar = new JButton("Recargar Tabla");
        panelInferior.add(btnRecargar);
        panelPrincipal.add(panelInferior, BorderLayout.SOUTH);

        recargarTabla(modelo);

        vent.setVisible(true);

        btnRecargar.addActionListener(new ActionListener() {
            @Override
            public void actionPerformed(ActionEvent e) {
                recargarTabla(modelo);
            }
        });

        btnAnadir.addActionListener(e -> {
            try {
                String nombre = txtNombre.getText().trim();
                String cantTexto = txtCantidad.getText().trim();

                if (!nombre.isEmpty() && !cantTexto.isEmpty()) {
                    int cant = Integer.parseInt(cantTexto);

                    if (cant < 0) {
                        JOptionPane.showMessageDialog(vent, "La cantidad no puede ser negativa", "Advertencia", JOptionPane.WARNING_MESSAGE);
                        return;
                    }

                    boolean exito = solicitarLibroEnBD(nombre, cant);

                    if (exito) {
                        JOptionPane.showMessageDialog(vent, "Solicitud realizada con éxito.", "Éxito", JOptionPane.INFORMATION_MESSAGE);
                        txtNombre.setText("");
                        txtCantidad.setText("");

                        recargarTabla(modelo);
                    } else {
                        JOptionPane.showMessageDialog(vent, "No se pudo completar la solicitud (sin stock o no existe).", "Error", JOptionPane.ERROR_MESSAGE);
                    }
                } else {
                    JOptionPane.showMessageDialog(vent, "Por favor, completa todos los campos.", "Campos vacíos", JOptionPane.WARNING_MESSAGE);
                }
            } catch (NumberFormatException ex) {
                JOptionPane.showMessageDialog(vent, "La cantidad debe ser un número entero válido.", "Error de formato", JOptionPane.ERROR_MESSAGE);
            }
        });
    }

    public static void openCliente() {
        SwingUtilities.invokeLater(PruebaGUI::cargarMain);
    }

    public static void main(String[] args) {
        openCliente();
    }
}
