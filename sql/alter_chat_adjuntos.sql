CREATE TABLE IF NOT EXISTS `chat_adjunto` (
  `id_adjunto` int(11) NOT NULL AUTO_INCREMENT,
  `id_mensaje` int(11) NOT NULL,
  `nombre_original` varchar(255) NOT NULL,
  `clave_archivo` varchar(80) NOT NULL,
  `tipo_mime` varchar(100) NOT NULL,
  `tamano` int(10) UNSIGNED NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_adjunto`),
  UNIQUE KEY `clave_archivo` (`clave_archivo`),
  KEY `id_mensaje` (`id_mensaje`),
  CONSTRAINT `chat_adjunto_ibfk_1`
    FOREIGN KEY (`id_mensaje`) REFERENCES `chat_mensaje_web` (`id_mensaje`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
