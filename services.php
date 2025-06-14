<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Services Médicaux</title>
    <link rel="stylesheet" href="../boxicons-master/css/boxicons.min.css">
    <style>
        /* Styles généraux */
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

        /* --- Section Hero (services.php) --- */
        .hero-section {
            position: relative;
            width: 100%;
            height: 400px; /* Ajustez la hauteur selon vos besoins */
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(60%); /* Assombrit l'image pour que le texte soit lisible */
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1;
        }

        .hero-overlay {
            position: relative;
            z-index: 2;
            color: white;
            text-align: center;
            padding: 20px;
        }

        .hero-overlay h1 {
            font-size: 3em;
            margin-bottom: 10px;
            color: white;
        }

        .hero-overlay p {
            font-size: 1.2em;
            margin-top: 0;
        }


        .services-container {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .services-container h2 {
            margin-bottom: 30px;
            font-size: 2.5em;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr); 
            gap: 30px;
        }

        .service-box {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .service-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .service-image {
            width: 100%;
            height: 180px; 
            object-fit: cover;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .service-box h3 {
            margin: 15px 10px 10px;
            font-size: 1.5em;
            color:rgb(100, 210, 184);
        }

        .service-box p {
            padding: 0 15px 15px;
            font-size: 0.95em;
            flex-grow: 1;
        }

        .service-box .read-more {
            display: block;
            padding: 10px 15px;
            background-color:rgb(100, 210, 184);
            color: white;
            text-align: center;
            border-radius: 0 0 8px 8px;
            transition: background-color 0.3s ease;
            margin-top: auto; 
        }

        .service-box .read-more:hover {
            background-color:rgb(84, 214, 183);
            text-decoration: none;
        }


        @media (max-width: 768px) {
            .hero-overlay h1 {
                font-size: 2.5em;
            }

            .hero-overlay p {
                font-size: 1em;
            }

            .service-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .hero-section {
                height: 300px;
            }

            .hero-overlay h1 {
                font-size: 2em;
            }

            .services-container {
                padding: 20px 10px;
            }

            .service-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php

    $host = 'localhost'; 
    $db   = 'hopital';   
    $user = 'root';     
    $pass = '';        
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
    ?>
    <header class="hero-section">
        <img src="services.jpg" alt="Image d'en-tête des services" class="hero-image">
        <div class="hero-overlay">
             <a href="index.php"><i class="bx bx-home" style= "color: white; font-size: 20px;"></i></a>
            <h1>Nos Services Médicaux Complets</h1>
            <p>Découvrez l'étendue de nos spécialités pour prendre soin de votre santé.</p>
        </div>
    </header> 

    <main class="services-container">
        <h2>Découvrez Nos Départements</h2>
        <div class="service-grid">
            <?php
            try {

                $stmt = $pdo->query("SELECT id_service, nom_service, description, image_service FROM SERVICES");

                if ($stmt->rowCount() > 0) {
                    while ($service = $stmt->fetch(PDO::FETCH_ASSOC)) {

                        $nom_service = $service['nom_service'] ?? 'Service Inconnu';
                        $description = $service['description'] ?? '';
                        $image_service = $service['image_service'] ?? 'default_service.jpg'; 

                        $truncated_description = substr($description, 0, 150);
                        if (strlen($description) > 150) {
                            $truncated_description .= '...';
                        }
                        ?>
                        <div class="service-box" onclick="location.href='departement.php?id=<?php echo htmlspecialchars($service['id_service']); ?>'">
                            <img src="<?php echo htmlspecialchars($image_service); ?>" alt="<?php echo htmlspecialchars($nom_service); ?>" class="service-image">
                            <h3><?php echo htmlspecialchars($nom_service); ?></h3> <p><?php echo htmlspecialchars($truncated_description); ?></p>
                            <a href="departement.php?id=<?php echo htmlspecialchars($service['id_service']); ?>" class="read-more">En savoir plus</a>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p>Aucun service disponible pour le moment. Veuillez ajouter des services dans la base de données.</p>";
                }
            } catch (PDOException $e) {
                echo "<p style='color: red;'>Erreur lors du chargement des services : " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
    </main>

    <footer>
        </footer>
</body>
</html>