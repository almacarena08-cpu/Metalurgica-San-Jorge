CREATE DATABASE IF NOT EXISTS metalurgica_san_jorge;
USE metalurgica_san_jorge;


-- =========================
-- ROLES
-- =========================

CREATE TABLE rol (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL
);

INSERT INTO rol (nombre) VALUES
('Gerencia'),
('Administracion'),
('Produccion'),
('Mantenimiento'),
('Deposito'),
('Compras'),
('Calidad');


-- =========================
-- USUARIOS
-- =========================

CREATE TABLE usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apellido VARCHAR(50) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    id_rol INT NOT NULL,

    FOREIGN KEY (id_rol) REFERENCES rol(id_rol)
);

INSERT INTO usuario (nombre, apellido, usuario, contrasena, id_rol)
SELECT 'Ana', 'Gomez', 'admin', 'admin', id_rol FROM rol WHERE nombre = 'Administracion'
UNION ALL
SELECT 'Carlos', 'Rossi', 'produccion', 'produccion', id_rol FROM rol WHERE nombre = 'Produccion'
UNION ALL
SELECT 'Marta', 'Lopez', 'mantenimiento', 'mantenimiento', id_rol FROM rol WHERE nombre = 'Mantenimiento'
UNION ALL
SELECT 'Diego', 'Perez', 'deposito', 'deposito', id_rol FROM rol WHERE nombre = 'Deposito'
UNION ALL
SELECT 'Laura', 'Suarez', 'compras', 'compras', id_rol FROM rol WHERE nombre = 'Compras'
UNION ALL
SELECT 'Sofia', 'Martinez', 'calidad', 'calidad', id_rol FROM rol WHERE nombre = 'Calidad';


-- =========================
-- PREFERENCIAS POR EMPLEADO
-- =========================

CREATE TABLE preferencias_usuario (
    id_usuario INT PRIMARY KEY,
    modo_oscuro TINYINT(1) NOT NULL DEFAULT 0,
    tamano_texto INT NOT NULL DEFAULT 12,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);


-- =========================
-- CLIENTES
-- =========================

CREATE TABLE cliente (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(100) NOT NULL,
    cuit VARCHAR(20),
    telefono VARCHAR(30),
    email VARCHAR(100),
    direccion VARCHAR(150)
);

INSERT INTO cliente (razon_social, cuit, telefono, email, direccion) VALUES
('Constructora Norte SRL', '30-71234567-8', '341-555-1001', 'compras@constructoranorte.com', 'Av. Industrial 1200'),
('AgroPartes del Sur SA', '30-70987654-3', '341-555-2040', 'produccion@agropartes.com', 'Ruta 9 Km 315');


-- =========================
-- CHAT CLIENTE / ADMINISTRACION
-- =========================

CREATE TABLE chat_mensaje (
    id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    mensaje TEXT NOT NULL,
    origen VARCHAR(30) DEFAULT 'Cliente',
    departamento VARCHAR(50) DEFAULT 'Administracion',
    respuesta TEXT,
    estado VARCHAR(30) DEFAULT 'Pendiente',
    fecha DATETIME NOT NULL,
    fecha_respuesta DATETIME,
    id_usuario_respuesta INT,

    FOREIGN KEY (id_usuario_respuesta) REFERENCES usuario(id_usuario)
);

INSERT INTO chat_mensaje (nombre, email, mensaje, origen, departamento, respuesta, estado, fecha, fecha_respuesta, id_usuario_respuesta) VALUES
('Constructora Norte SRL', 'compras@constructoranorte.com', 'Necesitamos consultar plazo estimado para estructuras metalicas livianas.', 'Cliente', 'Administracion', 'Recibimos la consulta. Administracion preparara una propuesta comercial.', 'Respondido', NOW(), NOW(), 1),
('AgroPartes del Sur SA', 'produccion@agropartes.com', 'Quisieramos cotizar soportes mecanizados en acero SAE 1010.', 'Cliente', 'Administracion', NULL, 'Pendiente', NOW(), NULL, NULL);


