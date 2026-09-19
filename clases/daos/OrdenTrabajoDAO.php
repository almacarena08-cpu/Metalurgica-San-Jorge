<?php
require_once __DIR__ . '/BaseDAO.php';

class OrdenTrabajoDAO extends BaseDAO {
    public function listar() {
        $stmt = $this->db()->query(
            'SELECT ot.id_orden, ot.id_pedido, ot.fecha_inicio, ot.fecha_prevista, ot.fecha_finalizacion,
                    ot.prioridad, ot.estado, ot.observaciones, ot.id_usuario,
                    p.descripcion, p.cantidad, p.material, p.fecha_entrega,
                    c.razon_social,
                    COALESCE(MAX(pr.avance), 0) AS avance
             FROM orden_trabajo ot
             INNER JOIN pedido p ON p.id_pedido = ot.id_pedido
             INNER JOIN cliente c ON c.id_cliente = p.id_cliente
             LEFT JOIN produccion pr ON pr.id_orden = ot.id_orden
             GROUP BY ot.id_orden, ot.id_pedido, ot.fecha_inicio, ot.fecha_prevista, ot.fecha_finalizacion,
                      ot.prioridad, ot.estado, ot.observaciones, ot.id_usuario,
                      p.descripcion, p.cantidad, p.material, p.fecha_entrega, c.razon_social
             ORDER BY ot.id_orden DESC'
        );
        return $stmt->fetchAll();
    }

    public function crear(array $datos) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $idPedido = (int)$datos['id_pedido'];
            $pedido = $pdo->prepare('SELECT id_pedido FROM pedido WHERE id_pedido = :id_pedido FOR UPDATE');
            $pedido->execute([':id_pedido' => $idPedido]);
            if (!$pedido->fetch()) {
                throw new InvalidArgumentException('El pedido no existe');
            }

            $existente = $pdo->prepare('SELECT id_orden FROM orden_trabajo WHERE id_pedido = :id_pedido LIMIT 1');
            $existente->execute([':id_pedido' => $idPedido]);
            if ($existente->fetch()) {
                throw new InvalidArgumentException('El pedido ya tiene una orden de trabajo');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO orden_trabajo
                    (id_pedido, fecha_inicio, fecha_prevista, prioridad, estado, observaciones, id_usuario)
                 VALUES
                    (:id_pedido, :fecha_inicio, :fecha_prevista, :prioridad, :estado, :observaciones, :id_usuario)'
            );
            $stmt->execute([
                ':id_pedido' => $idPedido,
                ':fecha_inicio' => $datos['fecha_inicio'] ?? date('Y-m-d'),
                ':fecha_prevista' => $datos['fecha_prevista'] ?? null,
                ':prioridad' => $datos['prioridad'] ?? 'Media',
                ':estado' => 'Pendiente',
                ':observaciones' => $datos['observaciones'] ?? '',
                ':id_usuario' => (int)($datos['id_usuario'] ?? 1)
            ]);
            $idOrden = (int)$pdo->lastInsertId();
            $upd = $pdo->prepare('UPDATE pedido SET estado = :estado WHERE id_pedido = :id_pedido');
            $upd->execute([':estado' => 'Con orden de trabajo', ':id_pedido' => $idPedido]);
            $pdo->commit();
            return $idOrden;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function actualizarEstado(array $datos) {
        $pdo = $this->db();
        $stmt = $pdo->prepare(
            'UPDATE orden_trabajo
             SET estado = :estado,
                 observaciones = CONCAT(COALESCE(observaciones, ""), :salto, :observaciones),
                 fecha_finalizacion = CASE WHEN :estado_fin = "Finalizada" THEN CURDATE() ELSE fecha_finalizacion END
             WHERE id_orden = :id_orden'
        );
        $observaciones = $datos['observaciones'] ?? '';
        $stmt->execute([
            ':estado' => $datos['estado'],
            ':estado_fin' => $datos['estado'],
            ':salto' => $observaciones === '' ? '' : "\n",
            ':observaciones' => $observaciones,
            ':id_orden' => (int)$datos['id_orden']
        ]);
        $pedido = $pdo->prepare(
            'UPDATE pedido p
             INNER JOIN orden_trabajo ot ON ot.id_pedido = p.id_pedido
             SET p.estado = :estado
             WHERE ot.id_orden = :id_orden'
        );
        $pedido->execute([':estado' => $datos['estado'], ':id_orden' => (int)$datos['id_orden']]);
    }
}
