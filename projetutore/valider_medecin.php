<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    die('ID médecin manquant.');
}

$id = (int) $_GET['id'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=tutoré;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("UPDATE medecin SET valide = TRUE WHERE id_medecin = ?");
    $stmt->execute([$id]);

    header('Location: admin_medecins.php');
    exit;

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>
