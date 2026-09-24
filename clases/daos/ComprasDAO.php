<?php
require_once __DIR__ . '/BaseDAO.php';

class ComprasDAO extends BaseDAO {
    public function listarProveedores() {
        return $this->db()->query(
            'SELECT id_proveedor, razon_social, cuit, telefono, email
             FROM proveedor
             ORDER BY razon_social'
        )->fetchAll();
    }

    public function listarCompras() {
        $stmt = $this->db()->query(
            'SELECT c.id_compra, c.fecha, c.total, c.estado,
                    p.razon_social AS proveedor,
                    GROUP_CONCAT(CONCAT(m.nombre, " x ", dc.cantidad, " ", m.unidad) SEPARATOR " | ") AS detalle
             FROM compra c
             INNER JOIN proveedor p ON p.id_proveedor = c.id_proveedor
             LEFT JOIN detalle_compra dc ON dc.id_compra = c.id_compra
             LEFT JOIN material m ON m.id_material = dc.id_material
             GROUP BY c.id_compra, c.fecha, c.total, c.estado, p.razon_social
             ORDER BY c.id_compra DESC'
        );
        return $stmt->fetchAll();
    }

    public function crearProveedor(array $datos) {
        $stmt = $this->db()->prepare(
            'INSERT INTO proveedor (razon_social, cuit, telefono, email)
             VALUES (:razon_social, :cuit, :telefono, :email)'
        );
        $stmt->execute([
            ':razon_social' => $datos['razon_social'],
            ':cuit' => $datos['cuit'] ?? '',
            ':telefono' => $datos['telefono'] ?? '',
            ':email' => $datos['email'] ?? ''
        ]);
        return (int)$this->db()->lastInsertId();
    }

    public function crearCompra(array $datos) {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $cantidad = (float)$datos['cantidad'];
            $precio = (float)$datos['precio'];
            $total = $cantidad * $precio;
            $compra = $pdo->prepare(
                'INSERT INTO compra (id_proveedor, fecha, total, estado)
                 VALUES (:id_proveedor, :fecha, :total, :estado)'
            );
            $compra->execute([
                ':id_proveedor' => (int)$datos['id_proveedor'],
                ':fecha' => $datos['fecha'] ?? date('Y-m-d'),
                ':total' => $total,
                ':estado' => $datos['estado'] ?? 'Solicitada'
            ]);
            $idCompra = (int)$pdo->lastInsertId();

            $detalle = $pdo->prepare(
                'INSERT INTO detalle_compra (id_compra, id_material, cantidad, precio)
                 VALUES (:id_compra, :id_material, :cantidad, :precio)'
            );
            $detalle->execute([
                ':id_compra' => $idCompra,
                ':id_material' => (int)$datos['id_material'],
                ':cantidad' => $cantidad,
                ':precio' => $precio
            ]);

            if (($datos['estado'] ?? 'Solicitada') === 'Recibida') {
                $stock = $pdo->prepare('UPDATE material SET stock = stock + :cantidad WHERE id_material = :id_material');
                $stock->execute([':cantidad' => $cantidad, ':id_material' => (int)$datos['id_material']]);
            }
            $pdo->commit();
            return $idCompra;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