-- =========================
-- PEDIDOS
-- =========================

CREATE TABLE pedido (
    id_pedido INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    fecha DATE NOT NULL,
    descripcion TEXT,
    cantidad INT,
    material VARCHAR(100),
    fecha_entrega DATE,
    estado VARCHAR(30),

    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente)
);

INSERT INTO pedido (id_cliente, fecha, descripcion, cantidad, material, fecha_entrega, estado) VALUES
(1, CURDATE(), 'Estructuras metalicas livianas para cerramiento industrial', 12, 'Acero estructural', DATE_ADD(CURDATE(), INTERVAL 21 DAY), 'Pendiente'),
(2, CURDATE(), 'Soportes mecanizados para linea de sembradoras', 80, 'Acero SAE 1010', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'Con orden de trabajo');


-- =========================
-- ORDENES DE TRABAJO
-- =========================

CREATE TABLE orden_trabajo (
    id_orden INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    fecha_inicio DATE,
    fecha_prevista DATE,
    fecha_finalizacion DATE,
    prioridad VARCHAR(20),
    estado VARCHAR(30),
    observaciones TEXT,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido)
);

INSERT INTO orden_trabajo (id_pedido, fecha_inicio, fecha_prevista, fecha_finalizacion, prioridad, estado, observaciones, id_usuario) VALUES
(2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 10 DAY), NULL, 'Alta', 'En produccion', 'Priorizar corte y mecanizado por fecha de entrega comprometida.', 1);


-- =========================
-- PRODUCCION
-- =========================

CREATE TABLE produccion (
    id_produccion INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL,
    fecha_inicio DATETIME,
    fecha_fin DATETIME,
    cantidad_producida INT,
    avance INT,
    observaciones TEXT,

    FOREIGN KEY (id_orden) REFERENCES orden_trabajo(id_orden)
);

INSERT INTO produccion (id_orden, fecha_inicio, fecha_fin, cantidad_producida, avance, observaciones) VALUES
(1, NOW(), NULL, 25, 35, 'Corte inicial completado. Pendiente mecanizado final.');


-- =========================
-- MAQUINAS
-- =========================

CREATE TABLE maquina (
    id_maquina INT AUTO_INCREMENT PRIMARY KEY,
    marca VARCHAR(50),
    modelo VARCHAR(50),
    numero_identificacion VARCHAR(50) UNIQUE,
    fecha_adquisicion DATE,
    costo DECIMAL(12,2),
    ubicacion VARCHAR(100),
    estado VARCHAR(30)
);

INSERT INTO maquina (marca, modelo, numero_identificacion, fecha_adquisicion, costo, ubicacion, estado) VALUES
('Cincinnati', 'CL-707', 'TOR-001', '2020-03-15', 4500000.00, 'Sector Torneria', 'Operativa'),
('Baykal', 'APH-3100', 'PLE-002', '2021-09-20', 6200000.00, 'Sector Plegado', 'Operativa'),
('Lincoln', 'Power MIG 350', 'SOL-003', '2019-06-12', 1800000.00, 'Sector Soldadura', 'Operativa');


-- =========================
-- MAQUINAS UTILIZADAS
-- =========================

CREATE TABLE orden_maquina (
    id_orden INT NOT NULL,
    id_maquina INT NOT NULL,
    horas_uso DECIMAL(10,2),

    PRIMARY KEY (id_orden, id_maquina),

    FOREIGN KEY (id_orden) REFERENCES orden_trabajo(id_orden),
    FOREIGN KEY (id_maquina) REFERENCES maquina(id_maquina)
);


-- =========================
-- MANTENIMIENTO
-- =========================

