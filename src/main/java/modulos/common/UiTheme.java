package modulos.common;

import javax.swing.BorderFactory;
import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JComponent;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTabbedPane;
import javax.swing.JTable;
import javax.swing.JTextArea;
import javax.swing.JTextField;
import javax.swing.AbstractButton;
import javax.swing.ListSelectionModel;
import javax.swing.SwingConstants;
import javax.swing.table.JTableHeader;
import javax.swing.plaf.basic.BasicTabbedPaneUI;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Dimension;
import java.awt.Font;
import java.awt.GridLayout;
import java.awt.GraphicsEnvironment;
import java.awt.Rectangle;
import java.util.function.Consumer;

public final class UiTheme {
    public static final Color BLACK = new Color(12, 14, 16);
    public static final Color NAVY = new Color(17, 20, 23);
    public static final Color NAVY_LIGHT = new Color(37, 42, 47);
    public static final Color STEEL = new Color(117, 124, 130);
    public static final Color ORANGE = new Color(255, 110, 32);
    public static final Color YELLOW = new Color(244, 160, 36);
    public static final Color BLUE = ORANGE;
    public static final Color BLUE_SOFT = new Color(66, 41, 24);
    public static final Color INK = new Color(241, 243, 245);
    public static final Color MUTED = new Color(174, 181, 187);
    public static final Color LINE = new Color(58, 63, 68);
    public static final Color BACKGROUND = new Color(20, 23, 26);
    public static final Color WHITE = new Color(30, 34, 38);

    private UiTheme() {
    }

    public static void configureWindow(javax.swing.JFrame window) {
        window.setMinimumSize(new Dimension(400, 600));
        if (!GraphicsEnvironment.isHeadless()) {
            Rectangle screen = GraphicsEnvironment.getLocalGraphicsEnvironment().getMaximumWindowBounds();
            int width = Math.min(1220, Math.max(400, (int) (screen.width * 0.86)));
            int height = Math.min(760, Math.max(600, (int) (screen.height * 0.86)));
            window.setSize(width, height);
        } else {
            window.setSize(900, 650);
        }
        window.setResizable(true);
    }

    public static JPanel shell(String module, String section) {
        return shell(module, section, entry -> {
        });
    }

    public static JPanel shell(String module, String section, Consumer<String> onNavigate) {
        return shell(module, section, "", onNavigate);
    }

    public static JPanel shell(String module, String section, String employeeName, Consumer<String> onNavigate) {
        JPanel shell = new JPanel(new BorderLayout());
        shell.setBackground(BACKGROUND);
        shell.add(sidebar(module, onNavigate), BorderLayout.WEST);
        JPanel content = new JPanel(new BorderLayout(0, 18));
        content.setBorder(BorderFactory.createEmptyBorder(0, 24, 24, 24));
        content.setBackground(BACKGROUND);
        content.add(topbar(section, employeeName), BorderLayout.NORTH);
        shell.add(content, BorderLayout.CENTER);
        return shell;
    }

    public static JPanel content(JPanel shell) {
        return (JPanel) shell.getComponent(1);
    }

    public static JPanel sidebar(String selected) {
        return sidebar(selected, entry -> {
        });
    }

