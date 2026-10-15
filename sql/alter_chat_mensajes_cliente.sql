-- Los mensajes del cliente no tienen un usuario de Administracion asociado.
-- En la base compartida con el sitio web, ejecutar solo si id_usuario sigue NOT NULL.
ALTER TABLE `chat_mensaje_web`
  MODIFY `id_usuario` int(11) DEFAULT NULL;
