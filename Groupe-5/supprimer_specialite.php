<?php
require_once("connexion.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM specialites WHERE id_specialite = ?");
    $stmt->execute([$id]);
}

header("Location: dashboard_admin.php");
exit();
?>