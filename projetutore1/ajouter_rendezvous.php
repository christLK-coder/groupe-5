<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Connexion à la base de données
try {
    $pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer patients
    $patients = $pdo->query("SELECT id_patient, nom, prenom FROM patient")->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer médecins
    $medecins = $pdo->query("SELECT id_medecin, nom, prenom FROM medecin")->fetchAll(PDO::FETCH_ASSOC);

    // Traitement du formulaire
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $patient_id = $_POST['patient_id'];
        $medecin_id = $_POST['medecin_id'];
        $date_heure = $_POST['date_heure'];
        $type = $_POST['type'];

        $stmt = $pdo->prepare("INSERT INTO rendez_vous (id_patient, id_medecin, date_heure, type, statut) VALUES (?, ?, ?, ?, 'en attente')");
        $stmt->execute([$patient_id, $medecin_id, $date_heure, $type]);

        $success = "Rendez-vous ajouté avec succès.";
    }

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un rendez-vous</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

<div class="container">
    <h2 class="mb-4">Ajouter un rendez-vous</h2>

    <?php if (isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>

    <form method="POST" class="card p-4 shadow-sm bg-white rounded">
        <div class="mb-3">
            <label for="patient_id" class="form-label">Patient :</label>
            <select name="patient_id" id="patient_id" class="form-select" required>
                <option value="">-- Sélectionner un patient --</option>
                <?php foreach ($patients as $patient): ?>
                    <option value="<?= $patient['id_patient'] ?>">
                        <?= ucfirst($patient['prenom']) . " " . strtoupper($patient['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="medecin_id" class="form-label">Médecin :</label>
            <select name="medecin_id" id="medecin_id" class="form-select" required>
                <option value="">-- Sélectionner un médecin --</option>
                <?php foreach ($medecins as $medecin): ?>
                    <option value="<?= $medecin['id_medecin'] ?>">
                        Dr. <?= ucfirst($medecin['prenom']) . " " . strtoupper($medecin['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="date_heure" class="form-label">Date et Heure :</label>
            <input type="datetime-local" name="date_heure" id="date_heure" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="type" class="form-label">Type de rendez-vous :</label>
            <select name="type" id="type" class="form-select" required>
                <option value="présentiel">Présentiel</option>
                <option value="en ligne">En ligne</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Ajouter</button>
        <a href="admin_dashboard.php" class="btn btn-secondary">Retour</a>
    </form>
</div>

</body>
</html>