CREATE TABLE mantenimiento (
    id_mantenimiento INT AUTO_INCREMENT PRIMARY KEY,
    id_maquina INT NOT NULL,
    id_usuario INT,
    fecha DATE,
    tipo VARCHAR(20),
    problema TEXT,
    reparacion TEXT,
    repuestos TEXT,
    estado VARCHAR(30),

    FOREIGN KEY (id_maquina) REFERENCES maquina(id_maquina),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);


-- =========================
-- MATERIALES
-- =========================

CREATE TABLE material (
    id_material INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo VARCHAR(50),
    unidad VARCHAR(20),
    espesor VARCHAR(30),
    stock DECIMAL(10,2) DEFAULT 0,
    stock_minimo DECIMAL(10,2) DEFAULT 0
);

INSERT INTO material (nombre, tipo, unidad, espesor, stock, stock_minimo) VALUES
('Acero estructural', 'Chapa', 'kg', '6 mm', 1450.00, 300.00),
('Acero SAE 1010', 'Barra', 'kg', '25 mm', 820.00, 250.00),
('Electrodo E6013', 'Consumible', 'kg', NULL, 95.00, 40.00);


-- =========================
-- MATERIALES DE UNA ORDEN
-- =========================

CREATE TABLE orden_material (
    id_orden INT NOT NULL,
    id_material INT NOT NULL,
    cantidad DECIMAL(10,2),

    PRIMARY KEY (id_orden, id_material),

    FOREIGN KEY (id_orden) REFERENCES orden_trabajo(id_orden),
    FOREIGN KEY (id_material) REFERENCES material(id_material)
);


-- =========================
-- MATERIALES QUE PUEDE USAR
-- CADA MAQUINA
-- =========================

CREATE TABLE maquina_material (
    id_maquina INT NOT NULL,
    id_material INT NOT NULL,

    PRIMARY KEY (id_maquina, id_material),

    FOREIGN KEY (id_maquina) REFERENCES maquina(id_maquina),
    FOREIGN KEY (id_material) REFERENCES material(id_material)
);


-- =========================
-- PROVEEDORES
-- =========================

CREATE TABLE proveedor (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(100) NOT NULL,
    cuit VARCHAR(20),
    telefono VARCHAR(30),
    email VARCHAR(100)
);


-- =========================
-- COMPRAS
-- =========================

CREATE TABLE compra (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    fecha DATE,
    total DECIMAL(12,2),
    estado VARCHAR(30),

    FOREIGN KEY (id_proveedor) REFERENCES proveedor(id_proveedor)
);


-- =========================
-- DETALLE DE COMPRA
-- =========================

CREATE TABLE detalle_compra (
    id_detalle INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_material INT NOT NULL,
    cantidad DECIMAL(10,2),
    precio DECIMAL(12,2),

    FOREIGN KEY (id_compra) REFERENCES compra(id_compra),
    FOREIGN KEY (id_material) REFERENCES material(id_material)
);


-- =========================
-- CONTROL DE CALIDAD
-- =========================

CREATE TABLE control_calidad (
    id_control INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL,
    id_usuario INT,
    fecha DATE,
    resultado VARCHAR(30),
    observaciones TEXT,

    FOREIGN KEY (id_orden) REFERENCES orden_trabajo(id_orden),
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
);


-- =========================
-- ENTREGA
-- =========================

CREATE TABLE entrega (
    id_entrega INT AUTO_INCREMENT PRIMARY KEY,
    id_orden INT NOT NULL,
    fecha_programada DATE,
    fecha_despacho DATE,
    fecha_entrega DATE,
    lugar VARCHAR(150),
    transporte VARCHAR(100),
    estado VARCHAR(30),

    FOREIGN KEY (id_orden) REFERENCES orden_trabajo(id_orden)
);


-- =========================
-- FACTURA
-- =========================

CREATE TABLE factura (
    id_factura INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_pedido INT,
    fecha DATE,
    numero VARCHAR(30),
    total DECIMAL(12,2),
    estado VARCHAR(30),

    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente),
    FOREIGN KEY (id_pedido) REFERENCES pedido(id_pedido)
);
