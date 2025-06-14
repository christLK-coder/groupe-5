<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    // Optionally, you can return an error or redirect,
    // but for an AJAX call, a JSON error is more appropriate.
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once("hosto.php");

$stats = [];  

// Récupération du nombre de services
$reqService = $conn->query("SELECT COUNT(*) FROM services");
$stats['nbServices'] = $reqService->fetchColumn();

// Récupération du nombre de spécialités
$reqSpecialite = $conn->query("SELECT COUNT(*) FROM specialite");
$stats['nbSpecialite'] = $reqSpecialite->fetchColumn();

// Récupération des données statistiques
$stats['nbPatients'] = $conn->query("SELECT COUNT(*) FROM patient")->fetchColumn();
$stats['nbMedecins'] = $conn->query("SELECT COUNT(*) FROM medecin")->fetchColumn();
$stats['nbRdvToday'] = $conn->query("SELECT COUNT(*) FROM rendezvous WHERE DATE(date_heure) = CURDATE()")->fetchColumn();
$stats['nbCommentaires'] = $conn->query("SELECT COUNT(*) FROM commentaire")->fetchColumn();

header('Content-Type: application/json');
echo json_encode($stats);
?>