<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Optionally, you can return an error or redirect,
    // but for an AJAX call, a JSON error is more appropriate.
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once 'connexion.php';

$stats = [];  

// Récupération du nombre de services
$reqService = $pdo->query("SELECT COUNT(*) FROM services");
$stats['nbServices'] = $reqService->fetchColumn();

// Récupération du nombre de spécialités
$reqSpecialite = $pdo->query("SELECT COUNT(*) FROM specialite");
$stats['nbSpecialite'] = $reqSpecialite->fetchColumn();

// Récupération des données statistiques
$stats['nbPatients'] = $pdo->query("SELECT COUNT(*) FROM patient")->fetchColumn();
$stats['nbMedecins'] = $pdo->query("SELECT COUNT(*) FROM medecin")->fetchColumn();
$stats['nbRdvToday'] = $pdo->query("SELECT COUNT(*) FROM rendezvous WHERE DATE(date_heure) = CURDATE()")->fetchColumn();
$stats['nbCommentaires'] = $pdo->query("SELECT COUNT(*) FROM commentaire")->fetchColumn();

header('Content-Type: application/json');
echo json_encode($stats);
?>