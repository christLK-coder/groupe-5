<?php
require_once("connexion.php");

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "DELETE FROM medecin WHERE id_medecin = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
}

header("Location: gestion_medecins.php");
exit();
?>
