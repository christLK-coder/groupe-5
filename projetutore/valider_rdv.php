<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}
?>
<?php
require 'db.php';
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE rendez_vous SET statut = 'confirmé' WHERE id_rdv = ?");
    $stmt->execute([$id]);
}
header('Location: admin_rendezvous.php');
exit;
