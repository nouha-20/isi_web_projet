<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/controllers/BlogController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

// Configuration Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../app/views');
$twig = new \Twig\Environment($loader, ['cache' => false]);
$twig->addGlobal('session', $_SESSION);

$blogController = new BlogController($twig);
$authController = new AuthController($twig);
$adminController = new AdminController($twig);

// Gestion des Actions (Login / Logout / Commentaires / Inconnue)
if (isset($_GET['action'])) {

    // Login
    if ($_GET['action'] === 'login') {
        $authController->login();
        exit;
    }
    // Logout
    elseif ($_GET['action'] === 'logout') {
        $authController->logout();
        exit;
    }
    //  Ajout du commentaire
    elseif ($_GET['action'] === 'add_comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $blogController->addComment($_POST);
        exit;
    }
    //  404
    else {
        $blogController->notFound();
        exit;
    }
}

//Gestion de l'Admin
elseif (isset($_GET['admin'])) {

    //On vérifie les rôles (Admin, Éditeur, Contributeur)
    $authorized_roles = ['Administrateur', 'Éditeur', 'Contributeur'];

    //Récupération des rôles stockés en session
    $user_roles = isset($_SESSION['user_roles']) ? $_SESSION['user_roles'] : [];

    $has_access = false;

    if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], $authorized_roles)) {
        $has_access = true;
    }

    if (!$has_access) {
        header('Location: index.php?action=login');
        exit;
    }

    // Routes Admin
    if ($_GET['admin'] === 'comments') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $adminController->moderateAction();
        } else {
            $adminController->comments();
        }
    }
    elseif ($_GET['admin'] === 'dashboard') {
        $adminController->dashboard();
    }
    elseif ($_GET['admin'] === 'add_article') {
        $adminController->addArticle();
    }
    elseif ($_GET['admin'] === 'publish_article') {
        $adminController->publishArticle();
    }
    elseif ($_GET['admin'] === 'edit_article') {
        $adminController->editArticle();
    }
    elseif ($_GET['admin'] === 'delete_article') {
        $adminController->deleteArticle();
    }
    elseif ($_GET['admin'] === 'users') {
        $adminController->users();
    }
    elseif ($_GET['admin'] === 'update_role') {
        $adminController->updateUserRole();
    }
    elseif ($_GET['admin'] === 'edit_user') {
        $adminController->editUser();
    }
    elseif ($_GET['admin'] === 'delete_user') {
        $adminController->deleteUser();
    }
    // GESTION DES TAGS
    elseif ($_GET['admin'] === 'tags') {
        $adminController->tags();
    }
    elseif ($_GET['admin'] === 'save_tag') {
        $adminController->saveTag();
    }
    elseif ($_GET['admin'] === 'delete_tag') {
        $adminController->deleteTag();
    }

    // Route inconnue dans l'admin -> 404
    else {
        $blogController->notFound();
        exit;
    }
}

// Affichage via SLUG
elseif (isset($_GET['slug'])) {
    $blogController->articleBySlug($_GET['slug']);
}

//  Affichage via ID
elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $blogController->articleDetail((int) $_GET['id']);
}
//  Page d'accueil
else {
    if (empty($_GET)) {
        $blogController->home();
    } else {
        $blogController->notFound();
    }
}