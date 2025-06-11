<?php
session_start();
$message = "";

// Connexion à la BD
$conn = new mysqli("localhost", "root", "", "hosto");
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $mot_de_passe = $_POST["mot_de_passe"];

    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($admin = $result->fetch_assoc()) {
        if (password_verify($mot_de_passe, $admin["mot_de_passe"])) {
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
            background: linear-gradient(135deg, #007bff, #33ccff);
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
            color: #007bff;
            margin-bottom: 20px;
        }

        .login-container i.fa-user-shield {
            font-size: 50px;
            color: #007bff;
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
            background: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
        }

        .login-container button:hover {
            background: #0056b3;
        }

        .error {
            color: red;
            margin-bottom: 15px;
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

        <?php if ($message): ?>
            <div class="error"><i class="fas fa-exclamation-triangle"></i> <?= $message ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="mot_de_passe" placeholder="Mot de passe" required>
            <button type="submit"><i class="fas fa-sign-in-alt"></i> Connexion</button>
        </form>

        <div class="footer-note">
            <i class="fas fa-hospital-symbol"></i> Système de gestion hospitalière Hosto 2025
        </div>
    </div>
</body>
</html>
