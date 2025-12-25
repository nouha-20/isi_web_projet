<?php
require_once __DIR__ . '/../../config/Database.php';

class Article {

    public static function getAllPublished() {
        $conn = Database::getInstance();
        $stmt = $conn->query("SELECT * FROM Articles WHERE statut='Publié' ORDER BY date_creation DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getById($id) {
        $conn = Database::getInstance();
        $stmt = $conn->prepare("SELECT * FROM Articles WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
