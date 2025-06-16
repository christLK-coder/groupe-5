<?php
require_once 'connexion.php';

session_start();


$patient_est_connecte = isset($_SESSION['id_patient']);
$id_patient_connecte = $patient_est_connecte ? $_SESSION['id_patient'] : null;


$id_medecin = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_medecin === 0) {
    echo "<p>Aucun médecin sélectionné. Veuillez revenir à la <a href='services.php'>page des services</a> ou à la page du département.</p>";
    exit;
}

try {
    $stmt_medecin = $pdo->prepare("
        SELECT
            M.nom,
            M.prenom,
            M.email,
            M.telephone,
            M.adresse,
            M.image_medecin,
            M.biographie,
            M.sexe,                  -- Added sexe
            S.nom AS specialite_nom,
            SVC.nom_service AS service_nom,
            AVG(N.note) AS average_rating
        FROM
            MEDECIN M
        JOIN
            specialite S ON M.id_specialite = S.id_specialite
        JOIN
            SERVICES SVC ON M.id_service = SVC.id_service
        LEFT JOIN -- Utilisez LEFT JOIN pour obtenir les infos du médecin même s'il n'a pas de notes
            NOTE N ON M.id_medecin = N.id_medecin
        WHERE
            M.id_medecin = ?
        GROUP BY
            M.id_medecin, M.nom, M.prenom, M.email, M.telephone, M.adresse, M.image_medecin, M.biographie, M.sexe, S.nom, SVC.nom_service
    "); 
    $stmt_medecin->execute([$id_medecin]);
    $medecin = $stmt_medecin->fetch(PDO::FETCH_ASSOC);

    if (!$medecin) {
        echo "<p>Médecin introuvable. Veuillez vérifier l'ID.</p>";
        exit;
    }


    $medecin_nom = htmlspecialchars($medecin['nom'] ?? 'Nom Inconnu');
    $medecin_prenom = htmlspecialchars($medecin['prenom'] ?? 'Prénom Inconnu');
    $medecin_email = htmlspecialchars($medecin['email'] ?? 'Non disponible');
    $medecin_telephone = htmlspecialchars($medecin['telephone'] ?? 'Non disponible');
    $medecin_adresse = htmlspecialchars($medecin['adresse'] ?? 'Non spécifiée');
    $medecin_image = htmlspecialchars($medecin['image_medecin'] ?? 'default_doctor_profile.jpg');
    $medecin_biographie = htmlspecialchars($medecin['biographie'] ?? 'Aucune biographie disponible pour ce médecin.');
    $medecin_sexe = htmlspecialchars($medecin['sexe'] ?? 'Non spécifié'); 
    $specialite_nom = htmlspecialchars($medecin['specialite_nom'] ?? 'Spécialité non spécifiée');
    $service_nom = htmlspecialchars($medecin['service_nom'] ?? 'Département non spécifié');

    $average_rating_display = 'Pas encore noté';
    if ($medecin['average_rating'] !== null && $medecin['average_rating'] > 0) {
        $average_rating_display = number_format($medecin['average_rating'], 1) . ' / 5';
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur lors de la récupération du profil du médecin : " . $e->getMessage() . "</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil du Dr. <?php echo $medecin_prenom . ' ' . $medecin_nom; ?></title>
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
            color: rgb(100, 210, 184);
        }

        a:hover {
            text-decoration: underline;
        }

        .profile-container {
            max-width: 900px;
            margin: 40px auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column; 
        }

        .profile-header {
            background-color:rgb(210, 246, 242);
            color: rgb(100, 210, 184);
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .profile-image-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            margin-top: -45px; 
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1;
            margin-left: auto;
            margin-right: auto;
            display: block; /* Center image */
        }

        .profile-header h1 {
            color: rgb(100, 210, 184);
            margin-top: 15px;
            margin-bottom: 5px;
            font-size: 2.5em;
        }

        .profile-header p {
            font-size: 1.2em;
            margin-top: 0;
            opacity: 0.9;
        }

        .average-rating {
            font-size: 1.1em;
            color: white;
            margin-top: 10px;
            font-weight: bold;
        }

        .profile-body {
            padding: 30px;
            display: flex; 
            gap: 30px;
        }

        .profile-info, .profile-bio {
            flex: 1;
        }

        .profile-info h2, .profile-bio h2 {
            font-size: 1.8em;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .profile-info p {
            margin-bottom: 15px;
            font-size: 1.1em;
        }

        .profile-info strong {
            color: #2c3e50;
        }

        .profile-bio p {
            line-height: 1.6;
            font-size: 1.05em;
            text-align: justify;
        }

        .chat-button-container {
            text-align: center;
            padding: 20px 30px 30px;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
        }

        .chat-button {
            display: inline-block;
            padding: 15px 30px;
            background-color:rgb(100, 210, 184);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(46, 204, 113, 0.3);
            text-decoration: none; 
        }

        .chat-button:hover {
            background-color: rgb(77, 210, 179);
            transform: translateY(-2px);
            text-decoration: none;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .profile-container {
                margin: 20px auto;
            }
            .profile-header h1 {
                font-size: 2em;
            }
            .profile-header p {
                font-size: 1em;
            }
            .profile-body {
                flex-direction: column;
                padding: 20px;
            }
            .profile-info, .profile-bio {
                width: 100%; 
            }
            .profile-info h2, .profile-bio h2 {
                font-size: 1.5em;
            }
            .profile-info p, .profile-bio p {
                font-size: 1em;
            }
            .profile-image-circle {
                margin-top: -60px; 
                width: 120px;
                height: 120px;
            }
        }

        @media (max-width: 480px) {
            .profile-header {
                padding: 20px;
            }
            .profile-header h1 {
                font-size: 1.8em;
            }
            .chat-button {
                padding: 12px 25px;
                font-size: 1.1em;
            }
        }
    </style>
</head>
<body>
    <a href="services.php"><i class="bx bx-arrow-back" style= "color: rgb(100, 210, 184); font-size: 20px; margin: 20px"></i></a>
    <div class="profile-container">
        <div class="profile-header">
            <h1>Dr. <?php echo $medecin_prenom . ' ' . $medecin_nom; ?></h1>
            <p><?php echo $specialite_nom; ?> - Département de <?php echo $service_nom; ?></p>
            <p class="average-rating">Note moyenne: <?php echo $average_rating_display; ?></p>
        </div>
        <img src="New folder/<?php echo $medecin_image; ?>" alt="Photo du Dr. <?php echo $medecin_prenom . ' ' . $medecin_nom; ?>" class="profile-image-circle">

        <div class="profile-body">
            <section class="profile-info">
                <h2>Informations de Contact</h2>
                <p><strong>Email:</strong> <a href="mailto:<?php echo $medecin_email; ?>"><?php echo $medecin_email; ?></a></p>
                <p><strong>Téléphone:</strong> <a href="tel:<?php echo $medecin_telephone; ?>"><?php echo $medecin_telephone; ?></a></p>
                <p><strong>Adresse:</strong> <?php echo $medecin_adresse; ?></p>
                <p><strong>Sexe:</strong> <?php echo $medecin_sexe; ?></p>
                <p><strong>Spécialité:</strong> <?php echo $specialite_nom; ?></p>
                <p><strong>Département:</strong> <?php echo $service_nom; ?></p>
            </section>

            <section class="profile-bio">
                <h2>À Propos du Dr. <?php echo $medecin_nom; ?></h2>
                <p><?php echo $medecin_biographie; ?></p>
            </section>
        </div>

        <div class="chat-button-container">
            <?php if ($patient_est_connecte): ?>
                <a href="chat.php?medecin_id_cible=<?php echo $id_medecin; ?>" class="chat-button">
                    Chatter avec le Dr. <?php echo $medecin_nom; ?>
                </a>
                <a href="demande_rendezvous.php?medecin_id=<?php echo $id_medecin; ?>" class="chat-button">
                    Demander un rendez-vous
                </a>
            <?php else: ?>
                <p>Vous devez être connecté pour chatter avec un médecin.</p>
                <a href="login_p.php" class="chat-button" style="background-color: #f39c12;">
                    Se connecter
                </a>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        </footer>
</body> 
</html>