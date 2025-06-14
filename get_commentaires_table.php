<?php
require_once("hosto.php");

// Récupération des commentaires
$sql = "SELECT * FROM commentaire ORDER BY date_commentaire DESC"; // Added ordering
$stmt = $conn->prepare($sql);
$stmt->execute();
$commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start(); // Start output buffering
?>
    <?php if (count($commentaires) === 0): ?>
        <tr><td colspan="4" class="text-center">Aucun commentaire disponible.</td></tr>
    <?php else: ?>
        <?php foreach ($commentaires as $com): ?>
            <tr id="comment-row-<?= $com['id_commentaire'] ?>">
                <td><?= htmlspecialchars($com['nom']) ?></td>
                <td><?= nl2br(htmlspecialchars($com['contenu'])) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($com['date_commentaire'])) ?></td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="<?= $com['id_commentaire'] ?>">
                        <i class="fas fa-trash-alt"></i> Supprimer
                    </button>
                </td> 
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
<?php
$html_output = ob_get_clean(); // Get the buffered HTML content
echo $html_output; // Output the HTML
?>