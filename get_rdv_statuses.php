<?php
session_start();

if (!isset($_SESSION['id_patient'])) {
    http_response_code(403); 
    exit();
}

$host = 'localhost';
$db = 'hopital';
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
    error_log("DB Error in get_rdv_statuses.php: " . $e->getMessage());
    http_response_code(500); 
    exit();
}

$user_id = $_SESSION['id_patient'];


$stmt = $pdo->prepare("SELECT id_rdv, statut FROM RENDEZVOUS WHERE id_patient = ? AND (statut = 'en_attente' OR statut = 'confirmé')");
$stmt->execute([$user_id]);
$rendezvous_statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($rendezvous_statuses);
?>