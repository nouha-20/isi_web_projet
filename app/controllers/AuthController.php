<?php
require_once __DIR__ . '/../Models/User.php';

class AuthController {
    private $twig;

    public function __construct($twig) {
        $this->twig = $twig;
    }

    /**
     * Gère le formulaire de connexion et l'authentification.
     */
    public function login() {
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email']);
            $password = $_POST['mot_de_passe'];

            $user = User::findByEmail($email);

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                // Création de la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nom_utilisateur'];
                $_SESSION['logged_in'] = true;

                $roles = !empty($user['roles']) ? explode(',', $user['roles']) : [];


                if (in_array('Administrateur', $roles)) {
                    $_SESSION['user_role'] = 'Administrateur';
                }
                elseif (in_array('Éditeur', $roles)) {
                    $_SESSION['user_role'] = 'Éditeur';
                }
                elseif (in_array('Contributeur', $roles)) {
                    $_SESSION['user_role'] = 'Contributeur';
                }
                else {
                    $_SESSION['user_role'] = 'Aucun'; // par défaut
                }

                header('Location: index.php?admin=dashboard');
                exit;
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        }

        echo $this->twig->render('blog/login.twig', ['error' => $error]);
    }

    /**
     * Déconnecte l'utilisateur et détruit la session.
     */
    public function logout() {
        session_destroy();
        header('Location: index.php');
        exit;
    }
}