<?php
session_start();
$host = 'localhost';
$db = 'tutore';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}

// Gestion des patients
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_patient') {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $telephone = $_POST['telephone'];
        $genre = $_POST['genre'];

        $stmt = $pdo->prepare("INSERT INTO patient (nom, prenom, email, telephone, genre) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $email, $telephone, $genre]);
        echo "Patient ajouté avec succès.";
    } elseif ($_POST['action'] === 'add_rendezvous') {
        $patient_id = $_POST['patient_id'];
        $date = $_POST['date'];

        $stmt = $pdo->prepare("INSERT INTO rendezvous (patient_id, date) VALUES (?, ?)");
        $stmt->execute([$patient_id, $date]);
        echo "Rendez-vous ajouté avec succès.";
    }
}

// Récupérer tous les patients
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_patients') {
    $stmt = $pdo->query("SELECT * FROM patient");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($patients);
}

// Récupérer tous les rendez-vous
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_rendezvous') {
    $stmt = $pdo->query("SELECT r.*, p.nom, p.prenom FROM rendezvous r JOIN patient p ON r.patient_id = p.id");
    $rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rendezvous);
}

// Supprimer un rendez-vous
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_rendezvous') {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM rendezvous WHERE id = ?");
    $stmt->execute([$id]);
    echo "Rendez-vous supprimé avec succès.";
}
?>