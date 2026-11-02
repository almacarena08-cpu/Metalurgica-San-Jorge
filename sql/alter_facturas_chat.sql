-- Aplicar una sola vez en bases de datos ya instaladas.
ALTER TABLE chat_conversacion
  ADD COLUMN id_pedido INT(11) NULL DEFAULT NULL,
  ADD UNIQUE KEY uq_chat_pedido (id_pedido),
  ADD CONSTRAINT chat_conversacion_ibfk_pedido
    FOREIGN KEY (id_pedido) REFERENCES pedido (id_pedido);

ALTER TABLE factura
  ADD COLUMN id_chat INT(11) NULL DEFAULT NULL,
  ADD COLUMN tipo_comprobante VARCHAR(1) NOT NULL DEFAULT 'B',
  ADD COLUMN condicion_venta VARCHAR(50) NOT NULL DEFAULT 'Cuenta corriente',
  ADD COLUMN iva_alicuota DECIMAL(5,2) NOT NULL DEFAULT 21.00,
  ADD KEY idx_factura_chat (id_chat),
  ADD CONSTRAINT factura_ibfk_chat FOREIGN KEY (id_chat) REFERENCES chat_conversacion (id_chat);
