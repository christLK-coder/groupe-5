<?php
require_once 'hosto.php'; // Connexion à la BD

$filtre = $_GET['filtre'] ?? '';

$query = "SELECT medecin.*, services.nom_service 
          FROM medecin 
          LEFT JOIN services ON medecin.id_service = services.id_service 
          WHERE 
            medecin.nom LIKE :filtre OR 
            medecin.prenom LIKE :filtre OR 
            services.nom_service LIKE :filtre 
          ORDER BY medecin.date_inscription DESC";
$stmt = $conn->prepare($query);
$stmt->execute(['filtre' => "%$filtre%"]);
$medecins = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start(); // Start output buffering  
?>
            <?php if (count($medecins) === 0): ?>
                <tr><td colspan="8">Aucun médecin trouvé.</td></tr>
            <?php else: ?>
                <?php foreach ($medecins as $m): ?>
                    <tr>
                        <td>
                            <?php if (!empty($m['image_medecin']) ): ?>
                                <img src="New folder/<?= htmlspecialchars($m['image_medecin']) ?>" alt="photos">
                            <?php else: ?>
                                <img src="images/default_avatar.png" alt="avatar">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($m['nom'] . ' ' . $m['prenom']) ?></td>
                        <td><?= htmlspecialchars($m['sexe']) ?></td>
                        <td><?= htmlspecialchars($m['nom_service']) ?></td>
                        <td><?= htmlspecialchars($m['email']) ?></td>
                        <td><?= htmlspecialchars($m['telephone']) ?></td>
                        <td>
                            <?= $m['valide'] ? "✅ Validé" : "⏳ En attente" ?><br>
                            <?= $m['statut_disponible'] ? "🟢 Disponible" : "🔴 Indisponible" ?>
                        </td>
                        <td class="actions">
                            <a href="detail_medecin.php?id=<?= $m['id_medecin'] ?>" title="Détails"><i class="fas fa-eye"></i></a>
                            <?php if (!$m['valide']): ?>
                                <a href="valider_medecin.php?id=<?= $m['id_medecin'] ?>" title="Valider"><i class="fas fa-check-circle"></i></a>
                            <?php endif; ?>
                            <a href="modifier_medecin.php?id=<?= $m['id_medecin'] ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                            <a href="supprimer_medecin.php?id=<?= $m['id_medecin'] ?>" title="Supprimer" onclick="return confirm('Confirmer la suppression ?')"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
<?php
$html_output = ob_get_clean(); // Get the buffered output
echo $html_output; // Output the HTML
?>