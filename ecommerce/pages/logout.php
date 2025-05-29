<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start(); // Start the session

// Destroy the session
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session

// Display a JavaScript alert and redirect to login page
echo "<script>
        alert('Logout successful!');
        window.location.href = 'login.php';
      </script>";
exit();
?>
