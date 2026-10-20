<?php
require_once __DIR__ . '/../php/database.php';

abstract class BaseDAO {
    protected function db() {
        return Database::getConnection();
    }

    protected function hasColumn(string $table, string $column): bool {
        $stmt = $this->db()->prepare(
            'SELECT 1
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = :table_name
               AND column_name = :column_name'
        );
        $stmt->execute([
            ':table_name' => $table,
            ':column_name' => $column
        ]);
        return $stmt->fetchColumn() !== false;
    }
}
