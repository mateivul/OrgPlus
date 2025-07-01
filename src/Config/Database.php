<?php

class Database
{
    private static ?PDO $instance = null;
    private string $host;
    private string $dbName;
    private string $username;
    private string $password;
    private string $charset;

    private function __construct()
    {
        $this->host = 'localhost';
        $this->dbName = 'org_plus';
        $this->username = 'root';
        $this->password = '';
        $this->charset = 'utf8mb4';
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $db = new self();

            if (!empty($_ENV['TEST_DB'])) {
                $file = $_ENV['TEST_DB'];
                $dsn = "sqlite:file:$file";
            } else {
                $dsn = "mysql:host={$db->host};dbname={$db->dbName};charset={$db->charset}";
            }

            try {
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                self::$instance = new PDO($dsn, $db->username, $db->password, $options);
            } catch (PDOException $e) {
                die('Connection failed: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }

    private function __clone() {}

    public function __wakeup() {}
}

Database::getConnection();
