<?php
require_once("connexion.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM services WHERE id_service = ?");
    $stmt->execute([$id]);
}

header("Location: dashboard_admin.php");
exit();
?>
