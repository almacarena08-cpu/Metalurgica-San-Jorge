<?php
require_once __DIR__ . '/database.php';

/**
 * Función de conveniencia para obtener la conexión PDO.
 * Uso: $pdo = conexion();
 */
function conexion() {
    return Database::getConnection();
}
