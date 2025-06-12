<?php
$motDePasseClair = "123456";
$motDePasseHashe = password_hash($motDePasseClair, PASSWORD_DEFAULT);

echo "Mot de passe haché : " . $motDePasseHashe;
?>
