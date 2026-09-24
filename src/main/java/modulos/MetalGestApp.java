package modulos;

import modulos.admin.AdminPanel;
import modulos.calidad.QualityPanel;
import modulos.compras.PurchasesPanel;
import modulos.common.EmployeeSession;
import modulos.common.ModuleLogin;
import modulos.common.ModuleLoginPanel;
import modulos.common.UiTheme;
import modulos.deposito.DepositPanel;
import modulos.mantenimiento.MaintenancePanel;
import modulos.produccion.ProductionPanel;

import javax.swing.JFrame;
import javax.swing.SwingUtilities;
import java.awt.BorderLayout;

public class MetalGestApp {
    public static void main(String[] args) {
        SwingUtilities.invokeLater(MetalGestApp::showApplication);
    }

    private static void showApplication() {
        JFrame ventana = new JFrame("MetalGest - Acceso");
        UiTheme.configureWindow(ventana);
        ventana.setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        ventana.setLocationRelativeTo(null);
        ventana.setLayout(new BorderLayout());
        ventana.add(new ModuleLoginPanel(session -> showModule(ventana, session)), BorderLayout.CENTER);
        ventana.setVisible(true);
    }

    private static void showModule(JFrame ventana, EmployeeSession session) {
        String rol = session.getRol();
        if ("Administracion".equalsIgnoreCase(rol) || "Gerencia".equalsIgnoreCase(rol)) {
            ventana.setTitle("MetalGest - Administracion");
            ventana.setContentPane(AdminPanel.createView(session));
        } else if ("Produccion".equalsIgnoreCase(rol)) {
            ventana.setTitle("MetalGest - Produccion");
            ventana.setContentPane(ProductionPanel.createView(session));
        } else if ("Mantenimiento".equalsIgnoreCase(rol)) {
            ventana.setTitle("MetalGest - Mantenimiento");
            ventana.setContentPane(MaintenancePanel.createView(session));
        } else if ("Deposito".equalsIgnoreCase(rol)) {
            ventana.setTitle("MetalGest - Deposito");
            ventana.setContentPane(DepositPanel.createView(session));
        } else if ("Compras".equalsIgnoreCase(rol)) {
            ventana.setTitle("MetalGest - Compras");
            ventana.setContentPane(PurchasesPanel.createView(session));
        } else if ("Calidad".equalsIgnoreCase(rol)) {
            ventana.setTitle("MetalGest - Calidad");
            ventana.setContentPane(QualityPanel.createView(session));
        }
        ventana.revalidate();
        ventana.repaint();
    }
}
