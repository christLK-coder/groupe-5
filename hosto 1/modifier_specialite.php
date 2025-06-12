<?php
require_once("hosto.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM specialites WHERE id_specialite = ?");
    $stmt->execute([$id]);
    $s = $stmt->fetch();

    if (!$s) {
        die("Spécialité introuvable !");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = $_POST['nom_specialite'];
        $desc = $_POST['description_specialite'];
        $id_service = $_POST['id_service'];
        $update = $conn->prepare("UPDATE specialites SET nom_specialite = ?, description_specialite = ?, id_service = ? WHERE id_specialite = ?");
        $update->execute([$nom, $desc, $id_service, $id]);
        header("Location: dashboard_admin.php");
        exit();
    }

    $services = $conn->query("SELECT * FROM services")->fetchAll();
} else {
    die("ID manquant !");
}
?>

<h2>Modifier une Spécialité</h2>
<form method="POST">
    <label>Nom de la Spécialité</label><br>
    <input type="text" name="nom_specialite" value="<?= htmlspecialchars($s['nom_specialite']) ?>" required><br><br>

    <label>Description</label><br>
    <textarea name="description_specialite" rows="4"><?= htmlspecialchars($s['description_specialite']) ?></textarea><br><br>

    <label>Service associé</label><br>
    <select name="id_service" required>
        <?php
        foreach ($services as $srv) {
            $selected = $s['id_service'] == $srv['id_service'] ? "selected" : "";
            echo "<option value='{$srv['id_service']}' $selected>{$srv['nom_service']}</option>";
        }
        ?>
    </select><br><br>

    <button type="submit">Mettre à jour</button>
</form>
