<?php
session_start();

// Assurez-vous que ce fichier gère correctement la connexion PDO
// et est accessible depuis cette page.
require_once 'connexion.php'; // Assurez-vous que le chemin vers connexion.php est correct

// Activer l'affichage des erreurs pour le développement (à désactiver en production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction pour obtenir la date actuelle à Yaoundé (fuseau horaire WAT)
function getCurrentYaoundeDate() {
    $dateTime = new DateTime('now', new DateTimeZone('Africa/Douala')); // Yaoundé est dans le même fuseau que Douala
    return $dateTime->format('Y-m-d');
}

// Vérifier l'authentification du médecin
if (!isset($_SESSION['id_medecin'])) {
    // Rediriger vers la page de connexion si non authentifié
    header('Location: login.php');
    exit();
}

$id_medecin = $_SESSION['id_medecin'];
// Récupérer les informations du médecin depuis la session
// Assurez-vous que ces variables de session sont bien définies lors de la connexion.
$nom = $_SESSION['nom'] ?? 'Nom';
$prenom = $_SESSION['prenom'] ?? 'Prénom';
$image_medecin = $_SESSION['image_medecin'] ?? 'default.jpg'; // Image par défaut si non définie

// Clé API Google Maps
// ATTENTION: Cette clé est visible côté client. Restreignez-la dans la console Google Cloud
// pour qu'elle ne soit utilisable que par votre domaine (ex: votre-domaine.com/*)
// et pour les API spécifiques (Maps JavaScript API, Directions API).
$Maps_api_key = 'AIzaSyCx6_tEAJWH1neOhEwR7seSg6cHPclKREg'; // Remplacez par votre clé API réelle

