# MetalGest

## Estructura web

La aplicación tiene una única entrada en `index.html` y separa las vistas, los estilos y la lógica en tres carpetas planas:

- `html/`: páginas y plantillas de `inicio`, `chat`, `administracion`, `produccion`, `mantenimiento`, `deposito`, `compras` y `calidad`.
- `css/`: hojas de estilo del inicio, chat y de cada módulo.
- `js/`: `inicio.js` inicializa la aplicación y gestiona el acceso; cada módulo y el chat tienen su propio archivo JavaScript.

Las vistas se cargan desde las plantillas HTML al iniciar la aplicación. La portada, el ingreso y el registro comparten `inicio.html`, `inicio.css` e `inicio.js`. La navegación expone los seis módulos de personal solicitados y mantiene el chat como acceso independiente para clientes y personal autorizado. Las funciones de pedidos, órdenes y clientes permanecen dentro de Administración.

## Backend y datos

- `clases/php/`: API y conexión a la base de datos.
- `clases/daos/`: acceso a datos por entidad o área.
- `sql/`: esquema y migraciones de base de datos.
- `vendor/`: dependencias de terceros.

El envío de verificación y recuperación usa una única cuenta SMTP de la empresa. Se puede configurar con las variables `METALGEST_SMTP_HOST`, `METALGEST_SMTP_PORT`, `METALGEST_SMTP_SECURE`, `METALGEST_SMTP_USER`, `METALGEST_SMTP_PASS` y `METALGEST_MAIL_FROM`; en XAMPP, si no están definidas, se leen los datos SMTP de `sendmail.ini`. Los clientes pueden registrarse con cualquier dirección de correo válida y no necesitan configurar el envío.

El chat web y el estado actual de los pedidos usan las tablas existentes; los mensajes se actualizan por sondeo y no requieren una migración. Con el esquema actual no es posible guardar una relación entre una conversación y su pedido, un historial independiente de estados ni la asociación de una falla de mantenimiento con un pedido. Por eso, el alta de pedidos sigue disponible desde Administración, pero no desde una conversación; el seguimiento muestra el estado actual y no simula un historial. Como tampoco existe un campo de presencia del cliente, se intenta enviar el aviso de correo en cada respuesta de Administración. El correo contiene solo el aviso, nunca el contenido del chat.

## Roles de empleados

La sección Administración > Empleados está disponible para Administrador y para el rol heredado Gerencia, que permite asignar inicialmente el nuevo rol Administrador desde las cuentas existentes. Desde allí se pueden crear empleados, asignar o cambiar su rol, actualizar sus datos y eliminarlos. El Administrador accede a todos los módulos; los roles Supervisor habilitan únicamente el módulo indicado. Las rutas de la API también validan esos permisos.

Al actualizar una base existente, ejecutar una vez `sql/roles_administrador_supervisores.sql` en la base `metalurgica_san_jorge`. Las nuevas importaciones pueden usar los roles ya incluidos en `sql/metalurgica_san_jorge (1).sql`. Las contraseñas de empleados nuevos se guardan con hash; las contraseñas existentes se convierten automáticamente al hash seguro cuando el empleado inicia sesión correctamente.
