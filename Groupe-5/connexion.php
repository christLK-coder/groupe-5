<?php
$host = 'localhost'; // Or your database host
$db   = 'hopital'; // Your database name
$user = 'root'; // Your database username
$pass = ''; // Your database password (if any)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Log the error (for debugging, don't show to users in production)
    error_log("Database connection error: " . $e->getMessage());
    die("Erreur de connexion à la base de données.");
}
?>