// --- Logique de gestion des requêtes AJAX (backend intégré) ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json'); // S'assurer que la réponse est en JSON

    switch ($_GET['action']) {
        case 'getDailyAppointments':
            // Utilise la date de Yaoundé pour la comparaison
            $current_date = getCurrentYaoundeDate();
            try {
                $stmt = $pdo->prepare("
                    SELECT
                        r.id_rdv,
                        r.date_debut,
                        r.type_consultation,
                        r.niveau_urgence,
                        r.statut,
                        r.latitude,
                        r.longitude,
                        p.nom AS nom_patient,    -- Alias 'nom_patient' pour la colonne 'nom' de PATIENT
                        p.prenom AS prenom_patient -- Alias 'prenom_patient' pour la colonne 'prenom' de PATIENT
                    FROM RENDEZVOUS r
                    JOIN PATIENT p ON r.id_patient = p.id_patient
                    WHERE r.id_medecin = :id_medecin
                    AND DATE(r.date_debut) = :current_date
                    AND r.type_consultation = 'domicile' -- Assurez-vous que c'est 'domicile' et non 'à domicile' selon votre enum
                    AND r.statut IN ('confirmé', 'encours')
                    ORDER BY r.date_debut ASC
                ");
                $stmt->execute([
                    ':id_medecin' => $id_medecin,
                    ':current_date' => $current_date
                ]);
                echo json_encode(['success' => true, 'appointments' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } catch (PDOException $e) {
                error_log("Database Error (getDailyAppointments): " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Erreur lors du chargement des rendez-vous.']);
                http_response_code(500); // Internal Server Error
            }
            exit(); // Terminer l'exécution du script après la réponse AJAX

        case 'getAppointmentDetails':
            if (isset($_GET['id_rdv'])) {
                $id_rdv = $_GET['id_rdv'];
                try {
                    $stmt = $pdo->prepare("
                        SELECT
                            r.latitude,
                            r.longitude,
                            p.nom AS nom_patient,
                            p.prenom AS prenom_patient
                        FROM RENDEZVOUS r
                        JOIN PATIENT p ON r.id_patient = p.id_patient
                        WHERE r.id_rdv = :id_rdv AND r.id_medecin = :id_medecin
                    ");
                    $stmt->execute([':id_rdv' => $id_rdv, ':id_medecin' => $id_medecin]);
                    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($appointment) {
                        echo json_encode(['success' => true, 'appointment' => $appointment]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Rendez-vous non trouvé ou non autorisé.']);
                        http_response_code(404); // Not Found
                    }
                } catch (PDOException $e) {
                    error_log("Database Error (getAppointmentDetails): " . $e->getMessage());
                    echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération des détails.']);
                    http_response_code(500); // Internal Server Error
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'ID de rendez-vous manquant.']);
                http_response_code(400); // Bad Request
            }
            exit(); // Terminer l'exécution du script après la réponse AJAX

        default:
            echo json_encode(['success' => false, 'error' => 'Action non valide.']);
            http_response_code(400); // Bad Request
            exit(); // Terminer l'exécution du script après la réponse AJAX
    }
}
// --- Fin de la logique AJAX ---
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itinéraires Rendez-vous Domicile - Dr. <?= htmlspecialchars($prenom . ' ' . $nom) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            display: flex;
            min-height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: 250px;
            background-color: #FFFFFF;
            position: sticky;
            top: 0;
            left: 0;
            bottom: 0;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .sidebar .profile {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        .sidebar .profile img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 10px;
            border: 2px solid #93d6d0;
        }
        .sidebar .profile h4 {
            margin: 5px 0;
            color: #333;
            font-size: 16px;
        }
        .sidebar .nav {
            padding-top: 20px;
            flex-grow: 1;
        }
        .sidebar .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            font-size: 15px;
            transition: background-color 0.3s, color 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #93d6d0;
            color: #FFFFFF;
        }
        .sidebar .nav-link .material-icons {
            margin-right: 10px;
            font-size: 20px;
        }

        #main-content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: row;
            padding: 20px;
            gap: 20px;
            overflow: hidden;
        }

        #map-container {
            flex: 2;
            background-color: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            min-height: 500px;
            position: relative;
        }
        #map {
            height: 100%;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }
        #appointments-sidebar {
            flex: 1;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow-y: auto;
            min-width: 300px;
        }
        .appointment-list-title {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.5rem;
        }
        .table-responsive {
            margin-top: 10px;
        }
        .loading-text {
            text-align: center;
            color: #6c757d;
            padding: 15px;
        }
        #route-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #e9ecef;
            border-radius: 5px;
            font-size: 0.95rem;
            color: #333;
        }
        #geolocation-status {
            font-size: 0.85rem;
            margin-bottom: 15px;
            padding: 8px 12px;
            border-radius: 5px;
        }
        .text-success-status {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .text-danger-status {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .btn-route {
            background-color: #93d6d0;
            border-color: #93d6d0;
            color: white;
            padding: 5px 10px;
            font-size: 0.85rem;
            border-radius: 5px;
        }
        .btn-route:hover {
            background-color: #7ababa;
            border-color: #7ababa;
            color: white;
        }

        /* Media Queries pour la responsivité */
        @media (max-width: 992px) {
            #main-content-wrapper {
                flex-direction: column;
                padding: 15px;
                gap: 15px;
            }
            #map-container {
                min-height: 400px;
            }
            #appointments-sidebar {
                min-width: unset;
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 10px 0;
            }
            .sidebar .profile h4, .sidebar .nav-link span {
                display: none;
            }
            .sidebar .profile img {
                width: 40px;
                height: 40px;
            }
            .sidebar .nav-link {
                justify-content: center;
            }
            #main-content-wrapper {
                margin-left: 70px;
                padding: 10px;
            }
        }
    </style>
