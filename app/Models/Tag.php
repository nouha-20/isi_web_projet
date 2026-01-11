<?php
require_once __DIR__ . '/../../config/Database.php';

class Tag {

    private static function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }
    // Récupérer tous les tags
    public static function getAll() {
        $conn = Database::getInstance();
        $stmt = $conn->query("SELECT * FROM Tags ORDER BY nom_tag");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //  Récupérer les tags avec le nombre d'articles associés
    public static function getAllWithCount() {
        $conn = Database::getInstance();
        $sql = "SELECT t.id, t.nom_tag, COUNT(at.article_id) as count 
                FROM Tags t 
                LEFT JOIN Article_Tag at ON t.id = at.tag_id 
                GROUP BY t.id 
                ORDER BY t.nom_tag";
        return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Récupérer un tag par son ID
    public static function getById($id) {
        $conn = Database::getInstance();
        $stmt = $conn->prepare("SELECT * FROM Tags WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public static function create($nom) {
        $conn = Database::getInstance();
        $nom = trim($nom);
        $slug = self::slugify($nom);

        $check = $conn->prepare("SELECT id FROM Tags WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetch()) {
            return false;
        }

        $stmt = $conn->prepare("INSERT INTO Tags (nom_tag, slug) VALUES (:nom, :slug)");
        return $stmt->execute(['nom' => $nom, 'slug' => $slug]);
    }

    public static function update($id, $nom) {
        $conn = Database::getInstance();
        $nom = trim($nom);
        $slug = self::slugify($nom);

        $stmt = $conn->prepare("UPDATE Tags SET nom_tag = :nom, slug = :slug WHERE id = :id");
        return $stmt->execute(['nom' => $nom, 'slug' => $slug, 'id' => $id]);
    }

    // Supprimer un tag
    public static function delete($id) {
        $conn = Database::getInstance();
        // On supprime d'abord les liaisons dans la table
        $conn->prepare("DELETE FROM Article_Tag WHERE tag_id = ?")->execute([$id]);
        // Puis le tag lui-même
        $stmt = $conn->prepare("DELETE FROM Tags WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Assigner des tags à un article
    public static function assignTags($articleId, $tagIds) {
        $conn = Database::getInstance();

        $deleteStmt = $conn->prepare("DELETE FROM Article_Tag WHERE article_id = :id");
        $deleteStmt->execute(['id' => $articleId]);


        if (!empty($tagIds)) {
            $insertStmt = $conn->prepare("INSERT INTO Article_Tag (article_id, tag_id) VALUES (:aid, :tid)");
            foreach ($tagIds as $tagId) {
                $insertStmt->execute(['aid' => $articleId, 'tid' => $tagId]);
            }
        }
    }

    // Récupérer les tags d'un article
    public static function getTagsByArticle($articleId) {
        $conn = Database::getInstance();
        $sql = "SELECT t.* FROM Tags t 
                JOIN Article_Tag at ON t.id = at.tag_id 
                WHERE at.article_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function getIdsByArticle($articleId) {
        $conn = Database::getInstance();
        $stmt = $conn->prepare("SELECT tag_id FROM Article_Tag WHERE article_id = ?");
        $stmt->execute([$articleId]);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}