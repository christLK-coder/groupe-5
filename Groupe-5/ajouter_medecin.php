<?php
require_once 'connexion.php';
$msg = "";

// Récupérer services et spécialités depuis la base
$services = $conn->query("SELECT * FROM services")->fetchAll(PDO::FETCH_ASSOC);
$specialites = $conn->query("SELECT * FROM specialite")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $sexe = $_POST['sexe'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
    $adresse = $_POST["adresse"];
    $biographie = $_POST["biographie"];
    $id_service = $_POST["id_service"];
    $id_specialite = $_POST["id_specialite"];

    // Photo du médecin
    $image_name = "";
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $image_name = basename($_FILES['photo']['name']);
        $upload_path = "New folder/" . $image_name;
        move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path);
    }


    // Insertion du médecin
    $stmt = $conn->prepare("INSERT INTO medecin 
        (nom, prenom, sexe, email, mot_de_passe, telephone, image_medecin, adresse, biographie, id_service, id_specialite) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt->execute([$nom, $prenom, $sexe, $email, $mot_de_passe, $telephone, $image_name, $adresse, $biographie, $id_service, $id_specialite])) {
        $msg = "Médecin ajouté avec succès.";
    } else {
        $msg = "Erreur lors de l'ajout.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un Médecin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #eef3f7;
            padding: 20px;
        }
        form {
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color:rgb(72, 207, 162);
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .btn-submit {
            background-color:rgb(72, 207, 162);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }
        .message {
            text-align: center;
            margin-top: 10px;
            color: green;
        }
        .back {

            margin-top: 15px;
        }
        .back a {
            text-decoration: none;
            color: rgb(72, 207, 162);
        }
        img.preview {
            max-width: 100%;
            border-radius: 10px;
            display: none;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="back">
        <a href="gestion_medecins.php" style="color= rgb(72, 207, 162)"><i class="fa-solid fa-arrow-left"></i> Retour</a>
    </div>
<form method="POST" enctype="multipart/form-data">
    <h2><i class="fa-solid fa-user-doctor"></i> Ajouter un Médecin</h2>

    <label>Nom :</label>
    <input type="text" name="nom" required>

    <label>Prénom :</label>
    <input type="text" name="prenom" required>

    <label>Email :</label>
    <input type="email" name="email" required>

    <label>Mot de passe :</label>
    <input type="password" name="mot_de_passe" required>

    <label>Téléphone :</label>
    <input type="text" name="telephone" required>

    <label for="sexe">Sexe :</label>
    <select name="sexe" id="sexe" required>
        <option value="Homme">Homme</option>
        <option value="Femme">Femme</option>
    </select>

    <label>Adresse :</label>
    <input type="text" name="adresse">

    <label>Biographie :</label>
    <textarea name="biographie" rows="5" placeholder="Parlez brièvement du médecin..."></textarea>

    <label>Service :</label>
    <select name="id_service" required>
        <option value="">-- Sélectionnez un service --</option>
        <?php foreach ($services as $service): ?>
            <option value="<?= $service['id_service'] ?>"><?= htmlspecialchars($service['nom_service']) ?></option>
        <?php endforeach; ?>
    </select>

    <!-- Aperçu image service -->
    <img id="preview_service" class="preview" alt="Aperçu Service">

    <label>Spécialité :</label>
    <select name="id_specialite" required>
        <option value="">-- Sélectionnez une spécialité --</option>
        <?php foreach ($specialites as $specialite): ?>
            <option value="<?= $specialite['id_specialite'] ?>"><?= htmlspecialchars($specialite['nom']) ?></option>
        <?php endforeach; ?>
    </select>

    <!-- Aperçu image spécialité -->
    <img id="preview_specialite" class="preview" alt="Aperçu Spécialité">

    <label>Photo du Médecin :</label>
    <input type="file" name="photo" accept="image/*" required>

    <button type="submit" class="btn-submit"><i class="fa-solid fa-plus"></i> Ajouter</button>

    <?php if ($msg): ?>
        <div class="message"><?= $msg ?></div>
    <?php endif; ?>


</form>

<!-- Script JS : affichage dynamique des images -->
<script>
    const services = <?= json_encode($services) ?>;
    const specialites = <?= json_encode($specialites) ?>;

    document.querySelector('[name="id_service"]').addEventListener('change', function () {
        const selectedId = this.value;
        const service = services.find(s => s.id_service == selectedId);
        if (service && service.image_service) {
            document.getElementById('preview_service').src = 'New folder/' + service.image_service;
            document.getElementById('preview_service').style.display = 'block';
        } else {
            document.getElementById('preview_service').style.display = 'none';
        }
    });

    document.querySelector('[name="id_specialite"]').addEventListener('change', function () {
        const selectedId = this.value;
        const specialite = specialites.find(s => s.id_specialite == selectedId);
        if (specialite && specialite.image_specialite) {
            document.getElementById('preview_specialite').src = 'New folder/' + specialite.image_specialite;
            document.getElementById('preview_specialite').style.display = 'block';
        } else {
            document.getElementById('preview_specialite').style.display = 'none';
        }
    });
</script>
</body>
</html>
