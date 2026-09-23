<?php
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/Libro.php';

class LibroDAO {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function create(Libro $libro) {
        $stmt = $this->pdo->prepare('INSERT INTO libros (nombre, cantidad) VALUES (:nombre, :cantidad)');
        $stmt->execute([':nombre' => $libro->nombre, ':cantidad' => $libro->cantidad]);
        $libro->id = (int)$this->pdo->lastInsertId();
        return $libro;
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare('SELECT id, nombre, cantidad FROM libros WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if ($row) {
            return new Libro($row['nombre'], (int)$row['cantidad'], (int)$row['id']);
        }
        return null;
    }

    public function getAll() {
        $stmt = $this->pdo->query('SELECT id, nombre, cantidad FROM libros ORDER BY id DESC');
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[] = new Libro($row['nombre'], (int)$row['cantidad'], (int)$row['id']);
        }
        return $result;
    }

    public function update(Libro $libro) {
        $stmt = $this->pdo->prepare('UPDATE libros SET nombre = :nombre, cantidad = :cantidad WHERE id = :id');
        return $stmt->execute([':nombre' => $libro->nombre, ':cantidad' => $libro->cantidad, ':id' => $libro->id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare('DELETE FROM libros WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    // Solicitar libros por nombre: resta cantidad si hay suficiente, devuelve Libro actualizado o false
    public function requestByName($nombre, $cantidad) {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT id, nombre, cantidad FROM libros WHERE nombre = :nombre FOR UPDATE');
            $stmt->execute([':nombre' => $nombre]);
            $row = $stmt->fetch();
            if (!$row) {
                $this->pdo->rollBack();
                return false;
            }
            $available = (int)$row['cantidad'];
            if ($available < $cantidad) {
                $this->pdo->rollBack();
                return false;
            }
            $new = $available - $cantidad;
            $up = $this->pdo->prepare('UPDATE libros SET cantidad = :cantidad WHERE id = :id');
            $up->execute([':cantidad' => $new, ':id' => $row['id']]);
            $this->pdo->commit();
            return new Libro($row['nombre'], $new, (int)$row['id']);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
