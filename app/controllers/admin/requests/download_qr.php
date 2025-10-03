<?php
// app/controllers/admin/requests/download_qr.php
session_start();
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    http_response_code(403);
    die('Access Denied');
}

require_once '../../../models/db_config.php';

$file = $_GET['file'] ?? null;

if (!$file) {
    http_response_code(400);
    die('File not specified.');
}

// Sanitize filename to prevent directory traversal
$base_name = basename($file);
$file_path = __DIR__ . '/../../../../public/qr/' . $base_name;

if (!file_exists($file_path)) {
    http_response_code(404);
    die('File not found.');
}

// --- Fetch details to create custom filename ---
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    // Fallback to default name if DB connection fails
    $download_filename = 'qrcode.png';
} else {
    $conn->set_charset("utf8");
    $sql = "SELECT v.license_plate, v.province, v.vehicle_type, vr.card_type 
            FROM vehicle_requests vr
            JOIN vehicles v ON vr.vehicle_id = v.id
            WHERE vr.qr_code_path = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $base_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($details = $result->fetch_assoc()) {
        $card_type_thai = $details['card_type'] === 'internal' ? 'ภายใน' : 'ภายนอก';
        $filename_parts = [
            $details['license_plate'],
            $details['province'],
            '(' . $details['vehicle_type'] . '_' . $card_type_thai . ')'
        ];
        // Replace invalid filename characters
        $download_filename = preg_replace('/[\s\/\\:*?"<>|]+/', '_', implode('_', $filename_parts)) . '.png';
    } else {
        $download_filename = 'qrcode_' . date('YmdHis') . '.png';
    }
    $stmt->close();
    $conn->close();
}


header('Content-Description: File Transfer');
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="' . $download_filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit;
?>

