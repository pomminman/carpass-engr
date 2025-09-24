<?php
// app/controllers/admin/activity/log_admin_process.php
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');

// 1. Check admin authentication
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true || !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

// 2. Include required files
require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

// 3. Get data from POST body
$data = json_decode(file_get_contents('php://input'), true);
$request_id = filter_var($data['request_id'] ?? null, FILTER_VALIDATE_INT);

if ($request_id) {
    // 4. Connect to database
    $conn = new mysqli($servername, $username, $password, $dbname);
    if (!$conn->connect_error) {
        $conn->set_charset("utf8");
        
        // 5. Call the log_activity function
        // The function automatically gets admin_id from the session
        log_activity($conn, 'admin_view_details', ['request_id' => $request_id]);
        
        $conn->close();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID provided.']);
}
?>
