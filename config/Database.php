<?php
class Database {
    private static $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance() {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    "mysql:host=localhost;dbname=blog_db;charset=utf8mb4",
                    "root", // ton user MySQL
                    "",     // ton mot de passe
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e) {
                die("Erreur de connexion DB : " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
