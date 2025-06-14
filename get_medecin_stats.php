<?php
require_once 'hosto.php'; // Connexion à la BD

header('Content-Type: application/json');

$stats = [];

// Récupération des statistiques
$stats['nb_total'] = $conn->query("SELECT COUNT(*) FROM medecin")->fetchColumn();
$stats['nb_valides'] = $conn->query("SELECT COUNT(*) FROM medecin WHERE valide = 1")->fetchColumn();
$stats['nb_attente'] = $conn->query("SELECT COUNT(*) FROM medecin WHERE valide = 0")->fetchColumn();
$stats['nb_indispo'] = $conn->query("SELECT COUNT(*) FROM medecin WHERE statut_disponible = 0")->fetchColumn();

echo json_encode($stats);
?>