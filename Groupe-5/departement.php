<?php
// Configuration de la connexion à la base de données
$host = 'localhost'; // Remplacez par l'adresse de votre serveur de base de données
$db   = 'hopital';   // Le nom de votre base de données
$user = 'root';     // Votre nom d'utilisateur
$pass = '';         // Votre mot de passe (laissez vide si pas de mot de passe)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("La connexion à la base de données a échoué : " . $e->getMessage());
}

$id_service = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_service === 0) {
    echo "<p>Aucun département sélectionné. Veuillez revenir à la <a href='services.php'>page des services</a>.</p>";
    exit;
}

// Retrieve service (department) information using nom_service
try {
    $stmt_service = $pdo->prepare("SELECT nom_service, description, image_service FROM SERVICES WHERE id_service = ?");
    $stmt_service->execute([$id_service]);
    $service = $stmt_service->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        echo "<p>Département introuvable. Veuillez revenir à la <a href='services.php'>page des services</a>.</p>";
        exit;
    }

    // Use null coalescing operator (??) for robustness
    $service_name = $service['nom_service'] ?? 'Département Inconnu';
    $service_description = $service['description'] ?? 'Description non disponible.';
    $service_image = $service['image_service'] ?? 'default_departement.jpg'; // Default image if NULL

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
    <style>
        /* Styles généraux (repeated for file autonomy) */
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

        /* --- Department Hero Section (departement.php) --- */
        .departement-hero-section {
            position: relative;
            width: 100%;
            height: 450px; /* Larger than regular hero section */
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

        /* --- Department Content (departement.php) --- */
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
            grid-template-columns: repeat(3, 1fr); /* This creates 3 equal columns */
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

        /* Responsive adjustments */
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
            <h1>Département de <?php echo htmlspecialchars($service_name); ?></h1>
            <p><?php echo htmlspecialchars($service_description); ?></p>
        </div>
    </header>

    <main class="departement-content">
        <section class="specialties-list">
            <h2>Nos Spécialités Associées à ce Département</h2>
            <ul>
                <?php
                // Retrieve specialties linked to this service
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
                // Retrieve doctors affiliated with this service AND their specific specialty name
                try {
                    $stmt_medecins = $pdo->prepare("
                        SELECT 
                            M.id_medecin, 
                            M.nom, 
                            M.prenom, 
                            S.nom AS specialite_nom, -- Alias for specialty name from specialite table
                            M.image_medecin 
                        FROM 
                            MEDECIN M
                        JOIN 
                            specialite S ON M.id_specialite = S.id_specialite
                        WHERE 
                            M.id_service = ? AND M.disponible = TRUE
                    ");
                    $stmt_medecins->execute([$id_service]);

                    if ($stmt_medecins->rowCount() > 0) {
                        while ($medecin = $stmt_medecins->fetch(PDO::FETCH_ASSOC)) {
                            // Use null coalescing operator (??) for robustness
                            $medecin_nom = $medecin['nom'] ?? '';
                            $medecin_prenom = $medecin['prenom'] ?? '';
                            $medecin_specialite_nom = $medecin['specialite_nom'] ?? 'Spécialité non spécifiée'; // Using the alias
                            $medecin_image = $medecin['image_medecin'] ?? 'default_doctor.jpg'; // Default image if NULL
                            ?>
                            <div class="doctor-box">
                                <img src="<?php echo htmlspecialchars($medecin_image); ?>" alt="<?php echo htmlspecialchars($medecin_nom . ' ' . $medecin_prenom); ?>" class="doctor-image-circle">
                                <h3>Nom: <?php echo htmlspecialchars($medecin_nom . ' ' . $medecin_prenom); ?></h3>
                                <p>Specialité: <?php echo htmlspecialchars($medecin_specialite_nom); ?></p>
                                <a href="medecin_profil.php?id=<?php echo htmlspecialchars($medecin['id_medecin']); ?>" class="view-profile">Voir le profil</a>
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