<?php
require_once __DIR__ . '/BaseDAO.php';

class ProduccionDAO extends BaseDAO {
    public function listar() {
        $stmt = $this->db()->query(
            'SELECT pr.id_produccion, pr.id_orden, pr.fecha_inicio, pr.fecha_fin, pr.cantidad_producida,
                    pr.avance, pr.observaciones, ot.estado AS estado_orden, p.descripcion, p.material,
                    c.razon_social
             FROM produccion pr
             INNER JOIN orden_trabajo ot ON ot.id_orden = pr.id_orden
             INNER JOIN pedido p ON p.id_pedido = ot.id_pedido
             INNER JOIN cliente c ON c.id_cliente = p.id_cliente
             ORDER BY pr.id_produccion DESC'
        );
        return $stmt->fetchAll();
    }

    public function registrar(array $datos) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $idOrden = (int)$datos['id_orden'];
            $orden = $pdo->prepare('SELECT id_orden FROM orden_trabajo WHERE id_orden = :id_orden FOR UPDATE');
            $orden->execute([':id_orden' => $idOrden]);
            if (!$orden->fetch()) {
                throw new InvalidArgumentException('La orden de trabajo no existe');
            }

            $avance = (int)$datos['avance'];
            $stmt = $pdo->prepare(
                'INSERT INTO produccion (id_orden, fecha_inicio, fecha_fin, cantidad_producida, avance, observaciones)
                 VALUES (:id_orden, NOW(), :fecha_fin, :cantidad_producida, :avance, :observaciones)'
            );
            $stmt->execute([
                ':id_orden' => $idOrden,
                ':fecha_fin' => $avance >= 100 ? date('Y-m-d H:i:s') : null,
                ':cantidad_producida' => (int)$datos['cantidad_producida'],
                ':avance' => $avance,
                ':observaciones' => $datos['observaciones'] ?? ''
            ]);
            $estado = $avance >= 100 ? 'Finalizada' : 'En produccion';
            $updOrden = $pdo->prepare(
                'UPDATE orden_trabajo
                 SET estado = :estado,
                     fecha_finalizacion = CASE WHEN :estado_fin = "Finalizada" THEN CURDATE() ELSE fecha_finalizacion END
                 WHERE id_orden = :id_orden'
            );
            $updOrden->execute([':estado' => $estado, ':estado_fin' => $estado, ':id_orden' => $idOrden]);
            $updPedido = $pdo->prepare(
                'UPDATE pedido p
                 INNER JOIN orden_trabajo ot ON ot.id_pedido = p.id_pedido
                 SET p.estado = :estado
                 WHERE ot.id_orden = :id_orden'
            );
            $updPedido->execute([':estado' => $estado, ':id_orden' => $idOrden]);
            $idProduccion = (int)$pdo->lastInsertId();
            $pdo->commit();
            return $idProduccion;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
