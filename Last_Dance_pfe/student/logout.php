<?php
require_once '../config/session.php';

// Logout user
logoutUser();

// Redirect to main page
header('Location: ../index.php');
exit();
?>