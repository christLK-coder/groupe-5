if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        (position) => {
            const userLatitude = position.coords.latitude;
            const userLongitude = position.coords.longitude;
            console.log(`User's location: ${userLatitude}, ${userLongitude}`);
            // Maintenant, utilisez ces coordonnées pour trouver les hôpitaux à proximité
        },
        (error) => {
            console.error("Error getting user's location:", error);
            alert("Impossible de récupérer votre position. Veuillez activer les services de localisation.");
        }
    );
} else {
    alert("La géolocalisation n'est pas prise en charge par votre navigateur.");
}