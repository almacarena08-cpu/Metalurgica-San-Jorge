<?php
require_once __DIR__ . '/BaseDAO.php';
require_once __DIR__ . '/PedidoSeguimientoDAO.php';

class MaquinaDAO extends BaseDAO {
    public function listar() {
        return $this->db()->query('SELECT * FROM maquina ORDER BY numero_identificacion')->fetchAll();
    }

    public function reportarFalla(array $datos) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $idOrden = (int)($datos['id_orden'] ?? 0);
            $afectaPlazo = !empty($datos['afecta_plazo']) ? 1 : 0;
            if ($afectaPlazo && $idOrden <= 0) {
                throw new InvalidArgumentException('Seleccioná el pedido afectado para informar una demora al cliente.');
            }
            $stmt = $pdo->prepare(
                'INSERT INTO mantenimiento (id_maquina, id_orden, afecta_plazo, id_usuario, fecha, tipo, problema, reparacion, repuestos, estado)
                 VALUES (:id_maquina, :id_orden, :afecta_plazo, :id_usuario, CURDATE(), :tipo, :problema, "", "", "Pendiente")'
            );
            $stmt->execute([
                ':id_maquina' => (int)$datos['id_maquina'],
                ':id_orden' => $idOrden > 0 ? $idOrden : null,
                ':afecta_plazo' => $afectaPlazo,
                ':id_usuario' => (int)($datos['id_usuario'] ?? 3),
                ':tipo' => $datos['tipo'] ?? 'Correctivo',
                ':problema' => $datos['problema']
            ]);
            $idMantenimiento = (int)$pdo->lastInsertId();
            $upd = $pdo->prepare('UPDATE maquina SET estado = "Con falla" WHERE id_maquina = :id_maquina');
            $upd->execute([':id_maquina' => (int)$datos['id_maquina']]);
            if ($afectaPlazo) {
                $idPedido = $pdo->prepare('SELECT id_pedido FROM orden_trabajo WHERE id_orden = :id_orden LIMIT 1');
                $idPedido->execute([':id_orden' => $idOrden]);
                $pedido = (int)$idPedido->fetchColumn();
                (new PedidoSeguimientoDAO())->registrarEnTransaccion(
                    $pdo,
                    $pedido,
                    'Pedido demorado',
                    $datos['seguimiento_descripcion'] ?? 'Se informó un problema con una máquina que afecta el plazo del pedido.',
                    $datos['area'] ?? 'Producción',
                    (int)($datos['id_usuario'] ?? 0)
                );
            }
            $pdo->commit();
            return $idMantenimiento;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
