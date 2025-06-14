<?php
require_once 'hosto.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "UPDATE medecin SET valide = TRUE WHERE id_medecin = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
}

header("Location: gestion_medecins.php");
exit();
?>
