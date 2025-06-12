<?php
require_once("hosto.php");

// Suppression d'un commentaire
if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    $stmt = $conn->prepare("DELETE FROM commentaire WHERE id_commentaire = ?");
    $stmt->execute([$id]);
    header("Location: commentaires_admin.php");
    exit;
}

// Récupération des commentaires
$sql = "SELECT c.*, 
               p.nom AS nom_patient, p.prenom AS prenom_patient, 
               m.nom AS nom_medecin, m.prenom AS prenom_medecin 
        FROM commentaire c
        JOIN patient p ON c.id_patient = p.id_patient
        LEFT JOIN medecin m ON c.id_medecin = m.id_medecin
        ORDER BY c.date_commentaire DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commentaires</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="text-primary mb-4"><i class="fas fa-comments me-2"></i>Commentaires des patients</h2>

    <?php if (count($commentaires) === 0): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle"></i> Aucun commentaire disponible.
        </div>
        <div class="back">
            <a href="dashboard_admin.php"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        </div>
    <?php else: ?>
        <div class="table-responsive shadow rounded">
            <table class="table table-bordered table-hover bg-white">
                <thead class="table-primary">
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Médecin</th>
                        <th>Contenu</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($commentaires as $com): ?>
                    <tr>
                        <td><?= $com['id_commentaire'] ?></td>
                        <td><?= $com['prenom_patient'] . " " . $com['nom_patient'] ?></td>
                        <td><?= $com['id_medecin'] ? ($com['prenom_medecin'] . " " . $com['nom_medecin']) : '<em>Aucun</em>' ?></td>
                        <td><?= nl2br(htmlspecialchars($com['contenu'])) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($com['date_commentaire'])) ?></td>
                        <td>
                            <a href="?supprimer=<?= $com['id_commentaire'] ?>" onclick="return confirm('Supprimer ce commentaire ?')" class="btn btn-sm btn-danger">
                                <i class="fas fa-trash-alt"></i> Supprimer
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
