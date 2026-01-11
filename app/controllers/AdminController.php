<?php
require_once __DIR__ . '/../models/Commentaire.php';
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../Models/Tag.php';

class AdminController {
    private $twig;

    public function __construct($twig) {
        $this->twig = $twig;
    }

    public function dashboard() {
        // On récupère qui est connecté
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];


        $pendingComments = Commentaire::getPending();
        $nbPending = count($pendingComments);


        // Un contributeur ne verra que les siens. Un Admin verra tout.
        $brouillons = Article::getByStatus('Brouillon', $userId, $userRole);
        $nbBrouillons = count($brouillons);

        $publies = Article::getByStatus('Publié', $userId, $userRole);
        $tags = Tag::getAllWithCount();

        echo $this->twig->render('admin/dashboard.twig', [
            'nb_pending_comments' => $nbPending,
            'brouillons' => $brouillons,
            'nb_brouillons' => $nbBrouillons,
            'publies' => $publies,
            'tags' => $tags
        ]);
    }

    public function publishArticle() {
        if ($_SESSION['user_role'] === 'Contributeur') {
            die("Accès refusé. Un contributeur ne peut pas publier.");
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            Article::updateStatus((int)$_POST['id'], 'Publié');
        }

        header('Location: index.php?admin=dashboard');
        exit;
    }
    public function editArticle() {
        if (!isset($_GET['id'])) { header('Location: index.php?admin=dashboard'); exit; }

        $id = (int)$_GET['id'];
        $article = Article::getById($id);
        if (!$article) { header('Location: index.php?admin=dashboard'); exit; }

        // Récupérer les tags dispos et les tags déjà cochés pour cet article
        $allTags = Tag::getAll();
        $currentTagIds = Tag::getIdsByArticle($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre = trim($_POST['titre']);
            $contenu = trim($_POST['contenu']);
            $statut = $_POST['statut'];
            $tags = $_POST['tags'] ?? [];

            $imageName = $this->handleUpload();

            if (!empty($titre) && !empty($contenu)) {
                Article::update($id, $titre, $contenu, $statut, $imageName);
                Tag::assignTags($id, $tags);

                header('Location: index.php?admin=dashboard');
                exit;
            }
        }

        echo $this->twig->render('admin/article_form.twig', [
            'article' => $article,
            'is_edit' => true,
            'tags' => $allTags,
            'current_tags' => $currentTagIds
        ]);
    }

    public function deleteArticle() {
        if ($_SESSION['user_role'] === 'Contributeur') {
            die("Accès refusé.");
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
            Article::delete((int)$_POST['id']);
        }

        header('Location: index.php?admin=dashboard');
        exit;
    }

    // Affiche la liste des commentaires
    public function comments() {
        $commentaires = Commentaire::getPending();
        echo $this->twig->render('admin/commentaires.twig', [
            'commentaires' => $commentaires
        ]);
    }

    // Actions de modération
    public function moderateAction() {
        if (!isset($_POST['id'], $_POST['action'])) return;

        $id = (int) $_POST['id'];

        if ($_POST['action'] === 'approve') {
            Commentaire::updateStatus($id, 'Approuvé');
        } elseif ($_POST['action'] === 'reject') { 
            Commentaire::updateStatus($id, 'Rejeté');
        } elseif ($_POST['action'] === 'delete') {
            Commentaire::delete($id);
        }

        // Retour à la liste des commentaires
        header('Location: index.php?admin=comments');
        exit;
    }
    public function addArticle() {
        $allTags = Tag::getAll();
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $titre = trim($_POST['titre']);
            $contenu = trim($_POST['contenu']);
            $statut = $_POST['statut'];
            $userId = $_SESSION['user_id'];
            $tags = $_POST['tags'] ?? [];

            $imageName = $this->handleUpload();

            if (!empty($titre) && !empty($contenu)) {
                $newArticleId = Article::create($titre, $contenu, $statut, $userId, $imageName);
                if ($newArticleId) {
                    Tag::assignTags($newArticleId, $tags);
                }
                header('Location: index.php?admin=dashboard');
                exit;
            } else {
                // Si les champs sont vides, on crée un message d'erreur
                $error = "Le titre et le contenu sont obligatoires.";
            }
        }

        echo $this->twig->render('admin/article_form.twig', [
            'tags' => $allTags,
            'error' => $error // On passe l'erreur à la vue
        ]);
    }
