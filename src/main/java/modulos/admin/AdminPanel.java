package modulos.admin;

import modulos.admin.dao.AdministracionDao;
import modulos.common.dao.ApiResult;
import modulos.common.EmployeeSession;
import modulos.common.ModuleLogin;
import modulos.common.IndustrialBanner;
import modulos.common.UiTheme;
import modulos.common.RecordDetailEditor;
import modulos.produccion.ProductionPanel;
import modulos.mantenimiento.MaintenancePanel;
import modulos.deposito.DepositPanel;
import modulos.compras.PurchasesPanel;
import modulos.calidad.QualityPanel;

import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JFrame;
import javax.swing.JDialog;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTable;
import javax.swing.JTextArea;
import javax.swing.JTextField;
import javax.swing.JPasswordField;
import javax.swing.JFileChooser;
import javax.swing.ListSelectionModel;
import javax.swing.BorderFactory;
import javax.swing.BoxLayout;
import javax.swing.SwingUtilities;
import javax.swing.JProgressBar;
import javax.swing.table.DefaultTableModel;
import java.awt.BorderLayout;
import java.awt.CardLayout;
import java.awt.GridLayout;
import java.awt.FlowLayout;
import java.awt.Color;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Container;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;
import java.io.IOException;
import java.io.File;
import java.nio.file.Files;
import java.awt.Desktop;
import java.time.LocalDate;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class AdminPanel {
    private static int usuarioId = 1;
    private static final AdministracionDao administracionDao = new AdministracionDao();
    private static JComboBox<ClienteOption> pedidoClienteCombo;
    private static String pedidoClientePendiente = "";

    private static final class ClienteOption {
        private final String id;
        private final String nombre;

        private ClienteOption(String id, String nombre) {
            this.id = id;
            this.nombre = nombre;
        }

        @Override
        public String toString() {
            return nombre;
        }
    }

    private static void abrirPedidoDesdeChat(String idCliente, CardLayout layout, JPanel pages) {
        pedidoClientePendiente = idCliente;
        if (pedidoClienteCombo != null) {
            for (int i = 0; i < pedidoClienteCombo.getItemCount(); i++) {
                if (idCliente.equals(pedidoClienteCombo.getItemAt(i).id)) {
                    pedidoClienteCombo.setSelectedIndex(i);
                    pedidoClientePendiente = "";
                    break;
                }
            }
            if (!pedidoClientePendiente.isEmpty()) {
                JOptionPane.showMessageDialog(pages, "El cliente de esta conversación ya no aparece en la lista de clientes.", "Crear pedido", JOptionPane.WARNING_MESSAGE);
                pedidoClientePendiente = "";
                return;
            }
        }
        layout.show(pages, "pedidos");
        UiTheme.selectNavigationFrom(pages, "Pedidos");
    }

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
            UiTheme.selectNavigationFrom(pages, "resumen".equals(name) ? "Inicio" : "pedidos".equals(name) ? "Pedidos" : "ordenes".equals(name) ? "Ordenes" : "facturacion".equals(name) ? "Facturacion" : "Chat");
        }), "resumen");
        pages.add(crearPanelPedidos(), "pedidos");
        pages.add(crearPanelOrdenes(), "ordenes");
        pages.add(crearPanelFacturacion(), "facturacion");
        pages.add(crearPanelChat(session, id -> abrirPedidoDesdeChat(id, layout, pages)), "chat");
        pages.add(crearPanelEmpleados(session.getId()), "empleados");
        pages.add(ProductionPanel.createView(session, onLogout), "mod_produccion");
        pages.add(MaintenancePanel.createView(session, onLogout), "mod_mantenimiento");
        pages.add(DepositPanel.createView(session, onLogout), "mod_deposito");
        pages.add(PurchasesPanel.createView(session, onLogout), "mod_compras");
        pages.add(QualityPanel.createView(session, onLogout), "mod_calidad");
        JPanel shell = UiTheme.shell("Administracion", "Panel de Administracion", session.getNombreCompleto(), entry -> {
            if ("Inicio".equals(entry) || "Administracion".equals(entry)) {
                layout.show(pages, "resumen");
            } else if ("Pedidos".equals(entry)) {
                layout.show(pages, "pedidos");
            } else if ("Ordenes de trabajo".equals(entry)) {
                layout.show(pages, "ordenes");
            } else if ("Facturacion".equals(entry)) {
                layout.show(pages, "facturacion");
            } else if ("Chat".equals(entry)) {
                layout.show(pages, "chat");
            } else if ("Empleados".equals(entry)) {
                layout.show(pages, "empleados");
            } else if ("Produccion".equals(entry)) layout.show(pages,"mod_produccion");
            else if ("Mantenimiento".equals(entry)) layout.show(pages,"mod_mantenimiento");
            else if ("Deposito".equals(entry)) layout.show(pages,"mod_deposito");
            else if ("Compras".equals(entry)) layout.show(pages,"mod_compras");
            else if ("Calidad".equals(entry)) layout.show(pages,"mod_calidad");
            else if (!"Administracion".equals(entry)) {
                JOptionPane.showMessageDialog(null, "Este módulo pertenece a otra aplicación.", "MetalGest", JOptionPane.INFORMATION_MESSAGE);
            }
        }, onLogout, "Administrador Global".equalsIgnoreCase(session.getRol()));
        UiTheme.content(shell).add(pages, BorderLayout.CENTER);
        return shell;
    }

    private static JPanel crearPanelEmpleados(int adminId) {
        JPanel panel=new JPanel(new BorderLayout(10,10)); panel.setBackground(UiTheme.BACKGROUND);
        DefaultTableModel model=new DefaultTableModel(new String[]{"ID","Nombre","Apellido","Usuario","Rol"},0){ public boolean isCellEditable(int r,int c){return false;} };
        JTable table=new JTable(model); UiTheme.styleTable(table); panel.add(UiTheme.tableScroll(table),BorderLayout.CENTER);
        JTextField nombre=new JTextField(), apellido=new JTextField(), usuario=new JTextField(); JPasswordField clave=new JPasswordField();
        JComboBox<String> rol=new JComboBox<>(new String[]{"Administrador Global","Administracion","Gerencia","Produccion","Mantenimiento","Deposito","Compras","Calidad","Supervisor Produccion","Supervisor Mantenimiento","Supervisor Deposito","Supervisor Compras","Supervisor Calidad"});
        JButton guardar=new JButton("Crear empleado"), recargar=new JButton("Recargar");
        JPanel form=new JPanel(new GridLayout(2,6,8,8));
        for(String s:new String[]{"Nombre","Apellido","Usuario","Contraseña (vacía conserva)","Rol","Acciones"}) form.add(new JLabel(s));
        form.add(nombre);form.add(apellido);form.add(usuario);form.add(clave);form.add(rol); JPanel actions=new JPanel();actions.add(guardar);actions.add(recargar);form.add(actions);panel.add(form,BorderLayout.SOUTH);
        Runnable refresh=()->{model.setRowCount(0);try{for(Map<String,String> r:administracionDao.listarEmpleados(adminId))model.addRow(new Object[]{r.get("id_usuario"),r.get("nombre"),r.get("apellido"),r.get("usuario"),r.get("rol")});}catch(IOException ex){JOptionPane.showMessageDialog(panel,ex.getMessage(),"Empleados",JOptionPane.ERROR_MESSAGE);}};
        table.getSelectionModel().addListSelectionListener(e->{if(!e.getValueIsAdjusting()&&table.getSelectedRow()>=0){int r=table.convertRowIndexToModel(table.getSelectedRow());nombre.setText(String.valueOf(model.getValueAt(r,1)));apellido.setText(String.valueOf(model.getValueAt(r,2)));usuario.setText(String.valueOf(model.getValueAt(r,3)));rol.setSelectedItem(model.getValueAt(r,4));guardar.setText("Guardar cambios");}});
        recargar.addActionListener(e->refresh.run());
        guardar.addActionListener(e->{if(nombre.getText().trim().isEmpty()||usuario.getText().trim().isEmpty()){JOptionPane.showMessageDialog(panel,"Completa nombre y usuario.");return;}boolean edit=table.getSelectedRow()>=0;Map<String,String>d=new LinkedHashMap<>();d.put("id_usuario",String.valueOf(adminId));d.put("id_empleado",edit?String.valueOf(model.getValueAt(table.convertRowIndexToModel(table.getSelectedRow()),0)):"");d.put("nombre",nombre.getText().trim());d.put("apellido",apellido.getText().trim());d.put("usuario",usuario.getText().trim());d.put("contrasena",new String(clave.getPassword()));d.put("rol",String.valueOf(rol.getSelectedItem()));try{ApiResult result=edit?administracionDao.actualizarEmpleado(d):administracionDao.crearEmpleado(d);if(result.isSuccess()){table.clearSelection();guardar.setText("Crear empleado");clave.setText("");refresh.run();}else JOptionPane.showMessageDialog(panel,result.getBody(),"Empleados",JOptionPane.ERROR_MESSAGE);}catch(IOException ex){JOptionPane.showMessageDialog(panel,ex.getMessage(),"Empleados",JOptionPane.ERROR_MESSAGE);}});
        refresh.run();return panel;
    }

    private static final class ChatOption {
        private final String id, clientId, clientName, subject, orderId;
        private ChatOption(Map<String, String> data) {
            id = clean(data.get("id_chat")); clientId = clean(data.get("id_cliente"));
            clientName = clean(data.get("nombre")).isEmpty() ? "Cliente" : clean(data.get("nombre"));
            subject = clean(data.get("asunto")).isEmpty() ? "Consulta" : clean(data.get("asunto"));
            orderId = clean(data.get("id_pedido"));
        }
        private static String clean(String value) { return value == null || "null".equalsIgnoreCase(value) ? "" : value; }
        @Override public String toString() { return subject + "  ·  " + clientName; }
    }

    private static JPanel crearPanelFacturacion() {
        JPanel panel = new JPanel(new BorderLayout(10, 10));
        panel.setBackground(UiTheme.BACKGROUND);
        DefaultTableModel model = new DefaultTableModel(new String[]{"ID", "N.º factura", "Cliente", "Asunto", "Pedido", "Fecha", "Total", "Estado", "ID cliente", "ID chat", "Tipo", "Condición de venta", "IVA"}, 0) {
            @Override public boolean isCellEditable(int row, int column) { return false; }
        };
        JTable table = new JTable(model);
        UiTheme.styleTable(table);
        for (int i=12; i>=8; i--) table.removeColumn(table.getColumnModel().getColumn(i));
        panel.add(UiTheme.tableScroll(table), BorderLayout.CENTER);
        JButton create = new JButton("Crear factura"), edit = new JButton("Editar"), delete = new JButton("Eliminar"), refresh = new JButton("Actualizar"), sendPdf = new JButton("Generar PDF y enviar al chat");
        UiTheme.styleButton(create, true); UiTheme.styleButton(edit, false); UiTheme.styleButton(delete, false); UiTheme.styleButton(refresh, false); UiTheme.styleButton(sendPdf, true);
        JPanel actions = new JPanel(new FlowLayout(FlowLayout.RIGHT)); actions.setOpaque(false);
        actions.add(refresh); actions.add(edit); actions.add(delete); actions.add(sendPdf); actions.add(create);
        panel.add(actions, BorderLayout.SOUTH);
        Runnable load = () -> {
            model.setRowCount(0);
            try {
                Map<String, Map<String,String>> chats = new LinkedHashMap<>();
                for (Map<String,String> chat : administracionDao.listarChatsApp()) chats.put(chat.get("id_chat"), chat);
                for (Map<String, String> invoice : administracionDao.listarFacturas()) {
                    Map<String,String> chat = chats.get(invoice.get("id_chat"));
                    String subject = chat == null ? "(sin conversación)" : chat.getOrDefault("asunto", "Consulta");
                    model.addRow(new Object[]{invoice.get("id_factura"), invoice.get("numero"), invoice.get("razon_social"), subject, invoice.get("id_pedido"), invoice.get("fecha"), invoice.get("total"), invoice.get("estado"), invoice.get("id_cliente"), invoice.get("id_chat"), invoice.get("tipo_comprobante"), invoice.get("condicion_venta"), invoice.get("iva_alicuota")});
                }
            } catch (IOException error) { JOptionPane.showMessageDialog(panel, error.getMessage(), "Facturación", JOptionPane.ERROR_MESSAGE); }
        };
        java.util.function.Function<Integer,Map<String,String>> invoiceAt = row -> {
            Map<String,String> item = new LinkedHashMap<>();
            item.put("id_factura", String.valueOf(model.getValueAt(row,0))); item.put("numero", String.valueOf(model.getValueAt(row,1)));
            item.put("id_cliente", String.valueOf(model.getValueAt(row,8))); item.put("id_chat", String.valueOf(model.getValueAt(row,9)));
            item.put("id_pedido", String.valueOf(model.getValueAt(row,4))); item.put("fecha", String.valueOf(model.getValueAt(row,5)));
            item.put("total", String.valueOf(model.getValueAt(row,6))); item.put("estado", String.valueOf(model.getValueAt(row,7)));
            item.put("tipo_comprobante", String.valueOf(model.getValueAt(row,10))); item.put("condicion_venta", String.valueOf(model.getValueAt(row,11)));
            item.put("iva_alicuota", String.valueOf(model.getValueAt(row,12))); return item;
        };
        create.addActionListener(event -> { if (abrirFormularioFactura(panel, null, null)) load.run(); });
        edit.addActionListener(event -> { int selected=table.getSelectedRow(); if(selected<0){JOptionPane.showMessageDialog(panel,"Seleccioná una factura.");return;} if(abrirFormularioFactura(panel,invoiceAt.apply(table.convertRowIndexToModel(selected)),null)) load.run(); });
        delete.addActionListener(event -> {
            int selected=table.getSelectedRow(); if(selected<0){JOptionPane.showMessageDialog(panel,"Seleccioná una factura para eliminar.");return;}
            int row=table.convertRowIndexToModel(selected);
            if(JOptionPane.showConfirmDialog(panel,"¿Eliminar la factura seleccionada?","Facturación",JOptionPane.YES_NO_OPTION)!=JOptionPane.YES_OPTION)return;
            Map<String,String> data=new LinkedHashMap<>(); data.put("id_factura",String.valueOf(model.getValueAt(row,0)));
            try { modulos.common.dao.ApiResult result=administracionDao.eliminarFactura(data); if(result.isSuccess())load.run(); else JOptionPane.showMessageDialog(panel,result.getBody(),"Facturación",JOptionPane.ERROR_MESSAGE); }
            catch(IOException error){JOptionPane.showMessageDialog(panel,error.getMessage(),"Facturación",JOptionPane.ERROR_MESSAGE);}
        });
        sendPdf.addActionListener(event -> {
            int selected=table.getSelectedRow(); if(selected<0){JOptionPane.showMessageDialog(panel,"Seleccioná una factura.");return;}
            int row=table.convertRowIndexToModel(selected); Map<String,String> data=new LinkedHashMap<>(); data.put("id_factura",String.valueOf(model.getValueAt(row,0)));
            try { modulos.common.dao.ApiResult result=administracionDao.enviarFacturaAlChat(data); if(result.isSuccess())JOptionPane.showMessageDialog(panel,"PDF enviado al chat asociado. El archivo está marcado como borrador hasta contar con CAE de ARCA."); else JOptionPane.showMessageDialog(panel,result.getBody(),"Facturación",JOptionPane.ERROR_MESSAGE); }
            catch(IOException error){JOptionPane.showMessageDialog(panel,error.getMessage(),"Facturación",JOptionPane.ERROR_MESSAGE);}
        });
        refresh.addActionListener(event -> load.run());
        load.run();
        return panel;
    }

    private static boolean abrirFormularioFactura(java.awt.Component parent, Map<String,String> factura, ChatOption prefilledChat) {
        JDialog dialog = new JDialog(SwingUtilities.getWindowAncestor(parent), factura == null ? "Crear factura" : "Editar factura", java.awt.Dialog.ModalityType.APPLICATION_MODAL);
        dialog.setDefaultCloseOperation(JDialog.DISPOSE_ON_CLOSE);
        JPanel form = new JPanel(new GridBagLayout()); form.setBackground(UiTheme.BACKGROUND); form.setBorder(BorderFactory.createEmptyBorder(18,18,12,18));
        JComboBox<ChatOption> conversation = new JComboBox<>();
        JLabel client = new JLabel("Seleccioná una conversación"), order = new JLabel("—");
        JTextField number = new JTextField(18), date = new JTextField(LocalDate.now().toString(), 18), total = new JTextField(18);
        JComboBox<String> type = new JComboBox<>(new String[]{"A","B","C"}), sale = new JComboBox<>(new String[]{"Cuenta corriente","Transferencia","Contado","Otro"}), vat = new JComboBox<>(new String[]{"21","10.5","27","0"}), status = new JComboBox<>(new String[]{"Pendiente","Pagada","Vencida","Anulada"});
        for(javax.swing.JComponent c:new javax.swing.JComponent[]{conversation,number,date,total,type,sale,vat,status}) UiTheme.styleInput(c);
        try {
            for(Map<String,String> row:administracionDao.listarChatsApp()) conversation.addItem(new ChatOption(row));
        } catch(IOException error) { JOptionPane.showMessageDialog(parent,error.getMessage(),"Facturación",JOptionPane.ERROR_MESSAGE); dialog.dispose(); return false; }
        Runnable updateChatLabels=()->{ChatOption selected=(ChatOption)conversation.getSelectedItem(); if(selected!=null){client.setText(selected.clientName);order.setText(selected.orderId.isEmpty()?"Sin pedido vinculado": "Pedido #"+selected.orderId);}};
        conversation.addActionListener(event->updateChatLabels.run());
        int y=0;
        for(Object[] row:new Object[][]{{"Conversación (asunto)",conversation},{"Cliente",client},{"Pedido vinculado",order},{"Número",number},{"Fecha (AAAA-MM-DD)",date},{"Tipo (borrador)",type},{"Condición de venta",sale},{"IVA (%)",vat},{"Total final (IVA incluido)",total},{"Estado",status}}){
            GridBagConstraints c=new GridBagConstraints(); c.gridx=0;c.gridy=y;c.anchor=GridBagConstraints.WEST;c.insets=new java.awt.Insets(5,4,5,12);form.add(new JLabel(String.valueOf(row[0])),c);
            c=new GridBagConstraints();c.gridx=1;c.gridy=y++;c.weightx=1;c.fill=GridBagConstraints.HORIZONTAL;c.insets=new java.awt.Insets(5,4,5,4);form.add((java.awt.Component)row[1],c);
        }
        if(prefilledChat!=null){ for(int i=0;i<conversation.getItemCount();i++)if(conversation.getItemAt(i).id.equals(prefilledChat.id)){conversation.setSelectedIndex(i);break;} }
        if(factura!=null){number.setText(factura.getOrDefault("numero",""));date.setText(factura.getOrDefault("fecha",LocalDate.now().toString()));total.setText(factura.getOrDefault("total",""));type.setSelectedItem(factura.getOrDefault("tipo_comprobante","B"));sale.setSelectedItem(factura.getOrDefault("condicion_venta","Cuenta corriente"));vat.setSelectedItem(factura.getOrDefault("iva_alicuota","21"));status.setSelectedItem(factura.getOrDefault("estado","Pendiente"));String id=factura.getOrDefault("id_chat","");for(int i=0;i<conversation.getItemCount();i++)if(conversation.getItemAt(i).id.equals(id)){conversation.setSelectedIndex(i);break;}}
        updateChatLabels.run();
        JLabel note=new JLabel("Borrador interno: no válido como factura fiscal sin autorización CAE de ARCA.");note.setForeground(new Color(170,75,35));
        JPanel content=new JPanel(new BorderLayout(8,8));content.add(form,BorderLayout.CENTER);content.add(note,BorderLayout.NORTH);
        JButton save=new JButton("Guardar"), saveAndSend=new JButton("Guardar y enviar PDF al chat"), cancel=new JButton("Cancelar");UiTheme.styleButton(save,true);UiTheme.styleButton(saveAndSend,true);UiTheme.styleButton(cancel,false);
        JPanel buttons=new JPanel(new FlowLayout(FlowLayout.RIGHT));buttons.add(cancel);buttons.add(save);buttons.add(saveAndSend);content.add(buttons,BorderLayout.SOUTH);
        final boolean[] saved={false};
        final String[] invoiceId={factura==null?"":factura.getOrDefault("id_factura","")};
        cancel.addActionListener(event->dialog.dispose());
        java.util.function.Consumer<Boolean> persist=sendAfterSave->{
            ChatOption selected=(ChatOption)conversation.getSelectedItem();
            if(selected==null||number.getText().trim().isEmpty()||total.getText().trim().isEmpty()){JOptionPane.showMessageDialog(dialog,"Completá conversación, número e importe.");return;}
            try{if(Double.parseDouble(total.getText().trim().replace(',','.'))<0)throw new NumberFormatException();}catch(NumberFormatException error){JOptionPane.showMessageDialog(dialog,"El importe debe ser un número igual o mayor que cero.");return;}
            if(!date.getText().trim().matches("\\d{4}-\\d{2}-\\d{2}")){JOptionPane.showMessageDialog(dialog,"Ingresá la fecha como AAAA-MM-DD.");return;}
            Map<String,String> data=new LinkedHashMap<>();data.put("id_cliente",selected.clientId);data.put("id_chat",selected.id);data.put("id_pedido",selected.orderId);data.put("numero",number.getText().trim());data.put("fecha",date.getText().trim());data.put("total",total.getText().trim().replace(',','.'));data.put("tipo_comprobante",String.valueOf(type.getSelectedItem()));data.put("condicion_venta",String.valueOf(sale.getSelectedItem()));data.put("iva_alicuota",String.valueOf(vat.getSelectedItem()));data.put("estado",String.valueOf(status.getSelectedItem()));
            boolean editing=!invoiceId[0].isEmpty();if(editing)data.put("id_factura",invoiceId[0]);
            try{
                modulos.common.dao.ApiResult result=editing?administracionDao.actualizarFactura(data):administracionDao.crearFactura(data);
                if(!result.isSuccess()){JOptionPane.showMessageDialog(dialog,result.getBody(),"Facturación",JOptionPane.ERROR_MESSAGE);return;}
                if(!editing){invoiceId[0]=modulos.common.JsonUtil.parseObject(result.getBody()).getOrDefault("id_factura","");}
                if(sendAfterSave){
                    if(invoiceId[0].isEmpty()){JOptionPane.showMessageDialog(dialog,"Se guardó la factura, pero no se obtuvo su identificador para generar el PDF.","Facturación",JOptionPane.ERROR_MESSAGE);return;}
                    Map<String,String> sendData=new LinkedHashMap<>();sendData.put("id_factura",invoiceId[0]);
                    modulos.common.dao.ApiResult sent=administracionDao.enviarFacturaAlChat(sendData);
                    if(!sent.isSuccess()){JOptionPane.showMessageDialog(dialog,"La factura se guardó, pero no se pudo enviar el PDF:\n"+sent.getBody(),"Facturación",JOptionPane.ERROR_MESSAGE);return;}
                }
                saved[0]=true;dialog.dispose();
            }
            catch(IOException error){JOptionPane.showMessageDialog(dialog,error.getMessage(),"Facturación",JOptionPane.ERROR_MESSAGE);}
        };
        save.addActionListener(event->persist.accept(false));saveAndSend.addActionListener(event->persist.accept(true));
        dialog.setContentPane(content);dialog.pack();dialog.setMinimumSize(new java.awt.Dimension(520,460));dialog.setLocationRelativeTo(parent);dialog.setVisible(true);return saved[0];
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
            UiTheme.selectNavigationFrom(pages, "resumen".equals(name) ? "Inicio" : "pedidos".equals(name) ? "Pedidos" : "ordenes".equals(name) ? "Ordenes" : "facturacion".equals(name) ? "Facturacion" : "Chat");
        }), "resumen");
        pages.add(crearPanelPedidos(), "pedidos");
        pages.add(crearPanelOrdenes(), "ordenes");
        pages.add(crearPanelFacturacion(), "facturacion");
        pages.add(crearPanelChat(null, id -> abrirPedidoDesdeChat(id, layout, pages)), "chat");
        JPanel shell = UiTheme.shell("Administracion", "Panel de Administracion", employeeName, entry -> {
            if ("Inicio".equals(entry) || "Administracion".equals(entry)) {
                layout.show(pages, "resumen");
            } else if ("Pedidos".equals(entry)) {
                layout.show(pages, "pedidos");
            } else if ("Ordenes de trabajo".equals(entry)) {
                layout.show(pages, "ordenes");
            } else if ("Facturacion".equals(entry)) {
                layout.show(pages, "facturacion");
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
        RecordDetailEditor.attach(tabla, "pedido");
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

        JPanel form = new JPanel(new BorderLayout(8, 8));
        JPanel campos = new JPanel(new GridLayout(2, 6, 8, 8));
        JComboBox<ClienteOption> cliente = new JComboBox<>();
        pedidoClienteCombo = cliente;
        JTextField email = new JTextField();
        JTextField telefono = new JTextField();
        JTextField descripcion = new JTextField();
        JTextField cantidad = new JTextField("1");
        JComboBox<String> material = new JComboBox<>();
        JTextField entrega = new JTextField(LocalDate.now().plusDays(14).toString());
        campos.add(new JLabel("Cliente"));
        campos.add(new JLabel("Email"));
        campos.add(new JLabel("Telefono"));
        campos.add(new JLabel("Descripcion"));
        campos.add(new JLabel("Cantidad"));
        campos.add(new JLabel("Material"));
        campos.add(cliente);
        campos.add(email);
        campos.add(telefono);
        campos.add(descripcion);
        campos.add(cantidad);
        campos.add(material);
        JTextField buscarCliente = new JTextField();
        buscarCliente.setToolTipText("Escribí el nombre para buscar un cliente");
        UiTheme.styleInput(buscarCliente);
        JPanel busquedaCliente = new JPanel(new BorderLayout(8, 0));
        busquedaCliente.setOpaque(false);
        busquedaCliente.add(new JLabel("Buscar cliente"), BorderLayout.WEST);
        busquedaCliente.add(buscarCliente, BorderLayout.CENTER);
        form.add(busquedaCliente, BorderLayout.NORTH);
        form.add(campos, BorderLayout.CENTER);

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
        List<Map<String, String>> clientesDisponibles = getRows("clientes_list");
        final boolean[] actualizandoClientes = {false};
        buscarCliente.getDocument().addDocumentListener(new javax.swing.event.DocumentListener() {
            private void actualizar() {
                if (actualizandoClientes[0]) return;
                String query = buscarCliente.getText().trim().toLowerCase();
                String selectedId = clienteIdSeleccionado(cliente);
                actualizandoClientes[0] = true;
                cliente.removeAllItems();
                for (Map<String, String> row : clientesDisponibles) {
                    String id = row.getOrDefault("id_cliente", "");
                    String name = row.getOrDefault("razon_social", "");
                    if (query.isEmpty() || name.toLowerCase().contains(query)) cliente.addItem(new ClienteOption(id, name));
                }
                boolean restored = false;
                for (int i = 0; i < cliente.getItemCount(); i++) if (selectedId.equals(cliente.getItemAt(i).id)) { cliente.setSelectedIndex(i); restored = true; break; }
                if (!restored && cliente.getItemCount() > 0) cliente.setSelectedIndex(0);
                actualizandoClientes[0] = false;
                completarContacto(cliente, clientesDisponibles, email, telefono);
            }
            public void insertUpdate(javax.swing.event.DocumentEvent e) { actualizar(); }
            public void removeUpdate(javax.swing.event.DocumentEvent e) { actualizar(); }
            public void changedUpdate(javax.swing.event.DocumentEvent e) { actualizar(); }
        });
        cargarMateriales(material);
        recargarPedidos(modelo);

        recargar.addActionListener(e -> {
            cargarClientes(cliente, email, telefono);
            clientesDisponibles.clear();
            clientesDisponibles.addAll(getRows("clientes_list"));
            cargarMateriales(material);
            recargarPedidos(modelo);
        });
        crearPedido.addActionListener(e -> {
            if (cliente.getSelectedItem() == null || material.getSelectedItem() == null || descripcion.getText().trim().isEmpty()) {
                mostrarAviso("Completa cliente, descripcion y material.");
                return;
            }
            Map<String, String> data = new LinkedHashMap<>();
            data.put("id_cliente", clienteIdSeleccionado(cliente));
            data.put("razon_social", clienteNombreSeleccionado(cliente));
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

        RecordDetailEditor.attach(tabla, "pedido");

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
        RecordDetailEditor.attach(tabla, "orden");
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
        boolean puedeEditar = EmployeeSession.canEditRecords();
        if (puedeEditar) {
            acciones.add(new JLabel("Estado"));
            acciones.add(estado);
            acciones.add(new JLabel("Observacion"));
            acciones.add(observaciones);
            acciones.add(actualizar);
        }
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

    private static JPanel crearPanelChat(EmployeeSession session, java.util.function.Consumer<String> crearPedido) {
        JPanel container = new JPanel(new BorderLayout());
        container.setBackground(UiTheme.BACKGROUND);
        if (!chatApiDisponible()) {
            JPanel unavailable = new JPanel(new BorderLayout(8, 8));
            unavailable.setBackground(UiTheme.WHITE);
            unavailable.setBorder(BorderFactory.createEmptyBorder(28, 30, 28, 30));
            JLabel message = new JLabel("<html><b>Chat no disponible</b><br>Verificá que la API de la app esté activa en la ruta configurada.</html>");
            message.setForeground(UiTheme.INK);
            unavailable.add(message, BorderLayout.CENTER);
            container.add(unavailable, BorderLayout.NORTH);
            return container;
        }

        CardLayout cards = new CardLayout();
        JPanel views = new JPanel(cards);
        views.setOpaque(false);
        Runnable[] abrirBandeja = {() -> {
            views.removeAll();
            JPanel inbox = crearBandejaChat(crearPedido);
            views.add(inbox, "bandeja");
            cards.show(views, "bandeja");
            views.revalidate();
            views.repaint();
        }};
        views.add(session == null
                ? crearIngresoChat(null, () -> abrirBandeja[0].run())
                : crearCargandoChat(), "acceso");
        cards.show(views, "acceso");
        if (session != null) {
            SwingUtilities.invokeLater(() -> {
                String rol = session.getRol();
                boolean autorizado = "Administracion".equalsIgnoreCase(rol) || "Gerencia".equalsIgnoreCase(rol);
                if (autorizado && activarSesionChat(session)) {
                    abrirBandeja[0].run();
                } else {
                    JLabel issue = new JLabel(autorizado
                            ? "No se pudo conectar con el chat. Volvé a seleccionar Chat para reintentar."
                            : "El chat está disponible para Administración y Gerencia.");
                    issue.setForeground(UiTheme.MUTED);
                    JPanel feedback = new JPanel(new GridBagLayout());
                    feedback.setOpaque(false);
                    feedback.add(issue, new GridBagConstraints());
                    views.removeAll();
                    views.add(feedback, "acceso");
                    cards.show(views, "acceso");
                    views.revalidate();
                    views.repaint();
                }
            });
        }
        container.add(views, BorderLayout.CENTER);
        return container;
    }

    private static JPanel crearCargandoChat() {
        JPanel panel = new JPanel(new GridBagLayout());
        panel.setOpaque(false);
        JLabel message = new JLabel("Conectando con el chat de Administración...");
        message.setForeground(UiTheme.MUTED);
        panel.add(message, new GridBagConstraints());
        return panel;
    }

    private static boolean activarSesionChat(EmployeeSession session) {
        try {
            return administracionDao.iniciarSesionChat(session.getId(), session.getRol()).isSuccess();
        } catch (IOException error) {
            return false;
        }
    }

    private static JPanel crearIngresoChat(EmployeeSession session, Runnable onLogin) {
        JPanel outer = new JPanel(new GridBagLayout());
        outer.setOpaque(false);
        if (session != null) {
            JLabel message = new JLabel("Conectando con el chat de Administración...");
            message.setForeground(UiTheme.MUTED);
            outer.add(message, new GridBagConstraints());
            SwingUtilities.invokeLater(() -> {
                if (activarSesionChat(session)) onLogin.run();
            });
            return outer;
        }
        JPanel card = new JPanel(new GridBagLayout());
        card.setBackground(UiTheme.WHITE);
        card.setBorder(BorderFactory.createCompoundBorder(BorderFactory.createLineBorder(UiTheme.LINE), BorderFactory.createEmptyBorder(22, 26, 22, 26)));
        GridBagConstraints c = new GridBagConstraints();
        c.gridx = 0;
        c.fill = GridBagConstraints.HORIZONTAL;
        c.weightx = 1;
        c.insets = new java.awt.Insets(6, 0, 6, 0);
        JLabel title = new JLabel("Chat de Administración");
        title.setFont(new java.awt.Font("SansSerif", java.awt.Font.BOLD, 20));
        title.setForeground(UiTheme.INK);
        chatAdd(card, title, c, 0);
        JLabel explanation = new JLabel(session == null
                ? "Ingresá con una cuenta de Administración o Gerencia autorizada en el sitio web."
                : "Se usa la misma sesión de Administración con la que ingresaste a MetalGest.");
        explanation.setForeground(UiTheme.MUTED);
        chatAdd(card, explanation, c, 1);
        JTextField usuario = new JTextField(session == null ? "" : session.getUsuario(), 22);
        JPasswordField clave = new JPasswordField(22);
        UiTheme.styleInput(usuario);
        UiTheme.styleInput(clave);
        if (session == null) {
            chatAdd(card, new JLabel("Usuario"), c, 2);
            chatAdd(card, usuario, c, 3);
            chatAdd(card, new JLabel("Contraseña"), c, 4);
            chatAdd(card, clave, c, 5);
        }
        JLabel estado = new JLabel(" ");
        estado.setForeground(new Color(220, 90, 60));
        chatAdd(card, estado, c, 6);
        JButton ingresar = new JButton("Ingresar al chat");
        UiTheme.styleButton(ingresar, true);
        if (session == null) {
            chatAdd(card, ingresar, c, 7);
        }
        ingresar.addActionListener(event -> {
            try {
                ApiResult result = session == null
                        ? administracionDao.iniciarSesionChat(usuario.getText().trim(), new String(clave.getPassword()))
                        : administracionDao.iniciarSesionChat(session.getId(), session.getRol());
                if (result.isSuccess()) {
                    onLogin.run();
                } else {
                    estado.setText("No se pudo iniciar sesión. Revisá tus datos o el acceso a Administración.");
                }
            } catch (IOException error) {
                estado.setText("No se pudo conectar con el sitio web.");
            }
        });
        clave.addActionListener(event -> ingresar.doClick());
        if (session != null) {
            SwingUtilities.invokeLater(ingresar::doClick);
        }
        outer.add(card, new GridBagConstraints());
        return outer;
    }

    private static JPanel crearBandejaChat(java.util.function.Consumer<String> crearPedido) {
        JPanel panel = new JPanel(new BorderLayout(0, 0));
        panel.setBackground(UiTheme.WHITE);
        panel.setBorder(BorderFactory.createLineBorder(UiTheme.LINE));
        DefaultTableModel model = new DefaultTableModel(new String[]{"ID", "Cliente", "Email", "Último mensaje", "Estado", "Cliente ID", "Asunto", "Pedido ID"}, 0) {
            @Override public boolean isCellEditable(int row, int column) { return false; }
        };
        JTable chats = new JTable(model);
        UiTheme.styleTable(chats);
        chats.setSelectionMode(ListSelectionModel.SINGLE_SELECTION);
        chats.getColumnModel().getColumn(0).setMinWidth(0);
        chats.getColumnModel().getColumn(0).setMaxWidth(0);
        chats.getColumnModel().getColumn(0).setPreferredWidth(0);
        chats.getColumnModel().getColumn(1).setPreferredWidth(130);
        chats.getColumnModel().getColumn(2).setPreferredWidth(145);
        chats.getColumnModel().getColumn(3).setPreferredWidth(190);
        chats.getColumnModel().getColumn(4).setPreferredWidth(80);
        chats.getColumnModel().getColumn(5).setMinWidth(0);
        chats.getColumnModel().getColumn(5).setMaxWidth(0);
        chats.getColumnModel().getColumn(5).setPreferredWidth(0);
        for (int i=6; i<=7; i++) { chats.getColumnModel().getColumn(i).setMinWidth(0); chats.getColumnModel().getColumn(i).setMaxWidth(0); chats.getColumnModel().getColumn(i).setPreferredWidth(0); }
        JPanel list = new JPanel(new BorderLayout(0, 8));
        list.setPreferredSize(new java.awt.Dimension(410, 0));
        list.setBackground(UiTheme.WHITE);
        list.setBorder(BorderFactory.createEmptyBorder(14, 14, 14, 0));
        JPanel listHeader = new JPanel(new BorderLayout());
        listHeader.setOpaque(false);
        JLabel listTitle = new JLabel("CONVERSACIONES");
        listTitle.setFont(new java.awt.Font("SansSerif", java.awt.Font.BOLD, 11));
        listTitle.setForeground(UiTheme.ORANGE);
        JButton refresh = new JButton("Actualizar");
        JButton logout = new JButton("Salir del chat");
        JButton createOrder = new JButton("Crear pedido");
        JButton createInvoice = new JButton("Crear factura");
        UiTheme.styleButton(refresh, false);
        UiTheme.styleButton(logout, false);
        UiTheme.styleButton(createOrder, true);
        UiTheme.styleButton(createInvoice, true);
        listHeader.add(listTitle, BorderLayout.WEST);
        JPanel headerActions = new JPanel(new FlowLayout(FlowLayout.RIGHT, 5, 0));
        headerActions.setOpaque(false);
        headerActions.add(createOrder);
        headerActions.add(createInvoice);
        headerActions.add(logout);
        listHeader.add(headerActions, BorderLayout.EAST);
        list.add(listHeader, BorderLayout.NORTH);
        list.add(UiTheme.tableScroll(chats), BorderLayout.CENTER);
        JPanel conversation = new JPanel(new BorderLayout(0, 0));
        conversation.setBackground(UiTheme.BACKGROUND);
        conversation.setBorder(BorderFactory.createEmptyBorder(0, 14, 14, 14));
        JPanel toolbar = new JPanel(new BorderLayout(8, 0));
        toolbar.setBackground(UiTheme.WHITE);
        toolbar.setBorder(BorderFactory.createEmptyBorder(12, 14, 12, 8));
        JLabel contact = new JLabel("Elegí una conversación");
        contact.setForeground(UiTheme.INK);
        contact.setFont(new java.awt.Font("SansSerif", java.awt.Font.BOLD, 13));
        toolbar.add(contact, BorderLayout.CENTER);
        JPanel tools = new JPanel(new FlowLayout(FlowLayout.RIGHT, 6, 0));
        tools.setOpaque(false);
        tools.add(refresh);
        toolbar.add(tools, BorderLayout.EAST);
        conversation.add(toolbar, BorderLayout.NORTH);
        JPanel messages = new JPanel();
        messages.setLayout(new BoxLayout(messages, BoxLayout.Y_AXIS));
        messages.setBackground(new Color(22, 25, 27));
        JScrollPane messageScroll = new JScrollPane(messages);
        messageScroll.setBorder(BorderFactory.createEmptyBorder());
        messageScroll.getViewport().setBackground(messages.getBackground());
        conversation.add(messageScroll, BorderLayout.CENTER);
        JTextArea composer = new JTextArea(3, 30);
        composer.setLineWrap(true);
        composer.setWrapStyleWord(true);
        UiTheme.styleInput(composer);
        JButton send = new JButton("Enviar  ➤");
        JButton attach = new JButton("Adjuntar");
        UiTheme.styleButton(send, true);
        UiTheme.styleButton(attach, false);
        send.setEnabled(false);
        JPanel compose = new JPanel(new BorderLayout(8, 0));
        compose.setBackground(UiTheme.WHITE);
        compose.setBorder(BorderFactory.createEmptyBorder(10, 10, 10, 10));
        compose.add(new JScrollPane(composer), BorderLayout.CENTER);
        JPanel composeActions = new JPanel(new FlowLayout(FlowLayout.RIGHT, 6, 0));
        composeActions.setOpaque(false);
        composeActions.add(attach);
        composeActions.add(send);
        compose.add(composeActions, BorderLayout.EAST);
        conversation.add(compose, BorderLayout.SOUTH);
        panel.add(list, BorderLayout.WEST);
        panel.add(conversation, BorderLayout.CENTER);
        final String[] selectedChat = {""};
        final String[] selectedClient = {""};
        final List<File> selectedFiles = new java.util.ArrayList<>();
        final JLabel fileHint = new JLabel(" ");
        fileHint.setForeground(UiTheme.MUTED);
        compose.add(fileHint, BorderLayout.NORTH);
        Runnable loadChats = () -> {
            model.setRowCount(0);
            try {
                List<Map<String, String>> chatRows = administracionDao.listarChatsApp();
                for (Map<String, String> row : chatRows) {
                    model.addRow(new Object[]{row.get("id_chat"), row.get("nombre"), row.get("email"), row.get("ultimo_mensaje"), row.get("estado"), row.get("id_cliente"), row.get("asunto"), row.get("id_pedido")});
                }
                if (!selectedChat[0].isEmpty()) {
                    for (int i = 0; i < model.getRowCount(); i++) {
                        if (selectedChat[0].equals(String.valueOf(model.getValueAt(i, 0)))) {
                            chats.setRowSelectionInterval(i, i);
                            break;
                        }
                    }
                }
                if (selectedChat[0].isEmpty() && model.getRowCount() > 0) {
                    chats.setRowSelectionInterval(0, 0);
                }
            } catch (IOException error) {
                messages.removeAll();
                JLabel issue = new JLabel("No se pudieron cargar las conversaciones: " + error.getMessage());
                issue.setForeground(new Color(255, 130, 90));
                messages.add(issue);
                messages.revalidate();
                messages.repaint();
            }
        };
        Runnable loadMessages = () -> {
            messages.removeAll();
            if (selectedChat[0].isEmpty()) {
                JLabel empty = new JLabel("Seleccioná una conversación para ver los mensajes.");
                empty.setForeground(UiTheme.MUTED);
                empty.setBorder(BorderFactory.createEmptyBorder(25, 20, 10, 10));
                messages.add(empty);
                send.setEnabled(false);
            } else {
                send.setEnabled(true);
                try {
                    List<Map<String, String>> chatMessages = administracionDao.listarMensajesChat(Integer.parseInt(selectedChat[0]));
                    Map<String, List<Map<String, String>>> attachments = new LinkedHashMap<>();
                    for (Map<String, String> attachment : administracionDao.listarAdjuntosChat(Integer.parseInt(selectedChat[0]))) {
                        attachments.computeIfAbsent(attachment.get("id_mensaje"), ignored -> new java.util.ArrayList<>()).add(attachment);
                    }
                    for (Map<String, String> row : chatMessages) {
                        boolean mine = "Administracion".equalsIgnoreCase(row.get("autor_tipo"));
                        JPanel line = new JPanel(new FlowLayout(mine ? FlowLayout.RIGHT : FlowLayout.LEFT, 8, 3));
                        line.setOpaque(false);
                        JPanel bubble = new JPanel(new BorderLayout(0, 5));
                        bubble.setBackground(mine ? new Color(128, 58, 24) : new Color(41, 45, 48));
                        bubble.setBorder(BorderFactory.createCompoundBorder(BorderFactory.createLineBorder(mine ? UiTheme.ORANGE : UiTheme.LINE), BorderFactory.createEmptyBorder(9, 12, 7, 12)));
                        JTextArea text = new JTextArea(row.getOrDefault("mensaje", ""));
                        text.setEditable(false);
                        text.setLineWrap(true);
                        text.setWrapStyleWord(true);
                        text.setOpaque(false);
                        text.setForeground(UiTheme.INK);
                        text.setFont(new java.awt.Font("SansSerif", java.awt.Font.PLAIN, 12));
                        text.setColumns(30);
                        bubble.add(text, BorderLayout.CENTER);
                        JLabel meta = new JLabel(row.getOrDefault("autor_nombre", "") + "  ·  " + row.getOrDefault("fecha", ""));
                        meta.setForeground(UiTheme.MUTED);
                        meta.setFont(new java.awt.Font("SansSerif", java.awt.Font.PLAIN, 9));
                        bubble.add(meta, BorderLayout.SOUTH);
                        List<Map<String, String>> messageAttachments = attachments.get(row.get("id_mensaje"));
                        if (messageAttachments != null) {
                            JPanel attachmentList = new JPanel(new GridLayout(0, 1, 0, 4));
                            attachmentList.setOpaque(false);
                            for (Map<String, String> attachment : messageAttachments) {
                                JButton download = new JButton("↓  " + attachment.getOrDefault("nombre_original", "Archivo"));
                                UiTheme.styleButton(download, false);
                                download.setFont(new java.awt.Font("SansSerif", java.awt.Font.PLAIN, 10));
                                download.addActionListener(event -> descargarAdjunto(panel, attachment));
                                attachmentList.add(download);
                            }
                            bubble.add(attachmentList, BorderLayout.NORTH);
                        }
                        line.add(bubble);
                        line.setAlignmentX(java.awt.Component.LEFT_ALIGNMENT);
                        messages.add(line);
                    }
                } catch (IOException | NumberFormatException error) {
                    JLabel issue = new JLabel("No se pudieron cargar los mensajes: " + error.getMessage());
                    issue.setForeground(UiTheme.MUTED);
                    messages.add(issue);
                }
            }
            messages.revalidate();
            messages.repaint();
            SwingUtilities.invokeLater(() -> messageScroll.getVerticalScrollBar().setValue(messageScroll.getVerticalScrollBar().getMaximum()));
        };
        chats.getSelectionModel().addListSelectionListener(event -> {
            if (event.getValueIsAdjusting() || chats.getSelectedRow() < 0) return;
            int row = chats.convertRowIndexToModel(chats.getSelectedRow());
            selectedChat[0] = String.valueOf(model.getValueAt(row, 0));
            selectedClient[0] = String.valueOf(model.getValueAt(row, 1));
            contact.setText(selectedClient[0] + "  ·  " + model.getValueAt(row, 2) + "  ·  " + model.getValueAt(row, 4));
            loadMessages.run();
        });
        createOrder.addActionListener(event -> {
            int selected = chats.getSelectedRow();
            if (selected < 0) { JOptionPane.showMessageDialog(panel, "Seleccioná una conversación.", "Crear pedido", JOptionPane.WARNING_MESSAGE); return; }
            int row = chats.convertRowIndexToModel(selected);
            String customerId = String.valueOf(model.getValueAt(row, 5));
            if (customerId.isEmpty() || "null".equals(customerId)) {
                JOptionPane.showMessageDialog(panel, "No se pudo asociar esta conversación con un cliente registrado.", "Crear pedido", JOptionPane.WARNING_MESSAGE);
                return;
            }
            crearPedido.accept(customerId);
        });
        createInvoice.addActionListener(event -> {
            int selected=chats.getSelectedRow();
            if(selected<0){JOptionPane.showMessageDialog(panel,"Seleccioná una conversación para crear la factura.","Facturación",JOptionPane.WARNING_MESSAGE);return;}
            int row=chats.convertRowIndexToModel(selected); Map<String,String> data=new LinkedHashMap<>();
            data.put("id_chat",String.valueOf(model.getValueAt(row,0)));data.put("id_cliente",String.valueOf(model.getValueAt(row,5)));
            data.put("nombre",String.valueOf(model.getValueAt(row,1)));data.put("asunto",String.valueOf(model.getValueAt(row,6)));
            data.put("id_pedido",model.getValueAt(row,7)==null?"":String.valueOf(model.getValueAt(row,7)));
            if(abrirFormularioFactura(panel,null,new ChatOption(data))) { loadChats.run(); loadMessages.run(); JOptionPane.showMessageDialog(panel,"Factura guardada y enviada al chat de la conversación.","Facturación",JOptionPane.INFORMATION_MESSAGE); }
        });
        refresh.addActionListener(event -> {
            String keep = selectedChat[0];
            loadChats.run();
            if (!keep.isEmpty()) {
                for (int i = 0; i < model.getRowCount(); i++) {
                    if (keep.equals(String.valueOf(model.getValueAt(i, 0)))) {
                        chats.setRowSelectionInterval(i, i);
                        break;
                    }
                }
            }
        });
        send.addActionListener(event -> {
            String value = composer.getText().trim();
            if (selectedChat[0].isEmpty() || (value.isEmpty() && selectedFiles.isEmpty())) return;
            Map<String, String> data = new LinkedHashMap<>();
            data.put("id_chat", selectedChat[0]);
            data.put("mensaje", value);
            try {
                ApiResult result = administracionDao.responderChat(data, selectedFiles);
                if (result.isSuccess()) {
                    composer.setText("");
                    selectedFiles.clear();
                    fileHint.setText(" ");
                    loadMessages.run();
                    loadChats.run();
                    for (int i = 0; i < model.getRowCount(); i++) {
                        if (selectedChat[0].equals(String.valueOf(model.getValueAt(i, 0)))) chats.setRowSelectionInterval(i, i);
                    }
                } else {
                    JOptionPane.showMessageDialog(panel, "No se pudo enviar la respuesta. Revisá la sesión del chat.", "Chat", JOptionPane.ERROR_MESSAGE);
                }
            } catch (IOException error) {
                JOptionPane.showMessageDialog(panel, "No se pudo conectar con el sitio web.", "Chat", JOptionPane.ERROR_MESSAGE);
            }
        });
        composer.addKeyListener(new java.awt.event.KeyAdapter() {
            @Override public void keyPressed(java.awt.event.KeyEvent event) {
                if (event.getKeyCode() == java.awt.event.KeyEvent.VK_ENTER && !event.isShiftDown()) {
                    event.consume();
                    send.doClick();
                }
            }
        });
        attach.addActionListener(event -> {
            JFileChooser chooser = new JFileChooser();
            chooser.setMultiSelectionEnabled(true);
            chooser.setFileFilter(new javax.swing.filechooser.FileNameExtensionFilter(
                    "Archivos permitidos (PDF, imágenes, DXF, DWG)", "pdf", "jpg", "jpeg", "png", "webp", "dxf", "dwg"));
            if (chooser.showOpenDialog(panel) != JFileChooser.APPROVE_OPTION) return;
            selectedFiles.clear();
            for (File file : chooser.getSelectedFiles()) {
                if (file.length() > 10L * 1024 * 1024) {
                    JOptionPane.showMessageDialog(panel, file.getName() + " supera el límite de 10 MB.", "Adjuntos", JOptionPane.WARNING_MESSAGE);
                    continue;
                }
                selectedFiles.add(file);
            }
            if (selectedFiles.size() > 5) selectedFiles.subList(5, selectedFiles.size()).clear();
            fileHint.setText(selectedFiles.isEmpty() ? " " : selectedFiles.size() + " archivo(s) listo(s) para enviar");
        });
        logout.addActionListener(event -> {
            try { administracionDao.cerrarSesionChat(); } catch (IOException ignored) { }
            Container ancestor = panel.getParent();
            while (ancestor != null && !(ancestor.getLayout() instanceof CardLayout)) ancestor = ancestor.getParent();
            if (ancestor instanceof JPanel) {
                JPanel views = (JPanel) ancestor;
                CardLayout cardLayout = (CardLayout) views.getLayout();
                views.removeAll();
                views.add(crearIngresoChat(null, () -> {
                    views.removeAll();
                    views.add(crearBandejaChat(crearPedido), "bandeja");
                    cardLayout.show(views, "bandeja");
                    views.revalidate();
                    views.repaint();
                }), "acceso");
                cardLayout.show(views, "acceso");
                views.revalidate();
                views.repaint();
            }
        });
        loadChats.run();
        loadMessages.run();
        return panel;
    }

    private static void chatAdd(JPanel panel, java.awt.Component component, GridBagConstraints base, int row) {
        GridBagConstraints c = (GridBagConstraints) base.clone();
        c.gridy = row;
        panel.add(component, c);
    }

    private static void descargarAdjunto(JPanel parent, Map<String, String> attachment) {
        JFileChooser chooser = new JFileChooser();
        chooser.setSelectedFile(new File(attachment.getOrDefault("nombre_original", "adjunto")));
        if (chooser.showSaveDialog(parent) != JFileChooser.APPROVE_OPTION) return;
        try {
            File destination = chooser.getSelectedFile();
            Files.write(destination.toPath(), administracionDao.descargarAdjunto(Integer.parseInt(attachment.get("id_adjunto"))));
            if (Desktop.isDesktopSupported()) Desktop.getDesktop().open(destination);
        } catch (Exception error) {
            JOptionPane.showMessageDialog(parent, "No se pudo descargar el archivo: " + error.getMessage(), "Adjuntos", JOptionPane.ERROR_MESSAGE);
        }
    }

    private static boolean chatApiDisponible() {
        try {
            java.net.HttpURLConnection connection = (java.net.HttpURLConnection) java.net.URI.create(
                    administracionDao.getChatApi() + "?action=health").toURL().openConnection();
            connection.setConnectTimeout(1500);
            connection.setReadTimeout(1500);
            try (java.io.InputStream stream = connection.getInputStream()) {
                return connection.getResponseCode() >= 200 && connection.getResponseCode() < 400;
            }
        } catch (Exception ignored) {
            return false;
        }
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

    private static void cargarClientes(JComboBox<ClienteOption> cliente, JTextField email, JTextField telefono) {
        cliente.removeAllItems();
        List<Map<String, String>> clientes = getRows("clientes_list");
        for (Map<String, String> fila : clientes) {
            cliente.addItem(new ClienteOption(fila.getOrDefault("id_cliente", ""), fila.getOrDefault("razon_social", "")));
        }
        Object previousListener = cliente.getClientProperty("metalgest.clienteListener");
        if (previousListener instanceof java.awt.event.ActionListener) cliente.removeActionListener((java.awt.event.ActionListener) previousListener);
        java.awt.event.ActionListener listener = event -> completarContacto(cliente, clientes, email, telefono);
        cliente.putClientProperty("metalgest.clienteListener", listener);
        cliente.addActionListener(listener);
        completarContacto(cliente, clientes, email, telefono);
        if (!pedidoClientePendiente.isEmpty()) {
            for (int i = 0; i < cliente.getItemCount(); i++) if (pedidoClientePendiente.equals(cliente.getItemAt(i).id)) cliente.setSelectedIndex(i);
            pedidoClientePendiente = "";
        }
    }

    private static void completarContacto(JComboBox<ClienteOption> cliente, List<Map<String, String>> clientes,
                                          JTextField email, JTextField telefono) {
        String idCliente = clienteIdSeleccionado(cliente);
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

    private static String clienteIdSeleccionado(JComboBox<ClienteOption> combo) {
        ClienteOption seleccionado = (ClienteOption) combo.getSelectedItem();
        return seleccionado == null ? "" : seleccionado.id;
    }

    private static String clienteNombreSeleccionado(JComboBox<ClienteOption> combo) {
        ClienteOption seleccionado = (ClienteOption) combo.getSelectedItem();
        return seleccionado == null ? "" : seleccionado.nombre;
    }

    private static String idSeleccionado(JComboBox<String> combo, int index) {
        Object value = combo.getItemAt(index);
        return value == null ? "" : value.toString().split(" - ", 2)[0];
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
