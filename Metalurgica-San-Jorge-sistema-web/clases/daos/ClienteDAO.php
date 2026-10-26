<?php
require_once __DIR__ . '/BaseDAO.php';

class ClienteDAO extends BaseDAO {
    public function listar() {
        return $this->db()->query(
            'SELECT id_cliente, razon_social, cuit, email, direccion
             FROM cliente
             WHERE eliminado = 0
             ORDER BY razon_social'
        )->fetchAll();
    }

    /**
     * Banea a un cliente: bloquea su acceso y lo quita del listado, pero conserva
     * pedidos, facturas y chats. Se mantiene el email para que no pueda volver a
     * registrarse con la misma dirección.
     * Devuelve false si el cliente no existe o ya estaba baneado/eliminado.
     */
    public function banear(int $idCliente) {
        $stmt = $this->db()->prepare(
            'UPDATE cliente
             SET activo = 0, eliminado = 1, contrasena = NULL,
                 token_verificacion = NULL, token_verificacion_vence = NULL, token_proposito = NULL,
                 token_cambio_contrasena = NULL, token_cambio_contrasena_vence = NULL
             WHERE id_cliente = :id_cliente AND eliminado = 0'
        );
        $stmt->execute([':id_cliente' => $idCliente]);
        return $stmt->rowCount() > 0;
    }

    public function crear(array $datos) {
        $stmt = $this->db()->prepare(
            'INSERT INTO cliente (razon_social, cuit, email, direccion)
             VALUES (:razon_social, :cuit, :email, :direccion)'
        );
        $stmt->execute([
            ':razon_social' => $datos['razon_social'],
            ':cuit' => $datos['cuit'] ?? '',
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
                 email = :email,
                 direccion = :direccion
             WHERE id_cliente = :id_cliente'
        );
        $stmt->execute([
            ':id_cliente' => (int)$datos['id_cliente'],
            ':razon_social' => $datos['razon_social'],
            ':cuit' => $datos['cuit'] ?? '',
            ':email' => $datos['email'] ?? '',
            ':direccion' => $datos['direccion'] ?? ''
        ]);
    }

    public function eliminar(int $idCliente) {
        $stmt = $this->db()->prepare('DELETE FROM cliente WHERE id_cliente = :id_cliente');
        $stmt->execute([':id_cliente' => $idCliente]);
    }
}
