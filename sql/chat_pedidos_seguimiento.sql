-- Integración de conversaciones con pedidos y línea de tiempo para el cliente.
-- Aplicar una sola vez sobre la base metalurgica_san_jorge.

ALTER TABLE `pedido`
  ADD COLUMN `id_chat_origen` int(11) DEFAULT NULL AFTER `id_cliente`,
  ADD UNIQUE KEY `uq_pedido_chat_origen` (`id_chat_origen`),
  ADD CONSTRAINT `fk_pedido_chat_origen`
    FOREIGN KEY (`id_chat_origen`) REFERENCES `chat_conversacion` (`id_chat`)
    ON DELETE SET NULL;

ALTER TABLE `chat_conversacion`
  ADD COLUMN `cliente_ultimo_acceso` datetime DEFAULT NULL AFTER `fecha_actualizacion`;

ALTER TABLE `mantenimiento`
  ADD COLUMN `id_orden` int(11) DEFAULT NULL AFTER `id_maquina`,
  ADD COLUMN `afecta_plazo` tinyint(1) NOT NULL DEFAULT 0 AFTER `id_orden`,
  ADD KEY `idx_mantenimiento_orden` (`id_orden`),
  ADD CONSTRAINT `fk_mantenimiento_orden`
    FOREIGN KEY (`id_orden`) REFERENCES `orden_trabajo` (`id_orden`)
    ON DELETE SET NULL;

CREATE TABLE `pedido_seguimiento` (
  `id_seguimiento` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_pedido` int(11) NOT NULL,
  `estado` varchar(60) NOT NULL,
  `descripcion` text NOT NULL,
  `area` varchar(60) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `visible_cliente` tinyint(1) NOT NULL DEFAULT 1,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_seguimiento`),
  KEY `idx_seguimiento_pedido_fecha` (`id_pedido`, `fecha`, `id_seguimiento`),
  KEY `idx_seguimiento_usuario` (`id_usuario`),
  CONSTRAINT `fk_seguimiento_pedido`
    FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_seguimiento_usuario`
    FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `pedido_seguimiento` (`id_pedido`, `estado`, `descripcion`, `area`, `fecha`)
SELECT p.`id_pedido`, COALESCE(NULLIF(p.`estado`, ''), 'Pedido registrado'),
       'Estado registrado al habilitar el seguimiento en línea.', 'Administración',
       TIMESTAMP(p.`fecha`, '00:00:00')
FROM `pedido` p
WHERE NOT EXISTS (
  SELECT 1 FROM `pedido_seguimiento` s WHERE s.`id_pedido` = p.`id_pedido`
);
