package modulos.common;

import javax.swing.AbstractButton;
import javax.swing.JComboBox;
import javax.swing.JComponent;
import javax.swing.JLabel;
import javax.swing.JMenu;
import javax.swing.JMenuItem;
import javax.swing.JTabbedPane;
import javax.swing.JTable;
import javax.swing.JTextField;
import javax.swing.table.TableColumn;
import javax.swing.border.TitledBorder;
import java.awt.Component;
import java.awt.Container;
import java.awt.Dialog;
import java.awt.Toolkit;
import java.awt.AWTEvent;
import java.awt.event.AWTEventListener;
import java.awt.event.WindowEvent;
import java.util.LinkedHashMap;
import java.util.Map;

/** Application-wide language state and UI translation for the Spanish/English interface. */
public final class AppLanguage {
    private static boolean english;
    private static final Map<String, String> PHRASES = new LinkedHashMap<>();
    private static final Map<String, String> WORDS = new LinkedHashMap<>();

    static {
        phrases(
            "MetalGest - Acceso", "MetalGest - Sign in", "MetalGest - Administracion", "MetalGest - Administration",
            "MetalGest - Produccion", "MetalGest - Production", "MetalGest - Mantenimiento", "MetalGest - Maintenance",
            "MetalGest - Deposito", "MetalGest - Warehouse", "MetalGest - Compras", "MetalGest - Purchasing",
            "MetalGest - Calidad", "MetalGest - Quality", "Acceso seguro al sistema", "Secure system access",
            "Buscar en este módulo...", "Search this module...", "Historial de avance", "Production history",
            "Ordenes de trabajo", "Work orders", "Ordenes recibidas", "Received orders", "Ordenes de compra", "Purchase orders",
            "Aviso a mantenimiento", "Maintenance request", "Programar preventivo", "Schedule preventive maintenance",
            "Editar solicitud", "Edit request", "Actualizar mantenimiento", "Update maintenance",
            "Crear empleado", "Create employee", "Contraseña (vacía conserva)", "Password (blank keeps current)",
            "Registrar movimiento", "Record movement", "Recargar inventario", "Refresh inventory",
            "Agregar proveedor", "Add supplier", "Crear compra", "Create purchase", "Crear material", "Create material",
            "Guardar límites", "Save limits", "Eliminar material", "Delete material", "Actualizar alertas", "Refresh alerts",
            "Registrar avance", "Record progress", "Registrar control", "Record inspection", "Recargar historial", "Refresh history",
            "Fecha programada (AAAA-MM-DD)", "Scheduled date (YYYY-MM-DD)", "Control preventivo", "Preventive check",
            "Reparación realizada", "Repair performed", "Repuestos utilizados", "Parts used", "Problema reportado", "Reported issue",
            "El acceso de clientes no está disponible en esta aplicación.", "Customer access is unavailable in this application.",
            "No se pudo validar el acceso con la API.", "Could not validate access with the API.",
            "No se pudo conectar con MetalGest: ", "Could not connect to MetalGest: ",
            "No se pudo conectar con la API: ", "Could not connect to the API: ",
            "No se pudo conectar con el sitio web.", "Could not connect to the website.",
            "No se pudo conectar con MetalGest.", "Could not connect to MetalGest.",
            "No se pudo conectar con la API.", "Could not connect to the API.",
            "Nombre o contraseña incorrectos.", "Incorrect username or password.",
            "Completa nombre y contraseña.", "Enter your username and password.",
            "El acceso fue validado, pero no se pudo abrir el módulo (", "Access was validated, but the module could not be opened (",
            "Revisá la consola.", "Check the console.", "No se pudo iniciar sesión. Revisá tus datos o el acceso a Administración.", "Could not sign in. Check your credentials and Administration access.",
            "El cliente de esta conversación ya no aparece en la lista de clientes.", "This conversation's customer is no longer in the customer list.",
            "Este módulo pertenece a otra aplicación.", "This module belongs to another application.",
            "Este modulo pertenece a otra aplicacion.", "This module belongs to another application.",
            "La operación no pudo completarse: ", "The operation could not be completed: ",
            "La operacion no pudo completarse: ", "The operation could not be completed: ",
            "No se pudieron cargar las preferencias.", "Could not load preferences.",
            "Preferencias personales", "Personal preferences", "Tamaño del texto", "Text size",
            "Preferencias guardadas solo para este empleado.", "Preferences saved for this employee only.",
            "No se pudieron guardar las preferencias.", "Could not save preferences.",
            "Detalle de fila", "Row details", "Detalle del registro", "Record details", "Creado por", "Created by",
            "Cambios guardados.", "Changes saved.", "No se pudieron leer los datos del registro.", "Could not read record data.",
            "No se pudo abrir el registro.", "Could not open the record.", "No se pudieron guardar los cambios.", "Could not save changes.",
            "Conectando con el chat de Administración...", "Connecting to the Administration chat...",
            "Chat de Administración", "Administration chat", "Chat web no configurado", "Web chat is not configured",
            "Elegí una conversación", "Select a conversation", "Seleccioná una conversación para ver los mensajes.", "Select a conversation to view messages.",
            "No se pudieron cargar las conversaciones: ", "Could not load conversations: ",
            "No se pudieron cargar los mensajes: ", "Could not load messages: ",
            "No se pudo enviar la respuesta. Revisá la sesión del chat.", "Could not send the reply. Check the chat session.",
            "No se pudo asociar esta conversación con un cliente registrado.", "Could not link this conversation to a registered customer.",
            "Seleccioná una conversación.", "Select a conversation.", "Ingresar al chat", "Sign in to chat",
            "Salir del chat", "Sign out of chat", "CONVERSACIONES", "CONVERSATIONS",
            "archivo(s) listo(s) para enviar", "file(s) ready to send", "supera el límite de 10 MB.", "exceeds the 10 MB limit.",
            "No se pudo descargar el archivo: ", "Could not download the file: ", "Adjuntar", "Attach",
            "No se pudo conectar con el sitio web.", "Could not connect to the website.",
            "Rol sin módulo", "No module for role", "El usuario inició sesión, pero no hay un módulo disponible para el rol: ", "The user signed in, but no module is available for this role: ",
            "(sin rol)", "(no role)", "Especificaciones", "Specifications", "Usuario interno", "Internal user",
            "No se pudo conectar con MetalGest", "Could not connect to MetalGest",
            "Movimiento registrado.", "Movement recorded.", "Control registrado.", "Inspection recorded.",
            "No se pudieron cargar las preferencias.", "Could not load preferences."
        );
        words("Idioma", "Language", "Usuario", "Username", "Contraseña", "Password", "Nombre", "Name", "Apellido", "Last name",
            "Ingresar", "Sign in", "Salir", "Sign out", "Administracion", "Administration", "Produccion", "Production",
            "Mantenimiento", "Maintenance", "Deposito", "Warehouse", "Compras", "Purchasing", "Calidad", "Quality",
            "Pedidos", "Orders", "Ordenes", "Orders", "Chat", "Chat", "Empleados", "Employees", "Inicio", "Home",
            "Solicitudes", "Requests", "Preventivos", "Preventive", "Inventario", "Inventory", "Movimientos", "Movements",
            "Proveedores", "Suppliers", "Materiales", "Materials", "Alertas de stock", "Stock alerts", "Historial", "History",
            "Mostrar", "Show", "Pendientes", "Pending", "En proceso", "In progress", "Resueltas", "Resolved",
            "Editar", "Edit", "Programar", "Schedule", "Recargar", "Refresh", "Filtrar", "Filter", "Buscar", "Search",
            "Registrar", "Record", "avance", "progress", "Número", "Number", "orden", "order", "Cantidad", "Quantity",
            "producida", "produced", "Observaciones", "Notes", "Observacion", "Note", "Acciones", "Actions", "Estado", "Status",
            "Tipo", "Type", "Material", "Material", "Motivo", "Reason", "Proveedor", "Supplier", "Precio unitario", "Unit price",
            "Fecha", "Date", "Razon social", "Business name", "Razón social", "Business name", "Telefono", "Phone", "Teléfono", "Phone",
            "Email", "Email", "CUIT", "Tax ID", "Resultado", "Result", "Máquina", "Machine", "Maquina", "Machine",
            "Problema", "Issue", "Reparación", "Repair", "Repuestos", "Parts", "Conexión", "Connection", "Conexion", "Connection",
            "Configuracion", "Settings", "Configuración", "Settings", "Detalle", "Details", "Eliminar", "Delete", "Guardar", "Save",
            "Crear", "Create", "Agregar", "Add", "Actualizar", "Update", "pedido", "order", "Sin registro", "No record",
            "Enviar", "Send", "Archivo", "File", "Archivos", "Files", "Atención", "Attention", "Supervisores", "Supervisors");
        words("Dashboard", "Dashboard", "Gestion", "Management", "Gestión", "Management", "Panel", "Dashboard",
            "Descripción", "Description", "Descripcion", "Description", "Entrega", "Delivery", "Cliente", "Customer",
            "Identificacion", "Identification", "Identificación", "Identification", "Marca", "Brand", "Modelo", "Model",
            "Ubicacion", "Location", "Ubicación", "Location", "Avance", "Progress", "Inicio", "Start", "Fin", "End",
            "Prioridad", "Priority", "Prevista", "Scheduled", "Media", "Medium", "Alta", "High", "Baja", "Low",
            "Aprobado", "Approved", "Observado", "Flagged", "Rechazado", "Rejected", "Pausada", "Paused",
            "Finalizada", "Completed", "Resuelta", "Resolved", "Pendiente", "Pending", "Pendientes", "Pending",
            "En produccion", "In production", "En producción", "In production", "En curso", "In progress",
            "Gerencia", "Management", "Administrador Global", "Global Administrator", "Supervisor", "Supervisor",
            "Reportar falla", "Report a fault", "Gestionar pedidos", "Manage orders", "Ver órdenes de trabajo", "View work orders",
            "Actualizar panel", "Refresh dashboard", "Conectando", "Connecting", "Especificaciones", "Specifications",
            "Tarea", "Task", "tareas", "tasks", "Máximo", "Maximum", "Mínimo", "Minimum", "Stock", "Stock",
            "Unidad", "Unit", "Espesor", "Thickness", "Límite", "Limit", "Limite", "Limit", "Realizada", "Performed",
            "Número de orden", "Order number", "Control", "Inspection", "inspección", "inspection", "turno", "shift",
            "área", "area", "operación", "operation", "operacion", "operation", "compra", "purchase", "compras", "purchases");
        phrases(
            "Generar orden", "Generate work order", "Generar orden de trabajo", "Generate work order",
            "Generar ordenes", "Generate work orders", "Crear orden", "Create work order",
            "Seguimiento de órdenes, avances y avisos de planta.", "Track work orders, production progress, and shop floor issues.",
            "Controlá el inventario y los movimientos de materiales.", "Manage inventory and material movements.",
            "Registrá controles y revisá los resultados de producción.", "Record inspections and review production results.",
            "Organizá solicitudes, reparaciones y tareas preventivas.", "Manage requests, repairs, and preventive maintenance tasks.",
            "Gestioná proveedores, abastecimiento y alertas de stock.", "Manage suppliers, purchasing, and stock alerts.",
            "Consultá las órdenes asignadas al turno", "Review work orders assigned to the shift",
            "Registrá el avance real de cada orden", "Record actual progress for each work order",
            "Informá una falla para que sea atendida", "Report a fault for maintenance",
            "Verificá existencias antes de registrar una salida", "Check stock before recording an issue",
            "Registrá ingresos y salidas de materiales", "Record material receipts and issues",
            "Atendé los materiales que requieren reposición", "Review materials that need replenishment",
            "Seleccioná una orden para registrar su control", "Select a work order to record its inspection",
            "Documentá observaciones y resultados de inspección", "Document inspection notes and results",
            "Consultá controles anteriores del área", "Review previous inspections for this area",
            "Revisá reportes de falla pendientes", "Review pending fault reports",
            "Actualizá reparaciones y repuestos utilizados", "Update repairs and parts used",
            "Programá controles preventivos para cada máquina", "Schedule preventive checks for each machine",
            "Creá y seguí órdenes de compra pendientes", "Create and track pending purchase orders",
            "Mantené actualizada la información de proveedores", "Keep supplier information up to date",
            "Priorizá la reposición de materiales críticos", "Prioritize replenishment of critical materials",
            "Actualiza la información del sector", "Update department information",
            "Revisa las tareas que requieren atención", "Review tasks that need attention",
            "Registra los movimientos y avances del día", "Record today's activity and progress",
            "Gestionar pedidos", "Manage orders", "Ver órdenes de trabajo", "View work orders",
            "Actualizar panel", "Refresh dashboard", "Accesos rápidos", "Quick actions",
            "Pedidos recientes", "Recent orders", "Actualizar indicadores", "Refresh metrics",
            "＋  Gestionar pedidos", "＋  Manage orders", "▣  Gestionar pedidos  →", "▣  Manage orders  →",
            "▤  Ver órdenes de trabajo  →", "▤  View work orders  →", "▥  Actualizar panel  →", "▥  Refresh dashboard  →",
            "Recargar ordenes", "Refresh work orders", "Recargar máquinas", "Refresh machines",
            "Recargar maquinas", "Refresh machines", "Registrar orden", "Create work order",
            "Cantidad producida", "Quantity produced", "Número de orden:", "Work order number:",
            "Precio unitario", "Unit price", "Stock inicial", "Initial stock", "Guardar límites", "Save stock limits",
            "Contraseña (vacía conserva)", "Password (leave blank to keep current)",
            "Comprometidos con la calidad,", "Committed to quality,", "la precisión y el trabajo en equipo.", "precision, and teamwork.",
            "Resumen general de pedidos, órdenes y actividad administrativa.", "Overview of orders, work orders, and administrative activity.",
            "Hola, equipo de Administración", "Hello, Administration team"
        );
    }

