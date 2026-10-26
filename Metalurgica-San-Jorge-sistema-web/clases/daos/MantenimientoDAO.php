<?php
require_once __DIR__ . '/BaseDAO.php';

class MantenimientoDAO extends BaseDAO {
    public function crearPreventivo(array $datos) {
        $stmt = $this->db()->prepare(
            'INSERT INTO mantenimiento (id_maquina, id_usuario, fecha, tipo, problema, reparacion, repuestos, estado)
             VALUES (:id_maquina, :id_usuario, :fecha, "Preventivo", :problema, "", "", "Pendiente")'
        );
        $stmt->execute([
            ':id_maquina' => (int)$datos['id_maquina'],
            ':id_usuario' => (int)($datos['id_usuario'] ?? 1),
            ':fecha' => $datos['fecha'],
            ':problema' => $datos['problema']
        ]);
        return (int)$this->db()->lastInsertId();
    }

    public function listar() {
        $stmt = $this->db()->query(
            'SELECT m.id_mantenimiento, m.id_maquina, m.id_usuario, m.fecha, m.tipo,
                    m.problema, m.reparacion, m.repuestos, m.estado,
                    q.numero_identificacion, q.marca, q.modelo, q.ubicacion
             FROM mantenimiento m
             INNER JOIN maquina q ON q.id_maquina = m.id_maquina
             ORDER BY m.id_mantenimiento DESC'
        );
        return $stmt->fetchAll();
    }

    public function actualizar(array $datos) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $detail = $pdo->prepare(
                'SELECT id_mantenimiento FROM mantenimiento WHERE id_mantenimiento = :id_mantenimiento FOR UPDATE'
            );
            $detail->execute([':id_mantenimiento' => (int)$datos['id_mantenimiento']]);
            $incidente = $detail->fetch();
            if (!$incidente) throw new InvalidArgumentException('La intervención de mantenimiento no existe.');

            $stmt = $pdo->prepare(
                'UPDATE mantenimiento
                 SET reparacion = :reparacion, repuestos = :repuestos, estado = :estado
                 WHERE id_mantenimiento = :id_mantenimiento'
            );
            $stmt->execute([
                ':reparacion' => $datos['reparacion'],
                ':repuestos' => $datos['repuestos'],
                ':estado' => $datos['estado'],
                ':id_mantenimiento' => (int)$datos['id_mantenimiento']
            ]);
            $estadoMaquina = $datos['estado'] === 'Resuelta' ? 'Operativa' : 'Con falla';
            $maquina = $pdo->prepare(
                'UPDATE maquina q
                 INNER JOIN mantenimiento m ON m.id_maquina = q.id_maquina
                 SET q.estado = :estado_maquina
                 WHERE m.id_mantenimiento = :id_mantenimiento'
            );
            $maquina->execute([
                ':estado_maquina' => $estadoMaquina,
                ':id_mantenimiento' => (int)$datos['id_mantenimiento']
            ]);
            $pdo->commit();
            return $incidente;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
