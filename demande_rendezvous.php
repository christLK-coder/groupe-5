
<?php

// --- 1. Database Connection Configuration ---
$host = 'localhost'; // Replace with your database server address
$db   = 'hopital';   // Your database name
$user = 'root';     // Your username
$pass = '';         // Your password (leave empty if no password)
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

// --- 2. Session Management and Patient Authentication ---
session_start();
if (!isset($_SESSION['id_patient'])) {
    header('Location: login.php'); // Redirect to login page if patient is not logged in
    exit;
}



$id_patient_connecte = $_SESSION['id_patient'];
$id_medecin = isset($_GET['medecin_id']) ? intval($_GET['medecin_id']) : 0;

$errors = [];
$success_message = '';
$available_slots = []; // To store available time slots

// --- 3. Function to Calculate Available Slots ---
/**
 * Calculates available time slots for a given doctor on a specific date.
 *
 * @param PDO    $pdo              PDO database connection object.
 * @param int    $medecin_id       The ID of the doctor.
 * @param string $date             The date in 'YYYY-MM-DD' format.
 * @param int    $duration_per_slot The duration of each slot in minutes.
 * @return array An array of available slots, each with 'start' and 'end' times.
 */
function getAvailableSlots($pdo, $medecin_id, $date, $duration_per_slot = 30) {
    $slots = [];
    $start_of_day = new DateTime($date . ' 08:00:00'); // Doctor starts at 8 AM
    $end_of_day = new DateTime($date . ' 18:00:00');   // Doctor ends at 6 PM

    // Retrieve existing appointments for the doctor on this date
    $stmt_rdv = $pdo->prepare("SELECT date_debut, date_fin FROM rendezvous WHERE id_medecin = ? AND DATE(date_debut) = ? AND statut IN ('en_attente', 'confirmé', 'encours')");
    $stmt_rdv->execute([$medecin_id, $date]);
    $existing_appointments = $stmt_rdv->fetchAll(PDO::FETCH_ASSOC);

    $current_time = clone $start_of_day;

    while ($current_time < $end_of_day) {
        $slot_end_time = clone $current_time;
        $slot_end_time->modify("+$duration_per_slot minutes");

        // Check if this time slot overlaps with any existing appointments
        $is_available = true;
        foreach ($existing_appointments as $rdv) {
            $rdv_start = new DateTime($rdv['date_debut']);
            $rdv_end = new DateTime($rdv['date_fin']);

            // Overlap condition: (StartA < EndB) AND (EndA > StartB)
            if ($current_time < $rdv_end && $slot_end_time > $rdv_start) {
                $is_available = false;
                // If overlap, advance current time just past the end of the existing appointment
                $current_time = clone $rdv_end;
                // Ensure $current_time is aligned to the next interval if necessary
                $minute = (int)$current_time->format('i');
                if ($minute % $duration_per_slot != 0) {
                    $current_time->modify('+' . ($duration_per_slot - ($minute % $duration_per_slot)) . ' minutes');
                }
                break; // No need to check other appointments for this slot
            }
        }

        if ($is_available && $slot_end_time <= $end_of_day) { // Ensure the slot fits within the doctor's working hours
            $slots[] = [
                'start' => $current_time->format('H:i'),
                'end'   => $slot_end_time->format('H:i')
            ];
            $current_time->modify("+$duration_per_slot minutes");
        } elseif (!$is_available) {
            // If not available due to overlap, current_time has already been advanced by the loop
            continue; // Continue to the next iteration to re-evaluate based on the new current_time
        } else {
            // If not available because slot_end_time exceeds end_of_day
            break; // Stop checking, doctor's day is over
        }
    }
    return $slots;
}

// --- 4. Doctor Information Retrieval for Display ---
$medecin_nom = '';
$medecin_prenom = '';
if ($id_medecin > 0) {
    try {
        $stmt_medecin = $pdo->prepare("SELECT nom, prenom FROM MEDECIN WHERE id_medecin = ?");
        $stmt_medecin->execute([$id_medecin]);
        $doctor_info = $stmt_medecin->fetch(PDO::FETCH_ASSOC);
        if ($doctor_info) {
            $medecin_nom = htmlspecialchars($doctor_info['nom']);
            $medecin_prenom = htmlspecialchars($doctor_info['prenom']);
        } else {
            $errors[] = "Médecin introuvable.";
        }
    } catch (PDOException $e) {
        $errors[] = "Erreur lors de la récupération des informations du médecin : " . $e->getMessage();
    }
} else {
    $errors[] = "Aucun médecin sélectionné pour la prise de rendez-vous.";
}

