INSERT INTO rol (nombre)
SELECT 'Administrador'
WHERE NOT EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) = 'administrador');

INSERT INTO rol (nombre)
SELECT 'Supervisor Administración'
WHERE NOT EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) = 'supervisor administración');

INSERT INTO rol (nombre)
SELECT 'Supervisor Producción'
WHERE NOT EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) = 'supervisor producción');

INSERT INTO rol (nombre)
SELECT 'Supervisor Mantenimiento'
WHERE NOT EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) = 'supervisor mantenimiento');

INSERT INTO rol (nombre)
SELECT 'Supervisor Depósito'
WHERE NOT EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) = 'supervisor depósito');

INSERT INTO rol (nombre)
SELECT 'Supervisor Compras'
WHERE NOT EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) = 'supervisor compras');

INSERT INTO rol (nombre)
SELECT 'Supervisor Calidad'
WHERE NOT EXISTS (SELECT 1 FROM rol WHERE LOWER(nombre) = 'supervisor calidad');
