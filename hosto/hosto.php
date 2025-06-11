<?php
// Paramètres de connexion
$host = "localhost";
$user = "root";
$password = ""; 
$dbname = "hosto"; 

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    // Pour voir les erreurs PDO
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connexion reussie"; 
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>