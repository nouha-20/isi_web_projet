<?php
require_once __DIR__ . '/../../config/Database.php';

class Commentaire {

    public static function getByArticle($article_id) {
        $conn = Database::getInstance();
        $stmt = $conn->prepare("
            SELECT * FROM Commentaires 
            WHERE article_id = :id 
            AND statut = 'Approuvé'
            ORDER BY date_commentaire ASC
        ");
        $stmt->execute(['id' => $article_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($article_id, $nom, $email, $contenu) {
        $conn = Database::getInstance();
        $stmt = $conn->prepare("
            INSERT INTO Commentaires 
            (article_id, nom_auteur, email_auteur, contenu, statut)
            VALUES (:article_id, :nom, :email, :contenu, 'En attente')
        ");
        $stmt->execute([
            'article_id' => $article_id,
            'nom' => $nom,
            'email' => $email,
            'contenu' => $contenu
        ]);

        // Récupérer l'ID du dernier commentaire ajouté
        $comment_id = $conn->lastInsertId();

        // Envoyer la notification à l’admin
        self::notifyAdmin($comment_id);

        return $comment_id;
    }

    public static function notifyAdmin($comment_id) {
        $conn = Database::getInstance();
        $stmt = $conn->prepare("SELECT * FROM Commentaires WHERE id = :id");
        $stmt->execute(['id' => $comment_id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($comment) {
            $admin_email = "nouha.meqdad19@gmail.com"; 
            $subject = "Nouveau commentaire en attente";
            $message = "Un nouveau commentaire a été posté sur l'article ID {$comment['article_id']}.\n\n";
            $message .= "Auteur : {$comment['nom_auteur']}\n";
            $message .= "Email : {$comment['email_auteur']}\n";
            $message .= "Contenu : {$comment['contenu']}\n\n";
            $message .= "Veuillez vous connecter au tableau d'administration pour le modérer.";

            mail($admin_email, $subject, $message);
        }
    }

    public static function getPending() {
        $conn = Database::getInstance();
        $stmt = $conn->query("
            SELECT * FROM Commentaires 
            WHERE statut = 'En attente'
            ORDER BY date_commentaire DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateStatus($id, $statut) {
        $conn = Database::getInstance();
        $stmt = $conn->prepare("
            UPDATE Commentaires 
            SET statut = :statut 
            WHERE id = :id
        ");
        return $stmt->execute([
            'statut' => $statut,
            'id' => $id
        ]);
    }

    public static function delete($id) {
        $conn = Database::getInstance();
        $stmt = $conn->prepare("DELETE FROM Commentaires WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
