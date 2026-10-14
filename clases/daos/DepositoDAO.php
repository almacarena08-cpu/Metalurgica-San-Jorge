<?php
require_once __DIR__ . '/BaseDAO.php';

class DepositoDAO extends BaseDAO {
    public function listarMateriales() {
        return $this->db()->query(
            'SELECT id_material, nombre, tipo, unidad, espesor, stock, stock_minimo
             FROM material
             ORDER BY nombre'
        )->fetchAll();
    }

    public function listarMovimientos() {
        $stmt = $this->db()->query(
            'SELECT mm.id_movimiento, mm.id_material, mm.tipo, mm.cantidad, mm.motivo,
                    mm.fecha, m.nombre AS material, m.unidad
             FROM material_movimiento mm
             INNER JOIN material m ON m.id_material = mm.id_material
             ORDER BY mm.id_movimiento DESC
             LIMIT 80'
        );
        return $stmt->fetchAll();
    }

    public function registrarMovimiento(array $datos) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $tipo = $datos['tipo'];
            $cantidad = (float)$datos['cantidad'];
            $signo = $tipo === 'Salida' ? -1 : 1;
            $idMaterial = (int)$datos['id_material'];

            $material = $pdo->prepare('SELECT stock FROM material WHERE id_material = :id_material FOR UPDATE');
            $material->execute([':id_material' => $idMaterial]);
            $row = $material->fetch();
            if (!$row) {
                throw new InvalidArgumentException('El material no existe');
            }
            $nuevoStock = (float)$row['stock'] + ($cantidad * $signo);
            if ($nuevoStock < 0) {
                throw new InvalidArgumentException('El stock no puede quedar negativo');
            }

            $movimiento = $pdo->prepare(
                'INSERT INTO material_movimiento (id_material, tipo, cantidad, motivo, fecha, id_usuario)
                 VALUES (:id_material, :tipo, :cantidad, :motivo, NOW(), :id_usuario)'
            );
            $movimiento->execute([
                ':id_material' => $idMaterial,
                ':tipo' => $tipo,
                ':cantidad' => $cantidad,
                ':motivo' => $datos['motivo'] ?? '',
                ':id_usuario' => (int)($datos['id_usuario'] ?? 1)
            ]);

            $stock = $pdo->prepare('UPDATE material SET stock = :stock WHERE id_material = :id_material');
            $stock->execute([':stock' => $nuevoStock, ':id_material' => $idMaterial]);
            $pdo->commit();
            return (int)$pdo->lastInsertId();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
