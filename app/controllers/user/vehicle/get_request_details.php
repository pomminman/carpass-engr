<?php
// app/controllers/user/vehicle/get_request_details.php
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Authentication required.']);
    exit;
}

require_once '../../../models/db_config.php';

$request_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$request_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
    exit;
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    error_log("DB Connection Error: " . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit;
}
$conn->set_charset("utf8");

$sql = "SELECT 
            vr.*,
            v.vehicle_type, v.brand, v.model, v.color, v.license_plate, v.province AS vehicle_province,
            u.user_key
        FROM vehicle_requests vr
        JOIN users u ON vr.user_id = u.id
        JOIN vehicles v ON vr.vehicle_id = v.id
        WHERE vr.id = ? AND vr.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $data = $result->fetch_assoc();

    // [MODIFIED] Add checks for renewal eligibility and license plate editability
    $data['can_renew'] = 'false';
    $data['can_edit_license'] = 'false';

    // Check for renewal
    $is_expired = !empty($data['card_expiry']) && (new DateTime() > new DateTime($data['card_expiry']));
    if ($is_expired) {
        $active_period_check = $conn->query("SELECT id FROM application_periods WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date LIMIT 1");
        if ($active_period_check->num_rows > 0) {
            $active_period = $active_period_check->fetch_assoc();
            $renewal_check = $conn->prepare("SELECT id FROM vehicle_requests WHERE vehicle_id = ? AND period_id = ? AND status IN ('pending', 'approved')");
            $renewal_check->bind_param("ii", $data['vehicle_id'], $active_period['id']);
            $renewal_check->execute();
            if ($renewal_check->get_result()->num_rows === 0) {
                $data['can_renew'] = 'true';
            }
            $renewal_check->close();
        }
    }

    // Check for license plate editability
    if ($data['status'] === 'pending') {
        $vehicle_id = $data['vehicle_id'];
        $count_stmt = $conn->prepare("SELECT COUNT(id) as request_count FROM vehicle_requests WHERE vehicle_id = ?");
        $count_stmt->bind_param("i", $vehicle_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        $count_stmt->close();
        if ($count_result && $count_result['request_count'] <= 1) {
            $data['can_edit_license'] = 'true';
        }
    }

    echo json_encode(['success' => true, 'data' => $data]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Request not found or you do not have permission to view it.']);
}

$stmt->close();
$conn->close();
?>

