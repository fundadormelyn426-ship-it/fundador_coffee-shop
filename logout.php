<?php

require_once 'config.php';

// Clear all session variables
session_unset();

// Destroy the current session
session_destroy();

// Redirect to the homepage
header('Location: index.php');
exit;

?>