
<?php
// parametres_patient.php

// --- ALWAYS START THE SESSION AT THE VERY TOP OF YOUR PHP FILE ---
session_start();

// Include your database connection file
include("hosto.php");

// --- Patient ID Management and Security Check ---
// Check if 'id_patient' exists in the session.
// This is your primary security check to ensure a user is logged in.
if (!isset($_SESSION['id_patient'])) {
    // If not, redirect them to your login page.
    // IMPORTANT: Replace 'login.php' with the actual path to your patient login page.
    header("Location: login.php");
    exit(); // Always exit after a header redirect to stop further script execution.
}

// Retrieve the patient ID from the session.
$id_patient = $_SESSION['id_patient'];

$message = ''; // For success messages
$error = '';   // For error messages

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine which form was submitted based on the 'action_type' hidden input
    $action_type = $_POST['action_type'] ?? '';

    if ($action_type === 'update_profile') {
        // --- Handle Profile Information Update ---
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $sexe = $_POST['sexe'] ?? '';

        // Basic validation
        if (empty($nom) || empty($prenom) || empty($email) || empty($sexe)) {
            $error = "Veuillez remplir tous les champs obligatoires (Nom, Prénom, Email, Sexe).";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Le format de l'email est invalide.";
        } else {
            try {
                // Check if email already exists for another patient (prevents duplicate emails)
                $stmt_check_email = $conn->prepare("SELECT id_patient FROM patient WHERE email = ? AND id_patient != ?");
                $stmt_check_email->execute([$email, $id_patient]);
                if ($stmt_check_email->rowCount() > 0) {
                    $error = "Cet email est déjà utilisé par un autre compte.";
                } else {
                    // Prepare and execute the UPDATE query for patient profile
                    $stmt_update = $conn->prepare("UPDATE patient SET nom = ?, prenom = ?, email = ?, telephone = ?, adresse = ?, sexe = ? WHERE id_patient = ?");
                    $stmt_update->execute([$nom, $prenom, $email, $telephone, $adresse, $sexe, $id_patient]);
                    $message = "Vos informations de profil ont été mises à jour avec succès.";
                }
            } catch (PDOException $e) {
                $error = "Erreur lors de la mise à jour des informations de profil : " . $e->getMessage();
            }
        }
    } elseif ($action_type === 'update_password') {
        // --- Handle Password Update ---
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_new_password = $_POST['confirm_new_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
            $error = "Veuillez remplir tous les champs de mot de passe.";
        } elseif ($new_password !== $confirm_new_password) {
            $error = "Le nouveau mot de passe et la confirmation ne correspondent pas.";
        } elseif (strlen($new_password) < 6) { // Example: minimum 6 characters for password strength
            $error = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
        } else {
            try {
                // Fetch current hashed password to verify against user input
                $stmt_get_password = $conn->prepare("SELECT mot_de_passe FROM patient WHERE id_patient = ?");
                $stmt_get_password->execute([$id_patient]);
                $patient_data = $stmt_get_password->fetch(PDO::FETCH_ASSOC);

                // Verify the current password
                if ($patient_data && password_verify($current_password, $patient_data['mot_de_passe'])) {
                    $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT); // Hash the new password
                    // Prepare and execute the UPDATE query for password
                    $stmt_update_password = $conn->prepare("UPDATE patient SET mot_de_passe = ? WHERE id_patient = ?");
                    $stmt_update_password->execute([$hashed_new_password, $id_patient]);
                    $message = "Votre mot de passe a été mis à jour avec succès.";
                } else {
                    $error = "Le mot de passe actuel est incorrect.";
                }
            } catch (PDOException $e) {
                $error = "Erreur lors de la mise à jour du mot de passe : " . $e->getMessage();
            }
        }
    } elseif ($action_type === 'update_image') {
        // --- Handle Image Upload ---
        if (isset($_FILES['image_patient']) && $_FILES['image_patient']['error'] == UPLOAD_ERR_OK) {
            $target_dir = "uploads/patients/"; // Directory where images will be saved
            // Create the directory if it doesn't exist
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true); // Consider more restrictive permissions like 0755 in production
            }

            $image_file_type = strtolower(pathinfo($_FILES['image_patient']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 5 * 1024 * 1024; // 5 MB in bytes

            // Validate file type and size
            if (!in_array($image_file_type, $allowed_types)) {
                $error = "Seuls les fichiers JPG, JPEG, PNG et GIF sont autorisés.";
            } elseif ($_FILES['image_patient']['size'] > $max_file_size) {
                $error = "La taille du fichier est trop grande. Maximum 5MB.";
            } else {
                // Generate a unique filename to prevent collisions
                $new_file_name = uniqid() . '.' . $image_file_type;
                $target_file = $target_dir . $new_file_name;

                // Move the uploaded file to the target directory
                if (move_uploaded_file($_FILES['image_patient']['tmp_name'], $target_file)) {
                    try {
                        // Get the current image path from the database to delete the old file
                        $stmt_get_current_image = $conn->prepare("SELECT image_patient FROM patient WHERE id_patient = ?");
                        $stmt_get_current_image->execute([$id_patient]);
                        $current_image = $stmt_get_current_image->fetchColumn();

                        // Prepare and execute the UPDATE query for the image path
                        $stmt_update_image = $conn->prepare("UPDATE patient SET image_patient = ? WHERE id_patient = ?");
                        $stmt_update_image->execute([$target_file, $id_patient]);

                        // Delete the old image file if it's not the default one and actually exists on disk
                        if ($current_image && $current_image !== 'New folder/default.jpg' && file_exists($current_image)) {
                            unlink($current_image);
                        }
                        $message = "Votre image de profil a été mise à jour avec succès.";
                    } catch (PDOException $e) {
                        $error = "Erreur lors de la mise à jour de l'image de profil : " . $e->getMessage();
                    }
                } else {
                    $error = "Désolé, une erreur est survenue lors du téléchargement de votre fichier.";
                }
            }
        } else {
            $error = "Veuillez sélectionner une image à télécharger.";
        }
    }
}

