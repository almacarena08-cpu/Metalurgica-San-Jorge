<?php
require_once __DIR__ . '/../php/database.php';

abstract class BaseDAO {
    protected function db() {
        return Database::getConnection();
    }
}
