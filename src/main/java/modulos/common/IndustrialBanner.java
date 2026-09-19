package modulos.common;

import javax.swing.JPanel;
import java.awt.Color;
import java.awt.Dimension;
import java.awt.Graphics;
import java.awt.Graphics2D;
import java.awt.RenderingHints;

public class IndustrialBanner extends JPanel {
    public IndustrialBanner() {
        setPreferredSize(new Dimension(320, 92));
        setMinimumSize(new Dimension(220, 72));
        setOpaque(false);
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
        g.setColor(UiTheme.STEEL);
        g.fillRect(0, height - 24, width, 24);
        g.setColor(UiTheme.ORANGE);
        g.fillRect(18, 18, Math.max(80, width / 3), 10);
        g.fillRect(18, 36, Math.max(50, width / 5), 6);
        g.setColor(UiTheme.YELLOW);
        for (int x = width - 130; x < width + 30; x += 26) {
            g.fillPolygon(new int[]{x, x + 13, x + 26}, new int[]{height - 24, height - 24, height}, 3);
        }
        g.setColor(UiTheme.WHITE);
        g.setFont(getFont().deriveFont(14f));
        g.drawString("⚙  METALGEST", 18, height - 7);
        g.dispose();
    }
}
