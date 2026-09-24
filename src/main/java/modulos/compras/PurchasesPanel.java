package modulos.compras;

import modulos.common.EmployeeSession;
import modulos.common.ModuleLogin;
import modulos.common.SettingsDialog;
import modulos.common.UiTheme;
import modulos.common.dao.ApiResult;
import modulos.compras.dao.ComprasDao;

import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTabbedPane;
import javax.swing.JTable;
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

public class PurchasesPanel {
    private static final ComprasDao comprasDao = new ComprasDao();

    public static void openCompras() {
        EmployeeSession session = ModuleLogin.authenticate("Compras", "Compras");
        if (session != null) {
            openAuthenticated(session);
        }
    }

    public static void openAuthenticated(EmployeeSession session) {
        SwingUtilities.invokeLater(() -> cargarMain(session.getNombreCompleto()));
    }

    public static JPanel createView(EmployeeSession session) {
        JTabbedPane tabs = crearTabs();
        JPanel shell = UiTheme.shell("Compras", "Gestion de compras", session.getNombreCompleto(), entry -> {
            if ("Compras".equals(entry) || "Inicio".equals(entry)) {
                tabs.setSelectedIndex(0);
            } else if ("Configuracion".equals(entry)) {
                SettingsDialog.open(session.getId());
            } else if (!"Compras".equals(entry)) {
                JOptionPane.showMessageDialog(null, "Este modulo pertenece a otra aplicacion.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
            }
        });
        UiTheme.content(shell).add(tabs, BorderLayout.CENTER);
        return shell;
    }

    private static void cargarMain(String employeeName) {
        JFrame ventana = new JFrame("MetalGest - Compras");
        UiTheme.configureWindow(ventana);
        ventana.setDefaultCloseOperation(JFrame.DISPOSE_ON_CLOSE);
        ventana.setLocationRelativeTo(null);
        JPanel shell = UiTheme.shell("Compras", "Gestion de compras", employeeName, entry -> { });
        UiTheme.content(shell).add(crearTabs(), BorderLayout.CENTER);
        ventana.add(shell);
        ventana.setVisible(true);
    }

    private static JTabbedPane crearTabs() {
        JTabbedPane tabs = new JTabbedPane();
        tabs.addTab("Ordenes de compra", crearCompras());
        tabs.addTab("Proveedores", crearProveedores());
        tabs.addTab("Alertas de stock", crearAlertas());
        return tabs;
    }

    private static JPanel crearCompras() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        panel.setBackground(UiTheme.BACKGROUND);
        DefaultTableModel modelo = new DefaultTableModel(new String[]{"ID", "Fecha", "Proveedor", "Total", "Estado", "Detalle"}, 0) {
            @Override public boolean isCellEditable(int row, int column) { return false; }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);

        JComboBox<String> proveedor = new JComboBox<>();
        JComboBox<String> material = new JComboBox<>();
        JTextField cantidad = new JTextField("1");
        JTextField precio = new JTextField("0");
        JTextField fecha = new JTextField(LocalDate.now().toString());
        JComboBox<String> estado = new JComboBox<>(new String[]{"Solicitada", "Recibida", "Cancelada"});
        JButton crear = new JButton("Crear compra");
        JButton recargar = new JButton("Recargar");
        for (javax.swing.JComponent input : new javax.swing.JComponent[]{proveedor, material, cantidad, precio, fecha, estado}) {
            UiTheme.styleInput(input);
        }
        UiTheme.styleButton(crear, true);
        UiTheme.styleButton(recargar, false);
        JPanel form = new JPanel(new GridLayout(2, 7, 8, 8));
        form.add(new JLabel("Proveedor"));
        form.add(new JLabel("Material"));
        form.add(new JLabel("Cantidad"));
        form.add(new JLabel("Precio unitario"));
        form.add(new JLabel("Fecha"));
        form.add(new JLabel("Estado"));
        form.add(new JLabel("Acciones"));
        form.add(proveedor);
        form.add(material);
        form.add(cantidad);
        form.add(precio);
        form.add(fecha);
        form.add(estado);
        JPanel acciones = new JPanel();
        acciones.add(crear);
        acciones.add(recargar);
        form.add(acciones);
        panel.add(form, BorderLayout.SOUTH);

        cargarCombos(proveedor, material);
        recargarCompras(modelo);
        recargar.addActionListener(e -> {
            cargarCombos(proveedor, material);
            recargarCompras(modelo);
        });
        crear.addActionListener(e -> {
            if (proveedor.getSelectedItem() == null || material.getSelectedItem() == null || !esDecimalPositivo(cantidad.getText()) || !esDecimalNoNegativo(precio.getText())) {
                mostrarAviso("Completa proveedor, material, cantidad y precio validos.");
                return;
            }
            Map<String, String> datos = new LinkedHashMap<>();
            datos.put("id_proveedor", idSeleccionado(proveedor));
            datos.put("id_material", idSeleccionado(material));
            datos.put("cantidad", cantidad.getText().trim());
            datos.put("precio", precio.getText().trim());
            datos.put("fecha", fecha.getText().trim());
            datos.put("estado", estado.getSelectedItem().toString());
            if (postCompra(datos)) {
                cantidad.setText("1");
                precio.setText("0");
                recargarCompras(modelo);
            }
        });
        return panel;
    }

    private static JPanel crearProveedores() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        panel.setBackground(UiTheme.BACKGROUND);
        DefaultTableModel modelo = new DefaultTableModel(new String[]{"ID", "Proveedor", "CUIT", "Telefono", "Email"}, 0) {
            @Override public boolean isCellEditable(int row, int column) { return false; }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);

