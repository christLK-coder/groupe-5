<?php
session_start();
// Vérifier si l'administrateur est connecté, sinon rediriger vers la page de connexion
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Inclure le fichier de connexion à la base de données. L'objet $pdo sera disponible.
require_once 'connexion.php';

$message = ""; // Variable pour afficher les messages à l'utilisateur

// --- Traitement de l'ajout d'une spécialité ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["nom_specialite"])) {
    // Récupérer et nettoyer les données du formulaire
    $id_service = trim($_POST["id_service"]);
    $nom_specialite = trim($_POST["nom_specialite"]);
    $description = trim($_POST["description_specialite"]);

    // Valider les entrées (exemple basique)
    if (empty($id_service) || empty($nom_specialite)) {
        $message = "<div style='color:red;'>❌ Le service et le nom de la spécialité sont obligatoires.</div>";
    } else {
        try {
            // Préparer la requête d'insertion. Utilisation de placeholders positionnels (?).
            $stmt = $pdo->prepare("INSERT INTO specialite (id_service, nom, description_specialite) VALUES (?, ?, ?)");
            
            // Exécuter la requête en passant les valeurs dans l'ordre des placeholders.
            if ($stmt->execute([$id_service, $nom_specialite, $description])) {
                $message = "<div style='color:green;'>✅ Spécialité ajoutée avec succès.</div>";
            } else {
                // Cette branche est peu probable si PDO::ERRMODE_EXCEPTION est activé,
                // car PDO lancerait une exception en cas d'échec SQL.
                $message = "<div style='color:red;'>❌ Une erreur est survenue lors de l'ajout.</div>";
            }
        } catch (\PDOException $e) {
            // Capturer les exceptions PDO pour des erreurs de base de données (ex: doublon si le nom est unique)
            error_log("Erreur PDO lors de l'ajout de spécialité: " . $e->getMessage());
            // Vérifier si l'erreur est due à une entrée dupliquée (code SQLSTATE 23000)
            if ($e->getCode() == '23000') {
                 $message = "<div style='color:red;'>❌ Cette spécialité existe déjà ou une contrainte a été violée.</div>";
            } else {
                $message = "<div style='color:red;'>❌ Erreur de base de données lors de l'ajout.</div>";
            }
        }
    }
}

// --- Récupération des services pour le menu déroulant ---
try {
    $services = $pdo->query("SELECT id_service, nom_service FROM services ORDER BY nom_service")->fetchAll();
} catch (\PDOException $e) {
    error_log("Erreur PDO lors de la récupération des services: " . $e->getMessage());
    $services = []; // Assurez-vous que $services est un tableau vide en cas d'erreur
    $message .= "<div style='color:red;'>❌ Impossible de charger les services.</div>"; // Ajouter un message d'erreur
}


// --- Récupération des spécialités avec fonctionnalité de recherche ---
$search = $_GET['q'] ?? ''; // Récupère le terme de recherche ou une chaîne vide
$sql = "SELECT s.id_specialite, s.nom, s.description_specialite, sv.nom_service 
        FROM specialite s 
        JOIN services sv ON s.id_service = sv.id_service";

