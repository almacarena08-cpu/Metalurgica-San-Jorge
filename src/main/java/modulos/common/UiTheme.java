package modulos.common;

import javax.swing.BorderFactory;
import javax.swing.JButton;
import javax.swing.JComboBox;
import javax.swing.JComponent;
import javax.swing.JLabel;
import javax.swing.JPanel;
import javax.swing.JScrollPane;
import javax.swing.JTable;
import javax.swing.JTextArea;
import javax.swing.JTextField;
import javax.swing.AbstractButton;
import javax.swing.ListSelectionModel;
import javax.swing.SwingConstants;
import javax.swing.table.JTableHeader;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Dimension;
import java.awt.Font;
import java.awt.GridLayout;
import java.awt.GraphicsEnvironment;
import java.awt.Rectangle;
import java.util.function.Consumer;

public final class UiTheme {
    public static final Color BLACK = new Color(11, 12, 13);
    public static final Color NAVY = BLACK;
    public static final Color NAVY_LIGHT = new Color(31, 33, 35);
    public static final Color STEEL = new Color(75, 79, 82);
    public static final Color ORANGE = new Color(255, 93, 22);
    public static final Color YELLOW = new Color(255, 184, 0);
    public static final Color BLUE = ORANGE;
    public static final Color BLUE_SOFT = new Color(66, 40, 24);
    public static final Color INK = new Color(239, 240, 241);
    public static final Color MUTED = new Color(155, 158, 161);
    public static final Color LINE = new Color(55, 58, 60);
    public static final Color BACKGROUND = new Color(18, 19, 20);
    public static final Color WHITE = new Color(29, 31, 33);

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
        sidebar.setPreferredSize(new Dimension(158, 0));
        sidebar.setBackground(NAVY);
        sidebar.setBorder(BorderFactory.createEmptyBorder(20, 14, 18, 14));

        JLabel brand = new JLabel("METALURGICA\nSAN JORGE");
        brand.setText("<html><b>METALURGICA</b><br>SAN JORGE</html>");
        brand.setForeground(WHITE);
        brand.setFont(new Font("SansSerif", Font.BOLD, 10));
        brand.setBorder(BorderFactory.createEmptyBorder(0, 8, 12, 0));
        sidebar.add(brand, BorderLayout.NORTH);

        JPanel menu = new JPanel(new GridLayout(0, 1, 0, 6));
        menu.setOpaque(false);
        String[] entries = selected.equals("Administracion")
            ? new String[]{"⚙  Administracion", "⌂  Inicio", "♙  Clientes", "📦  Pedidos", "⚙  Ordenes", "▤  Reportes"}
            : new String[]{"⌂  Inicio", "📦  Pedidos", "⚙  Ordenes", "🏭  Produccion", "🔧  Mantenimiento", "▣  Deposito", "🛒  Compras", "✓  Calidad", "▤  Reportes"};
        for (String entry : entries) {
            JButton item = new JButton(entry);
            item.setHorizontalAlignment(SwingConstants.LEFT);
            item.setFocusPainted(false);
            item.setBorderPainted(false);
            item.setOpaque(true);
            item.setBackground(entry.equals(selected) ? BLUE : NAVY);
            item.setForeground(entry.equals(selected) ? WHITE : new Color(204, 218, 230));
            item.setFont(new Font("SansSerif", entry.equals(selected) ? Font.BOLD : Font.PLAIN, 9));
            item.setBorder(BorderFactory.createEmptyBorder(8, 5, 8, 3));
            String action = entry.substring(entry.indexOf(' ') + 2);
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
        footer.setForeground(new Color(180, 198, 213));
        footer.setFont(new Font("SansSerif", Font.PLAIN, 9));
        footer.setBorder(BorderFactory.createEmptyBorder(8, 8, 0, 0));
        footer.addActionListener(event -> onNavigate.accept("Configuracion"));
        sidebar.add(footer, BorderLayout.SOUTH);
        return sidebar;
    }

    public static JPanel topbar(String section) {
        return topbar(section, "");
    }

    public static JPanel topbar(String section, String employeeName) {
        JPanel topbar = new JPanel(new BorderLayout());
        topbar.setPreferredSize(new Dimension(0, 58));
        topbar.setBackground(BLACK);
        topbar.setBorder(BorderFactory.createMatteBorder(0, 0, 1, 0, LINE));
        JLabel title = new JLabel(section);
        title.setForeground(INK);
        title.setFont(new Font("SansSerif", Font.BOLD, 14));
        topbar.add(title, BorderLayout.WEST);
        JTextField search = new JTextField("⌕  Buscar pedido, cliente o numero...");
        search.setForeground(MUTED);
        search.setBackground(NAVY_LIGHT);
        search.setBorder(BorderFactory.createEmptyBorder(7, 10, 7, 10));
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
        table.setGridColor(LINE);
        table.setShowVerticalLines(false);
        table.setFont(new Font("SansSerif", Font.PLAIN, 10));
        JTableHeader header = table.getTableHeader();
        header.setBackground(NAVY_LIGHT);
        header.setForeground(WHITE);
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
        button.setBackground(primary ? BLUE : WHITE);
        button.setForeground(primary ? WHITE : INK);
        button.setFont(new Font("SansSerif", Font.BOLD, 12));
        button.setBorder(BorderFactory.createEmptyBorder(10, 16, 10, 16));
    }

    public static void styleInput(JComponent input) {
        input.setFont(new Font("SansSerif", Font.PLAIN, 12));
        input.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(LINE),
                BorderFactory.createEmptyBorder(7, 8, 7, 8)));
        if (input instanceof JTextArea) {
            ((JTextArea) input).setLineWrap(true);
            ((JTextArea) input).setWrapStyleWord(true);
        }
        if (input instanceof JComboBox) {
            ((JComboBox<?>) input).setBackground(WHITE);
        }
    }

    public static void applyPreferences(java.awt.Component component, UserPreferences preferences) {
        applyPreferences(component, preferences.isDarkMode(), preferences.getTextSize());
    }

    private static void applyPreferences(java.awt.Component component, boolean dark, int textSize) {
        Color background = dark ? new Color(31, 31, 31) : BACKGROUND;
        Color foreground = dark ? new Color(242, 242, 242) : INK;
        if (component instanceof JPanel || component instanceof JScrollPane) {
            component.setBackground(background);
        }
        if (component instanceof JLabel || component instanceof JTable || component instanceof JTextField
                || component instanceof JTextArea || component instanceof JComboBox) {
            component.setForeground(foreground);
            if (component instanceof JTextField || component instanceof JTextArea || component instanceof JComboBox) {
                component.setBackground(dark ? new Color(55, 55, 55) : WHITE);
            }
        }
        if (component instanceof AbstractButton && !(component instanceof JButton)) {
            component.setForeground(foreground);
        }
        Font current = component.getFont();
        if (current != null) {
            component.setFont(current.deriveFont((float) textSize));
        }
        if (component instanceof java.awt.Container) {
            for (java.awt.Component child : ((java.awt.Container) component).getComponents()) {
                applyPreferences(child, dark, textSize);
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
        Color[] accents = {BLUE, new Color(236, 93, 102), new Color(38, 166, 113), new Color(243, 166, 54)};
        for (int i = 0; i < values.length; i++) {
            cards.add(card(values[i][0], values[i][1], accents[i % accents.length]));
        }
        return cards;
    }
}
