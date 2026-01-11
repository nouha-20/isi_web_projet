<?php
require_once __DIR__ . '/../../config/Database.php';

class User {

    /**
     * Récupère un utilisateur et son rôle associé via son email.
     */
    public static function findByEmail($email) {
        $conn = Database::getInstance();

        $sql = "SELECT u.id, u.nom_utilisateur, u.email, u.mot_de_passe, 
                       GROUP_CONCAT(r.nom_role) as roles
                FROM Utilisateurs u
                LEFT JOIN Role_User ru ON u.id = ru.user_id
                LEFT JOIN Roles r ON ru.role_id = r.id
                WHERE u.email = :email
                GROUP BY u.id";

        $stmt = $conn->prepare($sql);
        $stmt->execute(['email' => $email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les utilisateurs avec leurs rôles concaténés.
     */
    public static function getAll() {
        $conn = Database::getInstance();

        $sql = "SELECT u.id, u.nom_utilisateur, u.email, 
                       GROUP_CONCAT(r.nom_role SEPARATOR ', ') as nom_role
                FROM Utilisateurs u
                LEFT JOIN Role_User ru ON u.id = ru.user_id
                LEFT JOIN Roles r ON ru.role_id = r.id
                GROUP BY u.id 
                ORDER BY u.nom_utilisateur";

        return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les rôles possibles.
     */
    public static function getAllRoles() {
        $conn = Database::getInstance();
        return $conn->query("SELECT * FROM Roles")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les IDs des rôles d'un utilisateur.
     */
    public static function getRolesIds($userId) {
        $conn = Database::getInstance();
        $stmt = $conn->prepare("SELECT role_id FROM Role_User WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Assigner les rôles à un utilisateur.
     */
    public static function setRoles($userId, $roleIds) {
        $conn = Database::getInstance();

        // Nettoyage des anciens rôles
        $del = $conn->prepare("DELETE FROM Role_User WHERE user_id = :uid");
        $del->execute(['uid' => $userId]);

        // Insertion des nouveaux rôles cochés
        if (!empty($roleIds) && is_array($roleIds)) {
            $sql = "INSERT INTO Role_User (user_id, role_id) VALUES (:uid, :rid)";
            $stmt = $conn->prepare($sql);
            foreach ($roleIds as $rid) {
                $stmt->execute(['uid' => $userId, 'rid' => $rid]);
            }
        }
    }

    /**
     * Supprimer un utilisateur et ses rôles associés.
     */
    public static function delete($id) {
        $conn = Database::getInstance();

        // On supprime d'abord les liaisons de rôle
        $conn->prepare("DELETE FROM Role_User WHERE user_id = ?")->execute([$id]);

        // Puis l'utilisateur lui-même
        $stmt = $conn->prepare("DELETE FROM Utilisateurs WHERE id = ?");
        return $stmt->execute([$id]);
    }
}