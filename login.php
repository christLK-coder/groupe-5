<?php
session_start();
require_once 'connexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM MEDECIN WHERE email = ? AND mot_de_passe = ?");
    $stmt->execute([$email, $mot_de_passe]);
    $medecin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($medecin) {
        $_SESSION['id_medecin'] = $medecin['id_medecin'];
        $_SESSION['nom'] = $medecin['nom'];
        $_SESSION['prenom'] = $medecin['prenom'];
        header("Location: rendezvous.php");
        exit();
    } else {
        $erreur = "Email ou mot de passe incorrect";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Connexion Médecin</title>
</head>
<body>
  <h2>Connexion Médecin</h2>
  <?php if (!empty($erreur)): ?><p style="color: red"><?= $erreur ?></p><?php endif; ?>
  <form method="POST">
    <label>Email : <input type="email" name="email" required></label><br><br>
    <label>Mot de passe : <input type="password" name="mot_de_passe" required></label><br><br>
    <input type="submit" value="Connexion">
  </form>
</body>
</html>

