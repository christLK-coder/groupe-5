<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['id_medecin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$id_rdv = $_POST['id_rdv'] ?? null;
$contenu = $_POST['contenu'] ?? null;

if (!$id_rdv || !$contenu) {
    http_response_code(400);
    echo json_encode(['error' => 'Données incomplètes']);
    exit;
}

// Vérifier statut consultation
$stmt = $pdo->prepare("SELECT statut FROM RENDEZVOUS WHERE id_rdv = ?");
$stmt->execute([$id_rdv]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['error' => 'Rendez-vous introuvable']);
    exit;
}

if ($row['statut'] !== 'encours') {
    echo json_encode(['error' => 'La consultation est terminée. Diagnostic non modifiable.']);
    exit;
}

// Vérifier s’il existe déjà un diagnostic
$stmt = $pdo->prepare("SELECT id_diagnostic FROM DIAGNOSTIC WHERE id_rdv = ?");
$stmt->execute([$id_rdv]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $pdo->prepare("UPDATE DIAGNOSTIC SET contenu = ?, date_diagnostic = NOW() WHERE id_rdv = ?");
    $stmt->execute([$contenu, $id_rdv]);
    echo json_encode(['success' => true, 'message' => 'Diagnostic mis à jour.']);
} else {
    $stmt = $pdo->prepare("INSERT INTO DIAGNOSTIC (contenu, id_rdv) VALUES (?, ?)");
    $stmt->execute([$contenu, $id_rdv]);
    echo json_encode(['success' => true, 'message' => 'Diagnostic enregistré.']);
}
