package modulos.mantenimiento;

import modulos.common.dao.ApiResult;
import modulos.common.EmployeeSession;
import modulos.common.ModuleLogin;
import modulos.common.UiTheme;
import modulos.common.RecordDetailEditor;
import modulos.mantenimiento.dao.MantenimientoDao;

import javax.swing.*;
import javax.swing.table.DefaultTableModel;
import javax.swing.table.TableRowSorter;
import javax.swing.table.DefaultTableCellRenderer;
import javax.swing.RowFilter;
import java.awt.*;
import java.io.IOException;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class MaintenancePanel {
    private static final MantenimientoDao dao = new MantenimientoDao();

    public static void openMantenimiento() {
        EmployeeSession session = ModuleLogin.authenticate("Mantenimiento", "Mantenimiento");
        if (session != null) openAuthenticated(session);
    }
    public static void openAuthenticated(EmployeeSession session) {
        SwingUtilities.invokeLater(() -> cargarMain(session.getNombreCompleto()));
    }
    public static JPanel createView(EmployeeSession session, Runnable onLogout) {
        JTabbedPane tabs = crearTabs();
        JPanel shell = UiTheme.shell("Mantenimiento", "Gestion de mantenimiento", session.getNombreCompleto(), entry -> seleccionar(tabs, entry), onLogout);
        UiTheme.content(shell).add(tabs, BorderLayout.CENTER);
        return shell;
    }
    private static void cargarMain(String nombre) {
        JFrame ventana = new JFrame("MetalGest - Mantenimiento");
        UiTheme.configureWindow(ventana); ventana.setDefaultCloseOperation(JFrame.DISPOSE_ON_CLOSE); ventana.setLocationRelativeTo(null);
        JTabbedPane tabs = crearTabs();
        JPanel shell = UiTheme.shell("Mantenimiento", "Gestion de mantenimiento", nombre, entry -> seleccionar(tabs, entry));
        UiTheme.content(shell).add(tabs, BorderLayout.CENTER); ventana.add(shell); ventana.setVisible(true);
    }
    private static void seleccionar(JTabbedPane tabs, String entry) {
        if ("Solicitudes".equals(entry)) tabs.setSelectedIndex(1);
        else if ("Preventivos".equals(entry)) tabs.setSelectedIndex(2);
        else if ("Inicio".equals(entry) || "Mantenimiento".equals(entry)) tabs.setSelectedIndex(0);
    }
    private static JTabbedPane crearTabs() {
        JTabbedPane tabs = new JTabbedPane(); UiTheme.styleTabs(tabs);
        tabs.addTab("Dashboard", UiTheme.dashboard("Mantenimiento", "Organizá solicitudes, reparaciones y tareas preventivas.", "Solicitudes",
                new String[][]{{"Solicitudes", "Atender"}, {"Reparaciones", "En curso"}, {"Preventivos", "Programar"}},
                new String[]{"Revisá reportes de falla pendientes", "Actualizá reparaciones y repuestos utilizados", "Programá controles preventivos para cada máquina"}, entry -> seleccionar(tabs, entry)));
        tabs.addTab("Solicitudes", crearPanel(false));
        tabs.addTab("Preventivos", crearPanel(true));
        return tabs;
    }
    private static JPanel crearPanel(boolean preventivos) {
        JPanel panel = new JPanel(new BorderLayout(10, 10)); panel.setBackground(UiTheme.BACKGROUND);
        DefaultTableModel modelo = new DefaultTableModel(new String[]{"ID", "Máquina", "Marca", "Modelo", "Tipo", "Problema", "Reparación", "Repuestos", "Estado", "id_maquina"}, 0) {
            @Override public boolean isCellEditable(int row, int column) { return false; }
        };
        JTable tabla = new JTable(modelo); UiTheme.styleTable(tabla); tabla.setAutoResizeMode(JTable.AUTO_RESIZE_OFF);
        RecordDetailEditor.attach(tabla, "mantenimiento");
        JScrollPane scroll = UiTheme.tableScroll(tabla);
        tabla.getColumnModel().getColumn(9).setMinWidth(0); tabla.getColumnModel().getColumn(9).setMaxWidth(0); tabla.getColumnModel().getColumn(9).setPreferredWidth(0);
        TableRowSorter<DefaultTableModel> sorter = new TableRowSorter<>(modelo); tabla.setRowSorter(sorter);
        JCheckBox pendientes = new JCheckBox("Pendientes", true), proceso = new JCheckBox("En proceso", true), resueltas = new JCheckBox("Resueltas", true);
        for (JCheckBox check : new JCheckBox[]{pendientes, proceso, resueltas}) { check.setOpaque(false); check.setForeground(UiTheme.INK); }
        JPanel filters = new JPanel(new FlowLayout(FlowLayout.LEFT)); filters.setOpaque(false); filters.add(new JLabel("Mostrar:")); filters.add(pendientes); filters.add(proceso); filters.add(resueltas);
        JButton editar = new JButton("Editar solicitud"), programar = new JButton("Programar preventivo"), recargar = new JButton("Recargar");
        UiTheme.styleButton(editar, true); UiTheme.styleButton(programar, true); UiTheme.styleButton(recargar, false);
        JPanel actions = new JPanel(new FlowLayout(FlowLayout.RIGHT)); actions.setOpaque(false);
        if (preventivos) actions.add(programar); else actions.add(editar);
        actions.add(recargar);
        JPanel north = new JPanel(new BorderLayout()); north.setOpaque(false); north.add(filters, BorderLayout.WEST); north.add(actions, BorderLayout.EAST);
        panel.add(north, BorderLayout.NORTH); panel.add(scroll, BorderLayout.CENTER);
        Runnable filter = () -> sorter.setRowFilter(new RowFilter<DefaultTableModel, Integer>() {
            @Override public boolean include(Entry<? extends DefaultTableModel, ? extends Integer> entry) {
                String type = String.valueOf(entry.getValue(4));
                String status = String.valueOf(entry.getValue(8));
                if (!(preventivos ? "Preventivo" : "Correctivo").equalsIgnoreCase(type)) return false;
                return ("Pendiente".equalsIgnoreCase(status) && pendientes.isSelected())
                        || ("En proceso".equalsIgnoreCase(status) && proceso.isSelected())
                        || ("Resuelta".equalsIgnoreCase(status) && resueltas.isSelected());
            }
        });
        for (JCheckBox check : new JCheckBox[]{pendientes, proceso, resueltas}) check.addActionListener(e -> filter.run());
        for (int column = 0; column < tabla.getColumnCount(); column++) tabla.getColumnModel().getColumn(column).setCellRenderer(new DefaultTableCellRenderer() {
            @Override public Component getTableCellRendererComponent(JTable t, Object value, boolean selected, boolean focus, int row, int col) {
                Component c = super.getTableCellRendererComponent(t, value, selected, focus, row, col);
                String status = String.valueOf(t.getValueAt(row, 8));
                Color color = "Resuelta".equalsIgnoreCase(status) ? new Color(45, 170, 90) : "En proceso".equalsIgnoreCase(status) ? new Color(235, 185, 45) : new Color(205, 65, 60);
                ((JComponent)c).setBorder(BorderFactory.createMatteBorder(1, 1, 1, 1, color));
                return c;
            }
        });
        recargar.addActionListener(e -> cargarMantenimientos(modelo));
        editar.addActionListener(e -> editar(tabla, modelo));
        programar.addActionListener(e -> programar(tabla, modelo));
        cargarMantenimientos(modelo); filter.run();
        return panel;
    }
    private static void cargarMantenimientos(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        try {
            for (Map<String, String> row : dao.listar()) modelo.addRow(new Object[]{row.get("id_mantenimiento"), row.get("numero_identificacion"), row.get("marca"), row.get("modelo"), row.get("tipo"), row.get("problema"), row.get("reparacion"), row.get("repuestos"), row.get("estado"), row.get("id_maquina")});
        } catch (IOException e) { error(e); }
    }
    private static void editar(JTable tabla, DefaultTableModel modelo) {
        int selected = tabla.getSelectedRow(); if (selected < 0) { aviso("Seleccioná una solicitud."); return; }
        int row = tabla.convertRowIndexToModel(selected);
        JTextArea reparacion = new JTextArea(2, 30), repuestos = new JTextArea(2, 30), problema = new JTextArea(valor(modelo, row, 5), 2, 30);
        problema.setEditable(false); problema.setLineWrap(true); problema.setWrapStyleWord(true);
        reparacion.setText(valor(modelo, row, 6)); repuestos.setText(valor(modelo, row, 7));
        JComboBox<String> estado = new JComboBox<>(new String[]{"Pendiente", "En proceso", "Resuelta"}); estado.setSelectedItem(valor(modelo, row, 8));
        UiTheme.styleInput(reparacion); UiTheme.styleInput(repuestos); UiTheme.styleInput(problema); UiTheme.styleInput(estado);
        JPanel form = new JPanel(new GridBagLayout()); form.setBorder(BorderFactory.createEmptyBorder(6, 8, 6, 8));
        GridBagConstraints c = new GridBagConstraints(); c.gridx = 0; c.weightx = 1; c.fill = GridBagConstraints.HORIZONTAL; c.insets = new Insets(2, 2, 2, 2);
        c.gridy = 0; form.add(new JLabel("Máquina: " + valor(modelo, row, 1) + " · " + valor(modelo, row, 2) + " " + valor(modelo, row, 3)), c);
        c.gridy = 1; form.add(new JLabel("Tipo: " + valor(modelo, row, 4)), c);
        c.gridy = 2; form.add(new JLabel("Problema reportado"), c);
        c.gridy = 3; form.add(areaConAltura(problema), c);
        c.gridy = 4; form.add(new JLabel("Reparación realizada"), c);
        c.gridy = 5; form.add(areaConAltura(reparacion), c);
        c.gridy = 6; form.add(new JLabel("Repuestos utilizados"), c);
        c.gridy = 7; form.add(areaConAltura(repuestos), c);
        c.gridy = 8; form.add(new JLabel("Estado"), c);
        c.gridy = 9; form.add(estado, c);
        if (JOptionPane.showConfirmDialog(tabla, form, "Actualizar mantenimiento", JOptionPane.OK_CANCEL_OPTION, JOptionPane.PLAIN_MESSAGE) != JOptionPane.OK_OPTION) return;
        Map<String, String> data = new LinkedHashMap<>(); data.put("id_mantenimiento", valor(modelo, row, 0)); data.put("reparacion", reparacion.getText().trim()); data.put("repuestos", repuestos.getText().trim()); data.put("estado", String.valueOf(estado.getSelectedItem()));
        try { ApiResult result = dao.actualizar(data); if (result.isSuccess()) cargarMantenimientos(modelo); else JOptionPane.showMessageDialog(tabla, result.getBody(), "Mantenimiento", JOptionPane.ERROR_MESSAGE); }
        catch (IOException e) { error(e); }
    }
    private static JScrollPane areaConAltura(JTextArea area) {
        JScrollPane scroll = new JScrollPane(area);
        scroll.setPreferredSize(new Dimension(440, 48));
        return scroll;
    }
    private static void programar(JTable tabla, DefaultTableModel modelo) {
        int selected = tabla.getSelectedRow(); if (selected < 0) { aviso("Seleccioná una máquina de la lista."); return; }
        int row = tabla.convertRowIndexToModel(selected);
        JTextField fecha = new JTextField(java.time.LocalDate.now().plusMonths(1).toString()); JTextArea tarea = new JTextArea(4, 28);
        JPanel form = new JPanel(new GridLayout(0, 1, 7, 7)); form.add(new JLabel("Fecha programada (AAAA-MM-DD)")); form.add(fecha); form.add(new JLabel("Control preventivo")); form.add(new JScrollPane(tarea));
        if (JOptionPane.showConfirmDialog(tabla, form, "Programar preventivo", JOptionPane.OK_CANCEL_OPTION, JOptionPane.PLAIN_MESSAGE) != JOptionPane.OK_OPTION || tarea.getText().trim().isEmpty()) return;
        Map<String, String> data = new LinkedHashMap<>(); data.put("id_maquina", valor(modelo, row, 9)); data.put("fecha", fecha.getText().trim()); data.put("problema", tarea.getText().trim());
        try { ApiResult result = dao.crearPreventivo(data); if (result.isSuccess()) cargarMantenimientos(modelo); else JOptionPane.showMessageDialog(tabla, result.getBody(), "Mantenimiento", JOptionPane.ERROR_MESSAGE); }
        catch (IOException e) { error(e); }
    }
    private static String valor(DefaultTableModel model, int row, int col) { Object value = model.getValueAt(row, col); return value == null ? "" : value.toString(); }
    private static void aviso(String value) { JOptionPane.showMessageDialog(null, value, "MetalGest", JOptionPane.WARNING_MESSAGE); }
    private static void error(IOException e) { JOptionPane.showMessageDialog(null, "No se pudo conectar con la API: " + e.getMessage(), "Conexión", JOptionPane.ERROR_MESSAGE); }
    public static void main(String[] args) { openMantenimiento(); }
}
