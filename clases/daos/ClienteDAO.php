<?php
require_once __DIR__ . '/BaseDAO.php';

class ClienteDAO extends BaseDAO {
    public function listar() {
        return $this->db()->query(
            'SELECT id_cliente, razon_social, cuit, telefono, email, direccion
             FROM cliente
             ORDER BY razon_social'
        )->fetchAll();
    }

    public function crear(array $datos) {
        $stmt = $this->db()->prepare(
            'INSERT INTO cliente (razon_social, cuit, telefono, email, direccion)
             VALUES (:razon_social, :cuit, :telefono, :email, :direccion)'
        );
        $stmt->execute([
            ':razon_social' => $datos['razon_social'],
            ':cuit' => $datos['cuit'] ?? '',
            ':telefono' => $datos['telefono'] ?? '',
            ':email' => $datos['email'] ?? '',
            ':direccion' => $datos['direccion'] ?? ''
        ]);
        return (int)$this->db()->lastInsertId();
    }

    public function actualizar(array $datos) {
        $stmt = $this->db()->prepare(
            'UPDATE cliente
             SET razon_social = :razon_social,
                 cuit = :cuit,
                 telefono = :telefono,
                 email = :email,
                 direccion = :direccion
             WHERE id_cliente = :id_cliente'
        );
        $stmt->execute([
            ':id_cliente' => (int)$datos['id_cliente'],
            ':razon_social' => $datos['razon_social'],
            ':cuit' => $datos['cuit'] ?? '',
            ':telefono' => $datos['telefono'] ?? '',
            ':email' => $datos['email'] ?? '',
            ':direccion' => $datos['direccion'] ?? ''
        ]);
    }

    public function eliminar(int $idCliente) {
        $stmt = $this->db()->prepare('DELETE FROM cliente WHERE id_cliente = :id_cliente');
        $stmt->execute([':id_cliente' => $idCliente]);
    }
}
