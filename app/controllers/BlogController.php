<?php
require_once __DIR__ . '/../../app/models/Article.php';
require_once __DIR__ . '/../../app/models/Commentaire.php';

class BlogController {
    private $twig;

    public function __construct($twig) {
        $this->twig = $twig;
    }

    // Affiche la page d'accueil avec tous les articles publiés
    public function home() {
        $articles = Article::getAllPublished();
        echo $this->twig->render('test/home.twig', ['articles' => $articles]);
    }

    // Affiche le détail d'un article avec ses commentaires
    public function articleDetail($id) {
        $article = Article::getById($id);
        $commentaires = Commentaire::getByArticle($id);

        echo $this->twig->render('test/article.twig', [
            'article' => $article,
            'commentaires' => $commentaires
        ]);
    }

    // Méthode pour ajouter un commentaire
    public function addComment($data) {
        $article_id = (int) $data['article_id'];
        $nom_auteur = trim($data['nom_auteur']);
        $email_auteur = isset($data['email_auteur']) ? trim($data['email_auteur']) : null;
        $contenu = trim($data['contenu']);

        if ($nom_auteur && $contenu) {
            Commentaire::create($article_id, $nom_auteur, $email_auteur, $contenu);
        }

        // Redirige vers l'article pour voir le formulaire
        header("Location: index.php?id=$article_id");
        exit;
    }

    // Administration : afficher tous les commentaires en attente
    public function pendingComments() {
        $commentaires = Commentaire::getPending();
        echo $this->twig->render('admin/commentaires.twig', ['commentaires' => $commentaires]);
    }

    // Administration : traiter les actions (approuver/supprimer)
    public function moderateComment($id, $action) {
        if ($action === 'approve') {
            Commentaire::updateStatus($id, 'Approuvé');
        } elseif ($action === 'delete') {
            Commentaire::delete($id);
        }
        // Redirection après action
        header("Location: index.php?admin=comments");
        exit;
    }
}
