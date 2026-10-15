package modulos.common;

public class EmployeeSession {
    private final int id;
    private final String nombre;
    private final String apellido;
    private final String rol;
    private final String usuario;

    public EmployeeSession(int id, String nombre, String apellido, String rol) {
        this(id, nombre, apellido, rol, "");
    }

    public EmployeeSession(int id, String nombre, String apellido, String rol, String usuario) {
        this.id = id;
        this.nombre = nombre;
        this.apellido = apellido;
        this.rol = rol;
        this.usuario = usuario == null ? "" : usuario;
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

    public String getUsuario() {
        return usuario;
    }
}
