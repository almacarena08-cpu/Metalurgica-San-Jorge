package modulos.produccion;

import modulos.common.dao.ApiResult;
import modulos.common.EmployeeSession;
import modulos.common.ModuleLogin;
import modulos.common.SettingsDialog;
import modulos.common.UiTheme;
import modulos.produccion.dao.ProduccionDao;

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
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class ProductionPanel {
    private static int usuarioId = 2;
    private static final List<Map<String, String>> maquinas = new ArrayList<>();
    private static final ProduccionDao produccionDao = new ProduccionDao();

    public static void openProduccion() {
        EmployeeSession session = ModuleLogin.authenticate("Produccion", "Produccion");
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
        JTabbedPane tabs = new JTabbedPane();
        tabs.setFont(new java.awt.Font("SansSerif", java.awt.Font.BOLD, 12));
        tabs.addTab("Ordenes recibidas", crearPanelOrdenes());
        tabs.addTab("Historial de avance", crearPanelHistorial());
        tabs.addTab("Aviso a mantenimiento", crearPanelMantenimiento());
        JPanel shell = UiTheme.shell("Produccion", "Panel de Produccion", session.getNombreCompleto(), entry -> {
            if ("Produccion".equals(entry) || "Ordenes de trabajo".equals(entry)) {
                tabs.setSelectedIndex(0);
            } else if ("Mantenimiento".equals(entry)) {
                tabs.setSelectedIndex(2);
            } else if ("Configuracion".equals(entry)) {
                SettingsDialog.open(session.getId());
            } else if (!"Administracion".equals(entry)) {
                JOptionPane.showMessageDialog(null, "Este módulo pertenece a otra aplicación.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
            }
        });
        UiTheme.content(shell).add(tabs, BorderLayout.CENTER);
        return shell;
    }

    private static void cargarMain(String employeeName) {
        JFrame ventana = new JFrame("MetalGest - Produccion");
        UiTheme.configureWindow(ventana);
        ventana.setDefaultCloseOperation(JFrame.DISPOSE_ON_CLOSE);
        ventana.setLocationRelativeTo(null);

        JTabbedPane tabs = new JTabbedPane();
        tabs.setFont(new java.awt.Font("SansSerif", java.awt.Font.BOLD, 12));
        tabs.addTab("Ordenes recibidas", crearPanelOrdenes());
        tabs.addTab("Historial de avance", crearPanelHistorial());
        tabs.addTab("Aviso a mantenimiento", crearPanelMantenimiento());
        JPanel shell = UiTheme.shell("Produccion", "Panel de Produccion", employeeName, entry -> {
            if ("Produccion".equals(entry) || "Ordenes de trabajo".equals(entry)) {
                tabs.setSelectedIndex(0);
            } else if ("Mantenimiento".equals(entry)) {
                tabs.setSelectedIndex(2);
            } else if (!"Administracion".equals(entry)) {
                JOptionPane.showMessageDialog(null, "Este módulo pertenece a otra aplicación.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
            }
        });
        JPanel content = UiTheme.content(shell);
        content.add(tabs, BorderLayout.CENTER);
        ventana.add(shell);

        ventana.setVisible(true);
    }

    private static JPanel crearPanelOrdenes() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        DefaultTableModel modelo = new DefaultTableModel(
                new String[]{"ID", "Pedido", "Cliente", "Descripcion", "Material", "Cantidad", "Estado", "Avance"}, 0) {
            @Override
            public boolean isCellEditable(int row, int column) {
                return false;
            }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.setBackground(UiTheme.BACKGROUND);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);

        JPanel form = new JPanel(new GridLayout(2, 5, 8, 8));
        JTextField cantidad = new JTextField("0");
        JTextField avance = new JTextField("0");
        JTextArea observaciones = new JTextArea(3, 24);
        JButton registrar = new JButton("Registrar avance");
        JButton recargar = new JButton("Recargar");
        UiTheme.styleInput(cantidad);
        UiTheme.styleInput(avance);
        UiTheme.styleInput(observaciones);
        UiTheme.styleButton(registrar, true);
        UiTheme.styleButton(recargar, false);
        form.add(new JLabel("Cantidad producida"));
        form.add(new JLabel("Avance %"));
        form.add(new JLabel("Observaciones"));
        form.add(new JLabel(""));
        form.add(new JLabel(""));
        form.add(cantidad);
        form.add(avance);
        form.add(new JScrollPane(observaciones));
        form.add(registrar);
        form.add(recargar);
        panel.add(form, BorderLayout.SOUTH);

        recargarOrdenes(modelo);

        recargar.addActionListener(e -> recargarOrdenes(modelo));
        registrar.addActionListener(e -> {
            int row = tabla.getSelectedRow();
            if (row < 0) {
                mostrarAviso("Selecciona una orden de trabajo.");
                return;
            }
            if (!esNumeroNoNegativo(cantidad.getText()) || !esAvanceValido(avance.getText())) {
                mostrarAviso("Ingresa una cantidad no negativa y un avance entre 0 y 100.");
                return;
            }
            Map<String, String> data = new LinkedHashMap<>();
            data.put("id_orden", modelo.getValueAt(row, 0).toString());
            data.put("cantidad_producida", cantidad.getText().trim());
            data.put("avance", avance.getText().trim());
            data.put("observaciones", observaciones.getText().trim());
            if (postOk("produccion_register", data, "Avance registrado.")) {
                cantidad.setText("0");
                avance.setText("0");
                observaciones.setText("");
                recargarOrdenes(modelo);
            }
        });

        return panel;
    }

    private static JPanel crearPanelHistorial() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        DefaultTableModel modelo = new DefaultTableModel(
                new String[]{"ID", "Orden", "Cliente", "Material", "Cantidad", "Avance", "Inicio", "Fin", "Observaciones"}, 0) {
            @Override
            public boolean isCellEditable(int row, int column) {
                return false;
            }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.setBackground(UiTheme.BACKGROUND);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);
        JButton recargar = new JButton("Recargar historial");
        UiTheme.styleButton(recargar, false);
        panel.add(recargar, BorderLayout.SOUTH);
        recargarHistorial(modelo);
        recargar.addActionListener(e -> recargarHistorial(modelo));
        return panel;
    }

    private static JPanel crearPanelMantenimiento() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        DefaultTableModel modelo = new DefaultTableModel(
                new String[]{"ID", "Identificacion", "Marca", "Modelo", "Ubicacion", "Estado"}, 0) {
            @Override
            public boolean isCellEditable(int row, int column) {
                return false;
            }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.setBackground(UiTheme.BACKGROUND);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);

        JPanel form = new JPanel(new BorderLayout(8, 8));
        JTextArea problema = new JTextArea(4, 50);
        JButton reportar = new JButton("Reportar falla");
        JButton recargar = new JButton("Recargar maquinas");
        UiTheme.styleInput(problema);
        UiTheme.styleButton(reportar, true);
        UiTheme.styleButton(recargar, false);
        JPanel botones = new JPanel();
        botones.add(reportar);
        botones.add(recargar);
        form.add(new JScrollPane(problema), BorderLayout.CENTER);
        form.add(botones, BorderLayout.EAST);
        panel.add(form, BorderLayout.SOUTH);

        recargarMaquinas(modelo);

        recargar.addActionListener(e -> recargarMaquinas(modelo));
        reportar.addActionListener(e -> {
            int row = tabla.getSelectedRow();
            if (row < 0) {
                mostrarAviso("Selecciona la maquina con falla.");
                return;
            }
            if (problema.getText().trim().isEmpty()) {
                mostrarAviso("Describe la falla para Mantenimiento.");
                return;
            }
            Map<String, String> data = new LinkedHashMap<>();
            data.put("id_maquina", modelo.getValueAt(row, 0).toString());
            data.put("id_usuario", String.valueOf(usuarioId));
            data.put("tipo", "Correctivo");
            data.put("problema", problema.getText().trim());
            if (postOk("mantenimiento_report", data, "Aviso enviado a Mantenimiento.")) {
                problema.setText("");
                recargarMaquinas(modelo);
            }
        });

        return panel;
    }

    private static void recargarOrdenes(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        for (Map<String, String> row : getRows("ordenes_list")) {
            modelo.addRow(new Object[]{
                    row.get("id_orden"),
                    row.get("id_pedido"),
                    row.get("razon_social"),
                    row.get("descripcion"),
                    row.get("material"),
                    row.get("cantidad"),
                    row.get("estado"),
                    row.get("avance") + "%"
            });
        }
    }

    private static void recargarHistorial(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        for (Map<String, String> row : getRows("produccion_list")) {
            modelo.addRow(new Object[]{
                    row.get("id_produccion"),
                    row.get("id_orden"),
                    row.get("razon_social"),
                    row.get("material"),
                    row.get("cantidad_producida"),
                    row.get("avance") + "%",
                    row.get("fecha_inicio"),
                    row.get("fecha_fin"),
                    row.get("observaciones")
            });
        }
    }

    private static void recargarMaquinas(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        maquinas.clear();
        maquinas.addAll(getRows("maquinas_list"));
        for (Map<String, String> row : maquinas) {
            modelo.addRow(new Object[]{
                    row.get("id_maquina"),
                    row.get("numero_identificacion"),
                    row.get("marca"),
                    row.get("modelo"),
                    row.get("ubicacion"),
                    row.get("estado")
            });
        }
    }

    private static List<Map<String, String>> getRows(String action) {
        try {
            if ("ordenes_list".equals(action)) {
                return produccionDao.listarOrdenes();
            }
            if ("produccion_list".equals(action)) {
                return produccionDao.listarAvances();
            }
            if ("maquinas_list".equals(action)) {
                return produccionDao.listarMaquinas();
            }
            return java.util.Collections.emptyList();
        } catch (IOException e) {
            JOptionPane.showMessageDialog(null, "No se pudo conectar con MetalGest: " + e.getMessage(), "Conexion", JOptionPane.ERROR_MESSAGE);
            return java.util.Collections.emptyList();
        }
    }

    private static boolean postOk(String action, Map<String, String> data, String okMessage) {
        try {
            ApiResult result;
            if ("produccion_register".equals(action)) {
                result = produccionDao.registrarAvance(data);
            } else if ("mantenimiento_report".equals(action)) {
                result = produccionDao.reportarFalla(data);
            } else {
                return false;
            }
            if (result.isSuccess()) {
                JOptionPane.showMessageDialog(null, okMessage, "MetalGest", JOptionPane.INFORMATION_MESSAGE);
                return true;
            }
            JOptionPane.showMessageDialog(null, "La operacion no pudo completarse: " + result.getBody(), "MetalGest", JOptionPane.ERROR_MESSAGE);
        } catch (IOException e) {
            JOptionPane.showMessageDialog(null, "No se pudo conectar con MetalGest: " + e.getMessage(), "Conexion", JOptionPane.ERROR_MESSAGE);
        }
        return false;
    }

    private static void mostrarAviso(String mensaje) {
        JOptionPane.showMessageDialog(null, mensaje, "MetalGest", JOptionPane.WARNING_MESSAGE);
    }

    private static boolean esNumeroNoNegativo(String valor) {
        try {
            return Integer.parseInt(valor.trim()) >= 0;
        } catch (NumberFormatException e) {
            return false;
        }
    }

    private static boolean esAvanceValido(String valor) {
        try {
            int porcentaje = Integer.parseInt(valor.trim());
            return porcentaje >= 0 && porcentaje <= 100;
        } catch (NumberFormatException e) {
            return false;
        }
    }

    public static void main(String[] args) {
        openProduccion();
    }
}
