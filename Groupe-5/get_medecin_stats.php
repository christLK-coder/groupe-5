<?php
require_once 'connexion.php'; // Connexion à la BD

header('Content-Type: application/json');

$stats = [];

// Récupération des statistiques
$stats['nb_total'] = $pdo->query("SELECT COUNT(*) FROM medecin")->fetchColumn();
$stats['nb_valides'] = $pdo->query("SELECT COUNT(*) FROM medecin WHERE valide = 1")->fetchColumn();
$stats['nb_attente'] = $pdo->query("SELECT COUNT(*) FROM medecin WHERE valide = 0")->fetchColumn();
$stats['nb_indispo'] = $pdo->query("SELECT COUNT(*) FROM medecin WHERE statut_disponible = 0")->fetchColumn();

echo json_encode($stats);
?>