    public static JPanel sidebar(String selected, Consumer<String> onNavigate) {
        JPanel sidebar = new JPanel(new BorderLayout(0, 20));
        sidebar.setPreferredSize(new Dimension(190, 0));
        sidebar.setBackground(NAVY);
        sidebar.setBorder(BorderFactory.createEmptyBorder(20, 14, 18, 14));

        JLabel brand = new JLabel("METALURGICA\nSAN JORGE");
        brand.setText("<html><b>METALURGICA</b><br>SAN JORGE</html>");
        brand.setForeground(Color.WHITE);
        brand.setFont(new Font("SansSerif", Font.BOLD, 12));
        brand.setBorder(BorderFactory.createEmptyBorder(4, 8, 16, 0));
        sidebar.add(brand, BorderLayout.NORTH);

        JPanel menu = new JPanel(new GridLayout(0, 1, 0, 6));
        menu.setOpaque(false);
        String[] entries = menuEntries(selected);
        for (String entry : entries) {
            JButton item = new JButton(entry);
            item.setHorizontalAlignment(SwingConstants.LEFT);
            item.setFocusPainted(false);
            item.setBorderPainted(false);
            item.setOpaque(true);
            boolean active = entry.equals(selected) || ("Inicio".equals(entry) && "Inicio".equals(selected));
            item.setBackground(active ? BLUE : NAVY);
            item.setForeground(Color.WHITE);
            item.setFont(new Font("SansSerif", active ? Font.BOLD : Font.PLAIN, 11));
            item.setBorder(BorderFactory.createEmptyBorder(10, 9, 10, 6));
            String action = entry;
            if ("Ordenes".equals(action)) {
                action = "Ordenes de trabajo";
            }
            String navigationAction = action;
            item.addActionListener(event -> onNavigate.accept(navigationAction));
            menu.add(item);
        }
        sidebar.add(menu, BorderLayout.CENTER);
        JButton footer = new JButton("⚙  Configuracion");
        footer.setHorizontalAlignment(SwingConstants.LEFT);
        footer.setFocusPainted(false);
        footer.setBorderPainted(false);
        footer.setOpaque(false);
        footer.setForeground(new Color(213, 224, 230));
        footer.setFont(new Font("SansSerif", Font.PLAIN, 11));
        footer.setBorder(BorderFactory.createEmptyBorder(8, 8, 0, 0));
        footer.addActionListener(event -> onNavigate.accept("Configuracion"));
        sidebar.add(footer, BorderLayout.SOUTH);
        return sidebar;
    }

    private static String[] menuEntries(String module) {
        if ("Administracion".equals(module)) {
            return new String[]{"Inicio", "Administracion", "Pedidos", "Ordenes"};
        }
        if ("Produccion".equals(module)) {
            return new String[]{"Inicio", "Produccion", "Ordenes recibidas", "Historial de avance", "Aviso a mantenimiento"};
        }
        if ("Mantenimiento".equals(module)) {
            return new String[]{"Inicio", "Mantenimiento", "Solicitudes", "Preventivos"};
        }
        if ("Deposito".equals(module)) {
            return new String[]{"Inicio", "Deposito", "Inventario", "Movimientos"};
        }
        if ("Compras".equals(module)) {
            return new String[]{"Inicio", "Compras", "Ordenes de compra", "Proveedores", "Materiales", "Alertas de stock"};
        }
        return new String[]{"Inicio", "Calidad", "Registrar control", "Historial"};
    }

    public static JPanel topbar(String section) {
        return topbar(section, "");
    }

    public static JPanel topbar(String section, String employeeName) {
        JPanel topbar = new JPanel(new BorderLayout());
        topbar.setPreferredSize(new Dimension(0, 58));
        topbar.setBackground(WHITE);
        topbar.setBorder(BorderFactory.createMatteBorder(0, 0, 1, 0, LINE));
        JLabel title = new JLabel(section);
        title.setForeground(INK);
        title.setFont(new Font("SansSerif", Font.BOLD, 14));
        topbar.add(title, BorderLayout.WEST);
        JTextField search = new JTextField("Buscar en este módulo...");
        search.setForeground(MUTED);
        search.setBackground(NAVY_LIGHT);
        search.setBorder(BorderFactory.createCompoundBorder(BorderFactory.createLineBorder(LINE), BorderFactory.createEmptyBorder(7, 10, 7, 10)));
        search.setPreferredSize(new Dimension(245, 30));
        topbar.add(search, BorderLayout.CENTER);
        String greeting = employeeName == null || employeeName.isEmpty()
            ? "MetalGest   |   Usuario interno"
            : "Hola, " + employeeName + "!";
        JLabel user = new JLabel("🔔   " + greeting);
        user.setForeground(MUTED);
        user.setFont(new Font("SansSerif", Font.PLAIN, 10));
        topbar.add(user, BorderLayout.EAST);
        return topbar;
    }

    public static void styleTable(JTable table) {
        table.setRowHeight(28);
        table.setSelectionMode(ListSelectionModel.SINGLE_SELECTION);
        table.setSelectionBackground(BLUE_SOFT);
        table.setSelectionForeground(INK);
        table.setBackground(WHITE);
        table.setForeground(INK);
        table.setGridColor(LINE);
        table.setShowVerticalLines(false);
        table.setFont(new Font("SansSerif", Font.PLAIN, 10));
        JTableHeader header = table.getTableHeader();
        header.setBackground(NAVY_LIGHT);
        header.setForeground(INK);
        header.setFont(new Font("SansSerif", Font.BOLD, 10));
        header.setPreferredSize(new Dimension(0, 30));
    }

