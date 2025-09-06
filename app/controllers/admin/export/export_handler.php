<?php
session_start();
header('Content-Type: application/json');

// Security check: only logged-in admins can access
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

require_once '../../../models/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8");

$export_type = $_GET['type'] ?? 'all';
$columns = $_GET['columns'] ?? [];
$search_term = $_GET['search'] ?? '';

// Whitelist of all possible columns to prevent SQL injection
$allowed_columns_map = [
    'users' => [ 'u.user_type', 'u.phone_number', 'u.national_id', 'u.title', 'u.firstname', 'u.lastname', 'u.dob', 'u.gender', 'u.address', 'u.subdistrict', 'u.district', 'u.province', 'u.zipcode', 'u.work_department', 'u.position', 'u.official_id', 'u.created_at as user_created_at'],
    'vehicles' => ['vr.search_id', 'vr.card_type', 'vr.vehicle_type', 'vr.brand', 'vr.model', 'vr.color', 'vr.license_plate', 'vr.province as vehicle_province', 'vr.tax_expiry_date', 'vr.owner_type', 'vr.other_owner_name', 'vr.other_owner_relation', 'vr.status', 'vr.rejection_reason', 'vr.approved_at', 'vr.card_number', 'vr.card_expiry_year', 'vr.card_pickup_status', 'vr.edit_status', 'vr.created_at as request_created_at']
];
$allowed_columns = array_merge($allowed_columns_map['users'], $allowed_columns_map['vehicles']);

$select_clause = "";
$data_to_export = [];

switch ($export_type) {
    case 'table_view':
        $select_clause = "vr.search_id, u.title, u.firstname, u.lastname, vr.license_plate, vr.province as vehicle_province, vr.vehicle_type, vr.created_at as request_created_at";
        break;
    case 'users':
        $select_clause = implode(', ', $allowed_columns_map['users']);
        break;
    case 'vehicles':
        $select_clause = implode(', ', $allowed_columns_map['vehicles']);
        break;
    case 'custom':
        if (empty($columns)) {
            echo json_encode(['success' => false, 'message' => 'No columns selected for custom export.']);
            exit;
        }
        $selected_safe_columns = array_intersect($columns, $allowed_columns);
        if (empty($selected_safe_columns)) {
            echo json_encode(['success' => false, 'message' => 'Invalid columns selected.']);
            exit;
        }
        $select_clause = implode(', ', $selected_safe_columns);
        break;
    case 'all':
    default:
        $select_clause = implode(', ', $allowed_columns);
        break;
}

$sql = "SELECT $select_clause 
        FROM vehicle_requests vr 
        JOIN users u ON vr.user_id = u.id 
        WHERE vr.status = 'pending'";

$params = [];
$types = '';

if (!empty($search_term)) {
    $like_term = "%{$search_term}%";
    $sql .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR CONCAT(u.firstname, ' ', u.lastname) LIKE ? OR vr.search_id LIKE ? OR vr.license_plate LIKE ?)";
    for ($i = 0; $i < 5; $i++) {
        $params[] = $like_term;
        $types .= 's';
    }
}

$sql .= " ORDER BY vr.created_at ASC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $sequence = 1;
        while($row = $result->fetch_assoc()) {
            $processed_row = [];
            $processed_row['sequence'] = $sequence++;

            foreach($row as $key => $value) {
                switch($key) {
                    case 'user_type':
                        $processed_row[$key] = ($value == 'army') ? "ข้าราชการ/ลูกจ้าง/พนักงานราชการ ทบ." : "บุคคลภายนอก";
                        break;
                    case 'card_type':
                        $processed_row[$key] = ($value == 'internal') ? "ภายใน" : (($value == 'external') ? "ภายนอก" : $value);
                        break;
                    case 'owner_type':
                        $processed_row[$key] = ($value == 'self') ? "รถชื่อตนเอง" : "รถผู้อื่น";
                        break;
                    case 'status':
                         $processed_row[$key] = ($value == 'pending') ? "รออนุมัติ" : (($value == 'approved') ? "อนุมัติ" : "ไม่ผ่าน");
                        break;
                    case 'card_pickup_status':
                        $processed_row[$key] = ($value == 0) ? "ยังไม่ได้รับบัตร" : "รับบัตรแล้ว";
                        break;
                    case 'edit_status':
                        $processed_row[$key] = ($value == 0) ? "ยังไม่เคยแก้ไข" : "แก้ไขแล้ว";
                        break;
                    default:
                        $processed_row[$key] = $value;
                }
            }
             if ($export_type === 'table_view') {
                $data_to_export[] = [
                    'sequence' => $processed_row['sequence'],
                    'search_id' => $processed_row['search_id'],
                    'fullname' => ($processed_row['title'] ?? '') . ($processed_row['firstname'] ?? '') . ' ' . ($processed_row['lastname'] ?? ''),
                    'license' => ($processed_row['license_plate'] ?? '') . ' ' . ($processed_row['vehicle_province'] ?? ''),
                    'vehicle_type' => $processed_row['vehicle_type'],
                    'request_created_at' => $processed_row['request_created_at'],
                ];
            } else {
                $data_to_export[] = $processed_row;
            }
        }
        echo json_encode(['success' => true, 'data' => $data_to_export]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
    }
    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'SQL prepare failed: ' . $conn->error]);
}

$conn->close();
?>

