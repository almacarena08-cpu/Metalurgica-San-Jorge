<?php
require_once __DIR__ . '/BaseDAO.php';

class FacturaDAO extends BaseDAO {
    public function listar() {
        return $this->db()->query(
            'SELECT f.id_factura, f.id_cliente, f.id_pedido, f.id_chat, f.fecha, f.numero, f.total, f.estado, f.tipo_comprobante, f.condicion_venta, f.iva_alicuota, c.razon_social
             FROM factura f INNER JOIN cliente c ON c.id_cliente = f.id_cliente
             ORDER BY f.fecha DESC, f.id_factura DESC'
        )->fetchAll();
    }

    public function obtenerCompleta(int $idFactura) {
        $stmt = $this->db()->prepare(
            'SELECT f.id_factura,f.id_cliente,f.id_pedido,f.id_chat,f.fecha,f.numero,f.total,f.estado,f.tipo_comprobante,f.condicion_venta,f.iva_alicuota,
                    c.razon_social,c.cuit,c.direccion,c.email,p.descripcion,p.cantidad,p.material,h.asunto AS asunto_chat
             FROM factura f INNER JOIN cliente c ON c.id_cliente=f.id_cliente
             LEFT JOIN pedido p ON p.id_pedido=f.id_pedido LEFT JOIN chat_conversacion h ON h.id_chat=f.id_chat WHERE f.id_factura=:id LIMIT 1'
        );
        $stmt->execute([':id'=>$idFactura]);
        return $stmt->fetch();
    }

    public function crear(array $datos) {
        $stmt = $this->db()->prepare(
            'INSERT INTO factura (id_cliente, id_pedido, id_chat, fecha, numero, total, estado, tipo_comprobante, condicion_venta, iva_alicuota)
             VALUES (:id_cliente, :id_pedido, :id_chat, :fecha, :numero, :total, :estado, :tipo, :condicion, :iva)'
        );
        $stmt->execute([
            ':id_cliente' => (int)$datos['id_cliente'],
            ':id_pedido' => ($datos['id_pedido'] ?? '') === '' ? null : (int)$datos['id_pedido'],
            ':id_chat' => (int)$datos['id_chat'],
            ':fecha' => $datos['fecha'], ':numero' => $datos['numero'],
            ':total' => $datos['total'], ':estado' => $datos['estado'], ':tipo' => $datos['tipo_comprobante'],
            ':condicion' => $datos['condicion_venta'], ':iva' => $datos['iva_alicuota']
        ]);
        return (int)$this->db()->lastInsertId();
    }

    public function actualizar(array $datos) {
        $stmt = $this->db()->prepare(
            'UPDATE factura SET id_cliente=:id_cliente, id_pedido=:id_pedido, id_chat=:id_chat, fecha=:fecha,
             numero=:numero, total=:total, estado=:estado, tipo_comprobante=:tipo, condicion_venta=:condicion,
             iva_alicuota=:iva WHERE id_factura=:id_factura'
        );
        $stmt->execute([
            ':id_factura' => (int)$datos['id_factura'], ':id_cliente' => (int)$datos['id_cliente'],
            ':id_pedido' => ($datos['id_pedido'] ?? '') === '' ? null : (int)$datos['id_pedido'],
            ':id_chat' => (int)$datos['id_chat'],
            ':fecha' => $datos['fecha'], ':numero' => $datos['numero'],
            ':total' => $datos['total'], ':estado' => $datos['estado'], ':tipo' => $datos['tipo_comprobante'],
            ':condicion' => $datos['condicion_venta'], ':iva' => $datos['iva_alicuota']
        ]);
        return $stmt->rowCount();
    }

    public function eliminar(int $id) {
        $stmt = $this->db()->prepare('DELETE FROM factura WHERE id_factura=:id_factura');
        $stmt->execute([':id_factura' => $id]);
        return $stmt->rowCount();
    }
}