// --- 5. Handle Form Submission (POST Request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_medecin > 0) {
    $date_rdv = filter_input(INPUT_POST, 'date_rdv', FILTER_UNSAFE_RAW);
    $heure_debut_rdv = filter_input(INPUT_POST, 'heure_debut_rdv', FILTER_UNSAFE_RAW);
    $duree_rdv_minutes = filter_input(INPUT_POST, 'duree_rdv', FILTER_VALIDATE_INT);
    $type_consultation = filter_input(INPUT_POST, 'type_consultation', FILTER_UNSAFE_RAW);
    $niveau_urgence = filter_input(INPUT_POST, 'niveau_urgence', FILTER_UNSAFE_RAW);
    $symptomes = filter_input(INPUT_POST, 'symptomes', FILTER_UNSAFE_RAW);

    // Retrieve latitude and longitude (can be empty if not needed)
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

    // If consultation is at home, coordinates are mandatory
    if ($type_consultation === 'domicile') {
        if ($latitude === false || $longitude === false || $latitude === null || $longitude === null || ($latitude == 0.0 && $longitude == 0.0)) {
            $errors[] = "Veuillez fournir des coordonnées valides pour une consultation à domicile.";
        }
    } else {
        // For other types, ensure coordinates are null or 0
        $latitude = 0.0;
        $longitude = 0.0;
    }

    // --- Validation Checks ---

    // 1. Check if date is in the past
    $current_datetime = new DateTime();
    $requested_datetime_str = $date_rdv . ' ' . $heure_debut_rdv;
    $requested_datetime = DateTime::createFromFormat('Y-m-d H:i', $requested_datetime_str);

    if (!$requested_datetime) {
        $errors[] = "Format de date ou d'heure invalide.";
    } elseif ($requested_datetime < $current_datetime) {
        $errors[] = "La date et l'heure du rendez-vous ne peuvent pas être dans le passé.";
    }

    // 2. Validate duration
    if ($duree_rdv_minutes <= 0 || $duree_rdv_minutes > 600) { // Max 10 hours = 600 minutes
        $errors[] = "La durée du rendez-vous doit être entre 1 minute et 10 heures.";
    }

    // Calculate end time
    $date_debut = $requested_datetime->format('Y-m-d H:i:s');
    $date_fin_obj = clone $requested_datetime;
    $date_fin_obj->modify("+$duree_rdv_minutes minutes");
    $date_fin = $date_fin_obj->format('Y-m-d H:i:s');

    // Add check if the requested time slot is within the doctor's working hours (8h-18h)
    $doctor_work_start = new DateTime($date_rdv . ' 08:00:00');
    $doctor_work_end = new DateTime($date_rdv . ' 18:00:00');

    if ($requested_datetime < $doctor_work_start || $date_fin_obj > $doctor_work_end) {
        $errors[] = "Le rendez-vous doit être planifié entre 8h00 et 18h00.";
    }

    // 3. Check for appointment overlaps
    if (empty($errors)) {
        try {
            $stmt_overlap = $pdo->prepare("
                SELECT COUNT(*) AS overlap_count
                FROM rendezvous
                WHERE id_medecin = ?
                  AND statut IN ('en_attente', 'confirmé', 'encours')
                  AND (
                    date_debut < ? AND date_fin > ?
                  )
            ");
            $stmt_overlap->execute([$id_medecin, $date_fin, $date_debut]);
            $overlap_result = $stmt_overlap->fetch(PDO::FETCH_ASSOC);

            if ($overlap_result['overlap_count'] > 0) {
                $errors[] = "L'heure de rendez-vous demandée chevauche un rendez-vous existant du médecin. Veuillez choisir une autre plage horaire.";
                // In case of overlap, suggest available slots for the chosen date
                $available_slots = getAvailableSlots($pdo, $id_medecin, $date_rdv, $duree_rdv_minutes);
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la vérification des chevauchements de rendez-vous : " . $e->getMessage();
        }
    }

    // If no errors, proceed with appointment insertion
    if (empty($errors)) {
        try {
            $stmt_insert_rdv = $pdo->prepare("
                INSERT INTO rendezvous (
                    date_heure, type_consultation, niveau_urgence, statut,
                    symptomes, id_patient, id_medecin, date_debut, date_fin,
                    longitude, latitude, duree_rdv
                ) VALUES (
                    ?, ?, ?, 'en_attente', ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");

            $stmt_insert_rdv->execute([
                $date_debut, // Use date_debut for date_heure for consistency
                $type_consultation,
                $niveau_urgence,
                $symptomes,
                $id_patient_connecte,
                $id_medecin,
                $date_debut,
                $date_fin,
                $longitude,
                $latitude,
                $duree_rdv_minutes
            ]);

            $success_message = "Votre demande de rendez-vous a été soumise avec succès et est en attente de confirmation.";
            // Clear form fields after successful submission (optional)
            $_POST = [];

        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'enregistrement du rendez-vous : " . $e->getMessage();
        }
    }
}

// If no specific date is selected in the form, try to get available slots for today
$target_date_for_slots = isset($_POST['date_rdv']) && !empty($_POST['date_rdv']) ? $_POST['date_rdv'] : date('Y-m-d');
if ($id_medecin > 0 && empty($errors)) { // Retrieve slots only if doctor is valid and no critical errors
    $available_slots = getAvailableSlots($pdo, $id_medecin, $target_date_for_slots);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demander un rendez-vous avec Dr. <?php echo $medecin_prenom . ' ' . $medecin_nom; ?></title>
    <link rel="stylesheet" href="style_ren.css">
</head>
<body>
    <div class="container">
        <h1>Demander un rendez-vous avec Dr. <?php echo $medecin_prenom . ' ' . $medecin_nom; ?></h1>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="success-message">
                <p><?php echo htmlspecialchars($success_message); ?></p>
                <p><a href="medecin_profile.php?id=<?php echo $id_medecin; ?>">Retour au profil du médecin</a></p>
            </div>
        <?php endif; ?>

        <div class="form-section">
            <?php if (empty($errors) || !empty($_POST)): // Show form if no critical error, or if submitted with errors ?>
            <form action="demande_rendezvous.php?medecin_id=<?php echo $id_medecin; ?>" method="POST">
                <div class="form-group">
                    <label for="date_rdv">Date du rendez-vous :</label>
                    <input type="date" id="date_rdv" name="date_rdv" required
                        value="<?php echo htmlspecialchars($_POST['date_rdv'] ?? date('Y-m-d')); ?>"
                        min="<?php echo date('Y-m-d'); ?>"
                        onchange="this.form.submit();">
                </div>

                <div class="form-group">
                    <label for="heure_debut_rdv">Heure de début du rendez-vous :</label>
                    <input type="time" id="heure_debut_rdv" name="heure_debut_rdv" required
                        value="<?php echo htmlspecialchars($_POST['heure_debut_rdv'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="duree_rdv">Durée du rendez-vous (en minutes) :</label>
                    <input type="number" id="duree_rdv" name="duree_rdv" min="1" max="600" value="<?php echo htmlspecialchars($_POST['duree_rdv'] ?? 30); ?>" required>
                </div>

                <div class="form-group">
                    <label for="type_consultation">Type de consultation :</label>
                    <select id="type_consultation" name="type_consultation" required onchange="toggleCoordsInput()">
                        <option value="hopital" <?php echo (isset($_POST['type_consultation']) && $_POST['type_consultation'] == 'hopital') ? 'selected' : ''; ?>>À l'hôpital</option>
                        <option value="en_ligne" <?php echo (isset($_POST['type_consultation']) && $_POST['type_consultation'] == 'en_ligne') ? 'selected' : ''; ?>>En ligne</option>
                        <option value="domicile" <?php echo (isset($_POST['type_consultation']) && $_POST['type_consultation'] == 'domicile') ? 'selected' : ''; ?>>À domicile</option>
                    </select>
                </div>

                <div id="coordsGroup" class="coords-group">
                    <p>Veuillez entrer les coordonnées géographiques ou laisser le système les détecter.</p>
                    <div class="form-group">
                        <label for="latitude">Latitude :</label>
                        <input type="text" id="latitude" name="latitude" placeholder="Détection auto..." readonly
                            value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="longitude">Longitude :</label>
                        <input type="text" id="longitude" name="longitude" placeholder="Détection auto..." readonly
                            value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
                    </div>
                    <button type="button" onclick="getLocation()" style="padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                        <img src="location-icon.png" alt="Loc" style="height: 16px; vertical-align: middle; margin-right: 5px;">Détecter ma position
                    </button>
                </div>

                <div class="form-group">
                    <label for="niveau_urgence">Niveau d'urgence :</label>
                    <select id="niveau_urgence" name="niveau_urgence" required>
                        <option value="normal" <?php echo (isset($_POST['niveau_urgence']) && $_POST['niveau_urgence'] == 'normal') ? 'selected' : ''; ?>>Normal</option>
                        <option value="urgent" <?php echo (isset($_POST['niveau_urgence']) && $_POST['niveau_urgence'] == 'urgent') ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="symptomes">Symptômes (décrivez brièvement) :</label>
                    <textarea id="symptomes" name="symptomes" rows="5" required><?php echo htmlspecialchars($_POST['symptomes'] ?? ''); ?></textarea>
                </div>

                <input type="submit" value="Demander le rendez-vous">
            </form>
            <?php endif; ?>
        </div>

        <div class="available-slots-section">
            <h2>Plages horaires disponibles pour le Dr. <?php echo $medecin_nom; ?> le <?php echo htmlspecialchars($target_date_for_slots); ?></h2>
            <?php if (!empty($available_slots)): ?>
                <ul>
                    <?php foreach ($available_slots as $slot): ?>
                        <li>De <?php echo $slot['start']; ?> à <?php echo $slot['end']; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-slots">Aucune plage horaire disponible pour cette date ou le médecin est pleinement réservé.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize minimum date for the date field
            const today = new Date();
            const year = today.getFullYear();
            const month = (today.getMonth() + 1).toString().padStart(2, '0');
            const day = today.getDate().toString().padStart(2, '0');
            document.getElementById('date_rdv').min = `${year}-${month}-${day}`;

            // Call geolocation function and toggle coordinate display on load
            getLocation();
            toggleCoordsInput();
        });

        // Function to retrieve user's location
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError, {
                    enableHighAccuracy: true,
                    timeout: 5000,   // 5 seconds
                    maximumAge: 0    // Do not use cache
                });
            } else {
                console.log("La géolocalisation n'est pas supportée par ce navigateur.");
            }
        }

        // Callback function on successful geolocation
        function showPosition(position) {
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
            // Make fields non-readonly and clear placeholder if position detected
            document.getElementById('latitude').readOnly = false;
            document.getElementById('longitude').readOnly = false;
            document.getElementById('latitude').placeholder = "";
            document.getElementById('longitude').placeholder = "";
            // If consultation type is "domicile", set back to readonly
            if (document.getElementById('type_consultation').value === 'domicile') {
                document.getElementById('latitude').readOnly = true;
                document.getElementById('longitude').readOnly = true;
            }
        }

        // Callback function on geolocation error
        function showError(error) {
            let message = "Erreur de géolocalisation : ";
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message += "L'utilisateur a refusé la demande de géolocalisation.";
                    break;
                case error.POSITION_UNAVAILABLE:
                    message += "L'information de localisation n'est pas disponible.";
                    break;
                case error.TIMEOUT:
                    message += "Le délai d'attente pour obtenir la position a expiré.";
                    break;
                case error.UNKNOWN_ERROR:
                    message += "Une erreur inconnue s'est produite.";
                    break;
            }
            console.error(message);
            // Make fields editable if detection failed to allow manual entry
            document.getElementById('latitude').readOnly = false;
            document.getElementById('longitude').readOnly = false;
            document.getElementById('latitude').placeholder = "Saisir la latitude";
            document.getElementById('longitude').placeholder = "Saisir la longitude";
        }

        // Function to show/hide coordinate fields and manage their requirement
        function toggleCoordsInput() {
            const typeConsultation = document.getElementById('type_consultation').value;
            const coordsGroup = document.getElementById('coordsGroup');
            const latitudeInput = document.getElementById('latitude');
            const longitudeInput = document.getElementById('longitude');

            if (typeConsultation === 'domicile') {
                coordsGroup.style.display = 'block';
                latitudeInput.setAttribute('required', 'required');
                longitudeInput.setAttribute('required', 'required');
                // If fields are empty (no detection or cleared), make them editable.
                // Otherwise, if detected, keep them read-only.
                if (latitudeInput.value === "" && longitudeInput.value === "") {
                    latitudeInput.readOnly = false;
                    longitudeInput.readOnly = false;
                    latitudeInput.placeholder = "Saisir la latitude";
                    longitudeInput.placeholder = "Saisir la longitude";
                } else {
                    latitudeInput.readOnly = true; // Keep read-only if values are present
                    longitudeInput.readOnly = true;
                    latitudeInput.placeholder = ""; // Clear placeholder if a value is there
                    longitudeInput.placeholder = "";
                }
            } else {
                coordsGroup.style.display = 'none';
                latitudeInput.removeAttribute('required');
                longitudeInput.removeAttribute('required');
                latitudeInput.readOnly = false; // Always make editable when hidden
                longitudeInput.readOnly = false;
                // Values are not cleared to allow sending them as 0 if needed
                latitudeInput.placeholder = "Détection auto...";
                longitudeInput.placeholder = "Détection auto...";
            }
        }
    </script>
</body>
</html>
