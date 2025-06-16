<?php
session_start();

require_once("connexion.php");

header('Content-Type: application/json');

// Ensure patient is logged in
if (!isset($_SESSION['id_patient'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit();
}

$patient_id = $_SESSION['id_patient'];

// Get data from POST request
$conv_id = filter_input(INPUT_POST, 'conv_id', FILTER_VALIDATE_INT);
$message_content = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING); // Sanitize message content
$sender_type = filter_input(INPUT_POST, 'sender_type', FILTER_SANITIZE_STRING);
$sender_id = filter_input(INPUT_POST, 'sender_id', FILTER_VALIDATE_INT);

if (!$conv_id || empty($message_content) || $sender_type !== 'patient' || $sender_id !== $patient_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    exit();
}

// Verify that the conversation belongs to the current patient
$stmt_check = $pdo->prepare("SELECT COUNT(*) FROM CONVERSATION WHERE id_conversation = ? AND id_patient = ?");
$stmt_check->execute([$conv_id, $patient_id]);
if ($stmt_check->fetchColumn() === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Access denied to this conversation.']);
    exit();
}

try {
    $stmt_insert = $pdo->prepare("INSERT INTO MESSAGE (id_conversation, id_expediteur, type_expediteur, contenu) VALUES (?, ?, ?, ?)");
    $stmt_insert->execute([$conv_id, $patient_id, $sender_type, $message_content]);

    echo json_encode(['status' => 'success', 'message' => 'Message sent.']);
} catch (\PDOException $e) {
    error_log("Error inserting message: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to send message.']);
}
?>