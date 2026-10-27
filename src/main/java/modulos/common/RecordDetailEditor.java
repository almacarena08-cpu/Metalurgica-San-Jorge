package modulos.common;

import javax.swing.*;
import javax.swing.table.TableModel;
import java.awt.*;
import java.io.IOException;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/** Shared row detail and save dialog for module records. */
public final class RecordDetailEditor {
    private static final Map<String, String[]> FIELDS = new LinkedHashMap<>();
    private static final Map<String, List<String>> CHOICES = new LinkedHashMap<>();
    static {
        FIELDS.put("pedido", new String[]{"descripcion", "cantidad", "material", "fecha_entrega", "estado"});
        FIELDS.put("orden", new String[]{"fecha_inicio", "fecha_prevista", "prioridad", "estado", "observaciones"});
        FIELDS.put("produccion", new String[]{"cantidad_producida", "avance", "observaciones"});
        FIELDS.put("mantenimiento", new String[]{"fecha", "tipo", "problema", "reparacion", "repuestos", "estado"});
        FIELDS.put("material", new String[]{"nombre", "tipo", "unidad", "espesor", "stock", "stock_minimo", "stock_maximo"});
        FIELDS.put("movimiento", new String[]{"motivo"});
        FIELDS.put("proveedor", new String[]{"razon_social", "cuit", "telefono", "email"});
        FIELDS.put("compra", new String[]{"fecha", "estado"});
        FIELDS.put("control", new String[]{"fecha", "resultado", "observaciones"});
        CHOICES.put("pedido.estado", List.of("Pendiente", "Con orden de trabajo", "En produccion", "Finalizada", "Cancelado"));
        CHOICES.put("orden.prioridad", List.of("Baja", "Media", "Alta"));
        CHOICES.put("orden.estado", List.of("Pendiente", "En produccion", "Pausada", "Finalizada"));
        CHOICES.put("mantenimiento.tipo", List.of("Preventivo", "Correctivo"));
        CHOICES.put("mantenimiento.estado", List.of("Pendiente", "En proceso", "Resuelta"));
        CHOICES.put("compra.estado", List.of("Solicitada", "Recibida", "Cancelada"));
        CHOICES.put("control.resultado", List.of("Aprobado", "Observado", "Rechazado"));
    }

    private RecordDetailEditor() { }

    public static void attach(JTable table, String entity) {
        boolean canEdit = EmployeeSession.canEditRecords();
        boolean isSuperAdmin = EmployeeSession.isSuperAdmin();
        table.addMouseListener(new java.awt.event.MouseAdapter() {
            @Override public void mouseClicked(java.awt.event.MouseEvent event) {
                if (event.getClickCount() != 2) return;
                int viewRow = table.rowAtPoint(event.getPoint());
                if (viewRow < 0) return;
                int row = table.convertRowIndexToModel(viewRow);
                TableModel model = table.getModel();
                if (model.getColumnCount() == 0) return;
                if (!isSuperAdmin) {
                    showRowDetail(table, viewRow, row);
                    return;
                }
                String id = String.valueOf(model.getValueAt(row, 0));
                open(table, entity, id, canEdit);
            }
        });
    }

    private static void showRowDetail(JTable table, int viewRow, int modelRow) {
        JPanel details = new JPanel(new GridLayout(0, 2, 12, 8));
        details.setBorder(BorderFactory.createEmptyBorder(12, 14, 12, 14));
        for (int viewColumn = 0; viewColumn < table.getColumnCount(); viewColumn++) {
            int modelColumn = table.convertColumnIndexToModel(viewColumn);
            details.add(new JLabel(table.getColumnName(viewColumn) + ":"));
            Object value = table.getModel().getValueAt(modelRow, modelColumn);
            JTextArea text = new JTextArea(value == null ? "" : value.toString(), 2, 24);
            text.setLineWrap(true);
            text.setWrapStyleWord(true);
            text.setEditable(false);
            details.add(new JScrollPane(text));
        }
        JOptionPane.showMessageDialog(table, details, "Detalle de fila", JOptionPane.INFORMATION_MESSAGE);
    }

