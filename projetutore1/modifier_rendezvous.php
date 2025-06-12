<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_GET['id'])) {
        header("Location: admin_dashboard.php");
        exit();
    }

    $id_rdv = $_GET['id'];

    // Charger les données du rendez-vous
    $stmt = $pdo->prepare("SELECT * FROM rendez_vous WHERE id_rdv = ?");
    $stmt->execute([$id_rdv]);
    $rdv = $stmt->fetch();

    if (!$rdv) {
        die("Rendez-vous introuvable.");
    }

    // Charger patients et médecins
    $patients = $pdo->query("SELECT id_patient, nom, prenom FROM patient")->fetchAll(PDO::FETCH_ASSOC);
    $medecins = $pdo->query("SELECT id_medecin, nom, prenom FROM medecin")->fetchAll(PDO::FETCH_ASSOC);

    // Traitement du formulaire
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $patient_id = $_POST['patient_id'];
        $medecin_id = $_POST['medecin_id'];
        $date_heure = $_POST['date_heure'];
        $type = $_POST['type'];
        $statut = $_POST['statut'];

        $update = $pdo->prepare("UPDATE rendez_vous SET patient_id = ?, medecin_id = ?, date_heure = ?, type = ?, statut = ? WHERE id_rdv = ?");
        $update->execute([$patient_id, $medecin_id, $date_heure, $type, $statut, $id_rdv]);

        header("Location: admin_dashboard.php");
        exit();
    }

} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Rendez-vous</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h2>Modifier le rendez-vous</h2>

    <form method="post" class="card p-4 shadow-sm bg-white">

        <div class="mb-3">
            <label class="form-label">Patient</label>
            <select name="patient_id" class="form-select" required>
                <?php foreach ($patients as $patient): ?>
                    <option value="<?= $patient['id_patient']; ?>" <?= $rdv['patient_id'] == $patient['id_patient'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($patient['nom']) . ' ' . htmlspecialchars($patient['prenom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Médecin</label>
            <select name="medecin_id" class="form-select" required>
                <?php foreach ($medecins as $medecin): ?>
                    <option value="<?= $medecin['id_medecin']; ?>" <?= $rdv['medecin_id'] == $medecin['id_medecin'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($medecin['nom']) . ' ' . htmlspecialchars($medecin['prenom']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Date et heure</label>
            <input type="datetime-local" name="date_heure" class="form-control"
                   value="<?= date('Y-m-d\TH:i', strtotime($rdv['date_heure'])); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
                <option value="Présentiel" <?= $rdv['type'] === 'Présentiel' ? 'selected' : '' ?>>Présentiel</option>
                <option value="En ligne" <?= $rdv['type'] === 'En ligne' ? 'selected' : '' ?>>En ligne</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Statut</label>
            <select name="statut" class="form-select">
                <option value="en attente" <?= $rdv['statut'] === 'en attente' ? 'selected' : '' ?>>En attente</option>
                <option value="confirmé" <?= $rdv['statut'] === 'confirmé' ? 'selected' : '' ?>>Confirmé</option>
                <option value="annulé" <?= $rdv['statut'] === 'annulé' ? 'selected' : '' ?>>Annulé</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success">Enregistrer les modifications</button>
        <a href="admin_dashboard.php" class="btn btn-secondary">Annuler</a>

    </form>
</div>

</body>
</html>
