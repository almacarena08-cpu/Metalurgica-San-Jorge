<?php
require_once __DIR__ . '/BaseDAO.php';

class FacturaDAO extends BaseDAO {
    public function listar() {
        return $this->db()->query(
            'SELECT f.id_factura, f.id_cliente, f.id_chat, f.id_pedido, f.fecha, f.numero,
                    f.total, f.estado, f.tipo_comprobante, f.condicion_venta, f.iva_alicuota,
                    c.razon_social, ch.asunto AS asunto_chat
             FROM factura f
             INNER JOIN cliente c ON c.id_cliente = f.id_cliente
             LEFT JOIN chat_conversacion ch ON ch.id_chat = f.id_chat
             ORDER BY f.fecha DESC, f.id_factura DESC'
        )->fetchAll();
    }

    public function obtenerCompleta(int $idFactura) {
        $stmt = $this->db()->prepare(
            'SELECT f.id_factura, f.id_cliente, f.id_chat, f.id_pedido, f.fecha, f.numero,
                    f.total, f.estado, f.tipo_comprobante, f.condicion_venta, f.iva_alicuota,
                    c.razon_social, c.cuit, c.direccion, c.email, ch.asunto AS asunto_chat,
                    p.descripcion AS pedido_descripcion
             FROM factura f
             INNER JOIN cliente c ON c.id_cliente = f.id_cliente
             LEFT JOIN chat_conversacion ch ON ch.id_chat = f.id_chat
             LEFT JOIN pedido p ON p.id_pedido = f.id_pedido
             WHERE f.id_factura = :id_factura
             LIMIT 1'
        );
        $stmt->execute([':id_factura' => $idFactura]);
        return $stmt->fetch();
    }

    public function crear(array $datos) {
        $stmt = $this->db()->prepare(
            'INSERT INTO factura
                (id_cliente, id_chat, id_pedido, fecha, numero, total, estado,
                 tipo_comprobante, condicion_venta, iva_alicuota)
             VALUES
                (:id_cliente, :id_chat, :id_pedido, :fecha, :numero, :total, :estado,
                 :tipo_comprobante, :condicion_venta, :iva_alicuota)'
        );
        $stmt->execute($this->parametros($datos));
        return (int)$this->db()->lastInsertId();
    }

    public function actualizar(array $datos) {
        $params = $this->parametros($datos);
        $params[':id_factura'] = (int)$datos['id_factura'];
        $stmt = $this->db()->prepare(
            'UPDATE factura
             SET id_cliente = :id_cliente, id_chat = :id_chat, id_pedido = :id_pedido,
                 fecha = :fecha, numero = :numero, total = :total, estado = :estado,
                 tipo_comprobante = :tipo_comprobante, condicion_venta = :condicion_venta,
                 iva_alicuota = :iva_alicuota
             WHERE id_factura = :id_factura'
        );
        $stmt->execute($params);
        if ($stmt->rowCount() === 0 && !$this->existe((int)$datos['id_factura'])) {
            throw new RuntimeException('No se encontró la factura seleccionada.');
        }
    }

    public function eliminar(int $idFactura) {
        $stmt = $this->db()->prepare('DELETE FROM factura WHERE id_factura = :id_factura');
        $stmt->execute([':id_factura' => $idFactura]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('No se encontró la factura seleccionada.');
        }
    }

    private function parametros(array $datos) {
        return [
            ':id_cliente' => (int)$datos['id_cliente'],
            ':id_chat' => (int)$datos['id_chat'],
            ':id_pedido' => $datos['id_pedido'] !== null ? (int)$datos['id_pedido'] : null,
            ':fecha' => $datos['fecha'],
            ':numero' => $datos['numero'],
            ':total' => $datos['total'],
            ':estado' => $datos['estado'],
            ':tipo_comprobante' => $datos['tipo_comprobante'],
            ':condicion_venta' => $datos['condicion_venta'],
            ':iva_alicuota' => $datos['iva_alicuota']
        ];
    }

    private function existe(int $idFactura) {
        $stmt = $this->db()->prepare('SELECT 1 FROM factura WHERE id_factura = :id_factura LIMIT 1');
        $stmt->execute([':id_factura' => $idFactura]);
        return (bool)$stmt->fetchColumn();
    }
}