    private static void open(Component parent, String entity, String id, boolean canEdit) {
        try {
            Map<String, String> request = new LinkedHashMap<>();
            request.put("entidad", entity);
            request.put("id_registro", id);
            String body = ApiClient.post("record_get", request);
            Map<String, String> response = JsonUtil.parseObject(body);
            if (!"true".equals(response.get("success"))) {
                JOptionPane.showMessageDialog(parent, response.getOrDefault("message", "No se pudo abrir el registro."), "Detalle", JOptionPane.ERROR_MESSAGE);
                return;
            }
            Map<String, String> row = nestedObject(body, "registro");
            if (row.isEmpty()) {
                JOptionPane.showMessageDialog(parent, "No se pudieron leer los datos del registro.", "Detalle", JOptionPane.ERROR_MESSAGE);
                return;
            }
            String[] fields = FIELDS.getOrDefault(entity, new String[0]);
            JPanel form = new JPanel(new GridLayout(0, 2, 8, 6));
            Map<String, JComponent> inputs = new LinkedHashMap<>();
            for (String field : fields) {
                JLabel label = new JLabel(label(field));
                String current = row.getOrDefault(field, "");
                List<String> choices = CHOICES.get(entity + "." + field);
                JComponent input;
                if (choices != null) {
                    JComboBox<String> combo = new JComboBox<>();
                    for (String choice : choices) combo.addItem(choice);
                    if (!current.isBlank() && !choices.contains(current)) combo.addItem(current);
                    combo.setSelectedItem(current);
                    combo.setEnabled(canEdit);
                    input = combo;
                } else {
                    JTextArea area = new JTextArea(current, field.equals("observaciones") || field.equals("problema") ? 3 : 1, 24);
                    area.setLineWrap(true); area.setWrapStyleWord(true); area.setEditable(canEdit); UiTheme.styleInput(area);
                    input = new JScrollPane(area);
                }
                form.add(label); form.add(input); inputs.put(field, input);
            }
            JLabel creator = new JLabel(row.getOrDefault("creado_por", "Sin registro"));
            form.add(new JLabel("Creado por")); form.add(creator);
            if (!canEdit) {
                JOptionPane.showMessageDialog(parent, new JScrollPane(form), "Detalle del registro #" + id,
                        JOptionPane.INFORMATION_MESSAGE);
                return;
            }
            int result = JOptionPane.showConfirmDialog(parent, new JScrollPane(form), "Detalle del registro #" + id,
                    JOptionPane.OK_CANCEL_OPTION, JOptionPane.PLAIN_MESSAGE);
            if (result != JOptionPane.OK_OPTION) return;
            Map<String, String> update = new LinkedHashMap<>();
            update.put("entidad", entity); update.put("id_registro", id);
            for (Map.Entry<String, JComponent> field : inputs.entrySet()) {
                JComponent input = field.getValue();
                String value = input instanceof JComboBox<?> combo ? String.valueOf(combo.getSelectedItem())
                        : input instanceof JScrollPane scroll && scroll.getViewport().getView() instanceof JTextArea area ? area.getText().trim() : "";
                update.put(field.getKey(), value);
            }
            Map<String, String> saved = JsonUtil.parseObject(ApiClient.post("record_update", update));
            if ("true".equals(saved.get("success"))) JOptionPane.showMessageDialog(parent, "Cambios guardados.", "Detalle", JOptionPane.INFORMATION_MESSAGE);
            else JOptionPane.showMessageDialog(parent, saved.getOrDefault("message", "No se pudieron guardar los cambios."), "Detalle", JOptionPane.ERROR_MESSAGE);
        } catch (IOException e) {
            JOptionPane.showMessageDialog(parent, "No se pudo conectar con MetalGest: " + e.getMessage(), "Detalle", JOptionPane.ERROR_MESSAGE);
        }
    }

    private static Map<String, String> nestedObject(String json, String key) {
        String marker = "\"" + key + "\":"; int start = json.indexOf(marker);
        if (start < 0) return new LinkedHashMap<>();
        int open = json.indexOf('{', start + marker.length()); if (open < 0) return new LinkedHashMap<>();
        int depth = 0; boolean string = false, escape = false;
        for (int i = open; i < json.length(); i++) {
            char c = json.charAt(i);
            if (escape) escape = false;
            else if (c == '\\' && string) escape = true;
            else if (c == '"') string = !string;
            else if (!string && c == '{') depth++;
            else if (!string && c == '}' && --depth == 0) return JsonUtil.parseObject(json.substring(open, i + 1));
        }
        return new LinkedHashMap<>();
    }

    private static String label(String field) {
        String label = field.replace('_', ' ');
        return Character.toUpperCase(label.charAt(0)) + label.substring(1);
    }
}
