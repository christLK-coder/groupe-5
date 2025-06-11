<?php
$host = 'localhost';
$dbname = 'hopital'; // Remplace par le nom réel
$username = 'root'; // par défaut sous XAMPP
$password = ''; // vide sous XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Mode d’erreur en exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
