package modulos.deposito;

import modulos.common.EmployeeSession;
import modulos.common.ModuleLogin;
import modulos.common.SettingsDialog;
import modulos.common.UiTheme;
import modulos.common.dao.ApiResult;
import modulos.deposito.dao.DepositoDao;

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
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class DepositPanel {
    private static int usuarioId = 4;
    private static final DepositoDao depositoDao = new DepositoDao();

    public static void openDeposito() {
        EmployeeSession session = ModuleLogin.authenticate("Deposito", "Deposito");
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
        JPanel shell = UiTheme.shell("Deposito", "Gestion de deposito", session.getNombreCompleto(), entry -> {
            if ("Deposito".equals(entry) || "Inicio".equals(entry)) {
                tabs.setSelectedIndex(0);
            } else if ("Configuracion".equals(entry)) {
                SettingsDialog.open(session.getId());
            } else if (!"Deposito".equals(entry)) {
                JOptionPane.showMessageDialog(null, "Este modulo pertenece a otra aplicacion.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
            }
        });
        UiTheme.content(shell).add(tabs, BorderLayout.CENTER);
        return shell;
    }

    private static void cargarMain(String employeeName) {
        JFrame ventana = new JFrame("MetalGest - Deposito");
        UiTheme.configureWindow(ventana);
        ventana.setDefaultCloseOperation(JFrame.DISPOSE_ON_CLOSE);
        ventana.setLocationRelativeTo(null);
        JPanel shell = UiTheme.shell("Deposito", "Gestion de deposito", employeeName, entry -> { });
        UiTheme.content(shell).add(crearTabs(), BorderLayout.CENTER);
        ventana.add(shell);
        ventana.setVisible(true);
    }

    private static JTabbedPane crearTabs() {
        JTabbedPane tabs = new JTabbedPane();
        tabs.addTab("Inventario", crearInventario());
        tabs.addTab("Movimientos", crearMovimientos());
        return tabs;
    }

    private static JPanel crearInventario() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        panel.setBackground(UiTheme.BACKGROUND);
        DefaultTableModel modelo = new DefaultTableModel(new String[]{"ID", "Material", "Tipo", "Unidad", "Espesor", "Stock", "Minimo"}, 0) {
            @Override public boolean isCellEditable(int row, int column) { return false; }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);
        JButton recargar = new JButton("Recargar inventario");
        UiTheme.styleButton(recargar, false);
        panel.add(recargar, BorderLayout.SOUTH);
        recargarMateriales(modelo);
        recargar.addActionListener(e -> recargarMateriales(modelo));
        return panel;
    }

    private static JPanel crearMovimientos() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        panel.setBackground(UiTheme.BACKGROUND);
        DefaultTableModel modelo = new DefaultTableModel(new String[]{"ID", "Material", "Tipo", "Cantidad", "Motivo", "Fecha"}, 0) {
            @Override public boolean isCellEditable(int row, int column) { return false; }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);

        JComboBox<String> material = new JComboBox<>();
        JComboBox<String> tipo = new JComboBox<>(new String[]{"Ingreso", "Salida"});
        JTextField cantidad = new JTextField("1");
        JTextArea motivo = new JTextArea(3, 28);
        JButton guardar = new JButton("Registrar movimiento");
        JButton recargar = new JButton("Recargar");
        UiTheme.styleInput(material);
        UiTheme.styleInput(tipo);
        UiTheme.styleInput(cantidad);
        UiTheme.styleInput(motivo);
        UiTheme.styleButton(guardar, true);
        UiTheme.styleButton(recargar, false);
        JPanel form = new JPanel(new GridLayout(2, 5, 8, 8));
        form.add(new JLabel("Material"));
        form.add(new JLabel("Tipo"));
        form.add(new JLabel("Cantidad"));
        form.add(new JLabel("Motivo"));
        form.add(new JLabel("Acciones"));
        form.add(material);
        form.add(tipo);
        form.add(cantidad);
        form.add(new JScrollPane(motivo));
        JPanel acciones = new JPanel();
        acciones.add(guardar);
        acciones.add(recargar);
        form.add(acciones);
        panel.add(form, BorderLayout.SOUTH);

        cargarMaterialesCombo(material);
        recargarMovimientos(modelo);
        recargar.addActionListener(e -> {
            cargarMaterialesCombo(material);
            recargarMovimientos(modelo);
        });
        guardar.addActionListener(e -> {
            if (material.getSelectedItem() == null || !esDecimalPositivo(cantidad.getText())) {
                mostrarAviso("Selecciona un material e ingresa una cantidad mayor a cero.");
                return;
            }
            Map<String, String> datos = new LinkedHashMap<>();
            datos.put("id_material", idSeleccionado(material));
            datos.put("tipo", tipo.getSelectedItem().toString());
            datos.put("cantidad", cantidad.getText().trim());
            datos.put("motivo", motivo.getText().trim());
            datos.put("id_usuario", String.valueOf(usuarioId));
            if (postOk(datos)) {
                cantidad.setText("1");
                motivo.setText("");
                recargarMovimientos(modelo);
            }
        });
        return panel;
    }

    private static void recargarMateriales(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        for (Map<String, String> row : getRows("materiales")) {
            modelo.addRow(new Object[]{row.get("id_material"), row.get("nombre"), row.get("tipo"), row.get("unidad"), row.get("espesor"), row.get("stock"), row.get("stock_minimo")});
        }
    }

    private static void recargarMovimientos(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        for (Map<String, String> row : getRows("movimientos")) {
            modelo.addRow(new Object[]{row.get("id_movimiento"), row.get("material"), row.get("tipo"), row.get("cantidad"), row.get("motivo"), row.get("fecha")});
        }
    }

    private static void cargarMaterialesCombo(JComboBox<String> combo) {
        combo.removeAllItems();
        for (Map<String, String> row : getRows("materiales")) {
            combo.addItem(row.get("id_material") + " - " + row.get("nombre") + " (" + row.get("stock") + " " + row.get("unidad") + ")");
        }
    }

    private static List<Map<String, String>> getRows(String tipo) {
        try {
            return "movimientos".equals(tipo) ? depositoDao.listarMovimientos() : depositoDao.listarMateriales();
        } catch (IOException e) {
            JOptionPane.showMessageDialog(null, "No se pudo conectar con MetalGest: " + e.getMessage(), "Conexion", JOptionPane.ERROR_MESSAGE);
            return java.util.Collections.emptyList();
        }
    }

    private static boolean postOk(Map<String, String> datos) {
        try {
            ApiResult result = depositoDao.registrarMovimiento(datos);
            if (result.isSuccess()) {
                JOptionPane.showMessageDialog(null, "Movimiento registrado.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
                return true;
            }
            JOptionPane.showMessageDialog(null, result.getBody(), "MetalGest", JOptionPane.ERROR_MESSAGE);
        } catch (IOException e) {
            JOptionPane.showMessageDialog(null, "No se pudo conectar con MetalGest: " + e.getMessage(), "Conexion", JOptionPane.ERROR_MESSAGE);
        }
        return false;
    }

    private static String idSeleccionado(JComboBox<String> combo) {
        return combo.getSelectedItem().toString().split(" - ", 2)[0];
    }

    private static boolean esDecimalPositivo(String valor) {
        try {
            return Double.parseDouble(valor.trim()) > 0;
        } catch (NumberFormatException e) {
            return false;
        }
    }

    private static void mostrarAviso(String mensaje) {
        JOptionPane.showMessageDialog(null, mensaje, "MetalGest", JOptionPane.WARNING_MESSAGE);
    }

    public static void main(String[] args) {
        openDeposito();
    }
}
