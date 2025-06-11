<?php
// connexion.php

$host = 'localhost';         // Nom de l'hôte, souvent 'localhost'
$dbname = 'hopital';  // Remplace par le nom exact de ta base de données
$username = 'root';          // Nom d'utilisateur MySQL (souvent 'root' en local)
$password = '';              // Mot de passe MySQL (laisse vide si aucun)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Activer les exceptions PDO pour une meilleure gestion des erreurs
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Affichage d'une erreur claire en cas d'échec de la connexion
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
