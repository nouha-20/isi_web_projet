<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/Database.php';

use Config\Database;

$conn = Database::getInstance();

if ($conn) {
    echo "Connexion à la base réussie !";
}
