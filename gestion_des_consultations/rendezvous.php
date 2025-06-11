





<?php 
// fichier: rendezvous.php
session_start();
require_once 'connexion.php';



$id_medecin = $_SESSION['id_medecin'];

// Gérer les actions : annuler, refuser, reporter, terminer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_rdv = intval($_POST['id_rdv'] ?? 0);

    if ($id_rdv > 0) {
        switch ($action) {
            case 'annuler':
                $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET statut = 'annulé' WHERE id_rdv = ? AND statut = 'confirmé' AND id_medecin = ?");
                $stmt->execute([$id_rdv, $id_medecin]);
                break;
            case 'refuser':
                $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET statut = 'annulé' WHERE id_rdv = ? AND statut = 'en_attente' AND id_medecin = ?");
                $stmt->execute([$id_rdv, $id_medecin]);
                break;
            case 'terminer':
                $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET statut = 'terminé', date_fin = NOW() WHERE id_rdv = ? AND statut = 'encours' AND id_medecin = ?");
                $stmt->execute([$id_rdv, $id_medecin]);
                break;
            case 'reporter':
                $nouvelle_date = $_POST['nouvelle_date'] ?? '';
                $stmt = $pdo->prepare("UPDATE RENDEZVOUS SET date_heure = ?, statut = 'confirmé' WHERE id_rdv = ? AND id_medecin = ?");
                $stmt->execute([$nouvelle_date, $id_rdv, $id_medecin]);
                break;
        }
    }
}

// Mettre à jour automatiquement les rendez-vous confirmés devenus "encours"
$pdo->prepare("UPDATE RENDEZVOUS SET statut = 'encours', date_début = NOW() WHERE statut = 'confirmé' AND id_medecin = ? AND date_heure <= NOW()")->execute([$id_medecin]);

// Récupérer les rendez-vous du médecin
$stmt = $pdo->prepare("SELECT r.*, p.nom AS nom_patient, p.prenom AS prenom_patient 
                       FROM RENDEZVOUS r 
                       JOIN PATIENT p ON r.id_patient = p.id_patient 
                       WHERE r.id_medecin = ? 
                       ORDER BY r.date_heure DESC");
$stmt->execute([$id_medecin]);
$rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mes Rendez-vous</title>
  <style>
    body { font-family: Arial; background-color: #f4f4f4; padding: 20px; }
    table { width: 100%; border-collapse: collapse; background: #fff; }
    th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #70d5b7; color: white; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    form { display: inline; }
    .actions input[type="submit"] { margin-right: 5px; background: #70d5b7; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; }
    .actions input[type="submit"]:hover { background: #5cc3a3; }
  </style>
</head>
<body>
<h1>Mes Rendez-vous</h1>
<table>
  <thead>
    <tr>
      <th>Patient</th>
      <th>Date & Heure</th>
      <th>Type</th>
      <th>Urgence</th>
      <th>Statut</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rdvs as $rdv): ?>
    <tr>
      <td><?= htmlspecialchars($rdv['nom_patient'] . ' ' . $rdv['prenom_patient']) ?></td>
      <td><?= htmlspecialchars($rdv['date_heure']) ?></td>
      <td><?= ucfirst($rdv['type_consultation']) ?></td>
      <td><?= ucfirst($rdv['niveau_urgence']) ?></td>
      <td><?= ucfirst($rdv['statut']) ?></td>
      <td class="actions">
        <?php if ($rdv['statut'] === 'confirmé'): ?>
          <form method="POST"><input type="hidden" name="id_rdv" value="<?= $rdv['id_rdv'] ?>"><input type="hidden" name="action" value="annuler"><input type="submit" value="Annuler"></form>
        <?php elseif ($rdv['statut'] === 'en_attente'): ?>
          <form method="POST"><input type="hidden" name="id_rdv" value="<?= $rdv['id_rdv'] ?>"><input type="hidden" name="action" value="refuser"><input type="submit" value="Refuser"></form>
        <?php elseif ($rdv['statut'] === 'encours'): ?>
          <form method="POST"><input type="hidden" name="id_rdv" value="<?= $rdv['id_rdv'] ?>"><input type="hidden" name="action" value="terminer"><input type="submit" value="Terminer"></form>
        <?php endif; ?>
        <?php if (in_array($rdv['statut'], ['en_attente', 'confirmé'])): ?>
          <form method="POST">
            <input type="hidden" name="id_rdv" value="<?= $rdv['id_rdv'] ?>">
            <input type="hidden" name="action" value="reporter">
            <input type="datetime-local" name="nouvelle_date" required>
            <input type="submit" value="Reporter">
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>

