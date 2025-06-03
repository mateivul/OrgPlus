<?php

// No namespace or use statements

class Database
{
    private static ?PDO $instance = null; // Va ține instanța conexiunii PDO (Singleton Pattern)
    private string $host;
    private string $dbName;
    private string $username;
    private string $password;
    private string $charset;

    // Constructor privat pentru a asigura o singură instanță (Singleton)
    private function __construct()
    {
        // În practică, aceste credențiale ar trebui să vină dintr-un fișier .env sau o configurație sigură
        // Pentru moment, le punem aici, similar cu ce aveai tu.
        $this->host = 'localhost';
        $this->dbName = 'org_plus';
        $this->username = 'root';
        $this->password = '';
        $this->charset = 'utf8mb4'; // Folosim utf8mb4 pentru suport emoji și caractere extinse
    }

    // Metoda statică pentru a obține instanța conexiunii
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $db = new self(); // Crează instanța clasei (apelând constructorul privat)
            try {
                $dsn = "mysql:host={$db->host};dbname={$db->dbName};charset={$db->charset}";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Aruncă excepții pentru erori
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Rezultatele sub formă de array asociativ
                    PDO::ATTR_EMULATE_PREPARES => false, // Dezactivăm emularea prepared statements (preferăm native)
                ];
                self::$instance = new PDO($dsn, $db->username, $db->password, $options);
            } catch (PDOException $e) {
                // În producție, ai loga eroarea, nu ai afișa-o direct utilizatorului
                die('Connection failed: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }

    // Previno clonarea instanței
    private function __clone() {}

    // Previno deserializarea instanței
    public function __wakeup() {}
}

Database::getConnection();
