<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['id_medecin'])) {
    header('Location: login.php');
    exit();
}

$id_medecin = $_SESSION['id_medecin'];
$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];
$image_medecin = $_SESSION['image_medecin'] ?? 'default.jpg';

// Gestion des requêtes AJAX
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    if ($_GET['action'] === 'get_messages' && isset($_GET['conversation_id'])) {
        $conversation_id = intval($_GET['conversation_id']);
        $last_time = isset($_GET['last_time']) ? $_GET['last_time'] : '1970-01-01 00:00:00';

        try {
            $stmt = $pdo->prepare("
                SELECT id_expediteur, type_expediteur, contenu, date_message
                FROM MESSAGE
                WHERE id_conversation = ? AND date_message > ?
                ORDER BY date_message ASC
            ");
            $stmt->execute([$conversation_id, $last_time]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['messages' => $messages]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
        }
        exit;
    }

    if ($_GET['action'] === 'send_message' && isset($_POST['conversation_id']) && isset($_POST['message_content'])) {
        $conversation_id = intval($_POST['conversation_id']);
        $message_content = trim($_POST['message_content']);

        if (empty($message_content)) {
            echo json_encode(['error' => 'Le message ne peut pas être vide']);
            exit;
        }

        try {
            $stmt_check = $pdo->prepare("SELECT id_conversation FROM CONVERSATION WHERE id_conversation = ? AND id_medecin = ?");
            $stmt_check->execute([$conversation_id, $id_medecin]);
            if (!$stmt_check->fetch()) {
                echo json_encode(['error' => 'Conversation non autorisée']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO MESSAGE (id_conversation, id_expediteur, type_expediteur, contenu)
                VALUES (?, ?, 'medecin', ?)
            ");
            $stmt->execute([$conversation_id, $id_medecin, $message_content]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Erreur serveur : ' . $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['error' => 'Action non valide']);
    exit;
}

$current_conversation_id = null;
$current_patient_id = null;
$current_patient_nom_complet = 'Sélectionnez une conversation';
$messages = [];
$error_message = '';

// --- Logique de sélection ou création de conversation ---
if (isset($_GET['conversation_id'])) {
    $selected_conversation_id = intval($_GET['conversation_id']);
    try {
        $stmt = $pdo->prepare("SELECT id_conversation, id_patient FROM CONVERSATION WHERE id_conversation = ? AND id_medecin = ?");
        $stmt->execute([$selected_conversation_id, $id_medecin]);
        $conversation_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($conversation_data) {
            $current_conversation_id = $conversation_data['id_conversation'];
            $current_patient_id = $conversation_data['id_patient'];
        } else {
            $error_message = "Conversation introuvable ou non autorisée.";
        }
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la sélection de la conversation : " . $e->getMessage();
    }
} elseif (isset($_GET['patient_id_cible'])) {
    $target_patient_id = intval($_GET['patient_id_cible']);
    try {
        $stmt_check_conv = $pdo->prepare("SELECT id_conversation FROM CONVERSATION WHERE id_medecin = ? AND id_patient = ?");
        $stmt_check_conv->execute([$id_medecin, $target_patient_id]);
        $conversation = $stmt_check_conv->fetch(PDO::FETCH_ASSOC);

        if ($conversation) {
            $current_conversation_id = $conversation['id_conversation'];
            $current_patient_id = $target_patient_id;
        } else {
            $stmt_create_conv = $pdo->prepare("INSERT INTO CONVERSATION (id_medecin, id_patient) VALUES (?, ?)");
            $stmt_create_conv->execute([$id_medecin, $target_patient_id]);
            $current_conversation_id = $pdo->lastInsertId();
            $current_patient_id = $target_patient_id;
        }
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la création/recherche de la conversation : " . $e->getMessage();
    }
}

// --- Récupérer les informations du patient actif et les messages ---
if ($current_conversation_id && $current_patient_id) {
    try {
        $stmt_patient_name = $pdo->prepare("SELECT nom, prenom FROM PATIENT WHERE id_patient = ?");
        $stmt_patient_name->execute([$current_patient_id]);
        $patient_data = $stmt_patient_name->fetch(PDO::FETCH_ASSOC);
        if ($patient_data) {
            $current_patient_nom_complet = htmlspecialchars($patient_data['prenom'] . ' ' . $patient_data['nom']);
        } else {
            $current_patient_nom_complet = 'Patient Inconnu';
        }

        $stmt_messages = $pdo->prepare("
            SELECT id_expediteur, type_expediteur, contenu, date_message
            FROM MESSAGE
            WHERE id_conversation = ?
            ORDER BY date_message ASC
        ");
        $stmt_messages->execute([$current_conversation_id]);
        $messages = $stmt_messages->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Erreur lors du chargement des messages : " . $e->getMessage();
    }
}

// --- Récupérer toutes les conversations du médecin ---
$conversations_list = [];
try {
    $stmt_conversations = $pdo->prepare("
        SELECT
            C.id_conversation,
            P.nom AS patient_nom,
            P.prenom AS patient_prenom,
            (SELECT contenu FROM MESSAGE WHERE id_conversation = C.id_conversation ORDER BY date_message DESC LIMIT 1) AS last_message_content,
            (SELECT date_message FROM MESSAGE WHERE id_conversation = C.id_conversation ORDER BY date_message DESC LIMIT 1) AS last_message_date
        FROM CONVERSATION C
        JOIN PATIENT P ON C.id_patient = P.id_patient
        WHERE C.id_medecin = ?
        ORDER BY last_message_date DESC, C.date_creation DESC
    ");
    $stmt_conversations->execute([$id_medecin]);
    $conversations_list = $stmt_conversations->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erreur lors du chargement des conversations : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Conversations - Médecin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
<style>
    body {
        background-color: #f3fbfa;
        margin: 0;
        font-family: 'Roboto', sans-serif;
        display: flex;
        min-height: 100vh;
    }
    .sidebar {
        width: 250px;
        background-color: #FFFFFF;
        position: fixed;
        top: 0;
        bottom: 0;
        padding: 20px 0;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        transition: width 0.3s;
    }
    .sidebar.collapsed {
        width: 70px;
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
    .sidebar.collapsed .profile img {
        width: 40px;
        height: 40px;
    }
    .sidebar .profile h4 {
        margin: 5px 0;
        color: #333;
        font-size: 16px;
    }
    .sidebar.collapsed .profile h4, .sidebar.collapsed .nav-link span {
        display: none;
    }
    .sidebar .nav {
        padding-top: 20px;
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
    .sidebar.collapsed .nav-link {
        justify-content: center;
    }
    .sidebar .nav-link:hover, .sidebar .nav-link.active {
        background-color: #93d6d0;
        color: #FFFFFF;
    }
    .sidebar .nav-link .material-icons {
        margin-right: 10px;
        font-size: 20px;
    }
    .main-content {
        margin-left: 250px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        background-color: #f3fbfa;
        height: 100vh;
        transition: margin-left 0.3s;
    }
    .main-content.collapsed {
        margin-left: 70px;
    }
    .chat-wrapper {
        display: flex;
        flex-grow: 1;
        background-color: #FFFFFF;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin: 20px;
    }
    .conversation-list {
        flex: 0 0 300px;
        border-right: 1px solid #e0e0e0;
        background-color: #FFFFFF;
        overflow-y: auto;
        padding: 10px 0;
    }
    .conversation-list h2 {
        font-size: 1.2em;
        color: #333;
        padding: 10px 20px;
        margin: 0;
        border-bottom: 1px solid #e0e0e0;
    }
    .conversation-item {
        padding: 15px 20px;
        border-bottom: 1px solid #e0e0e0;
        cursor: pointer;
        transition: background-color 0.2s ease;
        text-decoration: none;
        color: #333;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .conversation-item:hover, .conversation-item.active {
        background-color: #93d6d0;
        color: #FFFFFF;
    }
    .conversation-item h3 {
        margin: 0;
        font-size: 1em;
        color: inherit;
    }
    .conversation-item p {
        margin: 0;
        font-size: 0.85em;
        color: #666;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .conversation-item .last-message-time {
        font-size: 0.75em;
        color: #999;
        text-align: right;
    }
    .chat-area {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .chat-header {
        background-color: #93d6d0;
        color: #FFFFFF;
        padding: 15px 20px;
        font-size: 1.3em;
        font-weight: bold;
        border-bottom: 1px solid #7bc7c1;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .chat-header .material-icons {
        font-size: 24px;
    }
    .chat-messages {
        flex-grow: 1;
        padding: 20px;
        overflow-y: auto;
        background-color: #f3fbfa;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .message-bubble {
        max-width: 70%;
        padding: 10px 15px;
        border-radius: 15px;
        margin-bottom: 10px;
        line-height: 1.4;
        word-wrap: break-word;
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .message-medecin {
        background-color: #93d6d0;
        color: #FFFFFF;
        align-self: flex-end;
        text-align: right;
    }
    .message-patient {
        background-color: #FFFFFF;
        border: 1px solid #e0e0e0;
        align-self: flex-start;
        text-align: left;
        color: #333;
    }
    .message-time {
        font-size: 0.75em;
        color: #777;
        margin-top: 5px;
        display: block;
    }
    .chat-input {
        padding: 15px 20px;
        border-top: 1px solid #e0e0e0;
        display: flex;
        align-items: center;
        background-color: #FFFFFF;
    }
    .chat-input textarea {
        flex-grow: 1;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        padding: 10px;
        font-size: 1em;
        resize: none;
        max-height: 100px;
        margin-right: 10px;
    }
    .chat-input button {
        background-color: #93d6d0;
        color: #FFFFFF;
        border: none;
        border-radius: 5px;
        padding: 10px 15px;
        font-size: 1em;
        cursor: pointer;
        transition: background-color 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .chat-input button:hover {
        background-color: #7bc7c1;
    }
    .error-message {
        color: #d9534f;
        text-align: center;
        padding: 10px;
        font-size: 0.9em;
    }
    .no-conversation-selected {
        text-align: center;
        padding: 50px;
        color: #666;
        font-size: 1.1em;
    }
    .toggle-sidebar {
        position: fixed;
        top: 10px;
        left: 10px;
        z-index: 1100;
        background-color: #93d6d0;
        color: #FFFFFF;
        border: none;
        padding: 8px;
        border-radius: 5px;
        display: none;
    }

    /* Media Queries pour la responsivité */
    @media (max-width: 992px) {
        .sidebar {
            width: 200px;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .main-content {
            margin-left: 200px;
        }
        .main-content.collapsed {
            margin-left: 70px;
        }
        .sidebar .profile img {
            width: 60px;
            height: 60px;
        }
        .sidebar .profile h4 {
            font-size: 14px;
        }
        .sidebar .nav-link {
            font-size: 14px;
            padding: 10px 15px;
        }
        .chat-wrapper {
            margin: 15px;
        }
        .conversation-list {
            flex: 0 0 250px;
        }
        .conversation-list h2 {
            font-size: 1.1em;
            padding: 8px 15px;
        }
        .conversation-item {
            padding: 12px 15px;
        }
        .conversation-item h3 {
            font-size: 0.95em;
        }
        .conversation-item p {
            font-size: 0.8em;
        }
        .chat-header {
            font-size: 1.2em;
            padding: 12px 15px;
        }
        .chat-messages {
            padding: 15px;
        }
        .message-bubble {
            max-width: 75%;
            padding: 8px 12px;
            font-size: 0.95em;
        }
        .chat-input {
            padding: 12px 15px;
        }
        .chat-input textarea {
            font-size: 0.95em;
            padding: 8px;
        }
        .chat-input button {
            padding: 8px 12px;
            font-size: 0.95em;
        }
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 70px;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .main-content {
            margin-left: 70px;
        }
        .main-content.collapsed {
            margin-left: 70px;
        }
        .toggle-sidebar {
            display: block;
        }
        .chat-wrapper {
            flex-direction: column;
            margin: 10px;
        }
        .conversation-list {
            flex: none;
            width: 100%;
            border-right: none;
            border-bottom: 1px solid #e0e0e0;
            max-height: 200px;
            overflow-y: auto;
        }
        .conversation-list h2 {
            font-size: 1em;
            padding: 8px 15px;
        }
        .conversation-item {
            padding: 10px 15px;
        }
        .conversation-item h3 {
            font-size: 0.9em;
        }
        .conversation-item p {
            font-size: 0.75em;
        }
        .conversation-item .last-message-time {
            font-size: 0.65em;
        }
        .chat-header {
            font-size: 1.1em;
            padding: 10px 15px;
        }
        .chat-header .material-icons {
            font-size: 20px;
        }
        .chat-messages {
            padding: 15px;
            gap: 8px;
        }
        .message-bubble {
            max-width: 85%;
            padding: 8px 12px;
            font-size: 0.9em;
        }
        .message-time {
            font-size: 0.65em;
        }
        .chat-input {
            padding: 10px 15px;
        }
        .chat-input textarea {
            font-size: 0.9em;
            padding: 8px;
            margin-right: 8px;
        }
        .chat-input button {
            padding: 8px 12px;
            font-size: 0.9em;
        }
        .error-message {
            font-size: 0.85em;
            padding: 8px;
        }
        .no-conversation-selected {
            font-size: 1em;
            padding: 40px;
        }
    }

    @media (max-width: 576px) {
        .sidebar {
            width: 60px;
        }
        .sidebar.collapsed {
            width: 60px;
        }
        .main-content {
            margin-left: 60px;
        }
        .main-content.collapsed {
            margin-left: 60px;
        }
        .chat-wrapper {
            margin: 8px;
        }
        .conversation-list h2 {
            font-size: 0.9em;
            padding: 6px 12px;
        }
        .conversation-item {
            padding: 8px 12px;
        }
        .conversation-item h3 {
            font-size: 0.85em;
        }
        .conversation-item p {
            font-size: 0.7em;
        }
        .conversation-item .last-message-time {
            font-size: 0.6em;
        }
        .chat-header {
            font-size: 1em;
            padding: 8px 12px;
        }
        .chat-header .material-icons {
            font-size: 18px;
        }
        .chat-messages {
            padding: 12px;
            gap: 6px;
        }
        .message-bubble {
            max-width: 90%;
            padding: 6px 10px;
            font-size: 0.85em;
        }
        .message-time {
            font-size: 0.6em;
        }
        .chat-input {
            padding: 8px 12px;
        }
        .chat-input textarea {
            font-size: 0.85em;
            padding: 6px;
            margin-right: 6px;
        }
        .chat-input button {
            padding: 6px 10px;
            font-size: 0.85em;
        }
        .error-message {
            font-size: 0.8em;
            padding: 6px;
        }
        .no-conversation-selected {
            font-size: 0.95em;
            padding: 30px;
        }
        .toggle-sidebar {
            padding: 6px;
        }
    }
</style>
</head>
<body>
    <button class="toggle-sidebar d-md-none" onclick="toggleSidebar()">
        <span class="material-icons">menu</span>
    </button>
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

    <div class="main-content">
        <div class="chat-wrapper">
            <div class="conversation-list">
                <h2>Mes Conversations</h2>
                <?php if (empty($conversations_list)): ?>
                    <p style="text-align: center; padding: 20px; color: #666;">
                        <span class="material-icons">info</span>
                        Vous n'avez pas encore de conversations.
                    </p>
                <?php else: ?>
                    <?php foreach ($conversations_list as $conv_item): ?>
                        <?php
                            $is_active = ($conv_item['id_conversation'] == $current_conversation_id) ? 'active' : '';
                            $patient_full_name = htmlspecialchars($conv_item['patient_prenom'] . ' ' . $conv_item['patient_nom']);
                            $last_msg = htmlspecialchars($conv_item['last_message_content'] ?? 'Pas de message');
                            $last_msg_time = $conv_item['last_message_date'] ? date('d/m H:i', strtotime($conv_item['last_message_date'])) : '';
                        ?>
                        <a href="messages.php?conversation_id=<?= $conv_item['id_conversation'] ?>" class="conversation-item <?= $is_active ?>">
                            <h3><?= $patient_full_name ?></h3>
                            <p><?= $last_msg ?></p>
                            <span class="last-message-time"><?= $last_msg_time ?></span> 
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-area">
                <div class="chat-header">
                    <span class="material-icons">person</span>
                    Conversation avec <?= $current_patient_nom_complet ?>
                </div>
                <div class="chat-messages" id="chatMessages">
                    <?php if ($current_conversation_id === null): ?>
                        <p class="no-conversation-selected">
                            <span class="material-icons">chat</span>
                            Sélectionnez une conversation pour commencer.
                        </p>
                    <?php elseif (empty($messages)): ?>
                        <p style="text-align: center; color: #666;">
                            <span class="material-icons">chat_bubble_outline</span>
                            Commencez cette nouvelle conversation !
                        </p>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <?php
                                $is_medecin_message = ($message['type_expediteur'] === 'medecin');
                                $sender_name = $is_medecin_message ? 'Vous' : $current_patient_nom_complet;
                            ?>
                            <div class="message-bubble <?= $is_medecin_message ? 'message-medecin' : 'message-patient' ?>">
                                <strong><?= $sender_name ?> :</strong>
                                <?= htmlspecialchars($message['contenu']) ?>
                                <span class="message-time"><?= date('H:i', strtotime($message['date_message'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($error_message)): ?>
                    <p class="error-message"><?= $error_message ?></p>
                <?php endif; ?>

                <div class="chat-input">
                    <?php if ($current_conversation_id): ?>
                        <textarea id="messageInput" placeholder="Écrivez votre message..." rows="1"></textarea>
                        <button id="sendMessageBtn">
                            <span class="material-icons">send</span>
                            Envoyer
                        </button>
                    <?php else: ?>
                        <p style="text-align: center; flex-grow: 1; color: #666;">
                            Sélectionnez une conversation pour envoyer un message.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        let lastMessageTime = '<?php echo end($messages)['date_message'] ?? '1970-01-01 00:00:00'; ?>';

        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('collapsed');
        }

        function scrollToBottom(firstLoad = false) {
            const chatMessages = document.getElementById('chatMessages');
            if (!chatMessages) return;
            const isScrolledToBottom = firstLoad || (chatMessages.scrollHeight - chatMessages.clientHeight <= chatMessages.scrollTop + 50);
            if (isScrolledToBottom) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Auto-resize textarea
        const textarea = document.getElementById('messageInput');
        if (textarea) {
            textarea.addEventListener('input', () => {
                textarea.style.height = 'auto';
                textarea.style.height = `${textarea.scrollHeight}px`;
            });
        }

        // Envoyer un message via AJAX
        const sendMessageBtn = document.getElementById('sendMessageBtn');
        if (sendMessageBtn) {
            sendMessageBtn.addEventListener('click', () => {
                const messageContent = textarea.value.trim();
                if (!messageContent) return;

                fetch('messages.php?action=send_message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `conversation_id=<?= $current_conversation_id ?>&message_content=${encodeURIComponent(messageContent)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        textarea.value = '';
                        textarea.style.height = 'auto';
                        fetchMessages(true);
                    } else {
                        showError(data.error || 'Erreur lors de l\'envoi du message.');
                    }
                })
                .catch(error => {
                    console.error('Erreur AJAX:', error);
                    showError('Erreur réseau lors de l\'envoi du message.');
                });
            });
        }

        function showError(message) {
            const errorDiv = document.createElement('p');
            errorDiv.className = 'error-message';
            errorDiv.textContent = message;
            const chatArea = document.querySelector('.chat-area');
            chatArea.insertBefore(errorDiv, document.querySelector('.chat-input'));
            setTimeout(() => errorDiv.remove(), 5000);
        }

        function escapeHTML(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function fetchMessages(scrollAfter = false) {
            <?php if ($current_conversation_id): ?>
            fetch(`messages.php?action=get_messages&conversation_id=<?= $current_conversation_id ?>&last_time=${encodeURIComponent(lastMessageTime)}`)
                .then(response => response.json())
                .then(data => {
                    const messagesContainer = document.getElementById('chatMessages');
                    if (data.error) {
                        showError(data.error);
                        return;
                    }

                    if (data.messages.length === 0 && !messagesContainer.querySelector('.message-bubble')) {
                        messagesContainer.innerHTML = '<p style="text-align: center; color: #666;"><span class="material-icons">chat_bubble_outline</span> Commencez cette nouvelle conversation !</p>';
                    } else if (data.messages.length > 0) {
                        const currentMessages = messagesContainer.querySelectorAll('.message-bubble').length;
                        data.messages.forEach(msg => {
                            const isMedecin = msg.type_expediteur === 'medecin';
                            const senderName = isMedecin ? 'Vous' : '<?= addslashes($current_patient_nom_complet) ?>';
                            const messageDiv = document.createElement('div');
                            messageDiv.className = `message-bubble ${isMedecin ? 'message-medecin' : 'message-patient'}`;
                            messageDiv.innerHTML = `
                                <strong>${escapeHTML(senderName)} :</strong>
                                ${escapeHTML(msg.contenu)}
                                <span class="message-time">${new Date(msg.date_message).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</span>
                            `;
                            messagesContainer.appendChild(messageDiv);
                            lastMessageTime = msg.date_message;
                        });
                        if (currentMessages === 0 && data.messages.length > 0) {
                            messagesContainer.innerHTML = '';
                            data.messages.forEach(msg => {
                                const isMedecin = msg.type_expediteur === 'medecin';
                                const senderName = isMedecin ? 'Vous' : '<?= addslashes($current_patient_nom_complet) ?>';
                                const messageDiv = document.createElement('div');
                                messageDiv.className = `message-bubble ${isMedecin ? 'message-medecin' : 'message-patient'}`;
                                messageDiv.innerHTML = `
                                    <strong>${escapeHTML(senderName)} :</strong>
                                    ${escapeHTML(msg.contenu)}
                                    <span class="message-time">${new Date(msg.date_message).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}</span>
                                `;
                                messagesContainer.appendChild(messageDiv);
                            });
                        }
                    }
                    if (scrollAfter) scrollToBottom();
                })
                .catch(error => {
                    console.error('Erreur de mise à jour des messages:', error);
                    showError('Erreur réseau lors du chargement des messages.');
                });
            <?php endif; ?>
        }

        // Polling toutes les 5 secondes
        <?php if ($current_conversation_id): ?>
        setInterval(fetchMessages, 5000);
        scrollToBottom(true);
        <?php endif; ?>
    </script>
</body>
</html>