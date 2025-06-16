<?php
session_start(); // Démarre la session, même si la connexion n'est plus obligatoire, pour d'autres usages potentiels.
require_once 'connexion.php';

$message_status = ''; // Pour afficher les messages de succès ou d'erreur

// --- 2. Traitement de la soumission du commentaire ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $nom_commentateur = trim(filter_input(INPUT_POST, 'commenter_name', FILTER_UNSAFE_RAW));
    $contenu_commentaire = trim(filter_input(INPUT_POST, 'comment_content', FILTER_UNSAFE_RAW));

    // Si le nom du commentateur est vide, attribue "Inconnu" par défaut
    if (empty($nom_commentateur)) {
        $nom_commentateur = 'Inconnu';
    }

    if (empty($contenu_commentaire)) {
        $message_status = '<div class="error-message">Le commentaire ne peut pas être vide.</div>';
    } else {
        try {
            // Insère le nom (soit fourni, soit "Inconnu") et le contenu du commentaire dans la table
            $stmt = $pdo->prepare("INSERT INTO commentaire (nom, contenu) VALUES (?, ?)");
            $stmt->execute([$nom_commentateur, $contenu_commentaire]);
            $message_status = '<div class="success-message">Votre commentaire a été publié avec succès !</div>';
            // Recharge la page pour afficher le nouveau commentaire immédiatement
            header('Location: commentaires.php');
            exit;
        } catch (PDOException $e) {
            $message_status = '<div class="error-message">Erreur lors de la publication du commentaire : ' . $e->getMessage() . '</div>';
        }
    }
}

// --- 3. Récupération des 3 premiers commentaires existants pour l'affichage initial ---
$commentaires = [];
$limit = 3; // Nombre de commentaires à afficher initiallement
$offset = 0; // Point de départ initial

try {
    // Récupération des commentaires en se basant uniquement sur la table 'commentaire'
    $stmt = $pdo->prepare("
        SELECT
            c.nom,
            c.contenu,
            c.date_commentaire
        FROM commentaire c
        ORDER BY c.date_commentaire DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vérifier s'il y a plus de commentaires que ceux affichés
    $stmtCount = $pdo->query("SELECT COUNT(*) FROM commentaire");
    $totalComments = $stmtCount->fetchColumn();
    $hasMoreComments = ($totalComments > count($commentaires));

} catch (PDOException $e) {
    $message_status = '<div class="error-message">Erreur lors du chargement des commentaires : ' . $e->getMessage() . '</div>';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commentaires sur le Site</title>
    <style>
        /* style_commentaires.css */

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f3fbfa;
    margin: 0;
    padding: 20px;
    color: #333;
    line-height: 1.6;
}

.container {
    max-width: 800px;
    margin: 30px auto;
    background-color: #ffffff;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

h1, h2 {
    color: rgb(72, 207, 162); /* Bleu primaire */
    text-align: center;
    margin-bottom: 25px;
    font-weight: 600;
}

h1 {
    font-size: 2.2em;
}

h2 {
    font-size: 1.6em;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
    margin-top: 40px;
}

/* --- Message Status (Success/Error) --- */
.error-message, .success-message {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: bold;
}

.error-message {
    background-color: #ffebe8;
    color: #cc0000;
    border: 1px solid #ff9999;
}

.success-message {
    background-color: #e6ffe6;
    color: #008000;
    border: 1px solid #99ff99;
}

/* --- Comment Form Section --- */
.comment-form-section {
    background-color: #fdfdfd;
    padding: 25px;
    border-radius: 10px;
    border: 1px solid #e0e0e0;
    margin-bottom: 30px;
}

/* New style for text inputs */
.input-text {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 1em;
    box-sizing: border-box;
}

.input-text:focus, textarea:focus {
    border-color: rgb(72, 207, 162);
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
    outline: none;
}


.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #444;
}

textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 1em;
    resize: vertical; /* Permet de redimensionner verticalement */
    min-height: 100px;
    box-sizing: border-box; /* Inclut le padding et le border dans la largeur/hauteur */
}


