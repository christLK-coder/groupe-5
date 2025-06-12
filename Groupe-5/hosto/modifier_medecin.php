<?php
require_once("hosto.php");

if (!isset($_GET['id'])) {
    header("Location: gestion_medecins.php");
    exit();
}

$id = $_GET['id'];
$message = "";

// Récupération des spécialités et services
$specialites = $conn->query("SELECT * FROM specialite")->fetchAll(PDO::FETCH_ASSOC);
$services = $conn->query("SELECT * FROM services")->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $id_specialite = $_POST['id_specialite'];
    $id_service = $_POST['id_service'];
    $statut_disponible = isset($_POST['statut_disponible']) ? 1 : 0;
    $adresse = $_POST['adresse'];
    $biographie = $_POST['biographie'];

    // Gestion de l'image
    $image_nom = $old_image = null;
    if (!empty($_FILES['image_medecin']['name'])) {
        $image_nom = basename($_FILES['image_medecin']['name']);
        $destination = "New folder/" . $image_nom;
        move_uploaded_file($_FILES['image_medecin']['tmp_name'], $destination);
    }

    if ($image_nom) {
        $sql = "UPDATE medecin SET nom=?, prenom=?, email=?, telephone=?, id_specialite=?, id_service=?, statut_disponible=?, adresse=?, biographie=?, image_medecin=? WHERE id_medecin=?";
        $stmt = $conn->prepare($sql);
        $params = [$nom, $prenom, $email, $telephone, $id_specialite, $id_service, $statut_disponible, $adresse, $biographie, $image_nom, $id];
    } else {
        $sql = "UPDATE medecin SET nom=?, prenom=?, email=?, telephone=?, id_specialite=?, id_service=?, statut_disponible=?, adresse=?, biographie=? WHERE id_medecin=?";
        $stmt = $conn->prepare($sql);
        $params = [$nom, $prenom, $email, $telephone, $id_specialite, $id_service, $statut_disponible, $adresse, $biographie, $id];
    }

    if ($stmt->execute($params)) {
        header("Location: gestion_medecins.php?modifie=1");
        exit();
    } else {
        $message = "Erreur lors de la mise à jour.";
    }
}

// Récupération des infos actuelles
$sql = "SELECT * FROM medecin WHERE id_medecin = ?";
$stmt = $conn->prepare($sql);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="mb-4 text-primary"><i class="fas fa-user-edit"></i> Modifier les informations du médecin</h2>

    <?php if ($message): ?>
        <div class="alert alert-danger"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="bg-white p-4 shadow rounded">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nom" class="form-label"><i class="fas fa-user"></i> Nom :</label>
                <input type="text" class="form-control" name="nom" id="nom" value="<?= htmlspecialchars($medecin['nom']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="prenom" class="form-label"><i class="fas fa-user"></i> Prénom :</label>
                <input type="text" class="form-control" name="prenom" id="prenom" value="<?= htmlspecialchars($medecin['prenom']) ?>" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email :</label>
            <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($medecin['email']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="telephone" class="form-label"><i class="fas fa-phone"></i> Téléphone :</label>
            <input type="text" class="form-control" name="telephone" id="telephone" value="<?= htmlspecialchars($medecin['telephone']) ?>" required>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="id_specialite" class="form-label"><i class="fas fa-stethoscope"></i> Spécialité :</label>
                <select name="id_specialite" class="form-select" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($specialites as $spec): ?>
                        <option value="<?= $spec['id_specialite'] ?>" <?= $spec['id_specialite'] == $medecin['id_specialite'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($spec['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label for="id_service" class="form-label"><i class="fas fa-hospital"></i> Service :</label>
                <select name="id_service" class="form-select" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($services as $srv): ?>
                        <option value="<?= $srv['id_service'] ?>" <?= $srv['id_service'] == $medecin['id_service'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($srv['nom_service']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label for="adresse" class="form-label"><i class="fas fa-map-marker-alt"></i> Adresse :</label>
            <input type="text" class="form-control" name="adresse" value="<?= htmlspecialchars($medecin['adresse'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="biographie" class="form-label"><i class="fas fa-info-circle"></i> Biographie :</label>
            <textarea class="form-control" name="biographie" rows="3"><?= htmlspecialchars($medecin['biographie'] ?? '') ?></textarea>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="statut_disponible" id="statut_disponible" <?= $medecin['statut_disponible'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="statut_disponible"><i class="fas fa-circle"></i> Disponible</label>
        </div>

        <div class="mb-3">
            <label for="image_medecin" class="form-label"><i class="fas fa-image"></i> Photo :</label>
            <input type="file" class="form-control" name="image_medecin" accept="image/*">
            <?php if (!empty($medecin['image_medecin'])): ?>
                <img src="New folder/<?= htmlspecialchars($medecin['image_medecin']) ?>" alt="Photo actuelle" class="img-thumbnail mt-2" style="width: 120px;">
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Enregistrer</button>
        <a href="gestion_medecins.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Retour</a>
    </form>
</div>
</body>
</html>
