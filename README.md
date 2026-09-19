# MetalGest - Rama app

Aplicacion de escritorio para la gestion interna de Metalurgica San Jorge.
Esta rama contiene exclusivamente los modulos de Administracion y Produccion.
La pagina publica y el contacto con clientes pertenecen a la rama `sistema-web`.

## Modulos

- **Administracion**: crea pedidos, genera ordenes de trabajo y actualiza sus estados.
- **Produccion**: consulta ordenes, registra avances y reporta fallas de maquinas a Mantenimiento.
- **Mantenimiento**: consulta fallas, registra reparaciones y repuestos, y cierra mantenimientos preventivos o correctivos.

## Estructura

- `src/main/java/modulos/admin`: interfaz Java de Administracion.
- `src/main/java/modulos/admin/dao`: DAO Java para pedidos y ordenes.
- `src/main/java/modulos/produccion`: interfaz Java de Produccion.
- `src/main/java/modulos/produccion/dao`: DAO Java para produccion y maquinas.
- `src/main/java/modulos/mantenimiento`: interfaz Java de Mantenimiento.
- `src/main/java/modulos/mantenimiento/dao`: DAO Java para el historial de mantenimientos.
- `src/main/java/modulos/common`: cliente HTTP y utilidades JSON.
- `src/main/java/modulos/common/dao`: infraestructura DAO compartida.
- `clases/daos`: DAOs PHP para la persistencia en MySQL.
- `clases/php/database.php`: conexion a MySQL.
- `clases/php/api.php`: API HTTP utilizada por los modulos Java.
- `sql/metalgest.sql`: estructura y datos iniciales de la base.

## Requisitos

- XAMPP con MariaDB/MySQL iniciado en el puerto `3306`.
- PHP 8 o superior.
- JDK con `javac` y `java`.
- Base de datos `metalurgica_san_jorge` creada desde `sql/metalgest.sql`.

## Ejecutar

Desde PowerShell:

```powershell
cd C:\xampp\htdocs\MetalGest
php -S localhost:8080 -t .
```

En otra terminal, compilar:

```powershell
cd C:\xampp\htdocs\MetalGest
New-Item -ItemType Directory -Force build\classes
& C:\jdk\bin\javac.exe -encoding UTF-8 -d build\classes (Get-ChildItem src\main\java -Recurse -Filter *.java)
```

Ejecutar la aplicacion con un unico login:

```powershell
& C:\jdk\bin\java.exe -cp build\classes modulos.MetalGestApp
```

El sistema abre automaticamente el modulo segun el rol del empleado autenticado. Para pruebas directas tambien se pueden ejecutar:

```powershell
& C:\jdk\bin\java.exe -cp build\classes modulos.admin.AdminPanel
& C:\jdk\bin\java.exe -cp build\classes modulos.produccion.ProductionPanel
& C:\jdk\bin\java.exe -cp build\classes modulos.mantenimiento.MaintenancePanel
```

La API queda disponible en `http://localhost:8080/clases/php/api.php?action=health`.

## Acceso por modulo

Cada modulo solicita el nombre que escriba el empleado y la contraseña compartida de ese modulo. Si el nombre todavía no existe, se registra automáticamente con ese rol:

| Modulo | Nombre | Contraseña |
| --- | --- | --- |
| Administracion | Cualquier nombre | `admin` |
| Produccion | Cualquier nombre | `produccion` |
| Mantenimiento | Cualquier nombre | `mantenimiento` |

Luego del acceso, la cabecera muestra `Hola, Nombre!`. Un empleado no puede usar la contraseña de otro modulo.
