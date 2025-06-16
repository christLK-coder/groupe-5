<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['id_medecin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$id_rdv = $_POST['id_rdv'] ?? null;
$medicament = $_POST['medicament'] ?? null;
$posologie = $_POST['posologie'] ?? null;
$duree = $_POST['duree'] ?? null;
$conseils = $_POST['conseils'] ?? null;

if (!$id_rdv || !$medicament || !$posologie || !$duree) {
    http_response_code(400);
    echo json_encode(['error' => 'Champs requis manquants']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO PRESCRIPTION (id_rdv, medicament, posologie, duree, conseils)
                       VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$id_rdv, $medicament, $posologie, $duree, $conseils]);

echo json_encode(['success' => true, 'message' => 'Prescription enregistrée']);