// --- Fetch Patient Data to Populate the Form (after any potential updates) ---
// This ensures the form is always pre-filled with the latest data.
try {
    $stmt_patient = $conn->prepare("SELECT * FROM patient WHERE id_patient = ?");
    $stmt_patient->execute([$id_patient]);
    $patient = $stmt_patient->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        // If patient is not found despite being in session, it's an integrity issue.
        // Destroy session and redirect to login.
        session_unset(); // Clear all session variables
        session_destroy(); // Destroy the session
        header("Location: login.php?error=patient_data_missing");
        exit();
    }
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des données du patient : " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres du Compte Patient</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../boxicons-master/css/boxicons.min.css">
    <style>
        /* Basic CSS for the page layout and form elements */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f0f2f5;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            background: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        h2 {
            text-align: center;
            color: rgb(72, 207, 162);
            margin-bottom: 30px;
            font-size: 2em;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 15px;
        }

        .form-section {
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fdfdfd;
        }

        .form-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5em;
            text-align: center;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1em;
            box-sizing: border-box; /* Include padding in element's total width and height */
            transition: border-color 0.3s ease;
        }

        .form-group input[type="file"] {
            padding: 8px 0;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: rgb(72, 207, 162);
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.25);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-submit {
            display: block;
            width: 100%;
            padding: 12px 20px;
            background-color:rgb(72, 207, 162);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn-submit:hover {
            background-color: rgb(72, 207, 162);
            transform: translateY(-2px);
        }

        .message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .profile-image-preview {
            text-align: center;
            margin-bottom: 20px;
        }

        .profile-image-preview img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <a href="mon_compte.php"><i class="bx bx-arrow-back" style= "color: green; font-size: 20px;"></i></a>
    <div class="container">
        <h2><i class="fas fa-cog"></i> Paramètres du Compte Patient</h2>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h3>Modifier les Informations Personnelles</h3>
            <form action="parametres_patient.php" method="POST">
                <input type="hidden" name="action_type" value="update_profile">
                <div class="form-group">
                    <label for="nom">Nom :</label>
                    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($patient['nom']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="prenom">Prénom :</label>
                    <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($patient['prenom']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($patient['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="telephone">Téléphone :</label>
                    <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($patient['telephone']) ?>">
                </div>
                <div class="form-group">
                    <label for="adresse">Adresse :</label>
                    <textarea id="adresse" name="adresse"><?= htmlspecialchars($patient['adresse']) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="sexe">Sexe :</label>
                    <select id="sexe" name="sexe" required>
                        <option value="Homme" <?= ($patient['sexe'] === 'Homme') ? 'selected' : '' ?>>Homme</option>
                        <option value="Femme" <?= ($patient['sexe'] === 'Femme') ? 'selected' : '' ?>>Femme</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Mettre à jour le profil</button>
            </form>
        </div>

        <div class="form-section">
            <h3>Modifier le Mot de Passe</h3>
            <form action="parametres_patient.php" method="POST">
                <input type="hidden" name="action_type" value="update_password">
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel :</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe :</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_new_password">Confirmer le nouveau mot de passe :</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" required>
                </div>
                <button type="submit" class="btn-submit">Modifier le mot de passe</button>
            </form>
        </div>

        <div class="form-section">
            <h3>Modifier la Photo de Profil</h3>
            <div class="profile-image-preview">
                <?php
                // Determine the image path to display
                $image_path = htmlspecialchars($patient['image_patient'] ?? 'New folder/default.jpg');
                if (!file_exists($image_path) || empty($patient['image_patient'])) {
                    $image_path = 'New folder/default.jpg'; // Fallback to a default image if none set or file not found
                }
                ?>
                <img src="<?= $image_path ?>" alt="Photo de profil">
            </div>
            <form action="parametres_patient.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action_type" value="update_image">
                <div class="form-group">
                    <label for="image_patient">Sélectionner une nouvelle image :</label>
                    <input type="file" id="image_patient" name="image_patient" accept="image/jpeg, image/png, image/gif">
                </div>
                <button type="submit" class="btn-submit">Mettre à jour la photo</button>
            </form>
        </div>

    </div>
</body>
</html>   
