<?php
require_once __DIR__ . '/../../config/Database.php';

class Article {



    // Transformer un titre en slug
    private static function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }

    // rendre un slug unique
    private static function makeUniqueSlug($slug, $excludeId = null) {
        $conn = Database::getInstance();

        // On vérifie si ce slug existe déjà
        $sql = "SELECT id FROM Articles WHERE slug = :slug";
        if ($excludeId) {
            $sql .= " AND id != :id";
        }

        $stmt = $conn->prepare($sql);
        $params = ['slug' => $slug];
        if ($excludeId) $params['id'] = $excludeId;

        $stmt->execute($params);

        if ($stmt->fetch()) {
            return $slug . '-' . time(); //  "zzz" devient "zzz-1704987654"
        }

        return $slug;
    }


    public static function create($titre, $contenu, $statut, $userId, $image = null) {
        $conn = Database::getInstance();


        $baseSlug = self::slugify($titre);

        $slug = self::makeUniqueSlug($baseSlug);

        $sql = "INSERT INTO Articles (titre, slug, contenu, statut, utilisateur_id, image_une, date_creation) 
                VALUES (:titre, :slug, :contenu, :statut, :uid, :img, NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'titre' => $titre,
            'slug' => $slug,
            'contenu' => $contenu,
            'statut' => $statut,
            'uid' => $userId,
            'img' => $image
        ]);

        return $conn->lastInsertId();
    }

    // Modifier un article
    public static function update($id, $titre, $contenu, $statut, $image = null) {
        $conn = Database::getInstance();


        $baseSlug = self::slugify($titre);
        $slug = self::makeUniqueSlug($baseSlug, $id);

        $sql = "UPDATE Articles SET titre = :titre, slug = :slug, contenu = :contenu, statut = :statut";
        $params = [
            'titre' => $titre,
            'slug' => $slug,
            'contenu' => $contenu,
            'statut' => $statut,
            'id' => $id
        ];

        if ($image) {
            $sql .= ", image_une = :img";
            $params['img'] = $image;
        }

        $sql .= " WHERE id = :id";
        $stmt = $conn->prepare($sql);
        return $stmt->execute($params);
    }

    // Récupérer un article
    public static function getBySlug($slug) {
        $conn = Database::getInstance();
        $sql = "SELECT a.*, u.nom_utilisateur 
                FROM Articles a 
                LEFT JOIN Utilisateurs u ON a.utilisateur_id = u.id 
                WHERE a.slug = :slug";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }



    public static function getById($id) {
        $conn = Database::getInstance();
        $sql = "SELECT a.*, u.nom_utilisateur 
                FROM Articles a 
                LEFT JOIN Utilisateurs u ON a.utilisateur_id = u.id 
                WHERE a.id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function delete($id) {
        $conn = Database::getInstance();

        $conn->prepare("DELETE FROM Article_Tag WHERE article_id = ?")->execute([$id]);
        $conn->prepare("DELETE FROM Commentaires WHERE article_id = ?")->execute([$id]);

        $stmt = $conn->prepare("DELETE FROM Articles WHERE id = ?");
        return $stmt->execute([$id]);
    }



    // Récupérer les articles
    public static function getByStatus($statut, $userId = null, $userRole = null) {
        $conn = Database::getInstance();
        $sql = "SELECT a.*, u.nom_utilisateur 
                FROM Articles a 
                LEFT JOIN Utilisateurs u ON a.utilisateur_id = u.id 
                WHERE a.statut = :statut";

        $params = ['statut' => $statut];

        if ($userRole === 'Contributeur') {
            $sql .= " AND a.utilisateur_id = :uid";
            $params['uid'] = $userId;
        }

        $sql .= " ORDER BY a.date_creation DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function getAllPublished() {
        $conn = Database::getInstance();
        $sql = "SELECT a.*, u.nom_utilisateur 
                FROM Articles a 
                LEFT JOIN Utilisateurs u ON a.utilisateur_id = u.id 
                WHERE a.statut = 'Publié' 
                ORDER BY a.date_creation DESC";

        return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function updateStatus($id, $statut) {
        $conn = Database::getInstance();
        $sql = "UPDATE Articles SET statut = :statut WHERE id = :id";
        $stmt = $conn->prepare($sql);
        return $stmt->execute(['statut' => $statut, 'id' => $id]);
    }
}