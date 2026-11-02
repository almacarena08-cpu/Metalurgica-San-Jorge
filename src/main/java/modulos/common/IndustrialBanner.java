package modulos.common;

import javax.swing.ImageIcon;
import javax.swing.JPanel;
import java.awt.Dimension;
import java.awt.Graphics;
import java.awt.Graphics2D;
import java.awt.RenderingHints;
import java.net.URL;

public class IndustrialBanner extends JPanel {
    private static final ImageIcon LOGO = loadLogo();

    public IndustrialBanner() {
        setPreferredSize(new Dimension(320, 92));
        setMinimumSize(new Dimension(220, 72));
        setOpaque(false);
    }

    private static ImageIcon loadLogo() {
        URL logo = IndustrialBanner.class.getResource("/modulos/icono.jpg");
        return logo == null ? null : new ImageIcon(logo);
    }

    @Override
    protected void paintComponent(Graphics graphics) {
        super.paintComponent(graphics);
        Graphics2D g = (Graphics2D) graphics.create();
        g.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
        int width = getWidth();
        int height = getHeight();
        g.setColor(UiTheme.BLACK);
        g.fillRoundRect(0, 0, width, height, 12, 12);
        if (LOGO != null) {
            int logoSize = Math.min(64, height - 16);
            g.drawImage(LOGO.getImage(), 16, (height - logoSize) / 2, logoSize, logoSize, this);
        }
        g.setColor(UiTheme.STEEL);
        g.fillRect(0, height - 24, width, 24);
        g.setColor(UiTheme.ORANGE);
        g.fillRect(0, height - 24, 6, 24);
        g.setColor(UiTheme.WHITE);
        g.setFont(getFont().deriveFont(18f));
        g.drawString("METALGEST", 94, height - 10);
        g.dispose();
    }
}
