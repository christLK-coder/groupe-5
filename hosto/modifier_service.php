<?php
require_once("hosto.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $service = $conn->prepare("SELECT * FROM services WHERE id_service = ?");
    $service->execute([$id]);
    $s = $service->fetch();

    if (!$s) {
        die("Service introuvable !");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = $_POST['nom_service'];
        $desc = $_POST['description'];
        $stmt = $conn->prepare("UPDATE services SET nom_service = ?, description = ? WHERE id_service = ?");
        $stmt->execute([$nom, $desc, $id]);
        header("Location: dashboard_admin.php");
        exit();
    }
} else {
    die("ID manquant !");
}
?>

<h2>Modifier un Service</h2>
<form method="POST">
    <label>Nom du Service</label><br>
    <input type="text" name="nom_service" value="<?= htmlspecialchars($s['nom_service']) ?>" required><br><br>

    <label>Description</label><br>
    <textarea name="description" rows="4"><?= htmlspecialchars($s['description']) ?></textarea><br><br>

    <button type="submit">Mettre à jour</button>
</form>
