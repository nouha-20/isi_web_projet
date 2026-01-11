<?php
require_once __DIR__ . '/../../app/models/Article.php';
require_once __DIR__ . '/../../app/models/Commentaire.php';
require_once __DIR__ . '/../Models/Tag.php';

class BlogController {
    private $twig;

    public function __construct($twig) {
        $this->twig = $twig;
    }

    // Affiche la page d'accueil
    public function home() {
        $articles = Article::getAllPublished();
        echo $this->twig->render('blog/home.twig', ['articles' => $articles]);
    }

    // Affiche la page 404
    public function notFound() {
        http_response_code(404);
        echo $this->twig->render('blog/404.twig', ['message' => 'La page demandée existe pas.']);
    }

    // Affiche le détail d'un article
    public function articleDetail($id) {
        $article = Article::getById($id);

        if (!$article) {
            $this->notFound();
            exit;
        }

        $commentaires = Commentaire::getByArticle($id);
        $tags = Tag::getTagsByArticle($id);

        echo $this->twig->render('blog/article.twig', [
            'article' => $article,
            'commentaires' => $commentaires,
            'tags' => $tags
        ]);
    }

    // Ajouter un commentaire
    public function addComment($data) {
        $article_id = (int) $data['article_id'];
        $nom_auteur = trim($data['nom_auteur']);
        $email_auteur = isset($data['email_auteur']) ? trim($data['email_auteur']) : null;
        $contenu = trim($data['contenu']);

        if ($nom_auteur && $contenu) {
            Commentaire::create($article_id, $nom_auteur, $email_auteur, $contenu);
        }
        header("Location: index.php?id=$article_id");
        exit;
    }


    public function articleBySlug($slug) {
        $article = Article::getBySlug($slug);

        if (!$article) {
            $this->notFound();
            exit;
        }

        // Récupération des commentaires et tags
        $commentaires = Commentaire::getByArticle($article['id']);
        $tags = Tag::getTagsByArticle($article['id']);

        echo $this->twig->render('blog/article.twig', [
            'article' => $article,
            'commentaires' => $commentaires,
            'tags' => $tags
        ]);
    }
}