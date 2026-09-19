package modulos.common;

public class EmployeeSession {
    private final int id;
    private final String nombre;
    private final String apellido;
    private final String rol;

    public EmployeeSession(int id, String nombre, String apellido, String rol) {
        this.id = id;
        this.nombre = nombre;
        this.apellido = apellido;
        this.rol = rol;
    }

    public int getId() {
        return id;
    }

    public String getNombreCompleto() {
        return nombre + " " + apellido;
    }

    public String getRol() {
        return rol;
    }
}
