
<?php
session_start();
require_once 'connexion.php'; // Assurez-vous que ce fichier initialise $pdo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $mot_de_passe_saisi = $_POST['mot_de_passe'] ?? ''; // Le mot de passe soumis par l'utilisateur

    if (empty($email) || empty($mot_de_passe_saisi)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        // 1. Récupérer l'utilisateur par son email
        $stmt = $pdo->prepare("SELECT * FROM MEDECIN WHERE email = ?");
        $stmt->execute([$email]);
        $medecin = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Vérifier si un médecin avec cet email existe
        if ($medecin) {
            // 3. Vérifier que le mot de passe stocké est un hachage valide
            if (strlen($medecin['mot_de_passe']) > 0 && strpos($medecin['mot_de_passe'], '$2y$') === 0) {
                // 4. Comparer le mot de passe saisi avec le hachage stocké dans la base de données
                if (password_verify($mot_de_passe_saisi, $medecin['mot_de_passe'])) {
                    // Mot de passe correct, démarrer la session
                    $_SESSION['id_medecin'] = $medecin['id_medecin'];
                    $_SESSION['nom'] = $medecin['nom'];
                    $_SESSION['prenom'] = $medecin['prenom'];
                    $_SESSION['image_medecin'] = $medecin['image_medecin'];
                    header("Location: test.php");
                    exit();
                } else {
                    // Mot de passe incorrect
                    $erreur = "Email ou mot de passe incorrect.";
                }
            } else {
                // Hachage invalide ou absent dans la base de données
                $erreur = "Erreur : le mot de passe stocké est invalide.";
            }
        } else {
            // Email non trouvé
            $erreur = "Email ou mot de passe incorrect.";
        }
    }
}
?> 

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Médecin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .login-container h2 {
            color: rgb(72, 207, 162);
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .form-control:focus {
            border-color: rgb(72, 207, 162);
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .btn-primary {
            background-color: rgb(72, 207, 162);
            border: none;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: rgb(72, 207, 162);
        }
        .alert {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Connexion Médecin</h2>
        <?php if (!empty($erreur)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Connexion</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
