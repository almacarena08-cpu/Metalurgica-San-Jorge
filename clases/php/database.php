<?php
$localConfigPath = __DIR__ . DIRECTORY_SEPARATOR . 'database.local.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
if (!is_array($localConfig)) {
    throw new UnexpectedValueException('La configuración de base de datos de la app debe devolver un arreglo.');
}

class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            global $localConfig;
            $host = self::environmentValue('METALGEST_DB_HOST', $localConfig['host'] ?? '127.0.0.1');
            $port = self::environmentValue('METALGEST_DB_PORT', $localConfig['port'] ?? '3306');
            $db = self::environmentValue('METALGEST_DB_NAME', $localConfig['database'] ?? 'metalurgica_san_jorge');
            $user = self::environmentValue('METALGEST_DB_USER', $localConfig['username'] ?? 'root');
            $pass = self::environmentValue('METALGEST_DB_PASSWORD', $localConfig['password'] ?? '');
            $charset = 'utf8mb4';
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$pdo = new PDO($dsn, $user, $pass, $options);
        }
        return self::$pdo;
    }

    private static function environmentValue($name, $default) {
        $value = getenv($name);
        return $value === false || $value === '' ? $default : $value;
    }
}