if (!empty($search)) {
    // Si un terme de recherche est présent, ajouter la clause WHERE
    $sql .= " WHERE s.nom LIKE :search_term OR s.description_specialite LIKE :search_term OR sv.nom_service LIKE :search_term";
    // Ajouter un ORDER BY pour une meilleure lisibilité
    $sql .= " ORDER BY s.nom ASC"; 

    try {
        $stmt = $pdo->prepare($sql);
        // CORRECTION CLÉ : Ici, le placeholder est :search_term, donc la clé dans execute doit être :search_term
        // $stmt->execute(['search' => '%' . $search . '%']); // Ceci était l'erreur précédente
        $stmt->execute([':search_term' => '%' . $search . '%']); // CORRECTION : Placeholder nommé avec deux-points
        $specialites = $stmt->fetchAll();
    } catch (\PDOException $e) {
        error_log("Erreur PDO lors de la recherche de spécialités: " . $e->getMessage());
        $specialites = []; // Assurez-vous que $specialites est un tableau vide en cas d'erreur
        $message .= "<div style='color:red;'>❌ Erreur lors de la recherche des spécialités.</div>";
    }
} else {
    // Si pas de terme de recherche, récupérer toutes les spécialités
    $sql .= " ORDER BY s.nom ASC"; // Tri par défaut
    try {
        $specialites = $pdo->query($sql)->fetchAll();
    } catch (\PDOException $e) {
        error_log("Erreur PDO lors de la récupération de toutes les spécialités: " . $e->getMessage());
        $specialites = []; // Assurez-vous que $specialites est un tableau vide en cas d'erreur
        $message .= "<div style='color:red;'>❌ Impossible de charger la liste des spécialités.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter et Gérer les Spécialités - Hosto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        background-color: #f0fdf5;
        margin: 40px;
    }
    .container {
        max-width: 850px;
        margin: auto;
        background: #ffffff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 0 15px #c0e7cb;
    }
    h2 {
        text-align: center;
        color: rgb(59, 197, 151);
        margin-bottom: 20px; /* Ajout d'un peu d'espace */
    }
    label {
        font-weight: bold;
        color: rgb(59, 197, 151);
        display: block; /* Pour que les labels prennent toute la largeur */
        margin-bottom: 5px; /* Espace entre label et input */
    }
    input[type="text"], input[type="email"], input[type="password"], select, textarea {
        width: calc(100% - 24px); /* Ajustement pour le padding */
        padding: 12px;
        margin-bottom: 15px; /* Espace après chaque champ */
        border-radius: 6px;
        border: 1px solid #ccc;
        box-sizing: border-box; /* Inclure le padding et la bordure dans la largeur */
    }
    button {
        width: 100%;
        padding: 12px;
        margin-top: 10px; /* Espace au-dessus du bouton */
        background-color: rgb(59, 197, 151);
        color: white;
        font-weight: bold;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    button:hover {
        background-color: rgb(40, 168, 125);
    }
    .search-box {
        display: flex; /* Utilisation de flexbox pour aligner input et button */
        gap: 10px; /* Espace entre les éléments */
        margin-bottom: 25px; /* Plus d'espace sous la barre de recherche */
    }
    .search-box input {
        flex-grow: 1; /* L'input prendra l'espace restant */
        margin: 0; /* Supprimer les marges par défaut pour flexbox */
    }
    .search-box button {
        width: auto; /* La largeur est gérée par le contenu */
        padding: 12px 20px; /* Plus de padding pour le bouton de recherche */
        margin: 0; /* Supprimer les marges par défaut */
    }
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05); /* Ombre légère pour le tableau */
    }
    .table th, .table td {
        border: 1px solid #bde5c8;
        padding: 12px; /* Augmenter le padding pour plus d'aération */
        text-align: left; /* Alignement à gauche par défaut */
    }
    .table th:last-child, .table td:last-child {
        text-align: center; /* Centrer les actions */
    }
    .table thead {
        background-color: #def7e0;
    }
    .actions a {
        margin: 0 5px;
        color: white;
        padding: 8px 12px; /* Plus de padding pour les boutons d'action */
        border-radius: 5px;
        text-decoration: none;
        display: inline-block; /* Pour un meilleur contrôle du padding */
        transition: background-color 0.2s ease;
    }
    .btn-warning {
        background-color: #ffc107;
    }
    .btn-warning:hover {
        background-color: #e0a800;
    }
    .btn-danger {
        background-color: #dc3545;
    }
    .btn-danger:hover {
        background-color: #c82333;
    }
    .back {
        margin-bottom: 30px; /* Plus d'espace au-dessus du conteneur principal */
    }
    .back a {
        text-decoration: none;
        color: rgb(59, 197, 151);
        font-weight: bold;
        display: inline-block; /* Pour pouvoir ajouter du padding si besoin */
        padding: 8px 15px; /* Un peu de padding pour le lien retour */
        border-radius: 5px;
        background-color: rgba(59, 197, 151, 0.1); /* Fond léger */
        transition: background-color 0.2s ease;
    }
    .back a:hover {
        background-color: rgba(59, 197, 151, 0.2);
    }
    .no-result {
        text-align: center;
        color: #999;
        font-style: italic;
        padding: 20px; /* Plus de padding pour le message */
    }

    /* Media Queries pour la responsivité */
    @media (max-width: 992px) {
        body { margin: 20px; }
        .container { padding: 20px; max-width: 100%; }
        h2 { font-size: 1.5em; }
        input[type="text"], input[type="email"], input[type="password"], select, textarea, button {
            padding: 10px; font-size: 0.95em; margin-bottom: 10px;
        }
        .search-box { flex-direction: column; gap: 8px; }
        .search-box input, .search-box button { width: 100%; margin: 0; }
        .table th, .table td { padding: 10px; font-size: 0.95em; }
        .actions a { padding: 6px 10px; font-size: 0.9em; }
        .back a { padding: 6px 12px; font-size: 0.95em; }
    }

    @media (max-width: 768px) {
        body { margin: 15px; }
        .container { padding: 15px; }
        h2 { font-size: 1.3em; }
        input[type="text"], input[type="email"], input[type="password"], select, textarea, button {
            padding: 8px; font-size: 0.9em; margin-bottom: 8px;
        }
        .table { display: block; overflow-x: auto; white-space: nowrap; }
        .table th, .table td { padding: 8px; font-size: 0.85em; }
        .actions a { padding: 4px 8px; font-size: 0.85em; }
        .back a { padding: 5px 10px; font-size: 0.9em; }
    }

    @media (max-width: 576px) {
        body { margin: 10px; }
        .container { padding: 10px; }
        h2 { font-size: 1.1em; }
        input[type="text"], input[type="email"], input[type="password"], select, textarea, button {
            padding: 6px; font-size: 0.85em; margin-bottom: 6px;
        }
        .search-box { gap: 5px; }
        .table th, .table td { padding: 6px; font-size: 0.8em; }
        .actions a { padding: 3px 6px; font-size: 0.8em; }
        .back a { padding: 4px 8px; font-size: 0.85em; }
    }
