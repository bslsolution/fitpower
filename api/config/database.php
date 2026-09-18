<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        $enDocker = file_exists('/.dockerenv');
        $this->host = getenv('DB_HOST') ?: ($enDocker ? 'db' : 'localhost');
        $this->db_name = getenv('DB_NAME') ?: 'mi_base_de_datos';
        $this->username = getenv('DB_USER') ?: 'usuario';
        $pass = getenv('DB_PASS');
        $this->password = ($pass !== false && $pass !== '') ? $pass : 'password';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            throw new Exception($e->getMessage());
        }
        return $this->conn;
    }
}