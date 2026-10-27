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
http://localhost/Metalurgica-San-Jorge/clases/php/api.php
```

Si se prefiere levantar PHP manualmente desde PowerShell:

```powershell
cd C:\xampp\htdocs\Metalurgica-San-Jorge
php -S localhost:8080 -t .
```

En otra terminal, compilar:

```powershell
cd C:\xampp\htdocs\Metalurgica-San-Jorge
New-Item -ItemType Directory -Force build\classes
& C:\jdk\bin\javac.exe -encoding UTF-8 -d build\classes (Get-ChildItem src\main\java -Recurse -Filter *.java)
```

Ejecutar la aplicacion con un unico login:

```powershell
& C:\jdk\bin\java.exe -Dmetalgest.api.url=http://localhost:8080/clases/php/api.php -cp build\classes modulos.MetalGestApp
```

Para compilar la version actual y ejecutarla con doble clic en Windows, iniciar `MetalGestApp.bat` desde la carpeta del proyecto. El lanzador genera `build\MetalGestApp-latest.jar` en cada ejecucion y requiere JDK 17 o superior.

El sistema abre automaticamente el modulo segun el rol del empleado autenticado. La API queda disponible en `http://localhost/Metalurgica-San-Jorge/clases/php/api.php?action=health` con XAMPP, o en `http://localhost:8080/clases/php/api.php?action=health` con el servidor PHP manual.

En producción, la app usa su propia API en `https://metalurgicasj.cavernservices.org/app/Metalurgica-San-Jorge/clases/php/api.php`; el chat también usa esa API y no depende del sitio web (`/index`). Se puede cambiar la URL con `-Dmetalgest.api.url=...`. Configurá su propia base mediante las variables de entorno `METALGEST_DB_HOST`, `METALGEST_DB_PORT`, `METALGEST_DB_NAME`, `METALGEST_DB_USER` y `METALGEST_DB_PASSWORD`, o con un archivo privado `clases/php/database.local.php` dentro del despliegue de la app. Si la base de la app es nueva, inicializala con `sql/metalgest.sql` y `sql/alter_modulos_faltantes.sql`; para el chat, aplicá también `sql/alter_chat_mensajes_cliente.sql`.

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
| Administrador Global | `admin_global` | `1234` |
| Supervisor Produccion | `supervisor_produccion` | `1234` |
| Supervisor Mantenimiento | `supervisor_mantenimiento` | `1234` |
| Supervisor Deposito | `supervisor_deposito` | `1234` |
| Supervisor Compras | `supervisor_compras` | `1234` |
| Supervisor Calidad | `supervisor_calidad` | `1234` |

Luego del acceso, la cabecera muestra `Hola, Nombre!`.

## Chat de Administración

El chat usa la misma API y la misma base de datos propias de la app. Para entrar se usa una cuenta de Administración o Gerencia. Si la base se creó desde el SQL de esta aplicación, aplicar una vez `sql/alter_chat_mensajes_cliente.sql` para permitir mensajes de clientes sin usuario interno asociado.

La app no lee la API ni la configuración de base de datos de la web (`/index`). Sin variables de entorno ni archivo local, PHP usa los valores predeterminados de XAMPP (`127.0.0.1`, puerto `3306`, base `metalurgica_san_jorge`, usuario `root` y contraseña vacía). En un servidor, creá el archivo privado `clases/php/database.local.php` con este formato y reemplazá los valores localmente:

```php
<?php
return [
    'host' => 'HOST_DE_MYSQL',
    'port' => '3306',
    'database' => 'BASE_DE_LA_APP',
    'username' => 'USUARIO_DE_LA_APP',
    'password' => 'CONTRASENA_DE_LA_APP',
];
```

Ese archivo está excluido de Git; no lo subas al repositorio.
