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
