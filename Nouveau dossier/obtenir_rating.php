<?php
session_start();
require_once 'connexion.php';

$response = ['success' => false, 'rating' => 0];

if (!isset($_SESSION['id_medecin'])) {
    echo json_encode($response);
    exit;
}

try {
    $id_medecin = $_SESSION['id_medecin'];
    $stmt = $pdo->prepare("SELECT AVG(note) as moyenne FROM NOTE WHERE id_medecin = ?");
    $stmt->execute([$id_medecin]);
    $note_moyenne = round($stmt->fetchColumn() ?: 0, 1);
    $response['success'] = true;
    $response['rating'] = $note_moyenne;
} catch (PDOException $e) {
    error_log("Database Error in get_rating.php: " . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
?>