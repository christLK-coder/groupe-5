<?php
require_once("hosto.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM services WHERE id_service = ?");
    $stmt->execute([$id]);
}

header("Location: dashboard_admin.php");
exit();
?>
