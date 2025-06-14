<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['id_medecin'])) {
    header('Location: login.php');
    exit();
}

$id_medecin = $_SESSION['id_medecin'];
$nom_session = $_SESSION['nom'];
$prenom_session = $_SESSION['prenom'];
$image_medecin_session = $_SESSION['image_medecin'] ?? 'default.jpg';

$message = '';
$message_type = '';

$medecin = [];
$services = [];
$specialites = [];

try {
    // Récupération des informations du médecin
    $stmt = $pdo->prepare("SELECT id_medecin, nom, prenom, email, telephone, adresse, sexe, id_service, id_specialite, biographie, image_medecin, mot_de_passe FROM MEDECIN WHERE id_medecin = ?");
    $stmt->execute([$id_medecin]);
    $medecin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$medecin) {
        error_log("Médecin avec id_medecin=$id_medecin non trouvé dans la base de données.");
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // Récupération des services
    $stmt_services = $pdo->query("SELECT id_service, nom_service FROM SERVICES ORDER BY nom_service ASC");
    $services = $stmt_services->fetchAll(PDO::FETCH_ASSOC);

    // Correction ici : `nom` au lieu de `nom_specialite`
    $stmt_specialites = $pdo->prepare("SELECT id_specialite, nom FROM specialite ORDER BY nom ASC");
    $stmt_specialites->execute();
    $specialites = $stmt_specialites->fetchAll(PDO::FETCH_ASSOC);

    // Vérification des tables vides
    if (empty($services)) {
        $message = "Aucun service disponible. Contactez l'administrateur.";
        $message_type = 'danger';
    }
    if (empty($specialites)) {
        $message = "Aucune spécialité disponible. Contactez l'administrateur.";
        $message_type = 'danger';
    }

    // Vérification que l'id_specialite du médecin existe dans specialite
    if (!empty($medecin['id_specialite'])) {
        $stmt_check_specialite = $pdo->prepare("SELECT COUNT(*) FROM specialite WHERE id_specialite = ?");
        $stmt_check_specialite->execute([$medecin['id_specialite']]);
        if ($stmt_check_specialite->fetchColumn() == 0) {
            $message = "La spécialité associée à votre profil n'existe plus. Veuillez en sélectionner une nouvelle.";
            $message_type = 'warning';
            $medecin['id_specialite'] = null; // Réinitialiser pour forcer une nouvelle sélection
        }
    }

} catch (PDOException $e) {
    error_log("Erreur PDO lors du chargement du profil médecin: " . $e->getMessage());
    $message = "Une erreur est survenue lors du chargement de vos informations. Veuillez réessayer.";
    $message_type = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $sexe = trim($_POST['sexe'] ?? '');
    $id_service = filter_var($_POST['id_service'] ?? 0, FILTER_VALIDATE_INT);
    $id_specialite = filter_var($_POST['id_specialite'] ?? 0, FILTER_VALIDATE_INT);
    $biographie = trim($_POST['biographie'] ?? '');

    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    if (empty($nom) || empty($prenom) || empty($email) || !$id_service || !$id_specialite) {
        $errors[] = "Tous les champs obligatoires (Nom, Prénom, Email, Service, Spécialité) doivent être remplis.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
        }
        if ($new_password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
    }

    // Validation des clés étrangères
    if ($id_service) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM SERVICES WHERE id_service = ?");
        $stmt->execute([$id_service]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = "Le service sélectionné n'existe pas.";
        }
    }
    if ($id_specialite) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM specialite WHERE id_specialite = ?");
        $stmt->execute([$id_specialite]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = "La spécialité sélectionnée n'existe pas.";
        }
    }

    $image_medecin_path = $medecin['image_medecin'] ?? 'default.jpg';
    if (isset($_FILES['image_medecin']) && $_FILES['image_medecin']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['image_medecin']['name'];
        $file_tmp_name = $_FILES['image_medecin']['tmp_name'];
        $file_size = $_FILES['image_medecin']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 2 * 1024 * 1024;

        if (!in_array($file_ext, $allowed_exts)) {
            $errors[] = "Seuls les fichiers JPG, JPEG, PNG, GIF sont autorisés pour l'image de profil.";
        }
        if ($file_size > $max_file_size) {
            $errors[] = "La taille de l'image de profil ne doit pas dépasser 2MB.";
        }

        if (empty($errors)) {
            // Définir les dossiers séparément
            $upload_dir = 'New folder/'; // Dossier physique pour l'écriture
            $db_image_path_prefix = ''; // Préfixe du chemin stocké dans la BD

            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
                $errors[] = "Impossible de créer le dossier d'upload.";
            }
            if (!is_writable($upload_dir)) {
                $errors[] = "Le dossier d'upload n'est pas accessible en écriture.";
            }

            if (empty($errors)) {
                $new_file_name = uniqid('medecin_') . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name; // Chemin pour l'écriture
                $image_medecin_path = $db_image_path_prefix . $new_file_name; // Chemin pour la BD

                if (move_uploaded_file($file_tmp_name, $destination)) {
                    if (!empty($medecin['image_medecin']) && $medecin['image_medecin'] !== 'default.jpg' && file_exists($upload_dir . basename($medecin['image_medecin']))) {
                        unlink($upload_dir . basename($medecin['image_medecin']));
                    }
                } else {
                    $errors[] = "Erreur lors de l'upload de l'image.";
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE MEDECIN SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, sexe = ?, id_service = ?, id_specialite = ?, biographie = ?, image_medecin = ? ";
            $params = [$nom, $prenom, $email, $telephone, $adresse, $sexe, $id_service, $id_specialite, $biographie, $image_medecin_path];

            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql .= ", mot_de_passe = ? ";
                $params[] = $hashed_password;
            }

            $sql .= "WHERE id_medecin = ?";
            $params[] = $id_medecin;

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $message = "Vos informations ont été mises à jour avec succès.";
                $message_type = 'success';

                $_SESSION['nom'] = $nom;
                $_SESSION['prenom'] = $prenom;
                $_SESSION['image_medecin'] = $image_medecin_path;

                $stmt = $pdo->prepare("SELECT id_medecin, nom, prenom, email, telephone, adresse, sexe, id_service, id_specialite, biographie, image_medecin, mot_de_passe FROM MEDECIN WHERE id_medecin = ?");
                $stmt->execute([$id_medecin]);
                $medecin = $stmt->fetch(PDO::FETCH_ASSOC);

            } else {
                $message = "Échec de la mise à jour de vos informations. Veuillez réessayer.";
                $message_type = 'danger';
            }

        } catch (PDOException $e) {
            error_log("Erreur PDO lors de la mise à jour des infos médecin: " . $e->getMessage());
            if ($e->getCode() == 23000) {
                $message = "L'adresse email que vous avez saisie est déjà utilisée.";
            } else {
                $message = "Une erreur est survenue lors de la mise à jour de vos informations. Veuillez réessayer.";
            }
            $message_type = 'danger';
        }
    } else {
        $message = "<ul>";
        foreach ($errors as $error) {
            $message .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $message .= "</ul>";
        $message_type = 'danger';
    }
}

