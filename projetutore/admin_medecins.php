<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', '');

// Récupération des médecins
$stmt = $pdo->query("SELECT * FROM medecin");
$medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Médecins</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #F8F9FA;
            color: #343A40;
            padding: 20px;
            margin: 0;
        }
        h1 {
            color: #007BFF;
        }
        a {
            color: #007BFF;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            border: 1px solid #DEE2E6;
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #007BFF;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #E9ECEF;
        }
        tr:hover {
            background-color: #D1E7DD;
        }
        .actions a {
            margin: 0 5px;
        }
        .logout {
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <h1>Gestion des Médecins</h1>
    <a href="admin_dashboard.php">⬅ Retour au tableau de bord</a>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom complet</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Spécialité</th>
                <th>Validé</th>
                <th>Disponible</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$medecins) : ?>
                <tr><td colspan="8">Aucun médecin trouvé.</td></tr>
            <?php else: ?>
                <?php foreach ($medecins as $medecin): ?>
                <tr>
                    <td><?= htmlspecialchars($medecin['id_medecin']) ?></td>
                    <td><?= htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']) ?></td>
                    <td><?= htmlspecialchars($medecin['email']) ?></td>
                    <td><?= htmlspecialchars($medecin['telephone'] ?? '') ?></td>
                    <td><?= htmlspecialchars($medecin['specialite'] ?? '') ?></td>
                    <td><?= $medecin['valide'] ? '✅ Oui' : '❌ Non' ?></td>
                    <td><?= $medecin['statut_disponible'] ? '✅ Oui' : '❌ Non' ?></td>
                    <td class="actions">
                        <?php if (!$medecin['valide']): ?>
                            <a href="valider_medecin.php?id=<?= $medecin['id_medecin'] ?>" onclick="return confirm('Valider ce médecin ?')">Valider</a>
                        <?php else: ?>
                            <a href="annuler_medecin.php?id=<?= $medecin['id_medecin'] ?>" onclick="return confirm('Annuler la validation de ce médecin ?')">Annuler</a>
                        <?php endif; ?>
                        | 
                        <a href="supprimer_medecin.php?id=<?= $medecin['id_medecin'] ?>" onclick="return confirm('Supprimer ce médecin ? Cette action est irréversible.')">Supprimer</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <p class="logout"><a href="logout.php">Déconnexion</a></p>

</body>
</html>