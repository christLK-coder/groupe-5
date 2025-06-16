<?php
session_start();
$message = "";

// Inclusion du fichier de connexion à la base de données
require_once 'connexion.php'; // Assure-toi que le fichier s'appelle bien connexion.php et est dans le même dossier

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $mot_de_passe = $_POST["mot_de_passe"];

    try {
        // Préparer la requête SQL
        $stmt = $pdo->prepare("SELECT id_admin, nom, mot_de_passe FROM admin WHERE email = :email");

        // Lier les paramètres
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);

        // Exécuter la requête
        $stmt->execute();

        // Récupérer les données
        $admin = $stmt->fetch();

        if ($admin) {
            // Vérifier le mot de passe avec password_verify
            if (password_verify($mot_de_passe, $admin["mot_de_passe"])) {
                // Connexion réussie
                $_SESSION["admin_id"] = $admin["id_admin"];
                $_SESSION["admin_nom"] = $admin["nom"];
                header("Location: dashboard_admin.php");
                exit();
            } else {
                $message = "Mot de passe incorrect.";
            }
        } else {
            $message = "Email non trouvé.";
        }
    } catch (\PDOException $e) {
        error_log("Erreur DB : " . $e->getMessage());
        $message = "Une erreur est survenue. Veuillez réessayer.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion Admin - Hosto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #f3fbfa;
            font-family: Arial, sans-serif;
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            width: 350px;
            text-align: center;
        }

        .login-container h2 {
            color: rgb(72, 207, 162);
            margin-bottom: 20px;
        }

        .login-container i.fa-user-shield {
            font-size: 50px;
            color: rgb(72, 207, 162);
            margin-bottom: 10px;
        }

        .login-container input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .login-container button {
            width: 100%;
            padding: 10px;
            background: rgb(72, 207, 162);
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
        }

        .login-container button:hover {
            background: rgb(50, 180, 130);
        }

        .error {
            color: red;
            margin-bottom: 15px;
            font-weight: bold;
        }

        .footer-note {
            margin-top: 15px;
            font-size: 12px;
            color: #555;
        }

        .footer-note i {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <i class="fas fa-user-shield"></i>
        <h2>Connexion Admin</h2>

        <?php if (!empty($message)): ?>
            <div class="error"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="email" name="email" placeholder="Email" required autocomplete="email">
            <input type="password" name="mot_de_passe" placeholder="Mot de passe" required autocomplete="current-password">
            <button type="submit"><i class="fas fa-sign-in-alt"></i> Connexion</button>
        </form>

        <div class="footer-note">
            <i class="fas fa-hospital-symbol"></i> Système de gestion hospitalière Hosto 2025
        </div>
    </div>
</body>
</html>
