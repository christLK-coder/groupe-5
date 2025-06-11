<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_rdv = intval($_GET['id']);

    try {
        $pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("UPDATE rendez_vous SET statut = 'annulé' WHERE id_rdv = ?");
        $stmt->execute([$id_rdv]);

        header("Location: admin_dashboard.php");
        exit();
    } catch (PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }
} else {
    echo "ID de rendez-vous invalide.";
}
?>
