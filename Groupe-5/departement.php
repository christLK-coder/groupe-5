<?php

require_once 'connexion.php';

$id_service = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_service === 0) {
    echo "<p>Aucun département sélectionné. Veuillez revenir à la <a href='services.php'>page des services</a>.</p>";
    exit;
}

// --- START: New PHP code for handling rating submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $id_medecin = isset($_POST['id_medecin']) ? intval($_POST['id_medecin']) : 0;
    $note = isset($_POST['note']) ? intval($_POST['note']) : 0;

    // Basic validation
    if ($id_medecin > 0 && $note >= 1 && $note <= 5) {
        try {
            $stmt_insert_note = $pdo->prepare("INSERT INTO note (note, id_medecin) VALUES (?, ?)");
            $stmt_insert_note->execute([$note, $id_medecin]);
            // Optional: Add a success message (e.g., using sessions or GET parameter for redirection)
            // For now, we'll just refresh the page to show the updated average.
            header("Location: departement.php?id=" . $id_service . "&rating_success=1");
            exit();
        } catch (PDOException $e) {
            // Optional: Add an error message
            // For now, silently fail or log the error
            error_log("Error inserting rating: " . $e->getMessage());
            header("Location: departement.php?id=" . $id_service . "&rating_error=1"); // Redirect with error
            exit();
        }
    } else {
        // Handle invalid input, e.g., redirect with an error message
        header("Location: departement.php?id=" . $id_service . "&rating_error=invalid");
        exit();
    }
}
// --- END: New PHP code for handling rating submission ---


