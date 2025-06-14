<?php
require_once("hosto.php");

// The PHP code for deleting comments is now handled by delete_commentaire.php via AJAX.
// The initial fetching of comments is also handled by get_commentaires_table.php via AJAX.
// So, we can remove the PHP blocks related to these operations from this file.
?>

<!DOCTYPE html>
<html lang="Fr">
<head>
    <meta charset="UTF-8">
    <title>Commentaires</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2 class="text-primary mb-4"><i class="fas fa-comments me-2"></i>Commentaires des patients</h2>

    <div class="alert alert-info text-center" id="no-comments-alert" style="display: none;">
        <i class="fas fa-info-circle"></i> Aucun commentaire disponible.
    </div>

    <div class="table-responsive shadow rounded">
        <table class="table table-bordered table-hover bg-white">
            <thead class="table-primary">
                <tr>
                    <th>Nom</th>
                    <th>Contenu</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="commentairesTableBody">
                <tr><td colspan="4" class="text-center">Chargement des commentaires...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <a href="dashboard_admin.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-2"></i> Retour au Dashboard</a>
    </div>

</div>

<script>
    // Function to fetch and display comments
    function fetchAndDisplayComments() {
        fetch('get_commentaires_table.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.text(); // Get response as plain HTML
            })
            .then(html => {
                const tbody = document.getElementById('commentairesTableBody');
                tbody.innerHTML = html;

                // Check if there are no comments to show the alert
                const noCommentsAlert = document.getElementById('no-comments-alert');
                if (tbody.children.length === 1 && tbody.children[0].tagName === 'TR' && tbody.children[0].innerText.includes('Aucun commentaire')) {
                    noCommentsAlert.style.display = 'block';
                    document.querySelector('.table-responsive').style.display = 'none'; // Hide table
                } else {
                    noCommentsAlert.style.display = 'none';
                    document.querySelector('.table-responsive').style.display = 'block'; // Show table
                }

                // Attach event listeners to new delete buttons
                attachDeleteListeners();
            })
            .catch(error => {
                console.error('There was a problem fetching comments:', error);
                document.getElementById('commentairesTableBody').innerHTML = '<tr><td colspan="4" class="text-center text-danger">Erreur lors du chargement des commentaires.</td></tr>';
                document.getElementById('no-comments-alert').style.display = 'none'; // Hide no comments alert on error
                document.querySelector('.table-responsive').style.display = 'block'; // Ensure table is visible for error message
            });
    }

    // Function to attach event listeners to delete buttons
    function attachDeleteListeners() {
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.removeEventListener('click', handleDeleteClick); // Remove existing listener to prevent duplicates
            button.addEventListener('click', handleDeleteClick);
        });
    }

    // Event handler for delete button clicks
    function handleDeleteClick(event) {
        const commentId = event.currentTarget.dataset.id;
        if (confirm('Confirmer la suppression de ce commentaire ?')) {
            fetch(`delete_commentaire.php?id=${commentId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json(); // Expect JSON response
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        // Remove the deleted row from the table
                        const rowToRemove = document.getElementById(`comment-row-${commentId}`);
                        if (rowToRemove) {
                            rowToRemove.remove();
                        }
                        // Re-check if table is empty after deletion
                        checkIfTableIsEmpty();
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error deleting comment:', error);
                    alert('Une erreur est survenue lors de la suppression.');
                });
        }
    }

    // Function to check if the table is empty and show/hide the alert
    function checkIfTableIsEmpty() {
        const tbody = document.getElementById('commentairesTableBody');
        const noCommentsAlert = document.getElementById('no-comments-alert');
        const tableResponsiveDiv = document.querySelector('.table-responsive');

        // After deletion, if tbody is empty or contains only the "no comments" row
        if (tbody.children.length === 0 || (tbody.children.length === 1 && tbody.children[0].tagName === 'TR' && tbody.children[0].innerText.includes('Aucun commentaire'))) {
            noCommentsAlert.style.display = 'block';
            tableResponsiveDiv.style.display = 'none';
        } else {
            noCommentsAlert.style.display = 'none';
            tableResponsiveDiv.style.display = 'block';
        }
    }


    // Initial load of comments when the page is ready
    document.addEventListener('DOMContentLoaded', fetchAndDisplayComments);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

