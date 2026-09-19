<?php
require_once __DIR__ . '/BaseDAO.php';

class PreferenciasUsuarioDAO extends BaseDAO {
    public function __construct() {
        $this->db()->exec(
            'CREATE TABLE IF NOT EXISTS preferencias_usuario (
                id_usuario INT PRIMARY KEY,
                modo_oscuro TINYINT(1) NOT NULL DEFAULT 0,
                tamano_texto INT NOT NULL DEFAULT 12,
                FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
            )'
        );
    }

    public function obtener($idUsuario) {
        $stmt = $this->db()->prepare(
            'SELECT modo_oscuro, tamano_texto FROM preferencias_usuario WHERE id_usuario = :id_usuario'
        );
        $stmt->execute([':id_usuario' => (int)$idUsuario]);
        $preferencias = $stmt->fetch();
        return $preferencias ?: ['modo_oscuro' => 0, 'tamano_texto' => 12];
    }

    public function guardar(array $datos) {
        $stmt = $this->db()->prepare(
            'INSERT INTO preferencias_usuario (id_usuario, modo_oscuro, tamano_texto)
             VALUES (:id_usuario, :modo_oscuro, :tamano_texto)
             ON DUPLICATE KEY UPDATE modo_oscuro = VALUES(modo_oscuro), tamano_texto = VALUES(tamano_texto)'
        );
        $stmt->execute([
            ':id_usuario' => (int)$datos['id_usuario'],
            ':modo_oscuro' => (int)$datos['modo_oscuro'],
            ':tamano_texto' => (int)$datos['tamano_texto']
        ]);
    }
}