$medecin_display = $medecin ?? [];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Dr. <?= htmlspecialchars($nom_session . ' ' . $prenom_session) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #93d6d0;
            --secondary-color: #7bc7c1;
            --light-bg: #f3fbfa;
            --white: #FFFFFF;
            --dark-text: #333;
            --light-grey-border: #e0e0e0;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        body {
            background-color: var(--light-bg);
            margin: 0;
            font-family: 'Roboto', sans-serif;
            display: flex;
            min-height: 100vh;
            color: var(--dark-text);
        }

        .sidebar {
            width: 250px;
            background-color: var(--white);
            position: fixed;
            top: 0;
            bottom: 0;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .sidebar .profile {
            text-align: center;
            padding: 20px;
            border-bottom: 2px solid var(--light-grey-border);
        }
        .sidebar .profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 2px solid var(--primary-color);
            transition: transform 0.3s ease;
        }
        .sidebar .profile img:hover {
            transform: scale(1.05);
        }
        .sidebar .profile h4 {
            margin: 5px 0;
            color: var(--dark-text);
            font-size: 16px;
            font-weight: 500;
        }
        .sidebar .nav {
            padding-top: 20px;
            flex-grow: 1;
        }
        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--dark-text);
            text-decoration: none;
            font-size: 15px;
            transition: background-color 0.3s, color 0.3s, padding-left 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: var(--white);
            padding-left: 25px;
        }
        .sidebar .nav-link .material-icons {
            margin-right: 10px;
            font-size: 20px;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
            flex-grow: 1;
            overflow-y: auto;
            background-color: var(--light-bg);
            transition: margin-left 0.3s ease;
        }
        .card {
            background-color: var(--white);
            border: none;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            padding: 20px;
        }
        .card-header {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            font-size: 1.25rem;
            font-weight: 500;
            margin: -20px -20px 20px -20px;
        }
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            color: var(--white);
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        .btn-icon {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .form-label {
            font-weight: 500;
            color: #555;
        }
        .profile-img-preview {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
            margin-bottom: 20px;
            display: block;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .alert {
            border-radius: 8px;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(147, 214, 208, 0.25);
        }
        h1 {
            color: var(--dark-text);
            font-weight: 700;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 10px 0;
                overflow: hidden;
            }
            .sidebar .profile h4, .sidebar .nav-link span {
                display: none;
            }
            .sidebar .profile img {
                width: 40px;
                height: 40px;
                margin-bottom: 0;
            }
            .sidebar .nav-link {
                justify-content: center;
                padding: 10px;
            }
            .sidebar .nav-link .material-icons {
                margin-right: 0;
            }
            .sidebar .nav-link:hover {
                padding-left: 10px;
            }
            .main-content {
                margin-left: 70px;
                padding: 20px;
            }
            .profile-img-preview {
                width: 120px;
                height: 120px;
                margin-bottom: 15px;
            }
            .card-header {
                font-size: 1rem;
                padding: 10px 15px;
            }
            .card-body {
                padding: 15px;
            }
        }

        @media (max-width: 576px) {
            .row > div {
                margin-bottom: 15px;
            }
            .btn-primary {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="profile">
            <img src="New folder/<?= htmlspecialchars($image_medecin_session) ?>" alt="Profil">
            <h4>Dr. <?= htmlspecialchars($nom_session . ' ' . $prenom_session) ?></h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"> <span class="material-icons">house</span>
                <span>Home</span>
            </a>
            <a class="nav-link" href="test.php"> <span class="material-icons">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a class="nav-link" href="rendezvous.php">
                <span class="material-icons">event</span>
                <span>Rendez-vous</span>
            </a>
            <a class="nav-link" href="messages.php">
                <span class="material-icons">chat</span>
                <span>Messages</span>
            </a>
            <a class="nav-link" href="diagnostics.php">
                <span class="material-icons">medical_services</span>
                <span>Diagnostic</span>
            </a>
            <a class="nav-link" href="historique.php">
                <span class="material-icons">history</span>
                <span>Historique</span>
            </a>
            <a class="nav-link active" href="api.php"> <span class="material-icons">map</span>
                <span>Carte RDV</span>
            </a>
            <a class="nav-link" href="profil.php">
                <span class="material-icons">settings</span>
                <span>Paramètres</span>
            </a>
            <a class="nav-link" href="logout.php">
                <span class="material-icons">logout</span>
                <span>Déconnexion</span>
            </a>
        </nav>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <h1 class="mb-4">Mon Profil</h1>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    Modifier mes informations
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-4 text-center">
                                <img src="New folder/<?= htmlspecialchars($medecin_display['image_medecin'] ?? 'default.jpg') ?>" alt="Image de profil" class="profile-img-preview" id="profileImagePreview">
                                <div class="mb-3">
                                    <label for="image_medecin" class="form-label">Changer l'image de profil</label>
                                    <input type="file" class="form-control" id="image_medecin" name="image_medecin" accept="image/*">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="nom" class="form-label">Nom</label>
                                        <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($medecin_display['nom'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="prenom" class="form-label">Prénom</label>
                                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($medecin_display['prenom'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($medecin_display['email'] ?? '') ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="telephone" class="form-label">Téléphone</label>
                                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($medecin_display['telephone'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <textarea class="form-control" id="adresse" name="adresse" rows="3"><?= htmlspecialchars($medecin_display['adresse'] ?? '') ?></textarea>
                                </div>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <label for="sexe" class="form-label">Sexe</label>
                                        <select class="form-select" id="sexe" name="sexe">
                                            <option value="">Sélectionner</option>
                                            <option value="Homme" <?= (isset($medecin_display['sexe']) && $medecin_display['sexe'] === 'Homme') ? 'selected' : '' ?>>Homme</option>
                                            <option value="Femme" <?= (isset($medecin_display['sexe']) && $medecin_display['sexe'] === 'Femme') ? 'selected' : '' ?>>Femme</option>
                                            <option value="Autre" <?= (isset($medecin_display['sexe']) && $medecin_display['sexe'] === 'Autre') ? 'selected' : '' ?>>Autre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="id_service" class="form-label">Service</label>
                                        <select class="form-select" id="id_service" name="id_service" required>
                                            <option value="">Sélectionner un service</option>
                                            <?php foreach ($services as $service): ?>
                                                <option value="<?= htmlspecialchars($service['id_service']) ?>"
                                                    <?= (isset($medecin_display['id_service']) && $medecin_display['id_service'] == $service['id_service']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($service['nom_service']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label for="id_specialite" class="form-label">Spécialité</label>
                                    <select class="form-select" id="id_specialite" name="id_specialite" required>
                                        <option value="">Sélectionner une spécialité</option>
                                        <?php foreach ($specialites as $specialite): ?>
                                            <option value="<?= htmlspecialchars($specialite['id_specialite']) ?>"
                                                <?= (isset($medecin_display['id_specialite']) && $medecin_display['id_specialite'] == $specialite['id_specialite']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($specialite['nom']) ?> </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mt-3">
                                    <label for="biographie" class="form-label">Biographie</label>
                                    <textarea class="form-control" id="biographie" name="biographie" rows="5"><?= htmlspecialchars($medecin_display['biographie'] ?? '') ?></textarea>
                                </div>

                                <hr class="my-4">
                                <h5 class="mb-3">Changer le mot de passe (laissez vide si vous ne voulez pas le changer)</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-icon mt-4 w-100">
                                    <span class="material-icons">save</span> Enregistrer les modifications
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('image_medecin').addEventListener('change', function(event) {
            const [file] = event.target.files;
            if (file) {
                document.getElementById('profileImagePreview').src = URL.createObjectURL(file);
            }
        });
    </script>
</body>
</html>