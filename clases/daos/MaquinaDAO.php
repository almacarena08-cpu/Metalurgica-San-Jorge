<?php
require_once __DIR__ . '/BaseDAO.php';

class MaquinaDAO extends BaseDAO {
    public function listar() {
        return $this->db()->query('SELECT * FROM maquina ORDER BY numero_identificacion')->fetchAll();
    }

    public function reportarFalla(array $datos) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO mantenimiento (id_maquina, id_usuario, fecha, tipo, problema, reparacion, repuestos, estado)
                 VALUES (:id_maquina, :id_usuario, CURDATE(), :tipo, :problema, "", "", "Pendiente")'
            );
            $stmt->execute([
                ':id_maquina' => (int)$datos['id_maquina'],
                ':id_usuario' => (int)($datos['id_usuario'] ?? 3),
                ':tipo' => $datos['tipo'] ?? 'Correctivo',
                ':problema' => $datos['problema']
            ]);
            $idMantenimiento = (int)$pdo->lastInsertId();
            $upd = $pdo->prepare('UPDATE maquina SET estado = "Con falla" WHERE id_maquina = :id_maquina');
            $upd->execute([':id_maquina' => (int)$datos['id_maquina']]);
            $pdo->commit();
            return $idMantenimiento;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