    public static JScrollPane tableScroll(JTable table) {
        JScrollPane scroll = new JScrollPane(table);
        scroll.setBorder(BorderFactory.createLineBorder(LINE));
        scroll.getViewport().setBackground(WHITE);
        return scroll;
    }

    public static void styleButton(JButton button, boolean primary) {
        button.setFocusPainted(false);
        button.setBorderPainted(false);
        button.setOpaque(true);
        button.setBackground(primary ? ORANGE : NAVY_LIGHT);
        button.setForeground(primary ? WHITE : INK);
        button.setFont(new Font("SansSerif", Font.BOLD, 12));
        button.setBorder(BorderFactory.createEmptyBorder(10, 16, 10, 16));
    }

    public static void styleInput(JComponent input) {
        input.setFont(new Font("SansSerif", Font.PLAIN, 12));
        input.setBackground(NAVY_LIGHT);
        input.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(LINE),
                BorderFactory.createEmptyBorder(7, 8, 7, 8)));
        if (input instanceof JTextArea) {
            ((JTextArea) input).setLineWrap(true);
            ((JTextArea) input).setWrapStyleWord(true);
        }
        input.setForeground(INK);
    }

    public static void applyPreferences(java.awt.Component component, UserPreferences preferences) {
        applyPreferences(component, preferences.isDarkMode(), preferences.getTextSize(), false);
    }

    private static void applyPreferences(java.awt.Component component, boolean dark, int textSize, boolean nested) {
        Color background = dark ? BLACK : BACKGROUND;
        Color surface = dark ? new Color(27, 31, 35) : WHITE;
        Color input = dark ? new Color(39, 44, 49) : NAVY_LIGHT;
        Color foreground = INK;
        Color secondary = MUTED;
        Color border = LINE;

        if (component instanceof JPanel) {
            component.setBackground(nested ? surface : background);
        } else if (component instanceof JScrollPane) {
            component.setBackground(surface);
            ((JScrollPane) component).getViewport().setBackground(surface);
            component.setForeground(foreground);
        } else if (component instanceof JTable) {
            JTable table = (JTable) component;
            table.setBackground(surface);
            table.setForeground(foreground);
            table.setGridColor(border);
            table.setSelectionBackground(BLUE_SOFT);
            table.setSelectionForeground(foreground);
            JTableHeader header = table.getTableHeader();
            header.setBackground(NAVY_LIGHT);
            header.setForeground(foreground);
        } else if (component instanceof JTextField || component instanceof JTextArea || component instanceof JComboBox) {
            component.setBackground(input);
            component.setForeground(foreground);
            if (component instanceof JComponent) {
                ((JComponent) component).setBorder(BorderFactory.createCompoundBorder(
                        BorderFactory.createLineBorder(border), BorderFactory.createEmptyBorder(7, 8, 7, 8)));
            }
        } else if (component instanceof JButton) {
            JButton button = (JButton) component;
            boolean primary = ORANGE.equals(button.getBackground());
            button.setBackground(primary ? ORANGE : NAVY_LIGHT);
            button.setForeground(primary ? WHITE : foreground);
        } else if (component instanceof JLabel || component instanceof AbstractButton) {
            component.setForeground(component.getForeground().equals(MUTED) ? secondary : foreground);
        }
        Font current = component.getFont();
        if (current != null && !nested) {
            component.setFont(current.deriveFont((float) textSize));
        }
        if (component instanceof java.awt.Container) {
            for (java.awt.Component child : ((java.awt.Container) component).getComponents()) {
                applyPreferences(child, dark, textSize, true);
            }
        }
    }

    public static JPanel card(String title, String value, Color accent) {
        JPanel card = new JPanel(new BorderLayout(4, 8));
        card.setBackground(WHITE);
        card.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 4, 0, 0, accent),
                BorderFactory.createEmptyBorder(14, 14, 14, 14)));
        JLabel heading = new JLabel(title);
        heading.setForeground(MUTED);
        heading.setFont(new Font("SansSerif", Font.PLAIN, 12));
        JLabel number = new JLabel(value);
        number.setForeground(INK);
        number.setFont(new Font("SansSerif", Font.BOLD, 24));
        card.add(heading, BorderLayout.NORTH);
        card.add(number, BorderLayout.CENTER);
        return card;
    }

    public static JPanel cards(String[][] values) {
        JPanel cards = new JPanel(new GridLayout(1, values.length, 12, 0));
        cards.setOpaque(false);
        Color[] accents = {ORANGE, YELLOW, new Color(198, 82, 25), new Color(255, 184, 77)};
        for (int i = 0; i < values.length; i++) {
            cards.add(card(values[i][0], values[i][1], accents[i % accents.length]));
        }
        return cards;
    }

    public static JPanel dashboard(String module, String description, String primaryAction, Consumer<String> onNavigate) {
        return dashboard(module, description, primaryAction,
                new String[][]{{"Actividad de hoy", "En curso"}, {"Tareas pendientes", "Revisar"}, {"Estado del sector", "Operativo"}},
                new String[]{"Actualiza la información del sector", "Revisa las tareas que requieren atención", "Registra los movimientos y avances del día"},
                onNavigate);
    }

    public static JPanel dashboard(String module, String description, String primaryAction, String[][] indicators,
                                   String[] activityItems, Consumer<String> onNavigate) {
        JPanel dashboard = new JPanel(new BorderLayout(18, 18));
        dashboard.setBackground(BACKGROUND);
        dashboard.setBorder(BorderFactory.createEmptyBorder(16, 6, 6, 6));

        JPanel welcome = new JPanel(new BorderLayout(10, 8));
        welcome.setOpaque(false);
        JLabel title = new JLabel(module);
        title.setForeground(INK);
        title.setFont(new Font("SansSerif", Font.BOLD, 23));
        JLabel subtitle = new JLabel(description);
        subtitle.setForeground(MUTED);
        subtitle.setFont(new Font("SansSerif", Font.PLAIN, 13));
        JPanel copy = new JPanel(new GridLayout(2, 1, 0, 3));
        copy.setOpaque(false);
        copy.add(title);
        copy.add(subtitle);
        JButton action = new JButton(primaryAction);
        styleButton(action, true);
        action.addActionListener(event -> onNavigate.accept(primaryAction));
        welcome.add(copy, BorderLayout.WEST);
        welcome.add(action, BorderLayout.EAST);
        dashboard.add(welcome, BorderLayout.NORTH);

        JPanel body = new JPanel(new BorderLayout(16, 16));
        body.setOpaque(false);
        body.add(cards(indicators), BorderLayout.NORTH);
        JPanel activity = panel("Actividad del sector");
        JPanel rows = new JPanel(new GridLayout(0, 1, 0, 8));
        rows.setOpaque(false);
        for (String item : activityItems) {
            JLabel row = new JLabel(item);
            row.setForeground(INK);
            row.setBorder(BorderFactory.createCompoundBorder(BorderFactory.createMatteBorder(0, 3, 0, 0, BLUE), BorderFactory.createEmptyBorder(7, 10, 7, 0)));
            rows.add(row);
        }
        activity.add(rows, BorderLayout.CENTER);
        body.add(activity, BorderLayout.CENTER);
        dashboard.add(body, BorderLayout.CENTER);
        return dashboard;
    }

    public static void styleTabs(JTabbedPane tabs) {
        tabs.setFont(new Font("SansSerif", Font.BOLD, 12));
        tabs.setBackground(BACKGROUND);
        tabs.setForeground(INK);
        tabs.setUI(new BasicTabbedPaneUI() {
            @Override
            protected void paintTabBackground(java.awt.Graphics graphics, int placement, int index,
                                              int x, int y, int width, int height, boolean selected) {
                graphics.setColor(selected ? ORANGE : NAVY_LIGHT);
                graphics.fillRect(x, y, width, height);
            }

            @Override
            protected void paintText(java.awt.Graphics graphics, int placement, Font font,
                                     java.awt.FontMetrics metrics, int index, String title,
                                     java.awt.Rectangle textRect, boolean selected) {
                graphics.setFont(font);
                graphics.setColor(selected ? WHITE : INK);
                graphics.drawString(title, textRect.x, textRect.y + metrics.getAscent());
            }
        });
    }

    private static JPanel panel(String title) {
        JPanel panel = new JPanel(new BorderLayout(10, 12));
        panel.setBackground(WHITE);
        panel.setBorder(BorderFactory.createCompoundBorder(BorderFactory.createLineBorder(LINE), BorderFactory.createEmptyBorder(15, 16, 15, 16)));
        JLabel label = new JLabel(title);
        label.setForeground(INK);
        label.setFont(new Font("SansSerif", Font.BOLD, 14));
        panel.add(label, BorderLayout.NORTH);
        return panel;
    }
}
