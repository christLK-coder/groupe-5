<?php
// annuler_medecin.php

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_medecin = intval($_GET['id']);

    try {
        $pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', ''); // adapte si besoin
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Met à jour la colonne valide à 0 (non validé)
        $stmt = $pdo->prepare('UPDATE medecin SET valide = 0 WHERE id_medecin = ?');
        $stmt->execute([$id_medecin]);

        header('Location: admin_medecins.php');
        exit();

    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
} else {
    echo "ID de médecin invalide.";
}
?>
