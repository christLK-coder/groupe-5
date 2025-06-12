<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}
?>
<?php
require 'connexion.php';

if (!isset($_GET['action'], $_GET['id'])) {
    header('Location: admin_medecins.php');
    exit;
}

$id = (int) $_GET['id'];
$action = $_GET['action'];

switch ($action) {
    case 'valider':
        $sql = "UPDATE medecin SET valide = 1 WHERE id_medecin = ?";
        break;
    case 'suspendre':
        $sql = "UPDATE medecin SET valide = 0 WHERE id_medecin = ?";
        break;
    case 'supprimer':
        $sql = "DELETE FROM medecin WHERE id_medecin = ?";
        break;
    default:
        header('Location: admin_medecins.php');
        exit;
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);

header('Location: admin_medecins.php');
exit;
