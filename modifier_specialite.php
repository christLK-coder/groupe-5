<?php
require_once("hosto.php"); // Ensure this path is correct

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM specialite WHERE id_specialite = ?");
    $stmt->execute([$id]);
    $s = $stmt->fetch();

    if (!$s) {
        // Handle case where specialty is not found more gracefully
        // For production, consider redirecting to a list page with an error message
        die("<div style='text-align:center; padding:20px;'>Spécialité introuvable ! <br><a href='ajouter_specialite.php'>Retour aux spécialités</a></div>");
    }

    // Handle form submission for updating specialty 
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = $_POST['nom'];
        $desc = $_POST['description_specialite'];
        $id_service = $_POST['id_service'];

        // Prepare and execute the update query
        $update = $conn->prepare("UPDATE specialite SET nom = ?, description_specialite = ?, id_service = ? WHERE id_specialite = ?");
        $update->execute([$nom, $desc, $id_service, $id]);

        // Redirect after successful update to prevent form resubmission
        header("Location: ajouter_specialite.php");
        exit();
    }

    // Fetch all services for the dropdown, needed for the form
    $services = $conn->query("SELECT * FROM services")->fetchAll();

} else {
    // Handle case where ID is missing more gracefully
    // For production, consider redirecting to a list page with an error message
    die("<div style='text-align:center; padding:20px;'>ID manquant ! <br><a href='ajouter_specialite.php'>Retour aux spécialités</a></div>");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Spécialité</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #e6f7ff; /* Lighter blue background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .container-form {
            background-color: #ffffff;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15); /* Slightly stronger shadow */
            width: 100%;
            max-width: 550px; /* Slightly wider for more content */
        }

        h2 {
            color:rgb(59, 197, 151); /* Primary blue for heading */
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
            font-size: 2em;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        h2 i {
            margin-right: 10px;
        }

        label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #444;
            display: block; /* Ensure label takes full width */
        }

        .form-control, .form-select {
            border-radius: 5px;
            border: 1px solid #cceeff; /* Light blue border */
            padding: 10px 12px;
            width: 100%;
            margin-bottom: 18px; /* More spacing */
            box-sizing: border-box; /* Include padding in element's total width and height */
        }

        .form-control:focus, .form-select:focus {
            border-color: rgb(59, 197, 151);
            box-shadow: 0 0 0 0.25rem rgb(30, 146, 107);
            outline: none; /* Remove default outline */
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px; /* Slightly larger min-height for description */
        }

        .btn-primary {
            background-color: rgb(59, 197, 151);
            border-color: rgb(59, 197, 151);
            padding: 12px 25px;
            border-radius: 5px;
            font-size: 1.15em;
            width: 100%;
            transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.2s ease;
        }

        .btn-primary:hover {
            background-color: rgb(40, 161, 121);
            border-color: rgb(40, 161, 121);
            transform: translateY(-2px); /* Slight lift effect on hover */
        }

        .btn-back {
            display: inline-block;
            margin-top: 25px; /* More spacing */
            color: #6c757d;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .btn-back:hover {
            color: #495057;
            text-decoration: underline; /* Add underline on hover */
        }
    </style>
</head>
<body>
    <div class="container-form">
        <h2><i class="fas fa-flask me-2"></i>Modifier une Spécialité</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="nom" class="form-label">Nom de la Spécialité</label>
                <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($s['nom']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="description_specialite" class="form-label">Description</label>
                <textarea class="form-control" id="description_specialite" name="description_specialite" rows="4"><?= htmlspecialchars($s['description_specialite']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="id_service" class="form-label">Service associé</label>
                <select class="form-select" id="id_service" name="id_service" required>
                    <?php
                    foreach ($services as $srv) {
                        $selected = ($s['id_service'] == $srv['id_service']) ? "selected" : "";
                        echo "<option value='" . htmlspecialchars($srv['id_service']) . "' $selected>" . htmlspecialchars($srv['nom_service']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </form>
        <a href="ajouter_specialite.php" class="btn-back"><i class="fas fa-arrow-left me-2"></i>Retour à la liste des spécialités</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>