.submit-btn {
    display: block;
    width: 100%;
    padding: 12px 20px;
    background-color: rgb(72, 207, 162);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.1em;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.submit-btn:hover {
    background-color: rgb(58, 165, 129);
    transform: translateY(-2px);
}

.submit-btn:active {
    transform: translateY(0);
}

/* --- Comments List Section --- */
.comments-list-section {
    margin-top: 30px;
}

.no-comments {
    text-align: center;
    color: #777;
    font-style: italic;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    border: 1px dashed #ddd;
}

.comment-item {
    background-color: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s ease;
}

.comment-item:hover {
    transform: translateY(-3px);
}

.comment-header {
    display: flex;
    align-items: center; /* Align items vertically in center */
    margin-bottom: 15px;
}

/* Removed .user-avatar as images are not used for anonymous comments */

.comment-author-date {
    display: flex;
    flex-direction: column;
}

.author-name {
    font-weight: bold;
    color: #333;
    font-size: 1.1em;
    margin-bottom: 3px;
}

.comment-date {
    font-size: 0.9em;
    color: #777;
}

.comment-content p {
    margin: 0;
    color: #555;
    white-space: pre-wrap; /* Préserve les retours à la ligne */
}

/* Load More Button */
.load-more-btn-container {
    text-align: center;
    margin-top: 25px;
}

.load-more-btn {
    background-color: #6c757d; /* Gris secondaire */
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-size: 1em;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.load-more-btn:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
}
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../boxicons-master/css/boxicons.min.css">
</head>
<body>
    
    <a href="index.php"><i class="bx bx-home" style= "color: rgb(72, 207, 162); font-size: 20px;"></i></a>
    <div class="container">
        <h1>Laissez votre avis sur le site !</h1>

        <?php echo $message_status; ?>

        <div class="comment-form-section">
            <h2>Écrire un nouveau commentaire</h2>
            <form action="commentaires.php" method="POST">
                <div class="form-group">
                    <label for="commenter_name">Votre nom (facultatif) :</label>
                    <input type="text" id="commenter_name" name="commenter_name" placeholder="Votre nom ou pseudonyme" class="input-text">
                </div>
                <div class="form-group">
                    <label for="comment_content">Votre commentaire :</label>
                    <textarea id="comment_content" name="comment_content" rows="6" placeholder="Partagez votre expérience ou vos suggestions..." required></textarea>
                </div>
                <button type="submit" name="submit_comment" class="submit-btn">Publier le commentaire</button>
            </form>
        </div>

        ---

        <div class="comments-list-section">
            <h2>Ce que nos visiteurs disent du site</h2>
            <div id="comments-container">
                <?php if (empty($commentaires)): ?>
                    <p class="no-comments" id="no-comments-message">Aucun commentaire pour l'instant. Soyez le premier à en laisser un !</p>
                <?php else: ?>
                    <?php foreach ($commentaires as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <div class="comment-author-date">
                                    <span class="author-name"><?php echo htmlspecialchars($comment['nom']); ?></span>
                                    <span class="comment-date"><?php echo (new DateTime($comment['date_commentaire']))->format('d/m/Y à H:i'); ?></span>
                                </div>
                            </div>
                            <div class="comment-content">
                                <p><?php echo nl2br(htmlspecialchars($comment['contenu'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($hasMoreComments): ?>
                <div class="load-more-btn-container">
                    <button id="loadMoreComments" class="load-more-btn">Voir plus de commentaires</button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const commentsContainer = document.getElementById('comments-container');
        const loadMoreBtn = document.getElementById('loadMoreComments');
        const noCommentsMessage = document.getElementById('no-comments-message');
        let offset = <?php echo count($commentaires); ?>; // Start offset from the number of comments initially loaded
        const limit = 3; // Number of comments to load per click

        console.log('Initial offset:', offset); // Ajout pour le débogage initial
        console.log('Load More button:', loadMoreBtn ? 'exists' : 'does not exist'); // Vérifie si le bouton est là

        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                console.log('Load More button clicked!'); // Confirme que le clic est détecté
                console.log('Fetching comments with offset:', offset, 'and limit:', limit); // Affiche les paramètres de la requête

                // Fetch more comments via AJAX
                fetch(`fetch_comments.php?offset=${offset}&limit=${limit}`)
                    .then(response => {
                        console.log('Response status:', response.status); // Affiche le statut HTTP (e.g., 200, 404, 500)
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Received data:', data); // IMPORTANT : Affiche la réponse JSON complète

                        if (data.comments.length > 0) {
                            // Hide "No comments" message if it exists
                            if (noCommentsMessage) {
                                noCommentsMessage.style.display = 'none';
                            }

                            data.comments.forEach(comment => {
                                const commentItem = document.createElement('div');
                                commentItem.classList.add('comment-item');
                                commentItem.innerHTML = `
                                    <div class="comment-header">
                                        <div class="comment-author-date">
                                            <span class="author-name">${comment.nom}</span>
                                            <span class="comment-date">${comment.date_commentaire}</span>
                                        </div>
                                    </div>
                                    <div class="comment-content">
                                        <p>${comment.contenu.replace(/\n/g, '<br>')}</p>
                                    </div>
                                `;
                                commentsContainer.appendChild(commentItem);
                            });
                            offset += data.comments.length;
                            console.log('New offset after loading:', offset); // Affiche le nouvel offset

                            // Hide the load more button if no more comments
                            if (!data.hasMore) {
                                loadMoreBtn.style.display = 'none';
                                console.log('No more comments, hiding button.');
                            }
                        } else {
                            // No more comments to load (or an issue where comments array is empty)
                            loadMoreBtn.style.display = 'none';
                            console.log('No comments received or no more comments to load, hiding button.');
                            if (offset === 0) { // If there were initially no comments and no new ones loaded
                                noCommentsMessage.style.display = 'block';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching comments:', error);
                        // Optionally display an error message to the user
                    });
            });
        }
    </script>
</body>
</html>
