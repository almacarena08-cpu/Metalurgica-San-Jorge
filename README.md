# MetalGest - Rama app

Aplicacion de escritorio para la gestion interna de Metalurgica San Jorge.
Esta rama contiene los modulos internos de Administracion, Produccion, Mantenimiento, Deposito, Compras y Calidad.
La pagina publica y el contacto con clientes pertenecen a la rama `sistema-web`.

## Modulos

- **Administracion**: crea pedidos, genera ordenes de trabajo y actualiza sus estados.
- **Produccion**: consulta ordenes, registra avances y reporta fallas de maquinas a Mantenimiento.
- **Mantenimiento**: consulta fallas, registra reparaciones y repuestos, y cierra mantenimientos preventivos o correctivos.
- **Deposito**: consulta inventario y registra ingresos o salidas de materiales.
- **Compras**: administra proveedores, ordenes de compra y alertas de stock minimo.
- **Calidad**: registra controles de calidad vinculados a ordenes de trabajo.

## Estructura

- `src/main/java/modulos/admin`: interfaz Java de Administracion.
- `src/main/java/modulos/admin/dao`: DAO Java para pedidos y ordenes.
- `src/main/java/modulos/produccion`: interfaz Java de Produccion.
- `src/main/java/modulos/produccion/dao`: DAO Java para produccion y maquinas.
- `src/main/java/modulos/mantenimiento`: interfaz Java de Mantenimiento.
- `src/main/java/modulos/mantenimiento/dao`: DAO Java para el historial de mantenimientos.
- `src/main/java/modulos/deposito`: interfaz Java de Deposito.
- `src/main/java/modulos/deposito/dao`: DAO Java para inventario y movimientos.
- `src/main/java/modulos/compras`: interfaz Java de Compras.
- `src/main/java/modulos/compras/dao`: DAO Java para proveedores y compras.
- `src/main/java/modulos/calidad`: interfaz Java de Calidad.
- `src/main/java/modulos/calidad/dao`: DAO Java para controles de calidad.
- `src/main/java/modulos/common`: cliente HTTP y utilidades JSON.
- `src/main/java/modulos/common/dao`: infraestructura DAO compartida.
- `clases/daos`: DAOs PHP para la persistencia en MySQL.
- `clases/php/database.php`: conexion a MySQL.
- `clases/php/api.php`: API HTTP utilizada por los modulos Java.
- `sql/metalgest.sql`: estructura y datos iniciales de la base.
- `sql/alter_modulos_faltantes.sql`: cambios incrementales de base para los modulos agregados.

## Requisitos

- XAMPP con MariaDB/MySQL iniciado en el puerto `3306`.
- PHP 8 o superior.
- JDK con `javac` y `java`.
- Base de datos `metalurgica_san_jorge` creada desde `sql/metalgest.sql`.
- Cambios incrementales aplicados desde `sql/alter_modulos_faltantes.sql`.

## Ejecutar

Con Apache y MySQL iniciados en XAMPP, la aplicacion usa automaticamente:

```text
http://localhost/MetalGest/clases/php/api.php
```

Si se prefiere levantar PHP manualmente desde PowerShell:

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
& C:\jdk\bin\java.exe -Dmetalgest.api.url=http://localhost:8080/clases/php/api.php -cp build\classes modulos.MetalGestApp
```

El sistema abre automaticamente el modulo segun el rol del empleado autenticado. La API queda disponible en `http://localhost/MetalGest/clases/php/api.php?action=health` con XAMPP, o en `http://localhost:8080/clases/php/api.php?action=health` con el servidor PHP manual.

## Acceso por rol

El login solicita usuario y contrasena. Segun el rol del usuario, la aplicacion abre automaticamente el modulo correspondiente:

| Rol | Usuario | Contrasena |
| --- | --- | --- |
| Gerencia | `gerencia` | `1234` |
| Administracion | `admin` | `1234` |
| Produccion | `produccion` | `1234` |
| Mantenimiento | `mantenimiento` | `1234` |
| Deposito | `deposito` | `1234` |
| Compras | `compras` | `1234` |
| Calidad | `calidad` | `1234` |

Luego del acceso, la cabecera muestra `Hola, Nombre!`.