        JTextField razon = new JTextField();
        JTextField cuit = new JTextField();
        JTextField telefono = new JTextField();
        JTextField email = new JTextField();
        JButton crear = new JButton("Agregar proveedor");
        JButton recargar = new JButton("Recargar");
        for (javax.swing.JComponent input : new javax.swing.JComponent[]{razon, cuit, telefono, email}) {
            UiTheme.styleInput(input);
        }
        UiTheme.styleButton(crear, true);
        UiTheme.styleButton(recargar, false);
        JPanel form = new JPanel(new GridLayout(2, 5, 8, 8));
        form.add(new JLabel("Razon social"));
        form.add(new JLabel("CUIT"));
        form.add(new JLabel("Telefono"));
        form.add(new JLabel("Email"));
        form.add(new JLabel("Acciones"));
        form.add(razon);
        form.add(cuit);
        form.add(telefono);
        form.add(email);
        JPanel acciones = new JPanel();
        acciones.add(crear);
        acciones.add(recargar);
        form.add(acciones);
        panel.add(form, BorderLayout.SOUTH);

        recargarProveedores(modelo);
        recargar.addActionListener(e -> recargarProveedores(modelo));
        crear.addActionListener(e -> {
            if (razon.getText().trim().isEmpty()) {
                mostrarAviso("Completa la razon social.");
                return;
            }
            Map<String, String> datos = new LinkedHashMap<>();
            datos.put("razon_social", razon.getText().trim());
            datos.put("cuit", cuit.getText().trim());
            datos.put("telefono", telefono.getText().trim());
            datos.put("email", email.getText().trim());
            if (postProveedor(datos)) {
                razon.setText("");
                cuit.setText("");
                telefono.setText("");
                email.setText("");
                recargarProveedores(modelo);
            }
        });
        return panel;
    }

    private static JPanel crearAlertas() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        panel.setBackground(UiTheme.BACKGROUND);
        DefaultTableModel modelo = new DefaultTableModel(new String[]{"ID", "Material", "Stock", "Minimo", "Unidad", "Estado"}, 0) {
            @Override public boolean isCellEditable(int row, int column) { return false; }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);
        JButton recargar = new JButton("Actualizar alertas");
        UiTheme.styleButton(recargar, false);
        panel.add(recargar, BorderLayout.SOUTH);
        recargarAlertas(modelo);
        recargar.addActionListener(e -> recargarAlertas(modelo));
        return panel;
    }

    private static void recargarCompras(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        for (Map<String, String> row : getRows("compras")) {
            modelo.addRow(new Object[]{row.get("id_compra"), row.get("fecha"), row.get("proveedor"), row.get("total"), row.get("estado"), row.get("detalle")});
        }
    }

    private static void recargarProveedores(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        for (Map<String, String> row : getRows("proveedores")) {
            modelo.addRow(new Object[]{row.get("id_proveedor"), row.get("razon_social"), row.get("cuit"), row.get("telefono"), row.get("email")});
        }
    }

    private static void recargarAlertas(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        for (Map<String, String> row : getRows("materiales")) {
            double stock = numero(row.get("stock"));
            double minimo = numero(row.get("stock_minimo"));
            if (stock <= minimo) {
                modelo.addRow(new Object[]{row.get("id_material"), row.get("nombre"), row.get("stock"), row.get("stock_minimo"), row.get("unidad"), "Reponer"});
            }
        }
    }

    private static void cargarCombos(JComboBox<String> proveedor, JComboBox<String> material) {
        proveedor.removeAllItems();
        material.removeAllItems();
        for (Map<String, String> row : getRows("proveedores")) {
            proveedor.addItem(row.get("id_proveedor") + " - " + row.get("razon_social"));
        }
        for (Map<String, String> row : getRows("materiales")) {
            material.addItem(row.get("id_material") + " - " + row.get("nombre"));
        }
    }

    private static List<Map<String, String>> getRows(String tipo) {
        try {
            if ("proveedores".equals(tipo)) {
                return comprasDao.listarProveedores();
            }
            if ("compras".equals(tipo)) {
                return comprasDao.listarCompras();
            }
            return comprasDao.listarMateriales();
        } catch (IOException e) {
            JOptionPane.showMessageDialog(null, "No se pudo conectar con MetalGest: " + e.getMessage(), "Conexion", JOptionPane.ERROR_MESSAGE);
            return java.util.Collections.emptyList();
        }
    }

    private static boolean postProveedor(Map<String, String> datos) {
        return post(() -> comprasDao.crearProveedor(datos), "Proveedor agregado.");
    }

    private static boolean postCompra(Map<String, String> datos) {
        return post(() -> comprasDao.crearCompra(datos), "Compra registrada.");
    }

    private static boolean post(IoCall call, String okMessage) {
        try {
            ApiResult result = call.run();
            if (result.isSuccess()) {
                JOptionPane.showMessageDialog(null, okMessage, "MetalGest", JOptionPane.INFORMATION_MESSAGE);
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
        return numero(valor) > 0;
    }

    private static boolean esDecimalNoNegativo(String valor) {
        return numero(valor) >= 0;
    }

    private static double numero(String valor) {
        try {
            return Double.parseDouble(valor == null || valor.isEmpty() ? "0" : valor.trim());
        } catch (NumberFormatException e) {
            return -1;
        }
    }

    private static void mostrarAviso(String mensaje) {
        JOptionPane.showMessageDialog(null, mensaje, "MetalGest", JOptionPane.WARNING_MESSAGE);
    }

    private interface IoCall {
        ApiResult run() throws IOException;
    }

    public static void main(String[] args) {
        openCompras();
    }
}