</style>
</head>
<body> 
    <div class="back">
        <a href="dashboard_admin.php"><i class="fa-solid fa-arrow-left"></i> Retour au tableau de bord</a>
    </div>

<div class="container">
    <h2>Ajouter une Spécialité</h2>
    <?= $message ?>
    <form action="" method="POST">
        <label for="id_service">Service associé :</label>
        <select name="id_service" id="id_service" required>
            <option value="">-- Choisir un service --</option>
            <?php foreach ($services as $s): ?>
                <option value="<?= htmlspecialchars($s['id_service']) ?>"><?= htmlspecialchars($s['nom_service']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="nom_specialite">Nom de la Spécialité :</label>
        <input type="text" name="nom_specialite" id="nom_specialite" required>

        <label for="description_specialite">Description :</label>
        <textarea name="description_specialite" id="description_specialite" rows="4"></textarea>

        <button type="submit">Ajouter Spécialité</button>
    </form>

    <hr>
    <h2>Liste des Spécialités</h2>

    <form method="GET" class="search-box">
        <input type="text" name="q" placeholder="Rechercher une spécialité..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit"><i class="fas fa-search"></i> Rechercher</button>
    </form>

    <table class="table">
        <thead>
        <tr>
            <th>Nom</th>
            <th>Description</th>
            <th>Service associé</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($specialites)): // Utilisez empty() pour vérifier un tableau vide ?>
            <tr>
                <td colspan="4" class="no-result">Aucune spécialité trouvée.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($specialites as $sp): ?>
                <tr>
                    <td><?= htmlspecialchars($sp['nom']) ?></td>
                    <td><?= htmlspecialchars($sp['description_specialite']) ?></td>
                    <td><?= htmlspecialchars($sp['nom_service']) ?></td>
                    <td class="actions">
                        <a href="modifier_specialite.php?id=<?= htmlspecialchars($sp['id_specialite']) ?>" class="btn-warning" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="supprimer_specialite.php?id=<?= htmlspecialchars($sp['id_specialite']) ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette spécialité ?')" class="btn-danger" title="Supprimer">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>