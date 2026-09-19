package modulos.common;

public class UserPreferences {
    private final boolean darkMode;
    private final int textSize;

    public UserPreferences(boolean darkMode, int textSize) {
        this.darkMode = darkMode;
        this.textSize = textSize;
    }

    public boolean isDarkMode() {
        return darkMode;
    }

    public int getTextSize() {
        return textSize;
    }
}
