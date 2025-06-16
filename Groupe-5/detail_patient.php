<?php
include("connexion.php");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID invalide";
    exit;
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM patient WHERE id_patient = :id");
$stmt->execute(['id' => $id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    echo "Patient non trouvé.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails du Patient</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #e6f5e6;
            margin: 30px;
        }
        h2 {
            color: #2e7d32;
        }
        .patient-details {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            max-width: 600px;
        }
        .patient-details p {
            margin: 10px 0;
        }
        img {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #2e7d32;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            color: #2e7d32;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<h2><i class="fas fa-user-injured"></i> Détails du Patient</h2>

<div class="patient-details">
    <p><strong>Nom :</strong> <?= htmlspecialchars($patient['nom']) ?></p>
    <p><strong>Prénom :</strong> <?= htmlspecialchars($patient['prenom']) ?></p>
    <p><strong>Sexe :</strong> <?= htmlspecialchars($patient['sexe']) ?></p>
    <p><strong>Email :</strong> <?= htmlspecialchars($patient['email']) ?></p>
    <p><strong>Téléphone :</strong> <?= htmlspecialchars($patient['telephone']) ?></p>
    <p><strong>Adresse :</strong> <?= htmlspecialchars($patient['adresse']) ?></p>
    <p><strong>Date d'inscription :</strong> <?= htmlspecialchars($patient['date_inscription']) ?></p>

    <?php if (!empty($patient['image_patient'])): ?>
        <p><strong>Photo :</strong><br>
            <img src="<?= htmlspecialchars($patient['image_patient']) ?>" alt="Photo du patient">
        </p>
    <?php endif; ?>
</div>

<a href="gestion_patients.php"><i class="fas fa-arrow-left"></i> Retour </a>

</body>
</html>
