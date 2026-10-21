# MetalGest

## Estructura web

La aplicación tiene una única entrada en `index.html` y separa las vistas, los estilos y la lógica en tres carpetas planas:

- `html/`: páginas y plantillas de `inicio`, `chat`, `administracion`, `produccion`, `mantenimiento`, `deposito`, `compras`, `calidad` y de cada supervisor operativo.
- `css/`: hojas de estilo del inicio, chat y de cada módulo; `supervisores.css` contiene estilos compartidos y cada supervisor operativo tiene su propia hoja.
- `js/`: `inicio.js` inicializa la aplicación y gestiona el acceso; cada módulo, cada panel de supervisor y el chat tienen su propio archivo JavaScript.

Las vistas se cargan desde las plantillas HTML al iniciar la aplicación. La portada, el ingreso y el registro comparten `inicio.html`, `inicio.css` e `inicio.js`. Cada supervisor operativo de Producción, Mantenimiento, Depósito, Compras y Calidad tiene su propio archivo HTML, CSS y JavaScript; `supervisores.js` centraliza la tabla y las acciones comunes de edición y eliminación. El selector de idioma en Configuración permite elegir español o inglés y conserva la preferencia en el navegador. La navegación expone los seis módulos de personal solicitados y mantiene el chat como acceso independiente para clientes y personal autorizado. Las funciones de pedidos, órdenes y clientes permanecen dentro de Administración.

## Backend y datos

- `clases/php/`: API y conexión a la base de datos.
- `clases/daos/`: acceso a datos por entidad o área.
- `sql/`: esquema y migraciones de base de datos.
- `vendor/`: dependencias de terceros.

El envío de verificación y recuperación usa una única cuenta SMTP de la empresa. Se puede configurar con las variables `METALGEST_SMTP_HOST`, `METALGEST_SMTP_PORT`, `METALGEST_SMTP_SECURE`, `METALGEST_SMTP_USER`, `METALGEST_SMTP_PASS` y `METALGEST_MAIL_FROM`; en XAMPP, si no están definidas, se leen los datos SMTP de `sendmail.ini`. Los clientes pueden registrarse con cualquier dirección de correo válida y no necesitan configurar el envío.

El chat web y el estado actual de los pedidos usan las tablas existentes; los mensajes se actualizan por sondeo y no requieren una migración. Con el esquema actual no es posible guardar una relación entre una conversación y su pedido, un historial independiente de estados ni la asociación de una falla de mantenimiento con un pedido. Por eso, el alta de pedidos sigue disponible desde Administración, pero no desde una conversación; el seguimiento muestra el estado actual y no simula un historial. Como tampoco existe un campo de presencia del cliente, se intenta enviar el aviso de correo en cada respuesta de Administración. El correo contiene solo el aviso, nunca el contenido del chat.

## Roles de empleados

La sección Administración > Empleados está disponible para Administrador, Gerencia y Supervisor de Administración. Este último ve todos los módulos, puede crear registros en todas las áreas y editar o eliminar registros de cada área; también puede crear, editar y eliminar empleados, excepto las cuentas de Administrador o Gerencia, que permanecen protegidas. Los supervisores operativos de Producción, Mantenimiento, Depósito, Compras y Calidad pueden acceder a los módulos operativos, crear registros y editar o eliminar registros existentes; no pueden gestionar empleados ni acceder al chat. En Compras y Depósito, cada categoría se muestra en una solapa independiente. La edición se abre con el botón correspondiente o haciendo doble clic en la fila. La interfaz y las rutas de la API validan esos permisos.

En una base existente que ya tiene los roles de supervisión, ejecutar `sql/usuarios_supervisores_iniciales.sql` en `metalurgica_san_jorge` para crear las cuentas de supervisores y Administrador Global que falten. Las nuevas importaciones pueden usar los roles y las cuentas incluidos en `sql/metalurgica_san_jorge (1).sql`. La contraseña inicial de esas cuentas es `1234`; al iniciar sesión correctamente se convierte automáticamente al hash seguro, por lo que debe cambiarse después.
