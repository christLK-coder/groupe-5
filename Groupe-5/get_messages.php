<?php
session_start();

$host = 'localhost';
$db   = 'hopital';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log("Database connection error in get_messages.php: " . $e->getMessage());
    http_response_code(500);
    exit();
}

// Ensure patient is logged in
if (!isset($_SESSION['id_patient'])) {
    http_response_code(403); // Forbidden
    exit();
}

$patient_id = $_SESSION['id_patient'];

// Get conversation ID from GET request
$conv_id = filter_input(INPUT_GET, 'conv_id', FILTER_VALIDATE_INT);

if (!$conv_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Conversation ID is missing or invalid.']);
    exit();
}

// Verify that the conversation belongs to the current patient
$stmt_check = $pdo->prepare("SELECT COUNT(*) FROM CONVERSATION WHERE id_conversation = ? AND id_patient = ?");
$stmt_check->execute([$conv_id, $patient_id]);
if ($stmt_check->fetchColumn() === 0) {
    http_response_code(403); // Forbidden - conversation does not belong to this patient
    echo json_encode(['error' => 'Access denied to this conversation.']);
    exit();
}

// Fetch messages for the given conversation ID
$stmt_messages = $pdo->prepare("SELECT contenu, date_message, id_expediteur, type_expediteur
                                FROM MESSAGE
                                WHERE id_conversation = ?
                                ORDER BY date_message ASC");
$stmt_messages->execute([$conv_id]);
$messages = $stmt_messages->fetchAll();

header('Content-Type: application/json');
echo json_encode($messages);
?>