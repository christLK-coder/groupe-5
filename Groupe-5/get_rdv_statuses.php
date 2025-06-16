<?php
session_start();

if (!isset($_SESSION['id_patient'])) {
    http_response_code(403); 
    exit();
}

require_once 'connexion.php';

$user_id = $_SESSION['id_patient'];


$stmt = $pdo->prepare("SELECT id_rdv, statut FROM RENDEZVOUS WHERE id_patient = ? AND (statut = 'en_attente' OR statut = 'confirmé')");
$stmt->execute([$user_id]);
$rendezvous_statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($rendezvous_statuses);
?>