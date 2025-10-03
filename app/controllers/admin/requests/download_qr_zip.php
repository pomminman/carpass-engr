<?php
// app/controllers/admin/requests/download_qr_zip.php
session_start();
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    die('Access Denied');
}

require_once '../../../models/db_config.php';

$download_all = isset($_POST['download_all']) && $_POST['download_all'] === 'true';

if (!$download_all && ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['request_ids']))) {
    die('Invalid request.');
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}
$conn->set_charset("utf8");

$sql = "SELECT 
            vr.search_id, vr.qr_code_path, vr.card_type,
            v.license_plate, v.province, v.vehicle_type
        FROM vehicle_requests vr
        JOIN vehicles v ON vr.vehicle_id = v.id
        WHERE vr.status = 'approved' AND vr.qr_code_path IS NOT NULL";

if ($download_all) {
    $stmt = $conn->prepare($sql);
} else {
    $request_ids = $_POST['request_ids'];
    $placeholders = implode(',', array_fill(0, count($request_ids), '?'));
    $types = str_repeat('i', count($request_ids));
    $sql .= " AND vr.id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$request_ids);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('No valid QR codes found for the selected requests.');
}

$zip = new ZipArchive();
$zip_filename = tempnam(sys_get_temp_dir(), 'qr_codes_') . '.zip';

if ($zip->open($zip_filename, ZipArchive::CREATE) !== TRUE) {
    die('Cannot create ZIP file.');
}

$qr_base_path = __DIR__ . '/../../../../public/qr/';

while ($row = $result->fetch_assoc()) {
    $file_path = $qr_base_path . basename($row['qr_code_path']);
    if (file_exists($file_path)) {
        $card_type_thai = $row['card_type'] === 'internal' ? 'ภายใน' : 'ภายนอก';
        $filename_parts = [
            $row['license_plate'],
            $row['province'],
            '(' . $row['vehicle_type'] . '_' . $card_type_thai . ')'
        ];
        $download_filename = preg_replace('/[\s\/\\:*?"<>|]+/', '_', implode('_', $filename_parts)) . '.png';
        $zip->addFile($file_path, $download_filename);
    }
}

$zip->close();
$stmt->close();
$conn->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="qrcodes_' . date('Y-m-d_H-i') . '.zip"');
header('Content-Length: ' . filesize($zip_filename));
header('Pragma: no-cache');
readfile($zip_filename);

unlink($zip_filename);
exit;
?>

