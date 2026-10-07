package modulos.admin;

import modulos.admin.dao.AdministracionDao;
import modulos.common.dao.ApiResult;
import modulos.common.EmployeeSession;
import modulos.common.ModuleLogin;
import modulos.common.SettingsDialog;
import modulos.common.IndustrialBanner;
import modulos.common.UiTheme;

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
import javax.swing.JProgressBar;
import javax.swing.table.DefaultTableModel;
import java.awt.BorderLayout;
import java.awt.CardLayout;
import java.awt.GridLayout;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;
import java.io.IOException;
import java.time.LocalDate;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class AdminPanel {
    private static int usuarioId = 1;
    private static final AdministracionDao administracionDao = new AdministracionDao();

    public static void openAdmin() {
        EmployeeSession session = ModuleLogin.authenticate("Administracion", "Administracion");
        if (session != null) {
            openAuthenticated(session);
        }
    }

    public static void openAuthenticated(EmployeeSession session) {
        usuarioId = session.getId();
        SwingUtilities.invokeLater(() -> cargarMain(session.getNombreCompleto()));
    }

    public static JPanel createView(EmployeeSession session, Runnable onLogout) {
        usuarioId = session.getId();
        CardLayout layout = new CardLayout();
        JPanel pages = new JPanel(layout);
        pages.add(crearDashboard(name -> {
            layout.show(pages, name);
            UiTheme.selectNavigationFrom(pages, "resumen".equals(name) ? "Inicio" : "pedidos".equals(name) ? "Pedidos" : "ordenes".equals(name) ? "Ordenes" : "Chat");
        }), "resumen");
        pages.add(crearPanelPedidos(), "pedidos");
        pages.add(crearPanelOrdenes(), "ordenes");
        pages.add(crearPanelChat(), "chat");
        JPanel shell = UiTheme.shell("Administracion", "Panel de Administracion", session.getNombreCompleto(), entry -> {
            if ("Inicio".equals(entry)) {
                layout.show(pages, "resumen");
            } else if ("Pedidos".equals(entry)) {
                layout.show(pages, "pedidos");
            } else if ("Ordenes de trabajo".equals(entry)) {
                layout.show(pages, "ordenes");
            } else if ("Chat".equals(entry)) {
                layout.show(pages, "chat");
            } else if ("Configuracion".equals(entry)) {
                SettingsDialog.open(session.getId());
            } else if (!"Administracion".equals(entry)) {
                JOptionPane.showMessageDialog(null, "Este módulo pertenece a otra aplicación.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
            }
        }, onLogout);
        UiTheme.content(shell).add(pages, BorderLayout.CENTER);
        return shell;
    }

    private static void cargarMain(String employeeName) {
        JFrame ventana = new JFrame("MetalGest - Administracion");
        UiTheme.configureWindow(ventana);
        ventana.setDefaultCloseOperation(JFrame.DISPOSE_ON_CLOSE);
        ventana.setLocationRelativeTo(null);

        CardLayout layout = new CardLayout();
        JPanel pages = new JPanel(layout);
        pages.add(crearDashboard(name -> {
            layout.show(pages, name);
            UiTheme.selectNavigationFrom(pages, "resumen".equals(name) ? "Inicio" : "pedidos".equals(name) ? "Pedidos" : "ordenes".equals(name) ? "Ordenes" : "Chat");
        }), "resumen");
        pages.add(crearPanelPedidos(), "pedidos");
        pages.add(crearPanelOrdenes(), "ordenes");
        pages.add(crearPanelChat(), "chat");
        JPanel shell = UiTheme.shell("Administracion", "Panel de Administracion", employeeName, entry -> {
            if ("Inicio".equals(entry)) {
                layout.show(pages, "resumen");
            } else if ("Pedidos".equals(entry)) {
                layout.show(pages, "pedidos");
            } else if ("Ordenes de trabajo".equals(entry)) {
                layout.show(pages, "ordenes");
            } else if ("Chat".equals(entry)) {
                layout.show(pages, "chat");
            } else if (!"Administracion".equals(entry)) {
                JOptionPane.showMessageDialog(null, "Este módulo pertenece a otra aplicación.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
            }
        });
        JPanel content = UiTheme.content(shell);
        content.add(pages, BorderLayout.CENTER);
        ventana.add(shell);

        ventana.setVisible(true);
    }

    private static JPanel crearDashboard(java.util.function.Consumer<String> navigate) {
        JPanel dashboard = new JPanel(new BorderLayout(14, 14));
        dashboard.setBackground(UiTheme.BACKGROUND);
        dashboard.setBorder(javax.swing.BorderFactory.createEmptyBorder(16, 8, 8, 8));

        JPanel intro = new JPanel(new BorderLayout());
        intro.setOpaque(false);
        JLabel saludo = new JLabel("<html><b>Hola, equipo de Administración</b><br>Resumen general de pedidos, órdenes y actividad administrativa.</html>");
        saludo.setForeground(UiTheme.INK);
        saludo.setFont(new java.awt.Font("SansSerif", java.awt.Font.PLAIN, 16));
        intro.add(saludo, BorderLayout.WEST);
        dashboard.add(intro, BorderLayout.NORTH);

        JPanel cuerpo = new JPanel(new BorderLayout(14, 14));
        cuerpo.setOpaque(false);
        cuerpo.add(crearIndicadores(), BorderLayout.NORTH);
        cuerpo.add(crearActividad(navigate), BorderLayout.CENTER);
        cuerpo.add(crearLateral(), BorderLayout.EAST);
        dashboard.add(cuerpo, BorderLayout.CENTER);
        return dashboard;
    }

    private static JPanel crearLateral() {
        JPanel lateral = new JPanel(new GridLayout(3, 1, 0, 14));
        lateral.setOpaque(false);
        lateral.setPreferredSize(new java.awt.Dimension(245, 0));
        lateral.add(crearNotificaciones());
        lateral.add(crearAccesosRapidos());
        JPanel identidad = new JPanel(new BorderLayout(6, 6));
        identidad.setBackground(UiTheme.WHITE);
        identidad.setBorder(javax.swing.BorderFactory.createCompoundBorder(
                javax.swing.BorderFactory.createLineBorder(UiTheme.LINE),
                javax.swing.BorderFactory.createEmptyBorder(10, 10, 10, 10)));
        identidad.add(new IndustrialBanner(), BorderLayout.NORTH);
        JLabel texto = new JLabel("<html><b><font color='#ff5d16'>Metalúrgica San Jorge</font></b><br>Comprometidos con la calidad,<br>la precisión y el trabajo en equipo.</html>");
        texto.setForeground(UiTheme.INK);
        identidad.add(texto, BorderLayout.CENTER);
        lateral.add(identidad);
        return lateral;
    }

    private static JPanel crearNotificaciones() {
        JPanel panel = panelDashboard("🔔  Notificaciones recientes");
        JPanel items = new JPanel(new GridLayout(0, 1, 0, 8));
        items.setOpaque(false);
        String[] avisos = {"⚠  Pedido demorado", "!  Orden pendiente de generar", "✓  Pedido finalizado", "ⓘ  Nueva actividad"};
        for (String aviso : avisos) {
            JLabel item = new JLabel("<html><b>" + aviso + "</b><br>Actualización registrada en el sistema</html>");
            item.setForeground(UiTheme.INK);
            items.add(item);
        }
        panel.add(items, BorderLayout.CENTER);
        return panel;
    }

    private static JPanel crearAccesosRapidos() {
        JPanel panel = panelDashboard("⚡  Accesos rápidos");
        JPanel items = new JPanel(new GridLayout(0, 1, 0, 8));
        items.setOpaque(false);
        for (String texto : new String[]{"▣  Gestionar pedidos  →", "▤  Ver órdenes de trabajo  →", "▥  Actualizar panel  →"}) {
            JButton boton = new JButton(texto);
            UiTheme.styleButton(boton, false);
            items.add(boton);
        }
        panel.add(items, BorderLayout.CENTER);
        return panel;
    }

    private static JPanel panelDashboard(String titulo) {
        JPanel panel = new JPanel(new BorderLayout(8, 10));
        panel.setBackground(UiTheme.WHITE);
        panel.setBorder(javax.swing.BorderFactory.createCompoundBorder(
                javax.swing.BorderFactory.createLineBorder(UiTheme.LINE),
                javax.swing.BorderFactory.createEmptyBorder(12, 12, 12, 12)));
        JLabel label = new JLabel(titulo);
        label.setFont(new java.awt.Font("SansSerif", java.awt.Font.BOLD, 13));
        label.setForeground(UiTheme.INK);
        panel.add(label, BorderLayout.NORTH);
        return panel;
    }

    private static JPanel crearIndicadores() {
        JPanel indicadores = new JPanel(new GridLayout(1, 4, 12, 0));
        indicadores.setOpaque(false);
        int pedidos = 0;
        int enProceso = 0;
        int finalizados = 0;
        for (Map<String, String> pedido : getRows("pedidos_list")) {
            pedidos++;
            String estado = pedido.getOrDefault("estado", "");
            if ("Finalizada".equalsIgnoreCase(estado)) {
                finalizados++;
            } else if (!"Pendiente".equalsIgnoreCase(estado)) {
                enProceso++;
            }
        }
        indicadores.add(UiTheme.card("📦 Pedidos totales", String.valueOf(pedidos), UiTheme.ORANGE));
        indicadores.add(UiTheme.card("⚙ En proceso", String.valueOf(enProceso), UiTheme.YELLOW));
        indicadores.add(UiTheme.card("✓ Finalizados", String.valueOf(finalizados), new java.awt.Color(73, 157, 92)));
        indicadores.add(UiTheme.card("⚠ Pendientes", String.valueOf(Math.max(0, pedidos - enProceso - finalizados)), new java.awt.Color(210, 69, 52)));
        return indicadores;
    }

    private static JPanel crearActividad(java.util.function.Consumer<String> navigate) {
        JPanel actividad = new JPanel(new GridLayout(1, 2, 14, 0));
        actividad.setOpaque(false);

        JPanel pedidos = new JPanel(new BorderLayout(8, 8));
        pedidos.setBackground(UiTheme.WHITE);
        pedidos.setBorder(javax.swing.BorderFactory.createCompoundBorder(
                javax.swing.BorderFactory.createLineBorder(UiTheme.LINE),
                javax.swing.BorderFactory.createEmptyBorder(12, 12, 12, 12)));
        JLabel tituloPedidos = new JLabel("Pedidos recientes");
        tituloPedidos.setFont(new java.awt.Font("SansSerif", java.awt.Font.BOLD, 14));
        tituloPedidos.setForeground(UiTheme.INK);
        pedidos.add(tituloPedidos, BorderLayout.NORTH);
        DefaultTableModel modelo = new DefaultTableModel(new String[]{"ID", "Cliente", "Estado", "Entrega"}, 0) {
            @Override
            public boolean isCellEditable(int row, int column) {
                return false;
            }
        };
        int limite = 0;
        for (Map<String, String> row : getRows("pedidos_list")) {
            if (limite++ == 6) {
                break;
            }
            modelo.addRow(new Object[]{row.get("id_pedido"), row.get("razon_social"), row.get("estado"), row.get("fecha_entrega")});
        }
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        pedidos.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);
        actividad.add(pedidos);

        JPanel accesos = new JPanel(new BorderLayout(8, 8));
        accesos.setBackground(UiTheme.WHITE);
        accesos.setBorder(javax.swing.BorderFactory.createCompoundBorder(
                javax.swing.BorderFactory.createLineBorder(UiTheme.LINE),
                javax.swing.BorderFactory.createEmptyBorder(12, 12, 12, 12)));
        JLabel tituloAccesos = new JLabel("Accesos rápidos");
        tituloAccesos.setFont(new java.awt.Font("SansSerif", java.awt.Font.BOLD, 14));
        tituloAccesos.setForeground(UiTheme.INK);
        accesos.add(tituloAccesos, BorderLayout.NORTH);
        JPanel acciones = new JPanel(new GridLayout(0, 1, 0, 10));
        acciones.setOpaque(false);
        JButton nuevo = new JButton("＋  Gestionar pedidos");
        JButton pedidosButton = new JButton("▣  Ver pedidos");
        JButton reporte = new JButton("▤  Actualizar indicadores");
        UiTheme.styleButton(nuevo, true);
        UiTheme.styleButton(pedidosButton, false);
        UiTheme.styleButton(reporte, false);
        acciones.add(nuevo);
        acciones.add(pedidosButton);
        acciones.add(reporte);
        nuevo.addActionListener(event -> navigate.accept("pedidos"));
        pedidosButton.addActionListener(event -> navigate.accept("pedidos"));
        reporte.addActionListener(event -> navigate.accept("resumen"));
        accesos.add(acciones, BorderLayout.NORTH);
        actividad.add(accesos);
        return actividad;
    }

    private static JPanel crearPanelPedidos() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        DefaultTableModel modelo = new DefaultTableModel(
                new String[]{"ID", "Cliente", "Descripcion", "Cantidad", "Material", "Entrega", "Estado"}, 0) {
            @Override
            public boolean isCellEditable(int row, int column) {
                return false;
            }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.setBackground(UiTheme.BACKGROUND);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);

        JPanel form = new JPanel(new GridLayout(2, 6, 8, 8));
        JComboBox<String> cliente = new JComboBox<>();
        JTextField email = new JTextField();
        JTextField telefono = new JTextField();
        JTextField descripcion = new JTextField();
        JTextField cantidad = new JTextField("1");
        JComboBox<String> material = new JComboBox<>();
        JTextField entrega = new JTextField(LocalDate.now().plusDays(14).toString());
        form.add(new JLabel("Cliente"));
        form.add(new JLabel("Email"));
        form.add(new JLabel("Telefono"));
        form.add(new JLabel("Descripcion"));
        form.add(new JLabel("Cantidad"));
        form.add(new JLabel("Material"));
        form.add(cliente);
        form.add(email);
        form.add(telefono);
        form.add(descripcion);
        form.add(cantidad);
        form.add(material);

        JPanel inferior = new JPanel(new BorderLayout(8, 8));
        inferior.add(form, BorderLayout.CENTER);
        JPanel acciones = new JPanel();
        acciones.add(new JLabel("Entrega"));
        acciones.add(entrega);
        JButton crearPedido = new JButton("Crear pedido");
        JButton crearOrden = new JButton("Generar orden");
        JButton recargar = new JButton("Recargar");
        UiTheme.styleButton(crearPedido, true);
        UiTheme.styleButton(crearOrden, true);
        UiTheme.styleButton(recargar, false);
        UiTheme.styleInput(cliente);
        UiTheme.styleInput(email);
        UiTheme.styleInput(telefono);
        UiTheme.styleInput(descripcion);
        UiTheme.styleInput(cantidad);
        UiTheme.styleInput(material);
        UiTheme.styleInput(entrega);
        email.setEditable(false);
        telefono.setEditable(false);
        acciones.add(crearPedido);
        acciones.add(crearOrden);
        acciones.add(recargar);
        inferior.add(acciones, BorderLayout.SOUTH);
        panel.add(inferior, BorderLayout.SOUTH);

        cargarClientes(cliente, email, telefono);
        cargarMateriales(material);
        recargarPedidos(modelo);

        recargar.addActionListener(e -> {
            cargarClientes(cliente, email, telefono);
            cargarMateriales(material);
            recargarPedidos(modelo);
        });
        crearPedido.addActionListener(e -> {
            if (cliente.getSelectedItem() == null || material.getSelectedItem() == null || descripcion.getText().trim().isEmpty()) {
                mostrarAviso("Completa cliente, descripcion y material.");
                return;
            }
            Map<String, String> data = new LinkedHashMap<>();
            data.put("id_cliente", idSeleccionado(cliente));
            data.put("razon_social", textoSeleccionado(cliente));
            data.put("email", email.getText().trim());
            data.put("telefono", telefono.getText().trim());
            data.put("descripcion", descripcion.getText().trim());
            data.put("cantidad", cantidad.getText().trim());
            data.put("material", textoSeleccionado(material));
            data.put("fecha_entrega", entrega.getText().trim());
            if (postOk("pedidos_create", data, "Pedido creado.")) {
                descripcion.setText("");
                cantidad.setText("1");
                recargarPedidos(modelo);
            }
        });

        tabla.addMouseListener(new MouseAdapter() {
            @Override
            public void mouseClicked(MouseEvent event) {
                if (event.getClickCount() == 2 && tabla.getSelectedRow() >= 0) {
                    mostrarDetallePedido(String.valueOf(modelo.getValueAt(tabla.getSelectedRow(), 0)));
                }
            }
        });

        crearOrden.addActionListener(e -> {
            int row = tabla.getSelectedRow();
            if (row < 0) {
                mostrarAviso("Selecciona un pedido para generar la orden.");
                return;
            }
            int idPedido = Integer.parseInt(modelo.getValueAt(row, 0).toString());
            JTextField fechaInicio = new JTextField(java.time.LocalDate.now().toString());
            JTextField fechaPrevista = new JTextField(entrega.getText().trim());
            JComboBox<String> prioridad = new JComboBox<>(new String[]{"Media", "Alta", "Baja"});
            JTextArea obs = new JTextArea(4, 24);
            JPanel dialog = new JPanel(new GridLayout(0, 1, 6, 6));
            dialog.add(new JLabel("Fecha de inicio"));
            dialog.add(fechaInicio);
            dialog.add(new JLabel("Fecha prevista"));
            dialog.add(fechaPrevista);
            dialog.add(new JLabel("Prioridad"));
            dialog.add(prioridad);
            dialog.add(new JLabel("Observaciones"));
            dialog.add(new JScrollPane(obs));
            int result = JOptionPane.showConfirmDialog(null, dialog, "Nueva orden de trabajo", JOptionPane.OK_CANCEL_OPTION);
            if (result == JOptionPane.OK_OPTION) {
                Map<String, String> data = new LinkedHashMap<>();
                data.put("id_pedido", String.valueOf(idPedido));
                data.put("fecha_inicio", fechaInicio.getText().trim());
                data.put("fecha_prevista", fechaPrevista.getText().trim());
                data.put("prioridad", prioridad.getSelectedItem().toString());
                data.put("observaciones", obs.getText().trim());
                data.put("id_usuario", String.valueOf(usuarioId));
                if (postOk("ordenes_create", data, "Orden generada para Produccion.")) {
                    recargarPedidos(modelo);
                }
            }
        });

        return panel;
    }

    private static JPanel crearPanelOrdenes() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        DefaultTableModel modelo = new DefaultTableModel(
                new String[]{"ID", "Pedido", "Cliente", "Material", "Cantidad", "Prioridad", "Estado", "Avance", "Prevista"}, 0) {
            @Override
            public boolean isCellEditable(int row, int column) {
                return false;
            }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.setBackground(UiTheme.BACKGROUND);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);

        JPanel acciones = new JPanel();
        JComboBox<String> estado = new JComboBox<>(new String[]{"Pendiente", "En produccion", "Pausada", "Finalizada"});
        JTextField observaciones = new JTextField(28);
        JButton actualizar = new JButton("Actualizar estado");
        JButton recargar = new JButton("Recargar");
        UiTheme.styleButton(actualizar, true);
        UiTheme.styleButton(recargar, false);
        UiTheme.styleInput(estado);
        UiTheme.styleInput(observaciones);
        acciones.add(new JLabel("Estado"));
        acciones.add(estado);
        acciones.add(new JLabel("Observacion"));
        acciones.add(observaciones);
        acciones.add(actualizar);
        acciones.add(recargar);
        panel.add(acciones, BorderLayout.SOUTH);

        recargarOrdenes(modelo);

        recargar.addActionListener(e -> recargarOrdenes(modelo));
        actualizar.addActionListener(e -> {
            int row = tabla.getSelectedRow();
            if (row < 0) {
                mostrarAviso("Selecciona una orden.");
                return;
            }
            Map<String, String> data = new LinkedHashMap<>();
            data.put("id_orden", modelo.getValueAt(row, 0).toString());
            data.put("estado", estado.getSelectedItem().toString());
            data.put("observaciones", observaciones.getText().trim());
            if (postOk("orden_update_status", data, "Estado actualizado y cliente notificado en el pedido.")) {
                observaciones.setText("");
                recargarOrdenes(modelo);
            }
        });

        return panel;
    }

    private static JPanel crearPanelChat() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        panel.setBackground(UiTheme.BACKGROUND);

        DefaultTableModel modelo = new DefaultTableModel(
                new String[]{"ID Chat", "Cliente", "Email", "Ultimo mensaje", "Estado", "Actualizado"}, 0) {
            @Override
            public boolean isCellEditable(int row, int column) {
                return false;
            }
        };
        JTable tabla = new JTable(modelo);
        UiTheme.styleTable(tabla);
        panel.add(UiTheme.tableScroll(tabla), BorderLayout.CENTER);

        JPanel respuestaPanel = new JPanel(new BorderLayout(8, 8));
        respuestaPanel.setBackground(UiTheme.WHITE);
        respuestaPanel.setBorder(javax.swing.BorderFactory.createCompoundBorder(
                javax.swing.BorderFactory.createLineBorder(UiTheme.LINE),
                javax.swing.BorderFactory.createEmptyBorder(12, 12, 12, 12)));

        JTextArea detalle = new JTextArea(6, 40);
        JTextArea respuesta = new JTextArea(4, 40);
        detalle.setEditable(false);
        UiTheme.styleInput(detalle);
        UiTheme.styleInput(respuesta);
        respuestaPanel.add(new JScrollPane(detalle), BorderLayout.NORTH);
        respuestaPanel.add(new JScrollPane(respuesta), BorderLayout.CENTER);

        JPanel acciones = new JPanel();
        acciones.setOpaque(false);
        JButton enviar = new JButton("Responder");
        JButton recargar = new JButton("Recargar");
        UiTheme.styleButton(enviar, true);
        UiTheme.styleButton(recargar, false);
        acciones.add(enviar);
        acciones.add(recargar);
        respuestaPanel.add(acciones, BorderLayout.SOUTH);
        panel.add(respuestaPanel, BorderLayout.SOUTH);

        recargarChat(modelo);

        tabla.getSelectionModel().addListSelectionListener(event -> {
            int row = tabla.getSelectedRow();
            if (row >= 0) {
                detalle.setText("Cliente: " + modelo.getValueAt(row, 1)
                        + "\nEmail: " + modelo.getValueAt(row, 2)
                        + "\nActualizado: " + modelo.getValueAt(row, 5)
                        + "\n\nMensaje:\n" + modelo.getValueAt(row, 3));
                respuesta.setText("");
            }
        });

        recargar.addActionListener(event -> recargarChat(modelo));
        enviar.addActionListener(event -> {
            int row = tabla.getSelectedRow();
            if (row < 0) {
                mostrarAviso("Selecciona un mensaje para responder.");
                return;
            }
            if (respuesta.getText().trim().isEmpty()) {
                mostrarAviso("Escribe una respuesta para el cliente.");
                return;
            }
            Map<String, String> data = new LinkedHashMap<>();
            data.put("id_chat", modelo.getValueAt(row, 0).toString());
            data.put("mensaje", respuesta.getText().trim());
            data.put("id_usuario", String.valueOf(usuarioId));
            if (postOk("chat_reply", data, "Respuesta enviada al chat del cliente.")) {
                respuesta.setText("");
                detalle.setText("");
                recargarChat(modelo);
            }
        });

        return panel;
    }

    private static void recargarPedidos(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        for (Map<String, String> row : getRows("pedidos_list")) {
            modelo.addRow(new Object[]{
                    row.get("id_pedido"),
                    row.get("razon_social"),
                    row.get("descripcion"),
                    row.get("cantidad"),
                    row.get("material"),
                    row.get("fecha_entrega"),
                    row.get("estado")
            });
        }
    }

    private static void cargarClientes(JComboBox<String> cliente, JTextField email, JTextField telefono) {
        cliente.removeAllItems();
        List<Map<String, String>> clientes = getRows("clientes_list");
        for (Map<String, String> fila : clientes) {
            cliente.addItem(fila.get("id_cliente") + " - " + fila.get("razon_social"));
        }
        cliente.addActionListener(event -> completarContacto(cliente, clientes, email, telefono));
        completarContacto(cliente, clientes, email, telefono);
    }

    private static void completarContacto(JComboBox<String> cliente, List<Map<String, String>> clientes,
                                          JTextField email, JTextField telefono) {
        String idCliente = idSeleccionado(cliente);
        for (Map<String, String> fila : clientes) {
            if (idCliente.equals(fila.get("id_cliente"))) {
                email.setText(fila.getOrDefault("email", ""));
                telefono.setText(fila.getOrDefault("telefono", ""));
                return;
            }
        }
        email.setText("");
        telefono.setText("");
    }

    private static void cargarMateriales(JComboBox<String> material) {
        material.removeAllItems();
        for (Map<String, String> fila : getRows("materiales_list")) {
            material.addItem(fila.get("id_material") + " - " + fila.get("nombre"));
        }
    }

    private static void mostrarDetallePedido(String idPedido) {
        for (Map<String, String> pedido : getRows("pedidos_list")) {
            if (!idPedido.equals(pedido.get("id_pedido"))) {
                continue;
            }
            JPanel detalle = new JPanel(new GridLayout(0, 2, 12, 8));
            detalle.setBackground(UiTheme.WHITE);
            detalle.setBorder(javax.swing.BorderFactory.createEmptyBorder(14, 16, 14, 16));
            agregarDetalle(detalle, "Cliente", pedido.get("razon_social"));
            agregarDetalle(detalle, "Estado", pedido.get("estado"));
            agregarDetalle(detalle, "E-mail", pedido.get("email"));
            agregarDetalle(detalle, "Teléfono", pedido.get("telefono"));
            agregarDetalle(detalle, "Descripción", pedido.get("descripcion"));
            agregarDetalle(detalle, "Cantidad", pedido.get("cantidad"));
            agregarDetalle(detalle, "Material", pedido.get("material"));
            agregarDetalle(detalle, "Entrega", pedido.get("fecha_entrega"));
            agregarDetalle(detalle, "Creado", pedido.get("fecha"));
            JOptionPane.showMessageDialog(null, detalle, "Pedido #" + idPedido + " · Especificaciones", JOptionPane.INFORMATION_MESSAGE);
            return;
        }
    }

    private static void agregarDetalle(JPanel detalle, String etiqueta, String valor) {
        JLabel label = new JLabel(etiqueta);
        label.setForeground(UiTheme.MUTED);
        JLabel contenido = new JLabel(valor == null || valor.isEmpty() ? "-" : valor);
        contenido.setForeground(UiTheme.INK);
        detalle.add(label);
        detalle.add(contenido);
    }

    private static void recargarOrdenes(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        for (Map<String, String> row : getRows("ordenes_list")) {
            modelo.addRow(new Object[]{
                    row.get("id_orden"),
                    row.get("id_pedido"),
                    row.get("razon_social"),
                    row.get("material"),
                    row.get("cantidad"),
                    row.get("prioridad"),
                    row.get("estado"),
                    row.get("avance") + "%",
                    row.get("fecha_prevista")
            });
        }
    }

    private static void recargarChat(DefaultTableModel modelo) {
        modelo.setRowCount(0);
        for (Map<String, String> row : getRows("chat_list")) {
            modelo.addRow(new Object[]{
                    row.get("id_chat"),
                    row.get("nombre"),
                    row.get("email"),
                    row.get("ultimo_mensaje"),
                    row.get("estado"),
                    row.get("fecha_actualizacion")
            });
        }
    }

    private static List<Map<String, String>> getRows(String action) {
        try {
            if ("pedidos_list".equals(action)) {
                return administracionDao.listarPedidos();
            }
            if ("ordenes_list".equals(action)) {
                return administracionDao.listarOrdenes();
            }
            if ("clientes_list".equals(action)) {
                return administracionDao.listarClientes();
            }
            if ("materiales_list".equals(action)) {
                return administracionDao.listarMateriales();
            }
            if ("chat_list".equals(action)) {
                return administracionDao.listarChats();
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
            if ("pedidos_create".equals(action)) {
                result = administracionDao.crearPedido(data);
            } else if ("ordenes_create".equals(action)) {
                result = administracionDao.crearOrden(data);
            } else if ("orden_update_status".equals(action)) {
                result = administracionDao.actualizarOrden(data);
            } else if ("chat_reply".equals(action)) {
                result = administracionDao.responderChat(data);
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

    private static String idSeleccionado(JComboBox<String> combo) {
        Object seleccionado = combo.getSelectedItem();
        if (seleccionado == null) {
            return "";
        }
        return seleccionado.toString().split(" - ", 2)[0];
    }

    private static String textoSeleccionado(JComboBox<String> combo) {
        Object seleccionado = combo.getSelectedItem();
        if (seleccionado == null) {
            return "";
        }
        String[] partes = seleccionado.toString().split(" - ", 2);
        return partes.length > 1 ? partes[1] : "";
    }

    public static void main(String[] args) {
        openAdmin();
    }
}