    private AppLanguage() { }
    private static void phrases(String... pairs) { for (int i = 0; i + 1 < pairs.length; i += 2) PHRASES.put(pairs[i], pairs[i + 1]); }
    private static void words(String... pairs) { for (int i = 0; i + 1 < pairs.length; i += 2) WORDS.put(pairs[i], pairs[i + 1]); }
    public static boolean isEnglish() { return english; }
    public static void setEnglish(boolean value) { english = value; }
    public static void installDialogTranslation() {
        Toolkit.getDefaultToolkit().addAWTEventListener(new AWTEventListener() {
            @Override public void eventDispatched(AWTEvent event) {
                if (event instanceof WindowEvent && event.getID() == WindowEvent.WINDOW_OPENED
                        && ((WindowEvent) event).getWindow() instanceof Dialog) {
                    apply(((Dialog) ((WindowEvent) event).getWindow()));
                }
            }
        }, AWTEvent.WINDOW_EVENT_MASK);
    }
    public static String text(String value) {
        if (value == null || !english) return value;
        String exact = PHRASES.get(value);
        if (exact != null) return exact;
        String translated = value;
        for (Map.Entry<String, String> entry : PHRASES.entrySet().stream()
                .sorted((a, b) -> Integer.compare(b.getKey().length(), a.getKey().length())).toList()) {
            translated = translated.replaceAll("(?iu)(?<![\\p{L}])" + java.util.regex.Pattern.quote(entry.getKey()) + "(?![\\p{L}])", entry.getValue());
        }
        for (Map.Entry<String, String> entry : WORDS.entrySet().stream()
                .sorted((a, b) -> Integer.compare(b.getKey().length(), a.getKey().length())).toList()) {
            translated = translated.replaceAll("(?iu)(?<![\\p{L}])" + java.util.regex.Pattern.quote(entry.getKey()) + "(?![\\p{L}])", entry.getValue());
        }
        return translated;
    }
    public static void apply(Component component) {
        if (component == null || !english) return;
        if (component instanceof AbstractButton) ((AbstractButton) component).setText(text(((AbstractButton) component).getText()));
        else if (component instanceof JLabel) ((JLabel) component).setText(text(((JLabel) component).getText()));
        else if (component instanceof JTextField && "Buscar en este módulo...".equals(((JTextField) component).getText())) ((JTextField) component).setText(text(((JTextField) component).getText()));
        if (component instanceof JTabbedPane) {
            JTabbedPane tabs = (JTabbedPane) component;
            for (int i = 0; i < tabs.getTabCount(); i++) tabs.setTitleAt(i, text(tabs.getTitleAt(i)));
        }
        if (component instanceof JTable) {
            JTable table = (JTable) component;
            for (int i = 0; i < table.getColumnCount(); i++) {
                TableColumn column = table.getColumnModel().getColumn(i);
                Object header = column.getHeaderValue();
                if (header != null) column.setHeaderValue(text(header.toString()));
            }
            table.getTableHeader().repaint();
        }
        if (component instanceof JComboBox) {
            JComboBox<?> combo = (JComboBox<?>) component;
            javax.swing.ListCellRenderer<?> base = combo.getRenderer();
            @SuppressWarnings({"rawtypes", "unchecked"}) JComboBox rawCombo = combo;
            rawCombo.setRenderer((list, value, index, selected, focus) -> {
                Component rendered = ((javax.swing.ListCellRenderer) base).getListCellRendererComponent(list, value, index, selected, focus);
                if (rendered instanceof JLabel && value instanceof String) ((JLabel) rendered).setText(text((String) value));
                return rendered;
            });
        }
        if (component instanceof JComponent && ((JComponent) component).getBorder() instanceof TitledBorder) {
            TitledBorder border = (TitledBorder) ((JComponent) component).getBorder();
            border.setTitle(text(border.getTitle()));
        }
        if (component instanceof JMenu) ((JMenu) component).setText(text(((JMenu) component).getText()));
        if (component instanceof JMenuItem) ((JMenuItem) component).setText(text(((JMenuItem) component).getText()));
        if (component instanceof Container) for (Component child : ((Container) component).getComponents()) apply(child);
        if (component instanceof javax.swing.JFrame) ((javax.swing.JFrame) component).setTitle(text(((javax.swing.JFrame) component).getTitle()));
        if (component instanceof Dialog) ((Dialog) component).setTitle(text(((Dialog) component).getTitle()));
    }
}
