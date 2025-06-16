<?php
session_start();

// Vérification de la connexion du patient
if (!isset($_SESSION['id_patient'])) {
    header("Location: login.php"); // Rediriger vers la page de connexion si le patient n'est pas connecté
    exit;
}

// Connexion à la base de données (comme dans votre code original)
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

$id_medecin = isset($_GET['medecin_id']) ? intval($_GET['medecin_id']) : 0;

if ($id_medecin === 0) {
    echo "<p>Médecin non spécifié.</p>";
    exit;
}

// Récupérer les rendez-vous existants du médecin
try {
    $stmt_rendezvous = $pdo->prepare("SELECT date_heure, statut FROM rendezvous WHERE id_medecin = ? AND id_patient = ?");
    $stmt_rendezvous->execute([$id_medecin, $_SESSION['id_patient']]);
    $rendezvous_existants = $stmt_rendezvous->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p style='color:red;'>Erreur lors de la récupération des rendez-vous : " . $e->getMessage() . "</p>";
    $rendezvous_existants = []; // Pour éviter les erreurs plus tard
}

// Traitement du formulaire de demande de rendez-vous
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_heure = $_POST['date_heure'];
    $type_consultation = $_POST['type_consultation'];
    $niveau_urgence = $_POST['niveau_urgence'];
    $symptomes = $_POST['symptomes'];
    $longitude = $_POST['longitude'];
    $latitude = $_POST['latitude'];

    // Validation des données (à adapter selon vos besoins)
    if (empty($date_heure) || empty($type_consultation)) {
        echo "<p style='color:red;'>Veuillez remplir tous les champs obligatoires.</p>";
    } else {
        // Insertion du rendez-vous dans la base de données
        try {
            $stmt_insertion = $pdo->prepare("INSERT INTO rendezvous (date_heure, type_consultation, niveau_urgence, symptomes, id_patient, id_medecin, longitude, latitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_insertion->execute([$date_heure, $type_consultation, $niveau_urgence, $symptomes, $_SESSION['id_patient'], $id_medecin, $longitude, $latitude]);
            echo "<p style='color:green;'>Votre demande de rendez-vous a été enregistrée. Elle est en attente de confirmation.</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red;'>Erreur lors de l'enregistrement du rendez-vous : " . $e->getMessage() . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de rendez-vous avec le Dr. ...</title>
    <style>
        /* Styles CSS (à adapter) */
    </style>
</head>
<body>
    <h1>Demande de rendez-vous avec le Dr. ...</h1>

    <?php if (!empty($rendezvous_existants)): ?>
        <h2>Vos rendez-vous existants avec ce médecin :</h2>
        <ul>
            <?php foreach ($rendezvous_existants as $rendezvous): ?>
                <li>
                    <?php echo htmlspecialchars($rendezvous['date_heure']); ?> -
                    Statut : <?php echo htmlspecialchars($rendezvous['statut']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="post">
        <label for="date_heure">Date et heure souhaitées :</label>
        <input type="datetime-local" id="date_heure" name="date_heure" required><br><br>

        <label for="type_consultation">Type de consultation :</label>
        <select id="type_consultation" name="type_consultation" required>
            <option value="hopital">Hôpital</option>
            <option value="en_ligne">En ligne</option>
            <option value="domicile">À domicile</option>
        </select><br><br>

        <label for="niveau_urgence">Niveau d'urgence :</label>
        <select id="niveau_urgence" name="niveau_urgence">
            <option value="normal">Normal</option>
            <option value="urgent">Urgent</option>
        </select><br><br>

        <label for="symptomes">Symptômes (facultatif) :</label><br>
        <textarea id="symptomes" name="symptomes" rows="4" cols="50"></textarea><br><br>

        <input type="hidden" id="longitude" name="longitude">
        <input type="hidden" id="latitude" name="latitude">

        <button type="submit">Demander un rendez-vous</button>
    </form>

    <script>
        // Récupération de la position du patient
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                document.getElementById("longitude").value = position.coords.longitude;
                document.getElementById("latitude").value = position.coords.latitude;
            }, function(error) {
                console.error("Erreur lors de la récupération de la position :", error);
                alert("Impossible de récupérer votre position. Veuillez l'activer dans les paramètres de votre navigateur.");
            });
        } else {
            alert("La géolocalisation n'est pas prise en charge par votre navigateur.");
        }

        // Fonction pour valider la date et l'heure (à adapter selon vos besoins)
        function validerDateHeure(dateHeure) {
            // Exemple : Vérifier si la date et l'heure sont dans le futur
            return new Date(dateHeure) > new Date();
        }

        // Exemple d'écouteur d'événement pour la soumission du formulaire
        document.querySelector('form').addEventListener('submit', function(event) {
            const dateHeure = document.getElementById('date_heure').value;
            if (!validerDateHeure(dateHeure)) {
                event.preventDefault(); // Empêcher la soumission du formulaire
                alert("Veuillez sélectionner une date et une heure futures.");
            }
        });
    </script>
</body>
</html>