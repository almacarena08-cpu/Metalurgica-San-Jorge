START TRANSACTION;

-- Corrige tres nombres de rol dañados por una importación con codificación incorrecta.
UPDATE rol
SET nombre = 'Supervisor Administración'
WHERE HEX(nombre) = '53757065727669736F722041646D696E69737472616369E2949CE294826E';

UPDATE rol
SET nombre = 'Supervisor Producción'
WHERE HEX(nombre) = '53757065727669736F722050726F6475636369E2949CE294826E';

UPDATE rol
SET nombre = 'Supervisor Depósito'
WHERE HEX(nombre) = '53757065727669736F7220446570E2949CE294827369746F';

-- Solo crea cuentas faltantes; la contraseña inicial se convierte en hash al iniciar sesión.
INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol, id_cliente)
SELECT 'Administrador', 'Global', 'admin_global', '1234',
       (SELECT id_rol FROM rol WHERE LOWER(nombre) IN ('administrador', 'administrador global') ORDER BY id_rol LIMIT 1),
       NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE usuario = 'admin_global')
  AND EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) IN ('administrador', 'administrador global'));

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol, id_cliente)
SELECT 'Supervisor', 'Administracion', 'supervisor_administracion', '1234',
       (SELECT id_rol FROM rol WHERE LOWER(nombre) IN ('supervisor administracion', 'supervisor administración') ORDER BY id_rol LIMIT 1),
       NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE usuario = 'supervisor_administracion')
  AND EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) IN ('supervisor administracion', 'supervisor administración'));

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol, id_cliente)
SELECT 'Supervisor', 'Produccion', 'supervisor_produccion', '1234',
       (SELECT id_rol FROM rol WHERE LOWER(nombre) IN ('supervisor produccion', 'supervisor producción') ORDER BY id_rol LIMIT 1),
       NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE usuario = 'supervisor_produccion')
  AND EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) IN ('supervisor produccion', 'supervisor producción'));

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol, id_cliente)
SELECT 'Supervisor', 'Mantenimiento', 'supervisor_mantenimiento', '1234',
       (SELECT id_rol FROM rol WHERE LOWER(nombre) = 'supervisor mantenimiento' ORDER BY id_rol LIMIT 1),
       NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE usuario = 'supervisor_mantenimiento')
  AND EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) = 'supervisor mantenimiento');

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol, id_cliente)
SELECT 'Supervisor', 'Deposito', 'supervisor_deposito', '1234',
       (SELECT id_rol FROM rol WHERE LOWER(nombre) IN ('supervisor deposito', 'supervisor depósito') ORDER BY id_rol LIMIT 1),
       NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE usuario = 'supervisor_deposito')
  AND EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) IN ('supervisor deposito', 'supervisor depósito'));

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol, id_cliente)
SELECT 'Supervisor', 'Compras', 'supervisor_compras', '1234',
       (SELECT id_rol FROM rol WHERE LOWER(nombre) = 'supervisor compras' ORDER BY id_rol LIMIT 1),
       NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE usuario = 'supervisor_compras')
  AND EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) = 'supervisor compras');

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol, id_cliente)
SELECT 'Supervisor', 'Calidad', 'supervisor_calidad', '1234',
       (SELECT id_rol FROM rol WHERE LOWER(nombre) = 'supervisor calidad' ORDER BY id_rol LIMIT 1),
       NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM usuario WHERE usuario = 'supervisor_calidad')
  AND EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) = 'supervisor calidad');

COMMIT;
