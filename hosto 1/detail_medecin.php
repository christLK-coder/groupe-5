<?php
include("hosto.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID du médecin non spécifié.";
    exit;
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT 
        m.*, 
        services.nom_service AS nom_service, 
        specialite.nom AS nom_specialite
    FROM medecin m
    LEFT JOIN services ON m.id_service = services.id_service
    LEFT JOIN specialite ON m.id_specialite = specialite.id_specialite
    WHERE m.id_medecin = :id
");
$stmt->execute(['id' => $id]);
$medecin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$medecin) {
    echo "Médecin introuvable.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail du Médecin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 30px;
            background-color: #f0f2f5;
        }
        .card {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .info {
            margin-top: 20px;
        }
        .info p {
            font-size: 16px;
            margin: 10px 0;
        }
        .info i {
            margin-right: 8px;
            color: #007BFF;
        }
        .statut {
            font-weight: bold;
        }
        .disponible {
            color: green;
        }
        .indisponible {
            color: red;
        }
        .photo {
            text-align: center;
            margin-top: 20px;
        }
        .photo img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ccc;
        }
        a.retour {
            display: block;
            margin-top: 25px;
            text-align: center;
            color: #007BFF;
            text-decoration: none;
        }
        a.retour:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="card">
    <h2><i class="fas fa-user-md"></i> Détails du Médecin</h2>

    <div class="photo">
        <?php if (!empty($medecin['image_medecin'])): ?>
            <img src="New folder/<?= htmlspecialchars($medecin['image_medecin']) ?>" alt="Photo null">
        <?php else: ?>
            <img src="New folder/default.jpg" alt="Photo par défaut">
        <?php endif; ?>
    </div>

    <div class="info">
        <p><i class="fas fa-id-card"></i> <strong>Nom :</strong> <?= htmlspecialchars($medecin['nom']) ?></p>
        <p><i class="fas fa-id-card"></i> <strong>Prénom :</strong> <?= htmlspecialchars($medecin['prenom']) ?></p>
        <p><i class="fas fa-venus-mars"></i> <strong>Sexe :</strong> <?= htmlspecialchars($medecin['sexe']) ?></p>
        <p><i class="fas fa-building"></i> <strong>Département :</strong> <?= htmlspecialchars($medecin['nom_service'] ?? 'Non défini') ?></p>
        <p><i class="fas fa-stethoscope"></i> <strong>Spécialité :</strong> <?= htmlspecialchars($medecin['nom_specialite'] ?? 'Non définie') ?></p>
        <p><i class="fas fa-phone"></i> <strong>Téléphone :</strong> <?= htmlspecialchars($medecin['telephone']) ?></p>
        <p><i class="fas fa-envelope"></i> <strong>Email :</strong> <?= htmlspecialchars($medecin['email']) ?></p>
        <p><i class="fas fa-map-marker-alt"></i> <strong>Adresse :</strong> <?= htmlspecialchars($medecin['adresse']) ?></p>
        <p><i class="fas fa-book"></i> <strong>Biographie :</strong> <?= nl2br(htmlspecialchars($medecin['biographie'])) ?></p>
        <p><i class="fas fa-calendar-alt"></i> <strong>Date d'inscription :</strong> <?= htmlspecialchars($medecin['date_inscription']) ?></p>
        <p><i class="fas fa-circle"></i> <strong>Disponibilité :</strong>
            <span class="statut <?= $medecin['statut_disponible'] ? 'disponible' : 'indisponible' ?>">
                <?= $medecin['statut_disponible'] ? 'Disponible' : 'Indisponible' ?>
            </span>
        </p>
    </div>

    <a href="gestion_medecins.php" class="retour"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
</div>

</body>
</html>
