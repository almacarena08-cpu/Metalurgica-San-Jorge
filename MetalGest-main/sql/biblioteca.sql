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