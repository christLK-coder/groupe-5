<?php
include("connexion.php");

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID du médecin non spécifié.";
    exit;
}

$id = intval($_GET['id']);
$medecin = null;

$stmt = $conn->prepare("SELECT * FROM medecins WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $medecin = $result->fetch_assoc();
} else {
    echo "Médecin introuvable.";
    exit;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail du Médecin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background-color: #f9f9f9;
        }
        .card {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            width: 500px;
            box-shadow: 0 3px 7px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        p {
            font-size: 16px;
            margin: 8px 0;
        }
        .disponible {
            color: green;
        }
        .indisponible {
            color: red;
        }
        a {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #007BFF;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Détails du Médecin</h2>

    <p><strong>Nom :</strong> <?= htmlspecialchars($medecin['nom']) ?></p>
    <p><strong>Prénom :</strong> <?= htmlspecialchars($medecin['prenom']) ?></p>
    <p><strong>Service :</strong> <?= htmlspecialchars($medecin['service']) ?></p>
    <p><strong>Spécialité :</strong> <?= htmlspecialchars($medecin['specialite']) ?></p>
    <p><strong>Téléphone :</strong> <?= htmlspecialchars($medecin['telephone']) ?></p>
    <p><strong>Email :</strong> <?= htmlspecialchars($medecin['email']) ?></p>
    <p><strong>Disponibilité :</strong>
        <span class="<?= $medecin['disponibilite'] ? 'disponible' : 'indisponible' ?>">
            <?= $medecin['disponibilite'] ? 'Disponible' : 'Indisponible' ?>
        </span>
    </p>

    <a href="changer_disponibilite.php?id=<?= $medecin['id'] ?>">Changer la disponibilité</a>
</div>

</body>
</html>