// Fonction utilitaire pour gérer l'upload
    private function handleUpload() {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/uploads/';
            $tmpName = $_FILES['image']['tmp_name'];
            $name = basename($_FILES['image']['name']);

            // On sécurise le nom du fichier pour éviter les écrasements
            $fileName = uniqid() . '_' . $name;

            if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                return $fileName;
            }
        }
        return null;
    }
    // Afficher la liste des utilisateurs
    public function users() {
        $users = User::getAll();
        $roles = User::getAllRoles();

        echo $this->twig->render('admin/users.twig', [
            'users' => $users,
            'roles' => $roles
        ]);
    }

    // Mettre à jour le rôle
    public function updateUserRole() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)$_POST['user_id'];
            $roleId = (int)$_POST['role_id'];

            // Sécurité : On empêche de modifier son propre rôle pour ne pas se bloquer
            if ($userId === $_SESSION['user_id']) {
                // Tu pourrais ajouter un message d'erreur ici
                header('Location: index.php?admin=users');
                exit;
            }

            User::updateRole($userId, $roleId);
        }
        header('Location: index.php?admin=users');
        exit;
    }

    // Supprimer un utilisateur
    public function deleteUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)$_POST['user_id'];

            // Sécurité : On ne peut pas se supprimer soi-même
            if ($userId === $_SESSION['user_id']) {
                header('Location: index.php?admin=users');
                exit;
            }

            User::delete($userId);
        }
        header('Location: index.php?admin=users');
        exit;
    }
    // Modifier un utilisateur
    public function editUser() {
        if ($_SESSION['user_role'] !== 'Administrateur') {
            header('Location: index.php?admin=dashboard'); exit;
        }

        if (!isset($_GET['id'])) { header('Location: index.php?admin=users'); exit; }
        $userId = (int)$_GET['id'];

        $conn = \Database::getInstance();
        $stmt = $conn->prepare("SELECT * FROM Utilisateurs WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roles = $_POST['roles'] ?? []; // Tableau des IDs cochés

            if ($userId != $_SESSION['user_id']) {
                User::setRoles($userId, $roles);
            }
            header('Location: index.php?admin=users');
            exit;
        }

        // pour la vue
        $allRoles = User::getAllRoles();
        $userRoles = User::getRolesIds($userId);

        echo $this->twig->render('admin/user_form.twig', [
            'user' => $user,
            'all_roles' => $allRoles,
            'user_roles' => $userRoles
        ]);
    }
    // Afficher la page de gestion des tags
    public function tags() {
        if ($_SESSION['user_role'] !== 'Administrateur') die("Accès refusé");

        $tags = Tag::getAllWithCount();
        echo $this->twig->render('admin/tags.twig', ['tags' => $tags]);
    }

// Ajouter ou Modifier un tag
    public function saveTag() {
        if ($_SESSION['user_role'] !== 'Administrateur') die("Accès refusé");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = $_POST['nom_tag'];
            $id = $_POST['id'] ?? null;

            if ($id) {
                Tag::update($id, $nom); // Modification
            } else {
                Tag::create($nom); // Création
            }
        }
        header('Location: index.php?admin=tags');
        exit;
    }

    // Supprimer un tag
    public function deleteTag() {
        if ($_SESSION['user_role'] !== 'Administrateur') die("Accès refusé");

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Tag::delete($_POST['id']);
        }
        header('Location: index.php?admin=tags');
        exit;
    }
}