<?php
session_start();

// Clear customer session
if (isset($_SESSION['customer'])) {
    unset($_SESSION['customer']);
}

// Redirect to home page
header("Location: index.php");
exit();
?>