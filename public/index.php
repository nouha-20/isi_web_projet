<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Autoload de Composer
require_once __DIR__ . '/../app/controllers/BlogController.php';

// Configuration de Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../app/views');
$twig = new \Twig\Environment($loader, [
    'cache' => false, // désactiver le cache pour le dev
]);

$controller = new BlogController($twig);

// Vérifier si une action admin est demandée
if (isset($_GET['admin']) && $_GET['admin'] === 'comments') {
    // Si une action de modération est soumise
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
        $id = (int) $_POST['id'];
        $action = $_POST['action'];
        $controller->moderateComment($id, $action);
    } else {
        // Affiche la liste des commentaires en attente
        $controller->pendingComments();
    }
}
// Vérifier si un formulaire de commentaire est soumis
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $controller->addComment($_POST);
}
// Vérifier si un ID d'article est passé dans l'URL
elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $controller->articleDetail((int) $_GET['id']);
}
// Sinon, afficher la page d'accueil
else {
    $controller->home();
}
