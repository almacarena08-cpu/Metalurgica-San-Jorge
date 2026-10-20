package modulos;

import modulos.admin.AdminPanel;
import modulos.calidad.QualityPanel;
import modulos.compras.PurchasesPanel;
import modulos.common.EmployeeSession;
import modulos.common.ApiClient;
import modulos.common.ModuleLogin;
import modulos.common.ModuleLoginPanel;
import modulos.common.UiTheme;
import modulos.deposito.DepositPanel;
import modulos.mantenimiento.MaintenancePanel;
import modulos.produccion.ProductionPanel;

import javax.swing.JFrame;
import javax.swing.SwingUtilities;
import java.awt.BorderLayout;
import java.io.IOException;
import java.util.Collections;

public class MetalGestApp {
    public static void main(String[] args) {
        UiTheme.installDarkDefaults();
        SwingUtilities.invokeLater(MetalGestApp::showApplication);
    }

    private static void showApplication() {
        JFrame ventana = new JFrame("MetalGest - Acceso");
        UiTheme.configureWindow(ventana);
        ventana.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        ventana.setLocationRelativeTo(null);
        ventana.setLayout(new BorderLayout());
        showLogin(ventana);
        ventana.setVisible(true);
    }

    private static void showLogin(JFrame ventana) {
        ventana.setTitle("MetalGest - Acceso");
        ventana.setContentPane(new ModuleLoginPanel(session -> showModule(ventana, session)));
        ventana.revalidate();
        ventana.repaint();
    }

    private static void cerrarSesion(JFrame ventana) {
        try { ApiClient.post("logout", Collections.emptyMap()); } catch (IOException ignored) { }
        showLogin(ventana);
    }

    private static void showModule(JFrame ventana, EmployeeSession session) {
        String rol = session.getRol();
        if (rol != null && rol.toLowerCase(java.util.Locale.ROOT).startsWith("supervisor ")) {
            rol = rol.substring("supervisor ".length()).trim();
        }
        if ("Administracion".equalsIgnoreCase(rol) || "Gerencia".equalsIgnoreCase(rol) || "Administrador Global".equalsIgnoreCase(rol)) {
            ventana.setTitle("MetalGest - Administracion");
            ventana.setContentPane(AdminPanel.createView(session, () -> cerrarSesion(ventana)));
        } else if ("Produccion".equalsIgnoreCase(rol)) {
            ventana.setTitle("MetalGest - Produccion");
            ventana.setContentPane(ProductionPanel.createView(session, () -> cerrarSesion(ventana)));
        } else if ("Mantenimiento".equalsIgnoreCase(rol)) {
            ventana.setTitle("MetalGest - Mantenimiento");
            ventana.setContentPane(MaintenancePanel.createView(session, () -> cerrarSesion(ventana)));
        } else if ("Deposito".equalsIgnoreCase(rol)) {
            ventana.setTitle("MetalGest - Deposito");
            ventana.setContentPane(DepositPanel.createView(session, () -> cerrarSesion(ventana)));
        } else if ("Compras".equalsIgnoreCase(rol)) {
            ventana.setTitle("MetalGest - Compras");
            ventana.setContentPane(PurchasesPanel.createView(session, () -> cerrarSesion(ventana)));
        } else if ("Calidad".equalsIgnoreCase(rol)) {
            ventana.setTitle("MetalGest - Calidad");
            ventana.setContentPane(QualityPanel.createView(session, () -> cerrarSesion(ventana)));
        }
        ventana.revalidate();
        ventana.repaint();
    }
}
