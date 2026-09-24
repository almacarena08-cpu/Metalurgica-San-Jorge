package modulos.calidad;

import modulos.calidad.dao.CalidadDao;
import modulos.common.EmployeeSession;
import modulos.common.ModuleLogin;
import modulos.common.SettingsDialog;
import modulos.common.UiTheme;
import modulos.common.dao.ApiResult;

import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTabbedPane;
import javax.swing.JTable;
import javax.swing.JTextArea;
import javax.swing.JTextField;
import javax.swing.SwingUtilities;
import javax.swing.table.DefaultTableModel;
import java.awt.BorderLayout;
import java.awt.GridLayout;
import java.io.IOException;
import java.time.LocalDate;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class QualityPanel {
    private static int usuarioId = 6;
    private static final CalidadDao calidadDao = new CalidadDao();

    public static void openCalidad() {
        EmployeeSession session = ModuleLogin.authenticate("Calidad", "Calidad");
        if (session != null) {
            openAuthenticated(session);
        }
    }

    public static void openAuthenticated(EmployeeSession session) {
        usuarioId = session.getId();
        SwingUtilities.invokeLater(() -> cargarMain(session.getNombreCompleto()));
    }

    public static JPanel createView(EmployeeSession session) {
        usuarioId = session.getId();
        JTabbedPane tabs = crearTabs();
        JPanel shell = UiTheme.shell("Calidad", "Control de calidad", session.getNombreCompleto(), entry -> {
            if ("Calidad".equals(entry) || "Inicio".equals(entry)) {
                tabs.setSelectedIndex(0);
            } else if ("Configuracion".equals(entry)) {
                SettingsDialog.open(session.getId());
            } else if (!"Calidad".equals(entry)) {
                JOptionPane.showMessageDialog(null, "Este modulo pertenece a otra aplicacion.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
            }
        });
        UiTheme.content(shell).add(tabs, BorderLayout.CENTER);
        return shell;
    }

    private static void cargarMain(String employeeName) {
        JFrame ventana = new JFrame("MetalGest - Calidad");
        UiTheme.configureWindow(ventana);
        ventana.setDefaultCloseOperation(JFrame.DISPOSE_ON_CLOSE);
        ventana.setLocationRelativeTo(null);
        JPanel shell = UiTheme.shell("Calidad", "Control de calidad", employeeName, entry -> { });
        UiTheme.content(shell).add(crearTabs(), BorderLayout.CENTER);
        ventana.add(shell);
        ventana.setVisible(true);
    }

    private static JTabbedPane crearTabs() {
        JTabbedPane tabs = new JTabbedPane();
        tabs.addTab("Registrar control", crearRegistro());
        tabs.addTab("Historial", crearHistorial());
        return tabs;
    }

    private static JPanel crearRegistro() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        panel.setBackground(UiTheme.BACKGROUND);
        DefaultTableModel modelo = new DefaultTableModel(new String[]{"ID", "Pedido", "Cliente", "Material", "Estado", "Avance"}, 0) {
            @Override public boolean isCellEditable(int row, int column) { return false; }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);

        JComboBox<String> resultado = new JComboBox<>(new String[]{"Aprobado", "Observado", "Rechazado"});
        JTextField fecha = new JTextField(LocalDate.now().toString());
        JTextArea observaciones = new JTextArea(3, 36);
        JButton registrar = new JButton("Registrar control");
        JButton recargar = new JButton("Recargar ordenes");
        UiTheme.styleInput(resultado);
        UiTheme.styleInput(fecha);
        UiTheme.styleInput(observaciones);
        UiTheme.styleButton(registrar, true);
        UiTheme.styleButton(recargar, false);
        JPanel form = new JPanel(new GridLayout(2, 4, 8, 8));
        form.add(new JLabel("Resultado"));
        form.add(new JLabel("Fecha"));
        form.add(new JLabel("Observaciones"));
        form.add(new JLabel("Acciones"));
        form.add(resultado);
        form.add(fecha);
        form.add(new JScrollPane(observaciones));
        JPanel acciones = new JPanel();
        acciones.add(registrar);
        acciones.add(recargar);
        form.add(acciones);
        panel.add(form, BorderLayout.SOUTH);

        recargarOrdenes(modelo);
        recargar.addActionListener(e -> recargarOrdenes(modelo));
        registrar.addActionListener(e -> {
            int row = tabla.getSelectedRow();
            if (row < 0) {
                mostrarAviso("Selecciona una orden para controlar.");
                return;
            }
            Map<String, String> datos = new LinkedHashMap<>();
            datos.put("id_orden", modelo.getValueAt(row, 0).toString());
            datos.put("id_usuario", String.valueOf(usuarioId));
            datos.put("fecha", fecha.getText().trim());
            datos.put("resultado", resultado.getSelectedItem().toString());
            datos.put("observaciones", observaciones.getText().trim());
            if (postControl(datos)) {
                observaciones.setText("");
                recargarOrdenes(modelo);
            }
        });
        return panel;
    }

    private static JPanel crearHistorial() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        panel.setBackground(UiTheme.BACKGROUND);
        DefaultTableModel modelo = new DefaultTableModel(new String[]{"ID", "Orden", "Cliente", "Material", "Fecha", "Resultado", "Observaciones"}, 0) {
            @Override public boolean isCellEditable(int row, int column) { return false; }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);
        JButton recargar = new JButton("Recargar historial");
        UiTheme.styleButton(recargar, false);
        panel.add(recargar, BorderLayout.SOUTH);
        recargarControles(modelo);
        recargar.addActionListener(e -> recargarControles(modelo));
        return panel;
    }

    private static void recargarOrdenes(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        for (Map<String, String> row : getRows("ordenes")) {
            modelo.addRow(new Object[]{row.get("id_orden"), row.get("id_pedido"), row.get("razon_social"), row.get("material"), row.get("estado"), row.get("avance") + "%"});
        }
    }

    private static void recargarControles(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        for (Map<String, String> row : getRows("controles")) {
            modelo.addRow(new Object[]{row.get("id_control"), row.get("id_orden"), row.get("razon_social"), row.get("material"), row.get("fecha"), row.get("resultado"), row.get("observaciones")});
        }
    }

    private static List<Map<String, String>> getRows(String tipo) {
        try {
            return "controles".equals(tipo) ? calidadDao.listarControles() : calidadDao.listarOrdenes();
        } catch (IOException e) {
            JOptionPane.showMessageDialog(null, "No se pudo conectar con MetalGest: " + e.getMessage(), "Conexion", JOptionPane.ERROR_MESSAGE);
            return java.util.Collections.emptyList();
        }
    }

    private static boolean postControl(Map<String, String> datos) {
        try {
            ApiResult result = calidadDao.registrarControl(datos);
            if (result.isSuccess()) {
                JOptionPane.showMessageDialog(null, "Control registrado.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
                return true;
            }
            JOptionPane.showMessageDialog(null, result.getBody(), "MetalGest", JOptionPane.ERROR_MESSAGE);
        } catch (IOException e) {
            JOptionPane.showMessageDialog(null, "No se pudo conectar con MetalGest: " + e.getMessage(), "Conexion", JOptionPane.ERROR_MESSAGE);
        }
        return false;
    }

    private static void mostrarAviso(String mensaje) {
        JOptionPane.showMessageDialog(null, mensaje, "MetalGest", JOptionPane.WARNING_MESSAGE);
    }

    public static void main(String[] args) {
        openCalidad();
    }
}
