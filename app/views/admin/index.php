<?php
session_start();

if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    // [แก้ไข] Redirect to the new dashboard page
    header("Location: home/dashboard.php");
    exit;
} else {
    header("Location: login/login.php");
    exit;
}
?>
