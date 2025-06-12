<?php
if (isset($_GET['id'])) {
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=tutoré;charset=utf8', 'root', '');
        $stmt = $pdo->prepare("SELECT * FROM rendez_vous WHERE id_rdv = ?");
        $stmt->execute([$_GET['id']]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Erreur serveur"]);
    }
}
?>
