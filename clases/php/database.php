<?php
class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            $localConfigPath = __DIR__ . '/database.local.php';
            $localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
            if (!is_array($localConfig)) {
                throw new UnexpectedValueException('La configuración local de la base de datos debe devolver un arreglo.');
            }

            $environmentValue = static function (string $name, $fallback): string {
                $value = getenv($name);
                return $value === false || $value === '' ? (string) $fallback : $value;
            };

            $host = $environmentValue('METALGEST_DB_HOST', $localConfig['host'] ?? 'pma.torga.com.ar');
            $port = $environmentValue('METALGEST_DB_PORT', $localConfig['port'] ?? '3306');
            $db = $environmentValue('METALGEST_DB_NAME', $localConfig['database'] ?? 's33_metalurgica-san-jorge');
            $user = $environmentValue('METALGEST_DB_USER', $localConfig['username'] ?? 'u33_DXajMzXZ2y');
            $pass = $environmentValue('METALGEST_DB_PASSWORD', $localConfig['password'] ?? 'u.T^x.DxW3bmy83=Pu5qlNF5');
            $charset = 'utf8mb4';
            $portDsn = $port === '' ? '' : ";port=$port";
            $dsn = "mysql:host=$host$portDsn;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$pdo = new PDO($dsn, $user, $pass, $options);
        }
        return self::$pdo;
    }
}
