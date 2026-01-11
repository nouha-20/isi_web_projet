<?php
require_once __DIR__ . '/config/Database.php';

$password_clair = "vttadmin";

$hash = password_hash($password_clair, PASSWORD_DEFAULT);

echo "Nouveau hash généré : " . $hash . "<br>";

try {
    $conn = Database::getInstance();

    $stmt = $conn->prepare("UPDATE Utilisateurs SET mot_de_passe = :hash ");
    $stmt->execute(['hash' => $hash]);

    echo " Succès mots de passe init à : <b>$password_clair</b>";

} catch (PDOException $e) {
    echo " Erreur SQL : " . $e->getMessage();
}