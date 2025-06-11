<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require 'db.php';

// Récupérer les rendez-vous
$sql = "SELECT rdv.id_rdv, p.nom AS nom_patient, m.nom AS nom_medecin, rdv.date_heure, rdv.type_consultation, rdv.statut
        FROM rendez_vous rdv
        JOIN patient p ON rdv.id_patient = p.id_patient
        JOIN medecin m ON rdv.id_medecin = m.id_medecin
        ORDER BY rdv.date_heure DESC";
$stmt = $pdo->query($sql);
$rendezvous = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin - Rendez-vous</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        a { margin: 0 5px; }
        .btn { padding: 4px 10px; text-decoration: none; border-radius: 5px; }
        .valider { background-color: #4CAF50; color: white; }
        .annuler { background-color: #f44336; color: white; }
        .supprimer { background-color: #555; color: white; }
    </style>
</head>
<body>

<h2>Bienvenue, admin !</h2>
<h3>Liste des rendez-vous</h3>

<a href="admin_dashboard.php">⬅ Retour au tableau de bord</a>

<table>
    <tr>
        <th>ID</th>
        <th>Patient</th>
        <th>Médecin</th>
        <th>Date et heure</th>
        <th>Type</th>
        <th>Statut</th>
        <th>Actions</th>
    </tr>

    <?php if (count($rendezvous) > 0): ?>
        <?php foreach ($rendezvous as $rdv): ?>
            <tr>
                <td><?= htmlspecialchars($rdv['id_rdv']) ?></td>
                <td><?= htmlspecialchars($rdv['nom_patient']) ?></td>
                <td><?= htmlspecialchars($rdv['nom_medecin']) ?></td>
                <td><?= htmlspecialchars($rdv['date_heure']) ?></td>
                <td><?= htmlspecialchars($rdv['type_consultation']) ?></td>
                <td><?= htmlspecialchars($rdv['statut']) ?></td>
                <td>
                    <?php if ($rdv['statut'] != 'confirmé'): ?>
                        <a class="btn valider" href="valider_rdv.php?id=<?= $rdv['id_rdv'] ?>">Valider</a>
                    <?php endif; ?>
                    <?php if ($rdv['statut'] != 'annulé'): ?>
                        <a class="btn annuler" href="annuler_rdv.php?id=<?= $rdv['id_rdv'] ?>">Annuler</a>
                    <?php endif; ?>
                    <a class="btn supprimer" href="supprimer_rdv.php?id=<?= $rdv['id_rdv'] ?>" onclick="return confirm('Supprimer ce rendez-vous ?');">Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="7">Aucun rendez-vous trouvé.</td></tr>
    <?php endif; ?>
</table>

<p><a href="logout.php">Déconnexion</a></p>

</body>
</html>
