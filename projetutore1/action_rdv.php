<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}
?>
<?php
require 'connexion.php';

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);

    $statut = '';
    if ($action === 'confirmer') {
        $statut = 'confirmé';
    } elseif ($action === 'annuler') {
        $statut = 'annulé';
    } elseif ($action === 'terminer') {
        $statut = 'terminé';
    }

    if ($statut !== '') {
        $stmt = $pdo->prepare("UPDATE rendez_vous SET statut = :statut WHERE id_rdv = :id");
        $stmt->execute([
            'statut' => $statut,
            'id' => $id
        ]);
    }
}

header('Location: admin_dashboard.php');
exit;
