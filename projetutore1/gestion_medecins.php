<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}
?>
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require 'connexion.php';

if (isset($_GET['valider']) && is_numeric($_GET['valider'])) {
    $idMedecin = intval($_GET['valider']);
    $sql = "UPDATE medecin SET valide = 1 WHERE id_medecin = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $idMedecin]);
    header('Location: gestion_medecins.php');
    exit;
}

$sql = "SELECT * FROM medecin WHERE valide = 0";
$stmt = $pdo->query($sql);
$medecins = $stmt->fetchAll();

foreach ($medecins as $medecin) {
    echo htmlspecialchars($medecin['nom']) . " " . htmlspecialchars($medecin['prenom']) . " - " . htmlspecialchars($medecin['specialite']);
    echo " <a href='gestion_medecins.php?valider=" . $medecin['id_medecin'] . "'>Valider</a><br>";
}
?>
