<?php
require_once 'connexion.php';

// The initial PHP query for patients is no longer strictly needed
// as JavaScript will handle fetching the table content on page load.
// We can set $patients to an empty array or remove it.
$patients = []; // Initialize as empty, JS will populate
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Patients</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background-color: #f3fbfa;
        }
        h2 {
            color: rgb(72, 207, 162);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }
        th {
            background-color: #d4edda;
        }
        a {
            text-decoration: none;
            color: rgb(72, 207, 162);
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
        form {
            margin-bottom: 20px;
        }
        input[type="text"] {
            padding: 8px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 4px; /* Added border-radius for aesthetics */
        }
        button {
            padding: 8px 15px;
            background-color: rgb(72, 207, 162);
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px; /* Added border-radius for aesthetics */
        }
        button:hover {
            background-color: rgb(39, 183, 135);
        }
    </style>
</head>
<body>

<a href="dashboard_admin.php" style="margin-top: 20px;"><i class="fas fa-arrow-left"></i> Retour</a>

<h2><i class="fas fa-user-injured"></i> Liste des Patients</h2>

<form id="searchForm">
    <input type="text" name="recherche" id="searchInput" placeholder="Rechercher par nom ou prénom" value="">
    <button type="submit"><i class="fas fa-search"></i> Rechercher</button>
</form>

<table>
    <thead>
        <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Sexe</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody id="patientsTableBody">
        </tbody>
</table>

<script> 
    // Function to fetch and update the patient table
    function fetchAndDisplayPatients(searchTerm = '') {
        // Construct the URL for the AJAX request
        const url = `get_patients_table.php?recherche=${encodeURIComponent(searchTerm)}`;

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.text(); // Get the response as plain text (HTML)
            })
            .then(html => {
                // Update the content of the tbody with the fetched HTML
                document.getElementById('patientsTableBody').innerHTML = html;
            })
            .catch(error => {
                console.error('There was a problem fetching patient data:', error);
                // Optionally display an error message to the user
                document.getElementById('patientsTableBody').innerHTML = '<tr><td colspan="6">Erreur lors du chargement des patients.</td></tr>';
            });
    }

    // Add event listener for when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initial load of the patient table when the page loads
        fetchAndDisplayPatients();

        // Get references to the form and search input
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');

        // Prevent default form submission and trigger AJAX on button click
        searchForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Stop the form from submitting normally
            fetchAndDisplayPatients(searchInput.value); // Call the function with current input value
        });

        // Optional: Implement live search as the user types (uncomment if desired)
        searchInput.addEventListener('keyup', function() {
            // Only trigger search if typing stops for a brief moment to avoid too many requests
            // or if the search term changes significantly.
            // For simplicity, we'll make a request on each keyup for now.
            fetchAndDisplayPatients(this.value);
        });
    });
</script>

</body>
</html>