try {
    $stmt_service = $pdo->prepare("SELECT nom_service, description, image_service FROM SERVICES WHERE id_service = ?");
    $stmt_service->execute([$id_service]);
    $service = $stmt_service->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        echo "<p>Département introuvable. Veuillez revenir à la <a href='services.php'>page des services</a>.</p>";
        exit;
    }


    $service_name = $service['nom_service'] ?? 'Département Inconnu';
    $service_description = $service['description'] ?? 'Description non disponible.';
    $service_image = $service['image_service'] ?? 'default_departement.jpg';

} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur lors de la récupération du département : " . $e->getMessage() . "</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Département de <?php echo htmlspecialchars($service_name); ?></title>
    <link rel="stylesheet" href="../boxicons-master/css/boxicons.min.css">
    <style>

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background-color: #f4f7f6;
            color: #333;
        }

        h1, h2, h3 {
            color: #2c3e50;
        }

        a {
            text-decoration: none;
            color: #3498db;
        }

        a:hover {
            text-decoration: underline;
        }


        .departement-hero-section {
            position: relative;
            width: 100%;
            height: 450px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .departement-hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(55%);
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }

        .departement-hero-overlay {
            position: relative;
            z-index: 2;
            color: white;
            padding: 20px;
        }

        .departement-hero-overlay h1 {
            font-size: 3.5em;
            margin-bottom: 15px;
            color: white;
        }

        .departement-hero-overlay p {
            font-size: 1.3em;
            max-width: 800px;
            margin: 0 auto;
        }


        .departement-content {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .specialties-list {
            margin-bottom: 40px;
        }

        .specialties-list h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 2em;
        }

        .specialties-list ul {
            list-style: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
        }

        .specialties-list li {
            background-color:rgb(210, 246, 242);
            color: #93d6d0;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1.1em;
            border: 1px solid #cce7f0;
        }

        .doctors-list {
            margin-top: 50px;
        }

        .doctors-list h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        .doctor-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .doctor-box {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .doctor-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .doctor-image-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: none;
            margin-bottom: 15px;
        }

        .doctor-box h3 {
            font-size: 1.4em;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .doctor-box p {
            font-size: 1em;
            color: #555;
            margin-bottom: 15px;
        }

        .doctor-box .view-profile {
            display: inline-block;
            padding: 8px 15px;
            background-color:rgb(100, 210, 200);
            color: white;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .doctor-box .view-profile:hover {
            background-color:rgb(100, 210, 184);
            text-decoration: none;
        }

        /* --- New styles for rating --- */
        .rating-form {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .rating-form label {
            font-weight: bold;
            color: #555;
        }

        .rating-form select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
        }

        .rating-form button {
            background-color: #28a745; /* Green for submit */
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .rating-form button:hover {
            background-color: #218838;
        }
        /* --- End new styles for rating --- */


        @media (max-width: 768px) {
            .departement-hero-overlay h1 {
                font-size: 2.5em;
            }

            .departement-hero-overlay p {
                font-size: 1em;
            }

            .doctor-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .departement-hero-section {
                height: 300px;
            }

            .departement-hero-overlay h1 {
                font-size: 2em;
            }

            .departement-content {
                padding: 20px 10px;
            }

            .specialties-list li {
                font-size: 0.9em;
                padding: 8px 15px;
            }
        }
    </style>
</head>
<body>
    <header class="departement-hero-section">
        <img src="<?php echo htmlspecialchars($service_image); ?>" alt="Image du département <?php echo htmlspecialchars($service_name); ?>" class="departement-hero-image">
        <div class="departement-hero-overlay">
            <a href="services.php"><i class="bx bx-arrow-back" style= "color: white; font-size: 20px;"></i></a>
            <h1>Département de <?php echo htmlspecialchars($service_name); ?></h1>
            <p><?php echo htmlspecialchars($service_description); ?></p>
        </div>
    </header>

    <main class="departement-content">
        <section class="specialties-list">
            <h2>Nos Spécialités Associées à ce Département</h2>
            <ul>
                <?php

                try {
                    $stmt_specialties = $pdo->prepare("SELECT nom FROM specialite WHERE id_service = ? AND est_active = TRUE");
                    $stmt_specialties->execute([$id_service]);

                    if ($stmt_specialties->rowCount() > 0) {
                        while ($specialty = $stmt_specialties->fetch(PDO::FETCH_ASSOC)) {
                            $specialty_name = $specialty['nom'] ?? 'Spécialité non spécifiée';
                            echo "<li>" . htmlspecialchars($specialty_name) . "</li>";
                        }
                    } else {
                        echo "<li>Aucune spécialité trouvée pour ce département.</li>";
                    }
                } catch (PDOException $e) {
                    echo "<p style='color: red;'>Erreur lors de la récupération des spécialités : " . $e->getMessage() . "</p>";
                }
                ?>
            </ul>
        </section>

        <section class="doctors-list">
            <h2>Nos Médecins Affiliés à ce Département</h2>
            <div class="doctor-grid">
                <?php

                try {
                    $stmt_medecins = $pdo->prepare("
                        SELECT
                            M.id_medecin,
                            M.nom,
                            M.prenom,
                            M.sexe,
                            S.nom AS specialite_nom,
                            M.image_medecin,
                            AVG(N.note) AS moyenne_note
                        FROM
                            MEDECIN M
                        JOIN
                            specialite S ON M.id_specialite = S.id_specialite
                        LEFT JOIN
                            note N ON M.id_medecin = N.id_medecin
                        WHERE
                            M.id_service = ? AND M.statut_disponible = TRUE
                        GROUP BY
                            M.id_medecin, M.nom, M.prenom, M.sexe, S.nom, M.image_medecin
                    ");
                    $stmt_medecins->execute([$id_service]);

                    if ($stmt_medecins->rowCount() > 0) {
                        while ($medecin = $stmt_medecins->fetch(PDO::FETCH_ASSOC)) {
                            $medecin_id = $medecin['id_medecin']; // Get the doctor's ID
                            $medecin_nom = $medecin['nom'] ?? '';
                            $medecin_prenom = $medecin['prenom'] ?? '';
                            $medecin_specialite_nom = $medecin['specialite_nom'] ?? 'Spécialité non spécifiée';
                            $medecin_image = $medecin['image_medecin'] ?? 'default_doctor.jpg';
                            $medecin_sexe = $medecin['sexe'] ?? 'Non spécifié';


                            $medecin_note_display = 'Pas encore noté';
                            if ($medecin['moyenne_note'] !== null && $medecin['moyenne_note'] > 0) {
                                $medecin_note_display = round($medecin['moyenne_note'], 1) . ' / 5';
                            }
                            ?>
                            <div class="doctor-box">
                                <img src="New folder/<?php echo htmlspecialchars($medecin_image); ?>" alt="<?php echo htmlspecialchars($medecin_nom . ' ' . $medecin_prenom); ?>" class="doctor-image-circle">
                                <h3>Nom: <?php echo htmlspecialchars($medecin_nom . ' ' . $medecin_prenom); ?></h3>
                                <p>Spécialité: <?php echo htmlspecialchars($medecin_specialite_nom); ?></p>
                                <p>Sexe: <?php echo htmlspecialchars($medecin_sexe); ?></p>
                                <p>Note: <?php echo htmlspecialchars($medecin_note_display); ?></p>
                                <a href="medecin_profil.php?id=<?php echo htmlspecialchars($medecin_id); ?>" class="view-profile">Voir le profil</a>

                                <form action="departement.php?id=<?php echo htmlspecialchars($id_service); ?>" method="POST" class="rating-form">
                                    <input type="hidden" name="id_medecin" value="<?php echo htmlspecialchars($medecin_id); ?>">
                                    <label for="note_<?php echo htmlspecialchars($medecin_id); ?>">Noter ce médecin:</label>
                                    <select name="note" id="note_<?php echo htmlspecialchars($medecin_id); ?>">
                                        <option value="1">1 Étoile</option>
                                        <option value="2">2 Étoiles</option>
                                        <option value="3">3 Étoiles</option>
                                        <option value="4">4 Étoiles</option>
                                        <option value="5">5 Étoiles</option>
                                    </select>
                                    <button type="submit" name="submit_rating">Soumettre la note</button>
                                </form>
                                </div>
                            <?php
                        }
                    } else {
                        echo "<p>Aucun médecin affilié à ce département pour le moment.</p>";
                    }
                } catch (PDOException $e) {
                    echo "<p style='color: red;'>Erreur lors de la récupération des médecins : " . $e->getMessage() . "</p>";
                }
                ?>
            </div>
        </section>
    </main>

    <footer>
    </footer>
</body>
</html>