<?php
session_start(); // Start the session if it's not already started

// Unset all of the session variables
$_SESSION = array();

// Destroy the session.
session_destroy();

// Redirect to the login page (or homepage, or any other page you want)
header("Location: login.php"); // Redirect to your login page
exit(); // Ensure that no other code is executed after the redirection
?>