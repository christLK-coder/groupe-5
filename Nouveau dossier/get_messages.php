<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['id_medecin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

if (!isset($_GET['conversation_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de conversation manquant']);
    exit;
}

$conversation_id = intval($_GET['conversation_id']);
$id_medecin = $_SESSION['id_medecin'];

try {
    $stmt = $pdo->prepare("
        SELECT id_expediteur, type_expediteur, contenu, date_message
        FROM MESSAGE
        WHERE id_conversation = ? AND EXISTS (
            SELECT 1 FROM CONVERSATION WHERE id_conversation = ? AND id_medecin = ?
        )
        ORDER BY date_message ASC
    ");
    $stmt->execute([$conversation_id, $conversation_id, $id_medecin]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les messages pour JSON
    $formatted_messages = array_map(function($msg) {
        return [
            'type_expediteur' => $msg['type_expediteur'],
            'contenu' => htmlspecialchars($msg['contenu']),
            'date_message' => $msg['date_message']
        ];
    }, $messages);

    header('Content-Type: application/json');
    echo json_encode($formatted_messages);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>