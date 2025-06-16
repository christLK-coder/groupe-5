<?php
$motDePasseClair = "Qwerty237";
$motDePasseHashe = password_hash($motDePasseClair, PASSWORD_DEFAULT);

echo "Mot de passe haché : " . $motDePasseHashe;
?>