</head>
<body> 
    <div class="sidebar">
        <div class="profile">
            <img src="New folder/<?= htmlspecialchars($image_medecin) ?>" alt="Profil">
            <h4>Dr. <?= htmlspecialchars($nom . ' ' . $prenom) ?></h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"> <span class="material-icons">house</span>
                <span>Home</span>
            </a>
            <a class="nav-link" href="test.php"> <span class="material-icons">dashboard</span>
                <span>Dashboard</span>
            </a>
            <a class="nav-link" href="rendezvous.php">
                <span class="material-icons">event</span>
                <span>Rendez-vous</span>
            </a>
            <a class="nav-link" href="messages.php">
                <span class="material-icons">chat</span>
                <span>Messages</span>
            </a>
            <a class="nav-link" href="diagnostics.php">
                <span class="material-icons">medical_services</span>
                <span>Diagnostic</span>
            </a>
            <a class="nav-link" href="historique.php">
                <span class="material-icons">history</span>
                <span>Historique</span>
            </a>
            <a class="nav-link active" href="api.php"> <span class="material-icons">map</span>
                <span>Carte RDV</span>
            </a>
            <a class="nav-link" href="profil.php">
                <span class="material-icons">settings</span>
                <span>Paramètres</span>
            </a>
            <a class="nav-link" href="logout.php">
                <span class="material-icons">logout</span>
                <span>Déconnexion</span>
            </a>
        </nav>
    </div>

    <div id="main-content-wrapper">
        <div id="map-container">
            <div id="map"></div>
        </div>

        <div id="appointments-sidebar">
            <h3 class="appointment-list-title">Rendez-vous à domicile du jour</h3>
            <p id="geolocation-status" class="alert d-flex align-items-center mb-3" role="alert"></p>
            <div id="route-info" class="mb-3">Sélectionnez un rendez-vous pour voir l'itinéraire.</div>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Heure</th>
                            <th>Patient</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="appointments-table-body">
                        <tr><td colspan="3" class="loading-text">Chargement des rendez-vous...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales pour la carte et les services
        let map;
        let userLocation; // Stocke la position actuelle de l'utilisateur
        let markers = []; // Tableau pour stocker les marqueurs des rendez-vous
        let directionsService; // Service pour calculer les itinéraires
        let directionsRenderer; // Pour afficher l'itinéraire sur la carte
        let infoWindow; // Pour afficher les informations sur les marqueurs

        // URL de cette même page pour les requêtes AJAX
        const API_URL = window.location.pathname;

        /**
         * Initialise la carte Google Maps et les services.
         * Fonction appelée par le script Google Maps API après son chargement.
         */
        function initMap() {
            // Position par défaut centrée sur Yaoundé, Cameroun
            // Cette position sera utilisée si la géolocalisation de l'utilisateur échoue et qu'il n'y a pas de RDV.
            const defaultLocation = { lat: 3.8480, lng: 11.5021 }; 

            // Création de la carte
            map = new google.maps.Map(document.getElementById("map"), {
                center: defaultLocation,
                zoom: 12, // Niveau de zoom initial
            });

            // Initialisation des services Google Maps nécessaires
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({ map: map });
            infoWindow = new google.maps.InfoWindow(); // Fenêtre d'information pour les marqueurs

            // Tenter d'obtenir la position de l'utilisateur et charger les rendez-vous
            getUserLocation();
            fetchDailyAppointments();

            // Rafraîchir les rendez-vous toutes les minutes (60 secondes)
            setInterval(fetchDailyAppointments, 60000);
        }

        /**
         * Tente de récupérer la position géographique actuelle de l'utilisateur.
         */
        function getUserLocation() {
            const statusDiv = document.getElementById('geolocation-status');
            statusDiv.innerHTML = `<span class="material-icons me-2">location_searching</span>Détection de votre position...`;
            statusDiv.className = `alert d-flex align-items-center mb-3 alert-info`;

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };
                        // Ajouter un marqueur pour la position de l'utilisateur
                        new google.maps.Marker({
                            position: userLocation,
                            map: map,
                            title: "Votre position actuelle",
                            icon: {
                                // Une icône bleue pour distinguer la position du médecin
                                url: "http://maps.google.com/mapfiles/ms/icons/blue-dot.png" 
                            }
                        });
                        map.setCenter(userLocation); // Centrer la carte sur l'utilisateur
                        statusDiv.innerHTML = `<span class="material-icons me-2">check_circle</span>Position actuelle détectée.`;
                        statusDiv.className = `alert d-flex align-items-center mb-3 text-success-status`;
                    },
                    (error) => {
                        // Gérer les erreurs de géolocalisation
                        handleLocationError(true, map.getCenter(), error.message);
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 } // Options pour une meilleure précision
                );
            } else {
                // Le navigateur ne supporte pas la géolocalisation
                handleLocationError(false, map.getCenter());
            }
        }

        /**
         * Gère les erreurs de géolocalisation et affiche un message à l'utilisateur.
         * @param {boolean} browserHasGeolocation - Vrai si le navigateur supporte la géolocalisation.
         * @param {object} pos - Position par défaut ou actuelle de la carte.
         * @param {string} [errorMessage] - Message d'erreur spécifique de la géolocalisation.
         */
        function handleLocationError(browserHasGeolocation, pos, errorMessage = '') {
            const statusDiv = document.getElementById('geolocation-status');
            statusDiv.className = `alert d-flex align-items-center mb-3 text-danger-status`;
            if (browserHasGeolocation) {
                statusDiv.innerHTML = `<span class="material-icons me-2">error</span>Erreur : La géolocalisation a échoué. ${errorMessage ? `(${errorMessage})` : ''} Veuillez activer la localisation (HTTPS requis).`;
            } else {
                statusDiv.innerHTML = `<span class="material-icons me-2">error</span>Erreur : Votre navigateur ne supporte pas la géolocalisation.`;
            }
            map.setCenter(pos); // Rester sur la position par défaut si la géolocalisation échoue
        }

        /**
         * Supprime tous les marqueurs actuels de la carte (sauf la position de l'utilisateur).
         */
        function clearMarkers() {
            for (let i = 0; i < markers.length; i++) {
                markers[i].setMap(null);
            }
            markers = []; // Vider le tableau des marqueurs
        }

        /**
         * Récupère les rendez-vous "à domicile" du jour via AJAX et les affiche sur la carte et dans le tableau.
         */
        async function fetchDailyAppointments() {
            try {
                // Requête AJAX vers la même page, avec l'action 'getDailyAppointments'
                const response = await fetch(`${API_URL}?action=getDailyAppointments`);
                const data = await response.json();

                if (data.success) {
                    clearMarkers(); // Supprimer les anciens marqueurs de rendez-vous
                    const tbody = document.getElementById('appointments-table-body');
                    tbody.innerHTML = ''; // Vider le tableau HTML

                    if (data.appointments.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Aucun rendez-vous à domicile pour aujourd\'hui.</td></tr>';
                    } else {
                        // Créer un Bounds object pour ajuster le zoom de la carte
                        const bounds = new google.maps.LatLngBounds();
                        if (userLocation) {
                           bounds.extend(userLocation); // Inclure la position de l'utilisateur dans les limites
                        }


                        data.appointments.forEach(appt => {
                            // Convertir les coordonnées en nombres flottants
                            const apptLat = parseFloat(appt.latitude);
                            const apptLng = parseFloat(appt.longitude);

                            // Vérifier si les coordonnées sont valides (non NaN et non nulles)
                            if (!isNaN(apptLat) && !isNaN(apptLng) && apptLat !== 0 && apptLng !== 0) {
                                const apptLocation = { lat: apptLat, lng: apptLng };

                                // Créer et ajouter un marqueur pour le rendez-vous
                                const marker = new google.maps.Marker({
                                    position: apptLocation,
                                    map: map,
                                    title: `RDV avec ${appt.prenom_patient} ${appt.nom_patient}`,
                                    icon: {
                                        // Une icône rouge pour les rendez-vous
                                        url: "http://maps.google.com/mapfiles/ms/icons/red-dot.png"
                                    }
                                });
                                markers.push(marker); // Ajouter le marqueur au tableau
                                bounds.extend(apptLocation); // Inclure le RDV dans les limites

                                // Ajouter un écouteur de clic pour afficher l'info-fenêtre
                                marker.addListener("click", () => {
                                    infoWindow.setContent(`
                                        <h5>${htmlspecialchars(appt.prenom_patient)} ${htmlspecialchars(appt.nom_patient)}</h5>
                                        <p>Heure: <strong>${new Date(appt.date_début).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</strong></p>
                                        <p>Type: ${htmlspecialchars(appt.type_consultation)}</p>
                                        <p>Statut: ${htmlspecialchars(appt.statut)}</p>
                                        <button class="btn btn-sm btn-route mt-2" onclick="showRoute(${appt.id_rdv})">Voir l'itinéraire</button>
                                    `);
                                    infoWindow.open(map, marker);
                                });

                                // Ajouter la ligne au tableau des rendez-vous
                                const row = tbody.insertRow();
                                row.innerHTML = `
                                    <td>${new Date(appt.date_début).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</td>
                                    <td>${htmlspecialchars(appt.nom_patient)} ${htmlspecialchars(appt.prenom_patient)}</td>
                                    <td><button class="btn btn-sm btn-route" onclick="showRoute(${appt.id_rdv})">Itinéraire</button></td>
                                `;
                            } else {
                                console.warn(`Rendez-vous ID ${appt.id_rdv} ignoré: Coordonnées (latitude/longitude) invalides ou manquantes.`);
                            }
                        });
                        // Ajuster le zoom de la carte pour inclure tous les marqueurs et la position de l'utilisateur
                        if (!bounds.isEmpty()) { // S'il y a des points dans les limites
                            map.fitBounds(bounds);
                            // Optionnel: si un seul point, définir un zoom plus proche
                            if (bounds.getNorthEast().equals(bounds.getSouthWest())) {
                                map.setZoom(14);
                            }
                        }
                    }
                } else {
                    console.error('Erreur lors du chargement des rendez-vous:', data.error);
                    document.getElementById('appointments-table-body').innerHTML = `<tr><td colspan="3" class="text-danger">Erreur: ${htmlspecialchars(data.error)}</td></tr>`;
                }
            } catch (error) {
                console.error('Erreur réseau ou serveur lors du chargement des rendez-vous:', error);
                document.getElementById('appointments-table-body').innerHTML = `<tr><td colspan="3" class="text-danger">Impossible de charger les rendez-vous. Vérifiez votre connexion.</td></tr>`;
            }
        }

        /**
         * Calcule et affiche l'itinéraire entre la position de l'utilisateur et le rendez-vous sélectionné.
         * @param {number} id_rdv - L'ID du rendez-vous pour lequel afficher l'itinéraire.
         */
        async function showRoute(id_rdv) {
            if (!userLocation) {
                alert("Veuillez activer la géolocalisation pour calculer l'itinéraire.");
                document.getElementById('route-info').innerHTML = `<span class="material-icons me-2">info</span>Impossible de calculer l'itinéraire sans votre position.`;
                return;
            }

            try {
                // Requête AJAX pour obtenir les détails du rendez-vous (latitude, longitude)
                const response = await fetch(`${API_URL}?action=getAppointmentDetails&id_rdv=${id_rdv}`);
                const data = await response.json();

                if (data.success && data.appointment) {
                    const destination = { lat: parseFloat(data.appointment.latitude), lng: parseFloat(data.appointment.longitude) };

                    // Cacher les marqueurs de rendez-vous lorsqu'un itinéraire est affiché pour plus de clarté
                    clearMarkers();
                    directionsRenderer.setDirections({ routes: [] }); // Effacer l'itinéraire précédent

                    // Calcul de l'itinéraire
                    directionsService.route(
                        {
                            origin: userLocation,
                            destination: destination,
                            travelMode: google.maps.TravelMode.DRIVING, // Mode de transport (conduite)
                        },
                        (response, status) => {
                            if (status === "OK") {
                                directionsRenderer.setDirections(response); // Afficher l'itinéraire sur la carte
                                const route = response.routes[0].legs[0]; // Première étape du premier itinéraire

                                // Afficher la distance et la durée estimée
                                document.getElementById('route-info').innerHTML = `
                                    <strong>Itinéraire vers ${htmlspecialchars(data.appointment.prenom_patient)} ${htmlspecialchars(data.appointment.nom_patient)}:</strong><br>
                                    Distance: ${route.distance.text}<br>
                                    Durée estimée: ${route.duration.text}
                                `;
                            } else {
                                console.error("Impossible de calculer l'itinéraire : " + status);
                                document.getElementById('route-info').innerHTML = `<span class="material-icons me-2">warning</span>Impossible de calculer l'itinéraire: ${status}`;
                            }
                        }
                    );
                } else {
                    console.error(`Erreur: ${data.error}`);
                    alert(`Erreur: ${data.error}`);
                    document.getElementById('route-info').innerHTML = `<span class="material-icons me-2">error</span>Détails du rendez-vous non trouvés.`;
                }
            } catch (error) {
                console.error('Erreur lors de la récupération des détails du rendez-vous:', error);
                alert("Erreur lors de la récupération des détails du rendez-vous.");
                document.getElementById('route-info').innerHTML = `<span class="material-icons me-2">error</span>Erreur lors de la récupération des détails du rendez-vous.`;
            }
        }

        // Fonction d'échappement HTML pour prévenir les failles XSS
        function htmlspecialchars(str) {
            if (typeof str != 'string') return str;
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }
    </script>

    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?= htmlspecialchars($Maps_api_key) ?>&callback=initMap"></script>

</body>
</html>