package modulos.mantenimiento;

import modulos.common.dao.ApiResult;
import modulos.common.EmployeeSession;
import modulos.common.ModuleLogin;
import modulos.common.SettingsDialog;
import modulos.common.UiTheme;
import modulos.mantenimiento.dao.MantenimientoDao;

import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTable;
import javax.swing.JTextArea;
import javax.swing.JTextField;
import javax.swing.SwingUtilities;
import javax.swing.table.DefaultTableModel;
import java.awt.BorderLayout;
import java.awt.GridLayout;
import java.io.IOException;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class MaintenancePanel {
    private static final MantenimientoDao mantenimientoDao = new MantenimientoDao();

    public static void openMantenimiento() {
        EmployeeSession session = ModuleLogin.authenticate("Mantenimiento", "Mantenimiento");
        if (session != null) {
            openAuthenticated(session);
        }
    }

    public static void openAuthenticated(EmployeeSession session) {
        SwingUtilities.invokeLater(() -> cargarMain(session.getNombreCompleto(), session.getId()));
    }

    public static JPanel createView(EmployeeSession session) {
        JPanel shell = UiTheme.shell("Mantenimiento", "Gestion de mantenimiento", session.getNombreCompleto(), entry -> {
            if ("Configuracion".equals(entry)) {
                SettingsDialog.open(session.getId());
            } else if (!"Mantenimiento".equals(entry)) {
                JOptionPane.showMessageDialog(null, "Este módulo pertenece a otra aplicación.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
            }
        });
        UiTheme.content(shell).add(crearPanel(), BorderLayout.CENTER);
        return shell;
    }

    private static void cargarMain(String employeeName, int employeeId) {
        JFrame ventana = new JFrame("MetalGest - Mantenimiento");
        UiTheme.configureWindow(ventana);
        ventana.setDefaultCloseOperation(JFrame.DISPOSE_ON_CLOSE);
        ventana.setLocationRelativeTo(null);
        JPanel shell = UiTheme.shell("Mantenimiento", "Gestion de mantenimiento", employeeName, entry -> {
            if (!"Mantenimiento".equals(entry)) {
                JOptionPane.showMessageDialog(null, "Este módulo pertenece a otra aplicación.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
            }
        });
        UiTheme.content(shell).add(crearPanel(), BorderLayout.CENTER);
        ventana.add(shell);
        ventana.setVisible(true);
    }

    private static JPanel crearPanel() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        DefaultTableModel modelo = new DefaultTableModel(
                new String[]{"ID", "Maquina", "Marca", "Modelo", "Tipo", "Problema", "Reparacion", "Repuestos", "Estado"}, 0) {
            @Override
            public boolean isCellEditable(int row, int column) {
                return false;
            }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        tabla.setAutoResizeMode(JTable.AUTO_RESIZE_OFF);
        panel.setBackground(UiTheme.BACKGROUND);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);

        JTextArea reparacion = new JTextArea(3, 24);
        JTextArea repuestos = new JTextArea(3, 24);
        JComboBox<String> estado = new JComboBox<>(new String[]{"Pendiente", "En proceso", "Resuelta"});
        JButton actualizar = new JButton("Guardar mantenimiento");
        JButton preventivo = new JButton("Programar preventivo");
        JButton recargar = new JButton("Recargar");
        UiTheme.styleInput(reparacion);
        UiTheme.styleInput(repuestos);
        UiTheme.styleInput(estado);
        UiTheme.styleButton(actualizar, true);
        UiTheme.styleButton(preventivo, true);
        UiTheme.styleButton(recargar, false);
        JPanel formulario = new JPanel(new GridLayout(2, 4, 8, 8));
        formulario.add(new JLabel("Reparacion realizada"));
        formulario.add(new JLabel("Repuestos utilizados"));
        formulario.add(new JLabel("Estado"));
        formulario.add(new JLabel("Acciones"));
        formulario.add(new JScrollPane(reparacion));
        formulario.add(new JScrollPane(repuestos));
        formulario.add(estado);
        JPanel botones = new JPanel();
        botones.add(actualizar);
        botones.add(preventivo);
        botones.add(recargar);
        formulario.add(botones);
        panel.add(formulario, BorderLayout.SOUTH);

        recargar.addActionListener(e -> cargarMantenimientos(modelo));
        tabla.getSelectionModel().addListSelectionListener(e -> {
            if (!e.getValueIsAdjusting() && tabla.getSelectedRow() >= 0) {
                reparacion.setText(valor(modelo, tabla.getSelectedRow(), 6));
                repuestos.setText(valor(modelo, tabla.getSelectedRow(), 7));
                estado.setSelectedItem(valor(modelo, tabla.getSelectedRow(), 8));
            }
        });
        actualizar.addActionListener(e -> {
            int fila = tabla.getSelectedRow();
            if (fila < 0) {
                mostrarAviso("Selecciona un mantenimiento.");
                return;
            }
            if (reparacion.getText().trim().isEmpty()) {
                mostrarAviso("Describe la reparacion realizada.");
                return;
            }
            Map<String, String> datos = new LinkedHashMap<>();
            datos.put("id_mantenimiento", valor(modelo, fila, 0));
            datos.put("reparacion", reparacion.getText().trim());
            datos.put("repuestos", repuestos.getText().trim());
            datos.put("estado", estado.getSelectedItem().toString());
            if (guardar(datos)) {
                cargarMantenimientos(modelo);
            }
        });
        preventivo.addActionListener(e -> programarPreventivo(tabla, modelo));
        cargarMantenimientos(modelo);
        return panel;
    }

    private static void programarPreventivo(JTable tabla, DefaultTableModel modelo) {
        int fila = tabla.getSelectedRow();
        if (fila < 0) {
            mostrarAviso("Selecciona una maquina.");
            return;
        }
        JTextField fecha = new JTextField(java.time.LocalDate.now().plusMonths(1).toString());
        JTextArea tarea = new JTextArea(3, 24);
        JPanel dialogo = new JPanel(new GridLayout(0, 1, 6, 6));
        dialogo.add(new JLabel("Fecha programada (AAAA-MM-DD)"));
        dialogo.add(fecha);
        dialogo.add(new JLabel("Control preventivo"));
        dialogo.add(new JScrollPane(tarea));
        int resultado = JOptionPane.showConfirmDialog(null, dialogo, "Programar mantenimiento preventivo", JOptionPane.OK_CANCEL_OPTION);
        if (resultado != JOptionPane.OK_OPTION || tarea.getText().trim().isEmpty()) {
            return;
        }
        Map<String, String> datos = new LinkedHashMap<>();
        datos.put("id_maquina", valor(modelo, fila, 0));
        datos.put("fecha", fecha.getText().trim());
        datos.put("problema", tarea.getText().trim());
        try {
            ApiResult respuesta = mantenimientoDao.crearPreventivo(datos);
            if (respuesta.isSuccess()) {
                JOptionPane.showMessageDialog(null, "Mantenimiento preventivo programado.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
                cargarMantenimientos(modelo);
            } else {
                JOptionPane.showMessageDialog(null, respuesta.getBody(), "MetalGest", JOptionPane.ERROR_MESSAGE);
            }
        } catch (IOException e) {
            mostrarError(e);
        }
    }

    private static void cargarMantenimientos(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        try {
            List<Map<String, String>> filas = mantenimientoDao.listar();
            for (Map<String, String> fila : filas) {
                modelo.addRow(new Object[]{
                        fila.get("id_mantenimiento"), fila.get("numero_identificacion"), fila.get("marca"),
                        fila.get("modelo"), fila.get("tipo"), fila.get("problema"), fila.get("reparacion"),
                        fila.get("repuestos"), fila.get("estado")
                });
            }
        } catch (IOException e) {
            mostrarError(e);
        }
    }

    private static boolean guardar(Map<String, String> datos) {
        try {
            ApiResult resultado = mantenimientoDao.actualizar(datos);
            if (resultado.isSuccess()) {
                JOptionPane.showMessageDialog(null, "Mantenimiento actualizado.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
                return true;
            }
            JOptionPane.showMessageDialog(null, resultado.getBody(), "MetalGest", JOptionPane.ERROR_MESSAGE);
        } catch (IOException e) {
            mostrarError(e);
        }
        return false;
    }

    private static String valor(DefaultTableModel modelo, int fila, int columna) {
        Object valor = modelo.getValueAt(fila, columna);
        return valor == null ? "" : valor.toString();
    }

    private static void mostrarAviso(String mensaje) {
        JOptionPane.showMessageDialog(null, mensaje, "MetalGest", JOptionPane.WARNING_MESSAGE);
    }

    private static void mostrarError(IOException error) {
        JOptionPane.showMessageDialog(null, "No se pudo conectar con la API: " + error.getMessage(), "Conexion", JOptionPane.ERROR_MESSAGE);
    }

    public static void main(String[] args) {
        openMantenimiento();
    }
}
