<?php
require_once __DIR__ . '/BaseDAO.php';

class CalidadDAO extends BaseDAO {
    public function listarControles() {
        $stmt = $this->db()->query(
            'SELECT cc.id_control, cc.id_orden, cc.id_usuario, cc.fecha, cc.resultado, cc.observaciones,
                    p.descripcion, p.material, c.razon_social
             FROM control_calidad cc
             INNER JOIN orden_trabajo ot ON ot.id_orden = cc.id_orden
             INNER JOIN pedido p ON p.id_pedido = ot.id_pedido
             INNER JOIN cliente c ON c.id_cliente = p.id_cliente
             ORDER BY cc.id_control DESC'
        );
        return $stmt->fetchAll();
    }

    public function registrar(array $datos) {
        $stmt = $this->db()->prepare(
            'INSERT INTO control_calidad (id_orden, id_usuario, fecha, resultado, observaciones)
             VALUES (:id_orden, :id_usuario, :fecha, :resultado, :observaciones)'
        );
        $stmt->execute([
            ':id_orden' => (int)$datos['id_orden'],
            ':id_usuario' => (int)($datos['id_usuario'] ?? 1),
            ':fecha' => $datos['fecha'] ?? date('Y-m-d'),
            ':resultado' => $datos['resultado'],
            ':observaciones' => $datos['observaciones'] ?? ''
        ]);
        return (int)$this->db()->lastInsertId();
    }
}
