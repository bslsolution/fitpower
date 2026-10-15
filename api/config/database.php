<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $reintentos = 3;
    public $conn;

    public function __construct() {
        $enDocker = file_exists('/.dockerenv') || file_exists('/run/.containerenv');
        $this->host = $this->valorEnv('DB_HOST', $enDocker ? 'db' : 'localhost');
        if ($enDocker && in_array($this->host, ['localhost', '127.0.0.1'], true)) {
            $this->host = 'db';
        }
        $this->db_name = $this->valorEnv('DB_NAME', 'mi_base_de_datos');
        $this->username = $this->valorEnv('DB_USER', 'usuario');
        $this->password = $this->valorEnv('DB_PASS', 'password');
        $this->reintentos = $enDocker ? 20 : 3;
    }

    public function getConnection() {
        $this->conn = null;
        $ultimo = null;
        for ($i = 0; $i < $this->reintentos; $i++) {
            try {
                $this->conn = new PDO(
                    'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4',
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return $this->conn;
            } catch (PDOException $e) {
                $ultimo = $e;
                usleep($this->reintentos > 5 ? 1000000 : 200000);
            }
        }
        throw new Exception($ultimo ? $ultimo->getMessage() : 'No se pudo conectar a la base');
    }

    private function valorEnv(string $clave, string $porDefecto): string {
        foreach ([$clave] as $k) {
            if (isset($_ENV[$k]) && $_ENV[$k] !== '') {
                return (string) $_ENV[$k];
            }
            if (isset($_SERVER[$k]) && is_string($_SERVER[$k]) && $_SERVER[$k] !== '') {
                return $_SERVER[$k];
            }
            $v = getenv($k);
            if ($v !== false && $v !== '') {
                return $v;
            }
        }
        return $porDefecto;
    }
}