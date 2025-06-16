<?php
require_once 'connexion.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $service = $pdo->prepare("SELECT * FROM services WHERE id_service = ?");
    $service->execute([$id]);
    $s = $service->fetch();

    if (!$s) {
        die("Service introuvable !"); // Consider a more user-friendly error page or redirect
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = $_POST['nom_service'];
        $desc = $_POST['description'];
        $stmt = $pdo->prepare("UPDATE services SET nom_service = ?, description = ? WHERE id_service = ?");
        $stmt->execute([$nom, $desc, $id]);
        header("Location: ajouter_service.php"); // Redirect to the service list after update
        exit();
    }
} else {
    die("ID manquant !"); // Consider a more user-friendly error page or redirect
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Service</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f2f5; /* Light gray background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Full viewport height */
            margin: 0;
            padding: 20px;
        }

        .container-form {
            background-color: #ffffff; /* White background for the form container */
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Soft shadow */
            width: 100%;
            max-width: 500px; /* Max width for better readability */
        }

        h2 {
            color: rgb(40, 161, 121); /* Green color for the heading */
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
            font-size: 1.8em;
        }

        label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .form-control {
            border-radius: 5px;
            border: 1px solid #ced4da;
            padding: 10px;
            width: 100%;
            margin-bottom: 15px; /* Spacing between form elements */
        }

        .form-control:focus {
            border-color: rgb(40, 161, 121);
            box-shadow: 0 0 0 0.25rem rgba(62, 188, 92, 0.25); /* Green focus glow */
        }

        textarea.form-control {
            resize: vertical; /* Allow vertical resizing */
            min-height: 100px; /* Minimum height for textarea */
        }

        .btn-primary {
            background-color: rgb(40, 161, 121); /* Green button */
            border-color: rgb(48, 174, 132);
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1.1em;
            width: 100%;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .btn-primary:hover {
            background-color: rgb(33, 132, 99); /* Darker green on hover */
            border-color: rgb(29, 139, 102);
        }

        .btn-back {
            display: inline-block;
            margin-top: 20px;
            color: #6c757d; /* Gray color for back button */
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .btn-back:hover {
            color: #495057; /* Darker gray on hover */
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container-form">
        <h2><i class="fas fa-edit me-2"></i>Modifier un Service</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="nom_service" class="form-label">Nom du Service</label>
                <input type="text" class="form-control" id="nom_service" name="nom_service" value="<?= htmlspecialchars($s['nom_service']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($s['description']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </form>
        <a href="ajouter_service.php" class="btn-back"><i class="fas fa-arrow-left me-2"></i>Retour à la liste des services</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 