package modulos.login;

import javax.swing.*;
import modulos.admin.AdminPanel;
import modulos.cliente.PruebaGUI;

public class LoginApp {
    public static void main(String[] args) {
        SwingUtilities.invokeLater(LoginApp::showLogin);
    }

    private static void showLogin() {
        JTextField userField = new JTextField(12);
        JPasswordField passField = new JPasswordField(12);

        JPanel panel = new JPanel();
        panel.add(new JLabel("Usuario:"));
        panel.add(userField);
        panel.add(new JLabel("Contraseña:"));
        panel.add(passField);

        int result = JOptionPane.showConfirmDialog(null, panel, "Login", JOptionPane.OK_CANCEL_OPTION, JOptionPane.PLAIN_MESSAGE);
        if (result != JOptionPane.OK_OPTION) {
            return;
        }

        String user = userField.getText().trim();
        String pass = new String(passField.getPassword());

        if (user.isEmpty() || pass.isEmpty()) {
            JOptionPane.showMessageDialog(null, "Completa usuario y contraseña.", "Error", JOptionPane.ERROR_MESSAGE);
            showLogin();
            return;
        }

        if (pass.equals("admin")) {
            AdminPanel.openAdmin();
        } else if (pass.equals("cliente")) {
            PruebaGUI.openCliente();
        } else {
            JOptionPane.showMessageDialog(null, "Credenciales incorrectas.", "Error", JOptionPane.ERROR_MESSAGE);
            showLogin();
        }
    }
}
