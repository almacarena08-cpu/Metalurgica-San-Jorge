USE metalurgica_san_jorge;

CREATE TABLE IF NOT EXISTS material_movimiento (
    id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_material INT NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    motivo TEXT,
    fecha DATETIME NOT NULL,
    id_usuario INT,
    FOREIGN KEY (id_material) REFERENCES material(id_material),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol)
SELECT 'Gerencia', 'San Jorge', 'gerencia', '1234', id_rol FROM rol WHERE nombre = 'Gerencia'
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido), contrasena = '1234', id_rol = VALUES(id_rol);

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol)
SELECT 'Administracion', 'San Jorge', 'admin', '1234', id_rol FROM rol WHERE nombre = 'Administracion'
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido), contrasena = '1234', id_rol = VALUES(id_rol);

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol)
SELECT 'Produccion', 'San Jorge', 'produccion', '1234', id_rol FROM rol WHERE nombre = 'Produccion'
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido), contrasena = '1234', id_rol = VALUES(id_rol);

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol)
SELECT 'Mantenimiento', 'San Jorge', 'mantenimiento', '1234', id_rol FROM rol WHERE nombre = 'Mantenimiento'
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido), contrasena = '1234', id_rol = VALUES(id_rol);

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol)
SELECT 'Deposito', 'San Jorge', 'deposito', '1234', id_rol FROM rol WHERE nombre = 'Deposito'
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido), contrasena = '1234', id_rol = VALUES(id_rol);

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol)
SELECT 'Compras', 'San Jorge', 'compras', '1234', id_rol FROM rol WHERE nombre = 'Compras'
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido), contrasena = '1234', id_rol = VALUES(id_rol);

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol)
SELECT 'Calidad', 'San Jorge', 'calidad', '1234', id_rol FROM rol WHERE nombre = 'Calidad'
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), apellido = VALUES(apellido), contrasena = '1234', id_rol = VALUES(id_rol);
