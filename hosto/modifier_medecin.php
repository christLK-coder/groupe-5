<?php
// hosto.php contient la connexion PDO à la base de données
require_once("hosto.php");

// Vérifier si l'ID du médecin est passé en GET
if (!isset($_GET['id'])) {
    header("Location: gestion_medecins.php");
    exit();
}

$id = $_GET['id'];
$message = "";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $specialite = $_POST['specialite'];
    $statut_disponible = isset($_POST['statut_disponible']) ? 1 : 0;

    $sql = "UPDATE medecin SET nom=?, prenom=?, email=?, telephone=?, specialite=?, statut_disponible=? WHERE id_medecin=?";
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$nom, $prenom, $email, $telephone, $specialite, $statut_disponible, $id])) {
        header("Location: gestion_medecins.php?modifie=1");
        exit();
    } else {
        $message = "Erreur lors de la mise à jour.";
    }
}

// Récupérer les informations actuelles du médecin
$sql = "SELECT * FROM medecin WHERE id_medecin = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$medecin) {
    header("Location: gestion_medecins.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Médecin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4 text-primary">Modifier les informations du médecin</h2>

    <?php if ($message): ?>
        <div class="alert alert-danger"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-4 shadow rounded">
        <div class="mb-3">
            <label for="nom" class="form-label">Nom :</label>
            <input type="text" class="form-control" name="nom" id="nom" value="<?= htmlspecialchars($medecin['nom']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="prenom" class="form-label">Prénom :</label>
            <input type="text" class="form-control" name="prenom" id="prenom" value="<?= htmlspecialchars($medecin['prenom']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email :</label>
            <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($medecin['email']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="telephone" class="form-label">Téléphone :</label>
            <input type="text" class="form-control" name="telephone" id="telephone" value="<?= htmlspecialchars($medecin['telephone']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="specialite" class="form-label">Spécialité :</label>
            <input type="text" class="form-control" name="specialite" id="specialite" value="<?= htmlspecialchars($medecin['specialite']) ?>" required>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" name="statut_disponible" id="statut_disponible" <?= $medecin['statut_disponible'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="statut_disponible">Disponible</label>
        </div>

        <button type="submit" class="btn btn-success">Enregistrer les modifications</button>
        <a href="gestion_medecins.php" class="btn btn-secondary">Annuler</a>
    </form>
</div>
</body>
</html>
