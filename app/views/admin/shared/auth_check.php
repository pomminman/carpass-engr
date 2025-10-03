<?php
// app/views/admin/shared/auth_check.php
// Script for checking admin authentication and fetching admin data.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Bangkok');

// 1. Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: /app/views/admin/login/login.php");
    exit;
}

// 2. Include necessary files
require_once __DIR__ . '/../../../models/db_config.php';

// 3. Establish database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    // In case of DB error, log out the user for safety
    session_destroy();
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// 4. Fetch logged-in admin's information
$admin_id = $_SESSION['admin_id'];
$admin_info = [];
$sql_admin = "SELECT id, username, title, firstname, lastname, department, role, view_permission FROM admins WHERE id = ?";
if ($stmt_admin = $conn->prepare($sql_admin)) {
    $stmt_admin->bind_param("i", $admin_id);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    if ($admin_user = $result_admin->fetch_assoc()) {
        $admin_info = $admin_user;
        $admin_info['name'] = htmlspecialchars($admin_user['title'] . $admin_user['firstname']);
        $admin_info['lastname'] = htmlspecialchars($admin_user['lastname']);
        $admin_info['view_permission_text'] = $admin_user['view_permission'] == 1 ? 'ดูได้ทุกสังกัด' : 'เฉพาะสังกัดตนเอง';
        
        $first_initial = mb_substr($admin_user['firstname'], 0, 1, 'UTF-8');
        $last_initial = mb_substr($admin_user['lastname'], 0, 1, 'UTF-8');
        $admin_info['initials'] = htmlspecialchars($first_initial . $last_initial);

    } else {
        session_destroy();
        header("Location: /app/views/admin/login/login.php");
        exit;
    }
    $stmt_admin->close();
}

// 5. Define current page for active menu styling
$current_page = basename($_SERVER['PHP_SELF']);

// Helper function for date formatting
if (!function_exists('format_thai_date')) {
    function format_thai_date($date) {
        if (empty($date) || $date === '0000-00-00' || strpos($date, '0000-00-00') !== false) return '-';
        $timestamp = strtotime($date);
        $thai_months = [1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'];
        $year_be = substr(date('Y', $timestamp) + 543, -2);
        return date('d', $timestamp) . ' ' . $thai_months[date('n', $timestamp)] . ' ' . $year_be;
    }
}
if (!function_exists('format_thai_datetime')) {
    function format_thai_datetime($datetime) {
        if (empty($datetime) || strpos($datetime, '0000-00-00') !== false) return '-';
        $timestamp = strtotime($datetime);
        return format_thai_date($datetime) . ' ' . date('H:i', $timestamp);
    }
}
if (!function_exists('format_thai_date_short')) {
    function format_thai_date_short($date) {
        if (empty($date) || $date === '0000-00-00' || strpos($date, '0000-00-00') !== false) return '-';
        $timestamp = strtotime($date);
        $year_be_short = substr(date('Y', $timestamp) + 543, -2);
        return date('d/m/', $timestamp) . $year_be_short;
    }
}
if (!function_exists('format_thai_datetime_short')) {
    function format_thai_datetime_short($datetime) {
        if (empty($datetime) || strpos($datetime, '0000-00-00') !== false) return '-';
        $timestamp = strtotime($datetime);
        $year_be_short = substr(date('Y', $timestamp) + 543, -2);
        return date('d/m/', $timestamp) . $year_be_short . ' ' . date('H:i', $timestamp);
    }
}

?>
