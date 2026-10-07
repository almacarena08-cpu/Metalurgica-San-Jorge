-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-10-2026 a las 02:34:22
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `metalurgica_san_jorge`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chat_conversacion`
--

CREATE TABLE `chat_conversacion` (
  `id_chat` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `asunto` varchar(150) NOT NULL DEFAULT 'Consulta a administracion',
  `estado` varchar(30) NOT NULL DEFAULT 'Abierto',
  `fecha_creacion` datetime NOT NULL,
  `fecha_actualizacion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `chat_conversacion`
--

INSERT INTO `chat_conversacion` (`id_chat`, `id_cliente`, `asunto`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 4, 'rulemanes para tirolesas', 'Abierto', '2026-10-06 21:30:30', '2026-10-06 21:30:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chat_mensaje`
--

CREATE TABLE `chat_mensaje` (
  `id_mensaje` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `origen` varchar(30) DEFAULT 'Cliente',
  `departamento` varchar(50) DEFAULT 'Administracion',
  `respuesta` text DEFAULT NULL,
  `estado` varchar(30) DEFAULT 'Pendiente',
  `fecha` datetime NOT NULL,
  `fecha_respuesta` datetime DEFAULT NULL,
  `id_usuario_respuesta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `chat_mensaje`
--

INSERT INTO `chat_mensaje` (`id_mensaje`, `nombre`, `email`, `mensaje`, `origen`, `departamento`, `respuesta`, `estado`, `fecha`, `fecha_respuesta`, `id_usuario_respuesta`) VALUES
(1, 'Constructora Norte SRL', 'compras@constructoranorte.com', 'Necesitamos consultar plazo estimado para estructuras metalicas livianas.', 'Cliente', 'Administracion', 'Recibimos la consulta. Administracion preparara una propuesta comercial.', 'Respondido', '2026-09-23 18:36:58', '2026-09-23 18:36:58', 1),
(2, 'AgroPartes del Sur SA', 'produccion@agropartes.com', 'Quisieramos cotizar soportes mecanizados en acero SAE 1010.', 'Cliente', 'Administracion', NULL, 'Pendiente', '2026-09-23 18:36:58', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chat_mensaje_web`
--

CREATE TABLE `chat_mensaje_web` (
  `id_mensaje` int(11) NOT NULL,
  `id_chat` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `autor_tipo` varchar(30) NOT NULL,
  `mensaje` text NOT NULL,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `chat_mensaje_web`
--

INSERT INTO `chat_mensaje_web` (`id_mensaje`, `id_chat`, `id_usuario`, `autor_tipo`, `mensaje`, `fecha`) VALUES
(1, 1, 295, 'Cliente', 'hola!, queria consultar el precio de 500 rulemanes', '2026-10-06 21:30:30'),
(2, 1, 295, 'Cliente', 'cuanto saldria', '2026-10-06 21:30:46'),
(3, 1, 295, 'Cliente', '??', '2026-10-06 21:30:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

CREATE TABLE `cliente` (
  `id_cliente` int(11) NOT NULL,
  `razon_social` varchar(100) NOT NULL,
  `cuit` varchar(20) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id_cliente`, `razon_social`, `cuit`, `telefono`, `email`, `direccion`) VALUES
(1, 'Constructora Norte SRL', '30-71234567-8', '341-555-1001', 'compras@constructoranorte.com', 'Av. Industrial 1200'),
(2, 'AgroPartes del Sur SA', '30-70987654-3', '341-555-2040', 'produccion@agropartes.com', 'Ruta 9 Km 315'),
(3, 'pedrito', '', '+549115103', 'pedrito@gmail.com', ''),
(4, 'Molino eventos', '123123422', '38128312381231', 'molinoeventos@gmail.com', 'Ramallo 456');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compra`
--

CREATE TABLE `compra` (
  `id_compra` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `fecha` date DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `estado` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `compra`
--

INSERT INTO `compra` (`id_compra`, `id_proveedor`, `fecha`, `total`, `estado`) VALUES
(1, 1, '2026-01-05', 350000.00, 'Recibida'),
(2, 2, '2026-01-10', 280000.00, 'Recibida'),
(3, 3, '2026-01-15', 410000.00, 'Pendiente'),
(4, 4, '2026-01-18', 560000.00, 'Recibida'),
(5, 5, '2026-01-22', 320000.00, 'Recibida'),
(6, 6, '2026-02-01', 150000.00, 'Pendiente'),
(7, 7, '2026-02-05', 700000.00, 'Recibida'),
(8, 8, '2026-02-10', 180000.00, 'Recibida'),
(9, 9, '2026-02-14', 260000.00, 'Recibida'),
(10, 10, '2026-02-20', 450000.00, 'Pendiente'),
(11, 11, '2026-03-01', 510000.00, 'Recibida'),
(12, 12, '2026-03-05', 290000.00, 'Recibida'),
(13, 13, '2026-03-10', 380000.00, 'Pendiente'),
(14, 14, '2026-03-15', 210000.00, 'Recibida'),
(15, 15, '2026-03-20', 160000.00, 'Recibida'),
(16, 16, '2026-03-25', 470000.00, 'Pendiente'),
(17, 17, '2026-04-01', 390000.00, 'Recibida'),
(18, 18, '2026-04-05', 250000.00, 'Recibida'),
(19, 19, '2026-04-10', 310000.00, 'Pendiente'),
(20, 20, '2026-04-15', 520000.00, 'Recibida'),
(21, 1, '2026-01-05', 350000.00, 'Recibida'),
(22, 2, '2026-01-10', 280000.00, 'Recibida'),
(23, 3, '2026-01-15', 410000.00, 'Pendiente'),
(24, 4, '2026-01-18', 560000.00, 'Recibida'),
(25, 5, '2026-01-22', 320000.00, 'Recibida'),
(26, 6, '2026-02-01', 150000.00, 'Pendiente'),
(27, 7, '2026-02-05', 700000.00, 'Recibida'),
(28, 8, '2026-02-10', 180000.00, 'Recibida'),
(29, 9, '2026-02-14', 260000.00, 'Recibida'),
(30, 10, '2026-02-20', 450000.00, 'Pendiente'),
(31, 11, '2026-03-01', 510000.00, 'Recibida'),
(32, 12, '2026-03-05', 290000.00, 'Recibida'),
(33, 13, '2026-03-10', 380000.00, 'Pendiente'),
(34, 14, '2026-03-15', 210000.00, 'Recibida'),
(35, 15, '2026-03-20', 160000.00, 'Recibida'),
(36, 16, '2026-03-25', 470000.00, 'Pendiente'),
(37, 17, '2026-04-01', 390000.00, 'Recibida'),
(38, 18, '2026-04-05', 250000.00, 'Recibida'),
(39, 19, '2026-04-10', 310000.00, 'Pendiente'),
(40, 20, '2026-04-15', 520000.00, 'Recibida');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `control_calidad`
--

CREATE TABLE `control_calidad` (
  `id_control` int(11) NOT NULL,
  `id_orden` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `resultado` varchar(30) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `control_calidad`
--

INSERT INTO `control_calidad` (`id_control`, `id_orden`, `id_usuario`, `fecha`, `resultado`, `observaciones`) VALUES
(21, 1, 6, '2026-01-10', 'Aprobado', 'Correcto'),
(22, 1, 6, '2026-01-15', 'Aprobado', 'Sin observaciones'),
(23, 1, 6, '2026-01-20', 'Rechazado', 'Soldadura'),
(24, 1, 6, '2026-01-25', 'Aprobado', 'Corregido'),
(25, 1, 6, '2026-02-01', 'Aprobado', 'Correcto'),
(26, 1, 6, '2026-02-05', 'Aprobado', 'Correcto'),
(27, 1, 6, '2026-02-10', 'Rechazado', 'Medidas'),
(28, 1, 6, '2026-02-15', 'Aprobado', 'Corregido'),
(29, 1, 6, '2026-02-20', 'Aprobado', 'OK'),
(30, 1, 6, '2026-02-25', 'Aprobado', 'OK'),
(31, 1, 6, '2026-03-01', 'Aprobado', 'OK'),
(32, 1, 6, '2026-03-05', 'Rechazado', 'Acabado'),
(33, 1, 6, '2026-03-10', 'Aprobado', 'Corregido'),
(34, 1, 6, '2026-03-15', 'Aprobado', 'OK'),
(35, 1, 6, '2026-03-20', 'Aprobado', 'OK'),
(36, 1, 6, '2026-03-25', 'Aprobado', 'OK'),
(37, 1, 6, '2026-04-01', 'Rechazado', 'Pintura'),
(38, 1, 6, '2026-04-05', 'Aprobado', 'Corregido'),
(39, 1, 6, '2026-04-10', 'Aprobado', 'OK'),
(40, 1, 6, '2026-04-15', 'Aprobado', 'OK');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_compra`
--

CREATE TABLE `detalle_compra` (
  `id_detalle` int(11) NOT NULL,
  `id_compra` int(11) NOT NULL,
  `id_material` int(11) NOT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `precio` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `detalle_compra`
--

INSERT INTO `detalle_compra` (`id_detalle`, `id_compra`, `id_material`, `cantidad`, `precio`) VALUES
(1, 1, 1, 500.00, 700.00),
(2, 2, 2, 300.00, 800.00),
(3, 3, 3, 50.00, 1500.00),
(4, 4, 1, 800.00, 700.00),
(5, 5, 2, 250.00, 820.00),
(6, 6, 3, 40.00, 1550.00),
(7, 7, 1, 900.00, 710.00),
(8, 8, 2, 200.00, 800.00),
(9, 9, 3, 60.00, 1600.00),
(10, 10, 1, 700.00, 720.00),
(11, 11, 2, 350.00, 830.00),
(12, 12, 3, 45.00, 1550.00),
(13, 13, 1, 600.00, 700.00),
(14, 14, 2, 220.00, 810.00),
(15, 15, 3, 55.00, 1500.00),
(16, 16, 1, 850.00, 715.00),
(17, 17, 2, 270.00, 825.00),
(18, 18, 3, 35.00, 1520.00),
(19, 19, 1, 500.00, 700.00),
(20, 20, 2, 300.00, 820.00),
(21, 1, 1, 500.00, 700.00),
(22, 2, 2, 300.00, 800.00),
(23, 3, 3, 50.00, 1500.00),
(24, 4, 1, 800.00, 700.00),
(25, 5, 2, 250.00, 820.00),
(26, 6, 3, 40.00, 1550.00),
(27, 7, 1, 900.00, 710.00),
(28, 8, 2, 200.00, 800.00),
(29, 9, 3, 60.00, 1600.00),
(30, 10, 1, 700.00, 720.00),
(31, 11, 2, 350.00, 830.00),
(32, 12, 3, 45.00, 1550.00),
(33, 13, 1, 600.00, 700.00),
(34, 14, 2, 220.00, 810.00),
(35, 15, 3, 55.00, 1500.00),
(36, 16, 1, 850.00, 715.00),
(37, 17, 2, 270.00, 825.00),
(38, 18, 3, 35.00, 1520.00),
(39, 19, 1, 500.00, 700.00),
(40, 20, 2, 300.00, 820.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entrega`
--

CREATE TABLE `entrega` (
  `id_entrega` int(11) NOT NULL,
  `id_orden` int(11) NOT NULL,
  `fecha_programada` date DEFAULT NULL,
  `fecha_despacho` date DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `lugar` varchar(150) DEFAULT NULL,
  `transporte` varchar(100) DEFAULT NULL,
  `estado` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `entrega`
--

INSERT INTO `entrega` (`id_entrega`, `id_orden`, `fecha_programada`, `fecha_despacho`, `fecha_entrega`, `lugar`, `transporte`, `estado`) VALUES
(1, 1, '2026-01-20', '2026-01-19', '2026-01-20', 'Rosario', 'Camion', 'Entregado'),
(2, 1, '2026-02-05', '2026-02-04', '2026-02-05', 'Santa Fe', 'Camion', 'Entregado'),
(3, 1, '2026-02-20', '2026-02-19', '2026-02-20', 'Cordoba', 'Camion', 'Entregado'),
(4, 1, '2026-03-05', NULL, NULL, 'Rosario', 'Camion', 'Pendiente'),
(5, 1, '2026-03-20', NULL, NULL, 'Buenos Aires', 'Camion', 'Pendiente'),
(6, 1, '2026-04-01', '2026-03-31', '2026-04-01', 'Rosario', 'Camion', 'Entregado'),
(7, 1, '2026-04-15', '2026-04-14', '2026-04-15', 'Santa Fe', 'Camion', 'Entregado'),
(8, 1, '2026-05-01', NULL, NULL, 'Cordoba', 'Camion', 'Pendiente'),
(9, 1, '2026-05-15', NULL, NULL, 'Rosario', 'Camion', 'Pendiente'),
(10, 1, '2026-06-01', '2026-05-31', '2026-06-01', 'Buenos Aires', 'Camion', 'Entregado'),
(11, 1, '2026-06-15', '2026-06-14', '2026-06-15', 'Santa Fe', 'Camion', 'Entregado'),
(12, 1, '2026-07-01', NULL, NULL, 'Rosario', 'Camion', 'Pendiente'),
(13, 1, '2026-07-15', NULL, NULL, 'Cordoba', 'Camion', 'Pendiente'),
(14, 1, '2026-08-01', '2026-07-31', '2026-08-01', 'Rosario', 'Camion', 'Entregado'),
(15, 1, '2026-08-15', '2026-08-14', '2026-08-15', 'Santa Fe', 'Camion', 'Entregado'),
(16, 1, '2026-09-01', NULL, NULL, 'Buenos Aires', 'Camion', 'Pendiente'),
(17, 1, '2026-09-15', NULL, NULL, 'Rosario', 'Camion', 'Pendiente'),
(18, 1, '2026-10-01', '2026-09-30', '2026-10-01', 'Cordoba', 'Camion', 'Entregado'),
(19, 1, '2026-10-15', NULL, NULL, 'Santa Fe', 'Camion', 'Pendiente'),
(20, 1, '2026-11-01', NULL, NULL, 'Rosario', 'Camion', 'Pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

CREATE TABLE `factura` (
  `id_factura` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_pedido` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `numero` varchar(30) DEFAULT NULL,
  `total` decimal(12,2) DEFAULT NULL,
  `estado` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `factura`
--

INSERT INTO `factura` (`id_factura`, `id_cliente`, `id_pedido`, `fecha`, `numero`, `total`, `estado`) VALUES
(1, 1, 1, '2026-01-20', 'A-0001-00000001', 450000.00, 'Pagada'),
(2, 2, 2, '2026-01-25', 'A-0001-00000002', 320000.00, 'Pagada'),
(3, 1, 1, '2026-02-10', 'A-0001-00000003', 500000.00, 'Pendiente'),
(4, 2, 2, '2026-02-15', 'A-0001-00000004', 410000.00, 'Pagada'),
(5, 1, 1, '2026-03-01', 'A-0001-00000005', 380000.00, 'Pagada'),
(6, 2, 2, '2026-03-10', 'A-0001-00000006', 470000.00, 'Pendiente'),
(7, 1, 1, '2026-03-20', 'A-0001-00000007', 520000.00, 'Pagada'),
(8, 2, 2, '2026-04-01', 'A-0001-00000008', 450000.00, 'Pagada'),
(9, 1, 1, '2026-04-10', 'A-0001-00000009', 600000.00, 'Pendiente'),
(10, 2, 2, '2026-04-20', 'A-0001-00000010', 350000.00, 'Pagada'),
(11, 1, 1, '2026-05-01', 'A-0001-00000011', 480000.00, 'Pagada'),
(12, 2, 2, '2026-05-10', 'A-0001-00000012', 390000.00, 'Pendiente'),
(13, 1, 1, '2026-06-01', 'A-0001-00000013', 550000.00, 'Pagada'),
(14, 2, 2, '2026-06-10', 'A-0001-00000014', 430000.00, 'Pagada'),
(15, 1, 1, '2026-07-01', 'A-0001-00000015', 620000.00, 'Pendiente'),
(16, 2, 2, '2026-07-10', 'A-0001-00000016', 360000.00, 'Pagada'),
(17, 1, 1, '2026-08-01', 'A-0001-00000017', 510000.00, 'Pagada'),
(18, 2, 2, '2026-08-10', 'A-0001-00000018', 440000.00, 'Pendiente'),
(19, 1, 1, '2026-09-01', 'A-0001-00000019', 590000.00, 'Pagada'),
(20, 2, 2, '2026-09-10', 'A-0001-00000020', 400000.00, 'Pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimiento`
--

CREATE TABLE `mantenimiento` (
  `id_mantenimiento` int(11) NOT NULL,
  `id_maquina` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `fecha` date DEFAULT NULL,
  `tipo` varchar(20) DEFAULT NULL,
  `problema` text DEFAULT NULL,
  `reparacion` text DEFAULT NULL,
  `repuestos` text DEFAULT NULL,
  `estado` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mantenimiento`
--

INSERT INTO `mantenimiento` (`id_mantenimiento`, `id_maquina`, `id_usuario`, `fecha`, `tipo`, `problema`, `reparacion`, `repuestos`, `estado`) VALUES
(1, 1, 4, '2026-01-10', 'Preventivo', 'Control general', 'Lubricacion', 'Aceite', 'Finalizado'),
(2, 2, 4, '2026-01-15', 'Correctivo', 'Sensor defectuoso', 'Cambio', 'Sensor', 'Finalizado'),
(3, 3, 4, '2026-01-20', 'Preventivo', 'Revision', 'Limpieza', 'Filtros', 'Finalizado'),
(4, 1, 4, '2026-02-01', 'Correctivo', 'Ruido', 'Ajuste', 'Rodamientos', 'Finalizado'),
(5, 2, 4, '2026-02-10', 'Preventivo', 'Control', 'Lubricacion', 'Grasa', 'Finalizado'),
(6, 3, 4, '2026-02-15', 'Correctivo', 'Cable dañado', 'Cambio', 'Cable', 'Finalizado'),
(7, 1, 4, '2026-02-20', 'Preventivo', 'Revision', 'Limpieza', 'Aceite', 'Finalizado'),
(8, 2, 4, '2026-03-01', 'Correctivo', 'Motor', 'Reparacion', 'Motor', 'En proceso'),
(9, 3, 4, '2026-03-05', 'Preventivo', 'Control', 'Lubricacion', 'Grasa', 'Finalizado'),
(10, 1, 4, '2026-03-10', 'Correctivo', 'Desgaste', 'Cambio', 'Correa', 'Finalizado'),
(11, 2, 4, '2026-03-15', 'Preventivo', 'Revision', 'Limpieza', 'Filtros', 'Finalizado'),
(12, 3, 4, '2026-03-20', 'Correctivo', 'Falla electrica', 'Reparacion', 'Cable', 'Finalizado'),
(13, 1, 4, '2026-04-01', 'Preventivo', 'Control', 'Lubricacion', 'Aceite', 'Finalizado'),
(14, 2, 4, '2026-04-05', 'Correctivo', 'Sensor', 'Cambio', 'Sensor', 'Finalizado'),
(15, 3, 4, '2026-04-10', 'Preventivo', 'Revision', 'Limpieza', 'Filtros', 'Finalizado'),
(16, 1, 4, '2026-04-15', 'Correctivo', 'Motor', 'Cambio', 'Motor', 'En proceso'),
(17, 2, 4, '2026-04-20', 'Preventivo', 'Control', 'Lubricacion', 'Grasa', 'Finalizado'),
(18, 3, 4, '2026-04-25', 'Correctivo', 'Cable', 'Cambio', 'Cable', 'Finalizado'),
(19, 1, 4, '2026-05-01', 'Preventivo', 'Revision', 'Limpieza', 'Aceite', 'Finalizado'),
(20, 2, 4, '2026-05-05', 'Correctivo', 'Rodamiento', 'Cambio', 'Rodamiento', 'Finalizado'),
(21, 1, 3, '2026-01-10', 'Preventivo', 'Control general', 'Lubricacion', 'Aceite', 'Finalizado'),
(22, 2, 3, '2026-01-15', 'Correctivo', 'Sensor defectuoso', 'Cambio', 'Sensor', 'Finalizado'),
(23, 3, 3, '2026-01-20', 'Preventivo', 'Revision', 'Limpieza', 'Filtros', 'Finalizado'),
(24, 1, 3, '2026-02-01', 'Correctivo', 'Ruido', 'Ajuste', 'Rodamientos', 'Finalizado'),
(25, 2, 3, '2026-02-10', 'Preventivo', 'Control', 'Lubricacion', 'Grasa', 'Finalizado'),
(26, 3, 3, '2026-02-15', 'Correctivo', 'Cable dañado', 'Cambio', 'Cable', 'Finalizado'),
(27, 1, 3, '2026-02-20', 'Preventivo', 'Revision', 'Limpieza', 'Aceite', 'Finalizado'),
(28, 2, 3, '2026-03-01', 'Correctivo', 'Motor', 'Reparacion', 'Motor', 'En proceso'),
(29, 3, 3, '2026-03-05', 'Preventivo', 'Control', 'Lubricacion', 'Grasa', 'Finalizado'),
(30, 1, 3, '2026-03-10', 'Correctivo', 'Desgaste', 'Cambio', 'Correa', 'Finalizado'),
(31, 2, 3, '2026-03-15', 'Preventivo', 'Revision', 'Limpieza', 'Filtros', 'Finalizado'),
(32, 3, 3, '2026-03-20', 'Correctivo', 'Falla electrica', 'Reparacion', 'Cable', 'Finalizado'),
(33, 1, 3, '2026-04-01', 'Preventivo', 'Control', 'Lubricacion', 'Aceite', 'Finalizado'),
(34, 2, 3, '2026-04-05', 'Correctivo', 'Sensor', 'Cambio', 'Sensor', 'Finalizado'),
(35, 3, 3, '2026-04-10', 'Preventivo', 'Revision', 'Limpieza', 'Filtros', 'Finalizado'),
(36, 1, 3, '2026-04-15', 'Correctivo', 'Motor', 'Cambio', 'Motor', 'En proceso'),
(37, 2, 3, '2026-04-20', 'Preventivo', 'Control', 'Lubricacion', 'Grasa', 'Finalizado'),
(38, 3, 3, '2026-04-25', 'Correctivo', 'Cable', 'Cambio', 'Cable', 'Finalizado'),
(39, 1, 3, '2026-05-01', 'Preventivo', 'Revision', 'Limpieza', 'Aceite', 'Finalizado'),
(40, 2, 3, '2026-05-05', 'Correctivo', 'Rodamiento', 'Cambio', 'Rodamiento', 'Finalizado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maquina`
--

CREATE TABLE `maquina` (
  `id_maquina` int(11) NOT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `numero_identificacion` varchar(50) DEFAULT NULL,
  `fecha_adquisicion` date DEFAULT NULL,
  `costo` decimal(12,2) DEFAULT NULL,
  `ubicacion` varchar(100) DEFAULT NULL,
  `estado` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `maquina`
--

INSERT INTO `maquina` (`id_maquina`, `marca`, `modelo`, `numero_identificacion`, `fecha_adquisicion`, `costo`, `ubicacion`, `estado`) VALUES
(1, 'Cincinnati', 'CL-707', 'TOR-001', '2020-03-15', 4500000.00, 'Sector Torneria', 'Operativa'),
(2, 'Baykal', 'APH-3100', 'PLE-002', '2021-09-20', 6200000.00, 'Sector Plegado', 'Operativa'),
(3, 'Lincoln', 'Power MIG 350', 'SOL-003', '2019-06-12', 1800000.00, 'Sector Soldadura', 'Operativa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maquina_material`
--

CREATE TABLE `maquina_material` (
  `id_maquina` int(11) NOT NULL,
  `id_material` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `maquina_material`
--

INSERT INTO `maquina_material` (`id_maquina`, `id_material`) VALUES
(1, 1),
(1, 2),
(2, 1),
(2, 2),
(2, 3),
(3, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `material`
--

CREATE TABLE `material` (
  `id_material` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `unidad` varchar(20) DEFAULT NULL,
  `espesor` varchar(30) DEFAULT NULL,
  `stock` decimal(10,2) DEFAULT 0.00,
  `stock_minimo` decimal(10,2) DEFAULT 0.00,
  `stock_maximo` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `material`
--

INSERT INTO `material` (`id_material`, `nombre`, `tipo`, `unidad`, `espesor`, `stock`, `stock_minimo`, `stock_maximo`) VALUES
(1, 'Acero estructural', 'Chapa', 'kg', '6 mm', 1450.00, 300.00, 1450.00),
(2, 'Acero SAE 1010', 'Barra', 'kg', '25 mm', 820.00, 250.00, 820.00),
(3, 'Electrodo E6013', 'Consumible', 'kg', NULL, 95.00, 40.00, 95.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `material_movimiento`
--

CREATE TABLE `material_movimiento` (
  `id_movimiento` int(11) NOT NULL,
  `id_material` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `motivo` text DEFAULT NULL,
  `fecha` datetime NOT NULL,
  `id_usuario` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `material_movimiento`
--

INSERT INTO `material_movimiento` (`id_movimiento`, `id_material`, `tipo`, `cantidad`, `motivo`, `fecha`, `id_usuario`) VALUES
(1, 1, 'Entrada', 500.00, 'Compra', '2026-01-01 08:00:00', 5),
(2, 2, 'Entrada', 300.00, 'Compra', '2026-01-02 08:00:00', 5),
(3, 3, 'Entrada', 50.00, 'Compra', '2026-01-03 08:00:00', 5),
(4, 1, 'Salida', 100.00, 'Produccion', '2026-01-05 10:00:00', 3),
(5, 2, 'Salida', 80.00, 'Produccion', '2026-01-06 10:00:00', 3),
(6, 3, 'Salida', 10.00, 'Soldadura', '2026-01-07 10:00:00', 3),
(7, 1, 'Entrada', 400.00, 'Compra', '2026-02-01 08:00:00', 5),
(8, 2, 'Entrada', 200.00, 'Compra', '2026-02-02 08:00:00', 5),
(9, 3, 'Entrada', 30.00, 'Compra', '2026-02-03 08:00:00', 5),
(10, 1, 'Salida', 120.00, 'Produccion', '2026-02-05 10:00:00', 3),
(11, 2, 'Salida', 90.00, 'Produccion', '2026-02-06 10:00:00', 3),
(12, 3, 'Salida', 12.00, 'Soldadura', '2026-02-07 10:00:00', 3),
(13, 1, 'Entrada', 450.00, 'Compra', '2026-03-01 08:00:00', 5),
(14, 2, 'Entrada', 220.00, 'Compra', '2026-03-02 08:00:00', 5),
(15, 3, 'Entrada', 35.00, 'Compra', '2026-03-03 08:00:00', 5),
(16, 1, 'Salida', 140.00, 'Produccion', '2026-03-05 10:00:00', 3),
(17, 2, 'Salida', 95.00, 'Produccion', '2026-03-06 10:00:00', 3),
(18, 3, 'Salida', 15.00, 'Soldadura', '2026-03-07 10:00:00', 3),
(19, 1, 'Entrada', 300.00, 'Compra', '2026-04-01 08:00:00', 5),
(20, 2, 'Salida', 70.00, 'Produccion', '2026-04-02 10:00:00', 3),
(21, 1, 'Entrada', 500.00, 'Compra', '2026-01-01 08:00:00', 4),
(22, 2, 'Entrada', 300.00, 'Compra', '2026-01-02 08:00:00', 4),
(23, 3, 'Entrada', 50.00, 'Compra', '2026-01-03 08:00:00', 4),
(24, 1, 'Salida', 100.00, 'Produccion', '2026-01-05 10:00:00', 2),
(25, 2, 'Salida', 80.00, 'Produccion', '2026-01-06 10:00:00', 2),
(26, 3, 'Salida', 10.00, 'Soldadura', '2026-01-07 10:00:00', 2),
(27, 1, 'Entrada', 400.00, 'Compra', '2026-02-01 08:00:00', 4),
(28, 2, 'Entrada', 200.00, 'Compra', '2026-02-02 08:00:00', 4),
(29, 3, 'Entrada', 30.00, 'Compra', '2026-02-03 08:00:00', 4),
(30, 1, 'Salida', 120.00, 'Produccion', '2026-02-05 10:00:00', 2),
(31, 2, 'Salida', 90.00, 'Produccion', '2026-02-06 10:00:00', 2),
(32, 3, 'Salida', 12.00, 'Soldadura', '2026-02-07 10:00:00', 2),
(33, 1, 'Entrada', 450.00, 'Compra', '2026-03-01 08:00:00', 4),
(34, 2, 'Entrada', 220.00, 'Compra', '2026-03-02 08:00:00', 4),
(35, 3, 'Entrada', 35.00, 'Compra', '2026-03-03 08:00:00', 4),
(36, 1, 'Salida', 140.00, 'Produccion', '2026-03-05 10:00:00', 2),
(37, 2, 'Salida', 95.00, 'Produccion', '2026-03-06 10:00:00', 2),
(38, 3, 'Salida', 15.00, 'Soldadura', '2026-03-07 10:00:00', 2),
(39, 1, 'Entrada', 300.00, 'Compra', '2026-04-01 08:00:00', 4),
(40, 2, 'Salida', 70.00, 'Produccion', '2026-04-02 10:00:00', 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_maquina`
--

CREATE TABLE `orden_maquina` (
  `id_orden` int(11) NOT NULL,
  `id_maquina` int(11) NOT NULL,
  `horas_uso` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `orden_maquina`
--

INSERT INTO `orden_maquina` (`id_orden`, `id_maquina`, `horas_uso`) VALUES
(1, 1, 8.50),
(1, 2, 6.00),
(1, 3, 4.50);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_material`
--

CREATE TABLE `orden_material` (
  `id_orden` int(11) NOT NULL,
  `id_material` int(11) NOT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `orden_material`
--

INSERT INTO `orden_material` (`id_orden`, `id_material`, `cantidad`) VALUES
(1, 1, 250.00),
(1, 2, 120.00),
(1, 3, 25.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_trabajo`
--

CREATE TABLE `orden_trabajo` (
  `id_orden` int(11) NOT NULL,
  `id_pedido` int(11) NOT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_prevista` date DEFAULT NULL,
  `fecha_finalizacion` date DEFAULT NULL,
  `prioridad` varchar(20) DEFAULT NULL,
  `estado` varchar(30) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `orden_trabajo`
--

INSERT INTO `orden_trabajo` (`id_orden`, `id_pedido`, `fecha_inicio`, `fecha_prevista`, `fecha_finalizacion`, `prioridad`, `estado`, `observaciones`, `id_usuario`) VALUES
(1, 2, '2026-09-23', '2026-10-03', NULL, 'Alta', 'En produccion', 'Priorizar corte y mecanizado por fecha de entrega comprometida.', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido`
--

CREATE TABLE `pedido` (
  `id_pedido` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad` int(11) DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `estado` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pedido`
--

INSERT INTO `pedido` (`id_pedido`, `id_cliente`, `fecha`, `descripcion`, `cantidad`, `material`, `fecha_entrega`, `estado`) VALUES
(1, 1, '2026-09-23', 'Estructuras metalicas livianas para cerramiento industrial', 12, 'Acero estructural', '2026-10-14', 'Pendiente'),
(2, 2, '2026-09-23', 'Soportes mecanizados para linea de sembradoras', 80, 'Acero SAE 1010', '2026-10-07', 'Con orden de trabajo'),
(3, 3, '2026-09-28', 'Rulemanes', 500, 'acero galvanizado', '2026-10-12', 'Pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preferencias_usuario`
--

CREATE TABLE `preferencias_usuario` (
  `id_usuario` int(11) NOT NULL,
  `modo_oscuro` tinyint(1) NOT NULL DEFAULT 0,
  `tamano_texto` int(11) NOT NULL DEFAULT 12
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `preferencias_usuario`
--

INSERT INTO `preferencias_usuario` (`id_usuario`, `modo_oscuro`, `tamano_texto`) VALUES
(1, 1, 12),
(5, 1, 12);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `produccion`
--

CREATE TABLE `produccion` (
  `id_produccion` int(11) NOT NULL,
  `id_orden` int(11) NOT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `cantidad_producida` int(11) DEFAULT NULL,
  `avance` int(11) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `produccion`
--

INSERT INTO `produccion` (`id_produccion`, `id_orden`, `fecha_inicio`, `fecha_fin`, `cantidad_producida`, `avance`, `observaciones`) VALUES
(1, 1, '2026-09-23 18:36:58', NULL, 25, 35, 'Corte inicial completado. Pendiente mecanizado final.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `id_proveedor` int(11) NOT NULL,
  `razon_social` varchar(100) NOT NULL,
  `cuit` varchar(20) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`id_proveedor`, `razon_social`, `cuit`, `telefono`, `email`) VALUES
(1, 'Aceros Rosario SA', '30-70000001-1', '3415551001', 'ventas@acerosrosario.com'),
(2, 'Metales del Litoral', '30-70000002-2', '3415551002', 'ventas@metaleslitoral.com'),
(3, 'Hierros Industriales', '30-70000003-3', '3415551003', 'info@hierros.com'),
(4, 'Siderurgia Norte', '30-70000004-4', '3415551004', 'contacto@siderurgia.com'),
(5, 'Chapas del Sur', '30-70000005-5', '3415551005', 'ventas@chapas.com'),
(6, 'Soldaduras Argentinas', '30-70000006-6', '3415551006', 'info@soldaduras.com'),
(7, 'Acindar Comercial', '30-70000007-7', '3415551007', 'ventas@acindar.com'),
(8, 'Perfiles SRL', '30-70000008-8', '3415551008', 'info@perfiles.com'),
(9, 'Insumos Metalicos', '30-70000009-9', '3415551009', 'ventas@insumos.com'),
(10, 'Torneria Central', '30-70000010-0', '3415551010', 'contacto@torneria.com'),
(11, 'Aceros Pampeanos', '30-70000011-1', '3415551011', 'ventas@pampeanos.com'),
(12, 'Industrial Sur', '30-70000012-2', '3415551012', 'info@indsur.com'),
(13, 'Metalmecánica SRL', '30-70000013-3', '3415551013', 'ventas@metal.com'),
(14, 'Abrasivos SA', '30-70000014-4', '3415551014', 'contacto@abrasivos.com'),
(15, 'Pinturas Industriales', '30-70000015-5', '3415551015', 'ventas@pinturas.com'),
(16, 'Cables y Aceros', '30-70000016-6', '3415551016', 'info@cables.com'),
(17, 'Importadora Industrial', '30-70000017-7', '3415551017', 'ventas@importadora.com'),
(18, 'Elementos Tecnicos', '30-70000018-8', '3415551018', 'info@tecnicos.com'),
(19, 'Metal Center', '30-70000019-9', '3415551019', 'ventas@metalcenter.com'),
(20, 'Distribuidora Rosario', '30-70000020-0', '3415551020', 'info@distro.com'),
(21, 'Aceros Rosario SA', '30-70000001-1', '3415551001', 'ventas@acerosrosario.com'),
(22, 'Metales del Litoral', '30-70000002-2', '3415551002', 'ventas@metaleslitoral.com'),
(23, 'Hierros Industriales', '30-70000003-3', '3415551003', 'info@hierros.com'),
(24, 'Siderurgia Norte', '30-70000004-4', '3415551004', 'contacto@siderurgia.com'),
(25, 'Chapas del Sur', '30-70000005-5', '3415551005', 'ventas@chapas.com'),
(26, 'Soldaduras Argentinas', '30-70000006-6', '3415551006', 'info@soldaduras.com'),
(27, 'Acindar Comercial', '30-70000007-7', '3415551007', 'ventas@acindar.com'),
(28, 'Perfiles SRL', '30-70000008-8', '3415551008', 'info@perfiles.com'),
(29, 'Insumos Metalicos', '30-70000009-9', '3415551009', 'ventas@insumos.com'),
(30, 'Torneria Central', '30-70000010-0', '3415551010', 'contacto@torneria.com'),
(31, 'Aceros Pampeanos', '30-70000011-1', '3415551011', 'ventas@pampeanos.com'),
(32, 'Industrial Sur', '30-70000012-2', '3415551012', 'info@indsur.com'),
(33, 'Metalmecanica SRL', '30-70000013-3', '3415551013', 'ventas@metal.com'),
(34, 'Abrasivos SA', '30-70000014-4', '3415551014', 'contacto@abrasivos.com'),
(35, 'Pinturas Industriales', '30-70000015-5', '3415551015', 'ventas@pinturas.com'),
(36, 'Cables y Aceros', '30-70000016-6', '3415551016', 'info@cables.com'),
(37, 'Importadora Industrial', '30-70000017-7', '3415551017', 'ventas@importadora.com'),
(38, 'Elementos Tecnicos', '30-70000018-8', '3415551018', 'info@tecnicos.com'),
(39, 'Metal Center', '30-70000019-9', '3415551019', 'ventas@metalcenter.com'),
(40, 'Distribuidora Rosario', '30-70000020-0', '3415551020', 'info@distro.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `nombre`) VALUES
(1, 'Gerencia'),
(2, 'Administracion'),
(3, 'Produccion'),
(4, 'Mantenimiento'),
(5, 'Deposito'),
(6, 'Compras'),
(7, 'Calidad'),
(8, 'Cliente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `id_cliente` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre`, `apellido`, `usuario`, `contrasena`, `id_rol`, `id_cliente`) VALUES
(1, 'Administracion', 'San Jorge', 'admin', '1234', 2, NULL),
(2, 'Produccion', 'San Jorge', 'produccion', '1234', 3, NULL),
(3, 'Mantenimiento', 'San Jorge', 'mantenimiento', '1234', 4, NULL),
(4, 'Deposito', 'San Jorge', 'deposito', '1234', 5, NULL),
(5, 'Compras', 'San Jorge', 'compras', '1234', 6, NULL),
(6, 'Calidad', 'San Jorge', 'calidad', '1234', 7, NULL),
(8, 'Gerencia', 'San Jorge', 'gerencia', '1234', 1, NULL),
(295, 'Molino eventos', '', 'molinoev', '1234', 8, 4);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `chat_conversacion`
--
ALTER TABLE `chat_conversacion`
  ADD PRIMARY KEY (`id_chat`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `chat_mensaje`
--
ALTER TABLE `chat_mensaje`
  ADD PRIMARY KEY (`id_mensaje`),
  ADD KEY `id_usuario_respuesta` (`id_usuario_respuesta`);

--
-- Indices de la tabla `chat_mensaje_web`
--
ALTER TABLE `chat_mensaje_web`
  ADD PRIMARY KEY (`id_mensaje`),
  ADD KEY `id_chat` (`id_chat`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `cliente`
--
ALTER TABLE `cliente`
  ADD PRIMARY KEY (`id_cliente`);

--
-- Indices de la tabla `compra`
--
ALTER TABLE `compra`
  ADD PRIMARY KEY (`id_compra`),
  ADD KEY `id_proveedor` (`id_proveedor`);

--
-- Indices de la tabla `control_calidad`
--
ALTER TABLE `control_calidad`
  ADD PRIMARY KEY (`id_control`),
  ADD KEY `id_orden` (`id_orden`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_compra` (`id_compra`),
  ADD KEY `id_material` (`id_material`);

--
-- Indices de la tabla `entrega`
--
ALTER TABLE `entrega`
  ADD PRIMARY KEY (`id_entrega`),
  ADD KEY `id_orden` (`id_orden`);

--
-- Indices de la tabla `factura`
--
ALTER TABLE `factura`
  ADD PRIMARY KEY (`id_factura`),
  ADD KEY `id_cliente` (`id_cliente`),
  ADD KEY `id_pedido` (`id_pedido`);

--
-- Indices de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  ADD PRIMARY KEY (`id_mantenimiento`),
  ADD KEY `id_maquina` (`id_maquina`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `maquina`
--
ALTER TABLE `maquina`
  ADD PRIMARY KEY (`id_maquina`),
  ADD UNIQUE KEY `numero_identificacion` (`numero_identificacion`);

--
-- Indices de la tabla `maquina_material`
--
ALTER TABLE `maquina_material`
  ADD PRIMARY KEY (`id_maquina`,`id_material`),
  ADD KEY `id_material` (`id_material`);

--
-- Indices de la tabla `material`
--
ALTER TABLE `material`
  ADD PRIMARY KEY (`id_material`);

--
-- Indices de la tabla `material_movimiento`
--
ALTER TABLE `material_movimiento`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `id_material` (`id_material`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `orden_maquina`
--
ALTER TABLE `orden_maquina`
  ADD PRIMARY KEY (`id_orden`,`id_maquina`),
  ADD KEY `id_maquina` (`id_maquina`);

--
-- Indices de la tabla `orden_material`
--
ALTER TABLE `orden_material`
  ADD PRIMARY KEY (`id_orden`,`id_material`),
  ADD KEY `id_material` (`id_material`);

--
-- Indices de la tabla `orden_trabajo`
--
ALTER TABLE `orden_trabajo`
  ADD PRIMARY KEY (`id_orden`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_pedido` (`id_pedido`);

--
-- Indices de la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD PRIMARY KEY (`id_pedido`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `preferencias_usuario`
--
ALTER TABLE `preferencias_usuario`
  ADD PRIMARY KEY (`id_usuario`);

--
-- Indices de la tabla `produccion`
--
ALTER TABLE `produccion`
  ADD PRIMARY KEY (`id_produccion`),
  ADD KEY `id_orden` (`id_orden`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`id_proveedor`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `id_rol` (`id_rol`),
  ADD KEY `fk_usuario_cliente` (`id_cliente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `chat_conversacion`
--
ALTER TABLE `chat_conversacion`
  MODIFY `id_chat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `chat_mensaje`
--
ALTER TABLE `chat_mensaje`
  MODIFY `id_mensaje` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `chat_mensaje_web`
--
ALTER TABLE `chat_mensaje_web`
  MODIFY `id_mensaje` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cliente`
--
ALTER TABLE `cliente`
  MODIFY `id_cliente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `compra`
--
ALTER TABLE `compra`
  MODIFY `id_compra` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `control_calidad`
--
ALTER TABLE `control_calidad`
  MODIFY `id_control` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `entrega`
--
ALTER TABLE `entrega`
  MODIFY `id_entrega` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `factura`
--
ALTER TABLE `factura`
  MODIFY `id_factura` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  MODIFY `id_mantenimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `maquina`
--
ALTER TABLE `maquina`
  MODIFY `id_maquina` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `material`
--
ALTER TABLE `material`
  MODIFY `id_material` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `material_movimiento`
--
ALTER TABLE `material_movimiento`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `orden_trabajo`
--
ALTER TABLE `orden_trabajo`
  MODIFY `id_orden` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pedido`
--
ALTER TABLE `pedido`
  MODIFY `id_pedido` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `produccion`
--
ALTER TABLE `produccion`
  MODIFY `id_produccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=303;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `chat_conversacion`
--
ALTER TABLE `chat_conversacion`
  ADD CONSTRAINT `chat_conversacion_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`);

--
-- Filtros para la tabla `chat_mensaje`
--
ALTER TABLE `chat_mensaje`
  ADD CONSTRAINT `chat_mensaje_ibfk_1` FOREIGN KEY (`id_usuario_respuesta`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `chat_mensaje_web`
--
ALTER TABLE `chat_mensaje_web`
  ADD CONSTRAINT `chat_mensaje_web_ibfk_1` FOREIGN KEY (`id_chat`) REFERENCES `chat_conversacion` (`id_chat`),
  ADD CONSTRAINT `chat_mensaje_web_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `compra`
--
ALTER TABLE `compra`
  ADD CONSTRAINT `compra_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedor` (`id_proveedor`);

--
-- Filtros para la tabla `control_calidad`
--
ALTER TABLE `control_calidad`
  ADD CONSTRAINT `control_calidad_ibfk_1` FOREIGN KEY (`id_orden`) REFERENCES `orden_trabajo` (`id_orden`),
  ADD CONSTRAINT `control_calidad_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `detalle_compra`
--
ALTER TABLE `detalle_compra`
  ADD CONSTRAINT `detalle_compra_ibfk_1` FOREIGN KEY (`id_compra`) REFERENCES `compra` (`id_compra`),
  ADD CONSTRAINT `detalle_compra_ibfk_2` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`);

--
-- Filtros para la tabla `entrega`
--
ALTER TABLE `entrega`
  ADD CONSTRAINT `entrega_ibfk_1` FOREIGN KEY (`id_orden`) REFERENCES `orden_trabajo` (`id_orden`);

--
-- Filtros para la tabla `factura`
--
ALTER TABLE `factura`
  ADD CONSTRAINT `factura_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  ADD CONSTRAINT `factura_ibfk_2` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`);

--
-- Filtros para la tabla `mantenimiento`
--
ALTER TABLE `mantenimiento`
  ADD CONSTRAINT `mantenimiento_ibfk_1` FOREIGN KEY (`id_maquina`) REFERENCES `maquina` (`id_maquina`),
  ADD CONSTRAINT `mantenimiento_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `maquina_material`
--
ALTER TABLE `maquina_material`
  ADD CONSTRAINT `maquina_material_ibfk_1` FOREIGN KEY (`id_maquina`) REFERENCES `maquina` (`id_maquina`),
  ADD CONSTRAINT `maquina_material_ibfk_2` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`);

--
-- Filtros para la tabla `material_movimiento`
--
ALTER TABLE `material_movimiento`
  ADD CONSTRAINT `material_movimiento_ibfk_1` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`),
  ADD CONSTRAINT `material_movimiento_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `orden_maquina`
--
ALTER TABLE `orden_maquina`
  ADD CONSTRAINT `orden_maquina_ibfk_1` FOREIGN KEY (`id_orden`) REFERENCES `orden_trabajo` (`id_orden`),
  ADD CONSTRAINT `orden_maquina_ibfk_2` FOREIGN KEY (`id_maquina`) REFERENCES `maquina` (`id_maquina`);

--
-- Filtros para la tabla `orden_material`
--
ALTER TABLE `orden_material`
  ADD CONSTRAINT `orden_material_ibfk_1` FOREIGN KEY (`id_orden`) REFERENCES `orden_trabajo` (`id_orden`),
  ADD CONSTRAINT `orden_material_ibfk_2` FOREIGN KEY (`id_material`) REFERENCES `material` (`id_material`);

--
-- Filtros para la tabla `orden_trabajo`
--
ALTER TABLE `orden_trabajo`
  ADD CONSTRAINT `orden_trabajo_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`),
  ADD CONSTRAINT `orden_trabajo_ibfk_2` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`);

--
-- Filtros para la tabla `pedido`
--
ALTER TABLE `pedido`
  ADD CONSTRAINT `pedido_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`);

--
-- Filtros para la tabla `preferencias_usuario`
--
ALTER TABLE `preferencias_usuario`
  ADD CONSTRAINT `preferencias_usuario_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `produccion`
--
ALTER TABLE `produccion`
  ADD CONSTRAINT `produccion_ibfk_1` FOREIGN KEY (`id_orden`) REFERENCES `orden_trabajo` (`id_orden`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`),
  ADD CONSTRAINT `usuario_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
