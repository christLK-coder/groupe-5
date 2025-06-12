<?php
include("hosto.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID du patient non spécifié.";
    exit;
}

$id = intval($_GET['id']);

// Supprimer la photo s'il y en a une
$stmt = $conn->prepare("SELECT image_patient FROM patient WHERE id_patient = :id");
$stmt->execute(['id' => $id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if ($patient && !empty($patient['image_patient'])) {
    $chemin = "images/" . $patient['image_patient'];
    if (file_exists($chemin)) {
        unlink($chemin);
    }
}

// Supprimer le patient
$stmt = $conn->prepare("DELETE FROM patient WHERE id_patient = :id");
$stmt->execute(['id' => $id]);

header("Location: gestion_patients.php");
exit;
