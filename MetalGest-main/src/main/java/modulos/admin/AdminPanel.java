package modulos.admin;

import javax.swing.*;
import javax.swing.table.DefaultTableModel;
import java.awt.BorderLayout;
import java.io.*;
import java.net.*;

public class AdminPanel {
    private static final String API_BASE = "http://localhost/MetalGest/index.php";

    public static void recargarTabla(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        try {
            URL url = new URL(API_BASE + "?action=list_admin");
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
                String[] objs = json.substring(1, json.length()-1).split("\\},\\{");
                for (String obj : objs) {
                    obj = obj.replaceAll("^\\{", "{").replaceAll("}$_", "}");
                    String idS = obj.replaceAll("(?s).*\"id\"\s*:\s*(\\d+).*", "$1");
                    String nombre = obj.replaceAll("(?s).*\"nombre\"\s*:\s*\"([^\"]*)\".*", "$1");
                    String cantS = obj.replaceAll("(?s).*\"cantidad\"\s*:\s*(\\d+).*", "$1");
                    try {
                        int id = Integer.parseInt(idS);
                        int cantidad = Integer.parseInt(cantS);
                        Object[] fila = {id, nombre, cantidad};
                        modelo.addRow(fila);
                    } catch (NumberFormatException ex) {
                        // ignore parse errors
                    }
                }
            }
        } catch (IOException e) {
            JOptionPane.showMessageDialog(null, "Error al cargar datos desde la API: " + e.getMessage(), "Error HTTP", JOptionPane.ERROR_MESSAGE);
        }
    }

    public static boolean eliminarLibro(int id) {
        try {
            URL url = new URL(API_BASE + "?action=delete");
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setDoOutput(true);
            conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8");

            String post = "id=" + URLEncoder.encode(String.valueOf(id), "UTF-8");
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
            JOptionPane.showMessageDialog(null, "Error al eliminar vía API: " + e.getMessage(), "Error HTTP", JOptionPane.ERROR_MESSAGE);
            return false;
        }
    }

    public static boolean crearLibro(String nombre, int cantidad) {
        try {
            URL url = new URL(API_BASE + "?action=create");
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
            JOptionPane.showMessageDialog(null, "Error al crear vía API: " + e.getMessage(), "Error HTTP", JOptionPane.ERROR_MESSAGE);
            return false;
        }
    }

    public static void cargarMain() {
        JFrame vent = new JFrame("Admin - Gestión de Biblioteca");
        vent.setSize(600, 400);
        vent.setDefaultCloseOperation(JFrame.DISPOSE_ON_CLOSE);
        vent.setLocationRelativeTo(null);

        JPanel panelPrincipal = new JPanel(new BorderLayout());
        vent.add(panelPrincipal);

        JPanel panelTop = new JPanel();
        JLabel etiNombre = new JLabel("Nombre:");
        JTextField txtNombre = new JTextField(12);
        JLabel etiCantidad = new JLabel("Cantidad:");
        JTextField txtCantidad = new JTextField(5);
        JButton btnAgregar = new JButton("Agregar Libro");
        panelTop.add(etiNombre);
        panelTop.add(txtNombre);
        panelTop.add(etiCantidad);
        panelTop.add(txtCantidad);
        panelTop.add(btnAgregar);
        panelPrincipal.add(panelTop, BorderLayout.NORTH);

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
        JButton btnEliminar = new JButton("Eliminar Seleccionado");
        panelInferior.add(btnRecargar);
        panelInferior.add(btnEliminar);
        panelPrincipal.add(panelInferior, BorderLayout.SOUTH);

        recargarTabla(modelo);

        vent.setVisible(true);

        btnRecargar.addActionListener(e -> recargarTabla(modelo));

        btnAgregar.addActionListener(e -> {
            try {
                String nombre = txtNombre.getText().trim();
                String cantText = txtCantidad.getText().trim();
                if (nombre.isEmpty() || cantText.isEmpty()) {
                    JOptionPane.showMessageDialog(vent, "Completa nombre y cantidad.", "Error", JOptionPane.WARNING_MESSAGE);
                    return;
                }
                int cant = Integer.parseInt(cantText);
                if (cant < 0) {
                    JOptionPane.showMessageDialog(vent, "Cantidad inválida.", "Error", JOptionPane.WARNING_MESSAGE);
                    return;
                }
                boolean ok = crearLibro(nombre, cant);
                if (ok) {
                    JOptionPane.showMessageDialog(vent, "Libro agregado.", "Éxito", JOptionPane.INFORMATION_MESSAGE);
                    txtNombre.setText(""); txtCantidad.setText("");
                    recargarTabla(modelo);
                } else {
                    JOptionPane.showMessageDialog(vent, "No se pudo agregar el libro.", "Error", JOptionPane.ERROR_MESSAGE);
                }
            } catch (NumberFormatException ex) {
                JOptionPane.showMessageDialog(vent, "Cantidad debe ser número.", "Error", JOptionPane.WARNING_MESSAGE);
            }
        });

        btnEliminar.addActionListener(e -> {
            int row = tabla.getSelectedRow();
            if (row >= 0) {
                int id = (int) modelo.getValueAt(row, 0);
                int ok = JOptionPane.showConfirmDialog(vent, "Eliminar libro ID=" + id + "?", "Confirmar", JOptionPane.YES_NO_OPTION);
                if (ok == JOptionPane.YES_OPTION) {
                    boolean exito = eliminarLibro(id);
                    if (exito) {
                        JOptionPane.showMessageDialog(vent, "Libro eliminado.", "Éxito", JOptionPane.INFORMATION_MESSAGE);
                        recargarTabla(modelo);
                    } else {
                        JOptionPane.showMessageDialog(vent, "No se pudo eliminar el libro.", "Error", JOptionPane.ERROR_MESSAGE);
                    }
                }
            } else {
                JOptionPane.showMessageDialog(vent, "Selecciona una fila para eliminar.", "Sin selección", JOptionPane.WARNING_MESSAGE);
            }
        });
    }

    public static void openAdmin() {
        SwingUtilities.invokeLater(AdminPanel::cargarMain);
    }

    public static void main(String[] args) {
        openAdmin();
    }
}
