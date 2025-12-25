<?php
require_once __DIR__ . '/../models/Commentaire.php';

class AdminCommentController {
    private $twig;

    public function __construct($twig) {
        $this->twig = $twig;
    }

    public function index() {
        $commentaires = Commentaire::getPending();
        echo $this->twig->render('admin/commentaires.twig', [
            'commentaires' => $commentaires
        ]);
    }

    public function action() {
        if (!isset($_POST['id'], $_POST['action'])) return;

        $id = (int) $_POST['id'];

        if ($_POST['action'] === 'approve') {
            Commentaire::updateStatus($id, 'Approuvé');
        }

        if ($_POST['action'] === 'reject') {
            Commentaire::updateStatus($id, 'Rejeté');
        }

        if ($_POST['action'] === 'delete') {
            Commentaire::delete($id);
        }

        header('Location: index.php?admin=commentaires');
        exit;
    }
}
