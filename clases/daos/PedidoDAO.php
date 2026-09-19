<?php
require_once __DIR__ . '/BaseDAO.php';

class PedidoDAO extends BaseDAO {
    public function listar() {
        $stmt = $this->db()->query(
            'SELECT p.id_pedido, p.fecha, p.descripcion, p.cantidad, p.material, p.fecha_entrega,
                    p.estado, c.razon_social, c.email, c.telefono
             FROM pedido p
             INNER JOIN cliente c ON c.id_cliente = p.id_cliente
             ORDER BY p.id_pedido DESC'
        );
        return $stmt->fetchAll();
    }

    public function crear(array $datos) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $idCliente = (int)($datos['id_cliente'] ?? 0);
            if ($idCliente <= 0) {
                $stmtCliente = $pdo->prepare(
                    'INSERT INTO cliente (razon_social, cuit, telefono, email, direccion)
                     VALUES (:razon_social, :cuit, :telefono, :email, :direccion)'
                );
                $stmtCliente->execute([
                    ':razon_social' => $datos['razon_social'],
                    ':cuit' => $datos['cuit'] ?? '',
                    ':telefono' => $datos['telefono'] ?? '',
                    ':email' => $datos['email'] ?? '',
                    ':direccion' => $datos['direccion'] ?? ''
                ]);
                $idCliente = (int)$pdo->lastInsertId();
            }

            $stmtPedido = $pdo->prepare(
                'INSERT INTO pedido (id_cliente, fecha, descripcion, cantidad, material, fecha_entrega, estado)
                 VALUES (:id_cliente, CURDATE(), :descripcion, :cantidad, :material, :fecha_entrega, :estado)'
            );
            $stmtPedido->execute([
                ':id_cliente' => $idCliente,
                ':descripcion' => $datos['descripcion'],
                ':cantidad' => (int)$datos['cantidad'],
                ':material' => $datos['material'],
                ':fecha_entrega' => $datos['fecha_entrega'] ?? null,
                ':estado' => 'Pendiente'
            ]);
            $idPedido = (int)$pdo->lastInsertId();
            $pdo->commit();
            return $idPedido;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
