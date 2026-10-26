<?php
require_once __DIR__ . '/BaseDAO.php';

class PedidoSeguimientoDAO extends BaseDAO {
    public function registrar(int $idPedido, string $estado, string $descripcion, string $area, ?int $idUsuario, bool $actualizarEstado = true) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $id = $this->registrarEnTransaccion($pdo, $idPedido, $estado, $descripcion, $area, $idUsuario, $actualizarEstado);
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function registrarEnTransaccion(PDO $pdo, int $idPedido, string $estado, string $descripcion, string $area, ?int $idUsuario, bool $actualizarEstado = true) {
        $pedido = $pdo->prepare('SELECT id_pedido FROM pedido WHERE id_pedido = :id_pedido FOR UPDATE');
        $pedido->execute([':id_pedido' => $idPedido]);
        if (!$pedido->fetch()) throw new InvalidArgumentException('El pedido no existe.');
        if ($actualizarEstado) {
            $update = $pdo->prepare('UPDATE pedido SET estado = :estado WHERE id_pedido = :id_pedido');
            $update->execute([':estado' => $estado, ':id_pedido' => $idPedido]);
        }
        return true;
    }

    public function registrarDesdeOrden(int $idOrden, string $estado, string $descripcion, string $area, ?int $idUsuario, bool $actualizarEstado = true) {
        $stmt = $this->db()->prepare(
            'SELECT id_pedido FROM orden_trabajo WHERE id_orden = :id_orden LIMIT 1'
        );
        $stmt->execute([':id_orden' => $idOrden]);
        $idPedido = (int)$stmt->fetchColumn();
        if ($idPedido <= 0) {
            throw new InvalidArgumentException('La orden no está asociada a un pedido.');
        }
        return $this->registrar($idPedido, $estado, $descripcion, $area, $idUsuario, $actualizarEstado);
    }

    public function registrarDesdeOrdenEnTransaccion(PDO $pdo, int $idOrden, string $estado, string $descripcion, string $area, ?int $idUsuario, bool $actualizarEstado = true) {
        $stmt = $pdo->prepare('SELECT id_pedido FROM orden_trabajo WHERE id_orden = :id_orden LIMIT 1');
        $stmt->execute([':id_orden' => $idOrden]);
        $idPedido = (int)$stmt->fetchColumn();
        if ($idPedido <= 0) throw new InvalidArgumentException('La orden no está asociada a un pedido.');
        return $this->registrarEnTransaccion($pdo, $idPedido, $estado, $descripcion, $area, $idUsuario, $actualizarEstado);
    }

    public function listarParaCliente(int $idCliente) {
        $stmt = $this->db()->prepare(
            'SELECT p.id_pedido, p.fecha, p.descripcion, p.cantidad, p.material,
                    p.fecha_entrega, p.estado, c.razon_social,
                    ot.id_orden, ot.prioridad, COALESCE(MAX(pr.avance), 0) AS avance
             FROM pedido p
             INNER JOIN cliente c ON c.id_cliente = p.id_cliente
             LEFT JOIN orden_trabajo ot ON ot.id_pedido = p.id_pedido
             LEFT JOIN produccion pr ON pr.id_orden = ot.id_orden
             WHERE p.id_cliente = :id_cliente
             GROUP BY p.id_pedido, p.fecha, p.descripcion, p.cantidad, p.material,
                      p.fecha_entrega, p.estado, c.razon_social, ot.id_orden, ot.prioridad
             ORDER BY p.id_pedido DESC'
        );
        $stmt->execute([':id_cliente' => $idCliente]);
        $pedidos = $stmt->fetchAll();
        foreach ($pedidos as &$pedido) {
            $pedido['seguimiento'] = [];
        }
        unset($pedido);
        return $pedidos;
    }
}
