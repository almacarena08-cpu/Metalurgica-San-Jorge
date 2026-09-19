package modulos.common;

import javax.swing.JButton;
import javax.swing.JCheckBox;
import javax.swing.JComboBox;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import java.awt.Component;
import java.awt.GridLayout;
import java.awt.Window;
import java.io.IOException;

public final class SettingsDialog {
    private static final PreferencesDao DAO = new PreferencesDao();

    private SettingsDialog() {
    }

    public static void open(int userId) {
        UserPreferences current;
        try {
            current = DAO.load(userId);
        } catch (IOException error) {
            JOptionPane.showMessageDialog(null, "No se pudieron cargar las preferencias.", "Configuracion", JOptionPane.ERROR_MESSAGE);
            return;
        }

        JCheckBox darkMode = new JCheckBox("🌙 Modo oscuro", current.isDarkMode());
        JComboBox<Integer> textSize = new JComboBox<>(new Integer[]{10, 12, 14, 16, 18, 20});
        textSize.setSelectedItem(current.getTextSize());
        JPanel form = new JPanel(new GridLayout(0, 1, 8, 8));
        form.add(new JLabel("Preferencias personales"));
        form.add(darkMode);
        form.add(new JLabel("Tamaño del texto"));
        form.add(textSize);
        int result = JOptionPane.showConfirmDialog(null, form, "⚙ Configuracion", JOptionPane.OK_CANCEL_OPTION);
        if (result != JOptionPane.OK_OPTION) {
            return;
        }

        int size = (Integer) textSize.getSelectedItem();
        try {
            if (DAO.save(userId, darkMode.isSelected(), size)) {
                UserPreferences updated = new UserPreferences(darkMode.isSelected(), size);
                for (Window window : Window.getWindows()) {
                    if (window.isDisplayable()) {
                        UiTheme.applyPreferences(window, updated);
                    }
                }
                JOptionPane.showMessageDialog(null, "Preferencias guardadas solo para este empleado.", "Configuracion", JOptionPane.INFORMATION_MESSAGE);
            }
        } catch (IOException error) {
            JOptionPane.showMessageDialog(null, "No se pudieron guardar las preferencias.", "Configuracion", JOptionPane.ERROR_MESSAGE);
        }
    }
}
