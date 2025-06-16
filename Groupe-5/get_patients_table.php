<?php
require_once 'connexion.php'; // Assurez-vous que votre objet PDO ($pdo) est bien défini ici

// Récupère le terme de recherche de la requête AJAX et le nettoie
$recherche = isset($_GET['recherche']) ? trim($_GET['recherche']) : "";

$sql = "SELECT * FROM patient"; // Assurez-vous que le nom de votre table est 'patient'

// Initialiser le tableau des paramètres
$params = [];

if (!empty($recherche)) {
    // CORRECTION MAJEURE ICI : Utilisez des noms de marqueurs de substitution uniques
    // Même si la valeur est la même, PDO attend une entrée distincte pour chaque occurrence
    $sql .= " WHERE nom LIKE :recherche_nom OR prenom LIKE :recherche_prenom";
    
    $params[':recherche_nom'] = "%$recherche%";
    $params[':recherche_prenom'] = "%$recherche%";

    // Si vous aviez d'autres champs à rechercher, ajoutez-les de la même manière :
    // $sql .= " OR email LIKE :recherche_email";
    // $params[':recherche_email'] = "%$recherche%";
}

$sql .= " ORDER BY nom ASC, prenom ASC"; // Ajoute un tri pour la cohérence des résultats

try {
    $stmt = $pdo->prepare($sql);

    // Exécuter la requête avec le tableau de paramètres.
    // Si $recherche est vide, $params sera vide, ce qui est correct pour $stmt->execute([]).
    // Si $recherche n'est pas vide, $params contiendra les clés ':recherche_nom' et ':recherche_prenom'.
    $stmt->execute($params); 

    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Journalisez l'erreur pour le débogage (consultez vos logs d'erreurs PHP)
    error_log("Erreur de base de données dans get_patients_table.php: " . $e->getMessage());
    // Affichez un message générique à l'utilisateur
    $patients = []; // S'assurer que $patients est vide en cas d'erreur
    // Assurez-vous que le colspan correspond au nombre de colonnes de votre tableau
    echo '<tr><td colspan="6" style="color:red; text-align:center;">Une erreur est survenue lors du chargement des patients.</td></tr>'; 
    exit(); // Arrêtez l'exécution du script en cas d'erreur critique
}

ob_start(); // Démarre la mise en mémoire tampon de la sortie HTML
?>
    <?php if (empty($patients)): ?>
        <tr><td colspan="6">Aucun patient trouvé.</td></tr>
    <?php else: ?>
        <?php foreach ($patients as $patient): ?>
            <tr>
                <td><?= htmlspecialchars($patient['nom']) ?></td>
                <td><?= htmlspecialchars($patient['prenom']) ?></td>
                <td><?= htmlspecialchars($patient['sexe']) ?></td>
                <td><?= htmlspecialchars($patient['email']) ?></td>
                <td><?= htmlspecialchars($patient['telephone']) ?></td>
                <td>
                    <a href="detail_patient.php?id=<?= htmlspecialchars($patient['id_patient']) ?>" title="Voir les détails"><i class="fas fa-eye"></i> Voir</a> |
                    <a href="supprimer_patient.php?id=<?= htmlspecialchars($patient['id_patient']) ?>" title="Supprimer le patient" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce patient ?')"><i class="fas fa-trash-alt"></i> Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
<?php
$html_output = ob_get_clean(); // Récupère le contenu HTML mis en mémoire tampon
echo $html_output; // Affiche le HTML
?>