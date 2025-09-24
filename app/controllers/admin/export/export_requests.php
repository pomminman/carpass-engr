<?php
// app/controllers/admin/export/export_requests.php
session_start();
date_default_timezone_set('Asia/Bangkok');

// --- Security Check: Only logged-in admins can access ---
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    http_response_code(403);
    die("Access Denied");
}

require_once '../../../models/db_config.php';

// --- Helper Functions for Formatting ---

/**
 * Ensures a value is not empty, otherwise returns a default placeholder.
 */
function format_value($value, $default = '-') {
    return (isset($value) && trim($value) !== '') ? $value : $default;
}

/**
 * Formats a 10-digit phone number into xxx-xxx-xxxx.
 */
function format_phone($phone) {
    $cleaned = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleaned) === 10) {
        return substr($cleaned, 0, 3) . '-' . substr($cleaned, 3, 3) . '-' . substr($cleaned, 6);
    }
    return $phone; // Return original if not 10 digits
}

/**
 * Formats a 13-digit national ID into x-xxxx-xxxxx-xx-x.
 */
function format_nid($nid) {
    $cleaned = preg_replace('/[^0-9]/', '', $nid);
    if (strlen($cleaned) === 13) {
        return substr($cleaned, 0, 1) . '-' . substr($cleaned, 1, 4) . '-' . substr($cleaned, 5, 5) . '-' . substr($cleaned, 10, 2) . '-' . substr($cleaned, 12, 1);
    }
    return $nid;
}

// --- Database Connection ---
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// --- Get filter parameters from URL ---
$selected_period_id = $_GET['period_id'] ?? 'all';
$selected_status = $_GET['status'] ?? 'all';

// --- Build dynamic WHERE clauses for SQL query based on selections ---
$where_clauses = [];
$params = [];
$types = '';

if ($selected_period_id !== 'all' && is_numeric($selected_period_id)) {
    $where_clauses[] = 'vr.period_id = ?';
    $params[] = (int)$selected_period_id;
    $types .= 'i';
}
if ($selected_status !== 'all') {
    $where_clauses[] = 'vr.status = ?';
    $params[] = $selected_status;
    $types .= 's';
}
$where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);

// --- Main SQL Query to fetch all relevant data ---
$sql = "SELECT 
            vr.search_id,
            vr.status,
            vr.card_number,
            vr.card_type,
            vr.request_key,
            vr.created_at,
            vr.approved_at,
            vr.card_pickup_date,
            vr.card_expiry,
            vr.rejection_reason,
            COALESCE(aud.title, u.title) AS user_title,
            COALESCE(aud.firstname, u.firstname) AS user_firstname,
            COALESCE(aud.lastname, u.lastname) AS user_lastname,
            COALESCE(aud.user_type, u.user_type) AS user_type,
            COALESCE(aud.phone_number, u.phone_number) AS phone_number,
            COALESCE(aud.national_id, u.national_id) AS national_id,
            COALESCE(aud.work_department, u.work_department) AS work_department,
            COALESCE(aud.position, u.position) AS position,
            COALESCE(aud.official_id, u.official_id) AS official_id,
            CONCAT(COALESCE(aud.address, u.address), ' ต.', COALESCE(aud.subdistrict, u.subdistrict), ' อ.', COALESCE(aud.district, u.district), ' จ.', COALESCE(aud.province, u.province), ' ', COALESCE(aud.zipcode, u.zipcode)) AS full_address,
            v.license_plate,
            v.province AS vehicle_province,
            v.vehicle_type,
            v.brand,
            v.model,
            v.color
        FROM vehicle_requests vr
        JOIN users u ON vr.user_id = u.id
        JOIN vehicles v ON vr.vehicle_id = v.id
        LEFT JOIN approved_user_data aud ON vr.id = aud.request_id
        $where_sql
        ORDER BY vr.created_at DESC";

$stmt = $conn->prepare($sql);
if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// --- Generate CSV File ---
// [REVISED] Change filename back to .csv
$filename = "carpass_requests_" . date('Y-m-d_His') . ".csv";

// [REVISED] Change Content-Type back to CSV format
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Add BOM to support UTF-8 in Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// --- Define CSV Headers ---
$headers = [
    'ลำดับ', 'รหัสคำร้อง', 'สถานะ', 'ชื่อ-สกุลผู้ยื่น', 'ประเภทผู้ใช้', 'เบอร์โทรศัพท์', 'เลขบัตรประชาชน',
    'สังกัด', 'ตำแหน่ง', 'เลขบัตร ขรก.', 'ที่อยู่',
    'ทะเบียนรถ', 'จังหวัด', 'ประเภทรถ', 'ยี่ห้อ', 'รุ่น', 'สี',
    'ประเภทบัตร', 'เลขที่บัตร', 'ชื่อไฟล์ QR Code', 'วันที่ยื่น', 'วันที่อนุมัติ/ปฏิเสธ', 'วันที่นัดรับบัตร', 'วันที่บัตรหมดอายุ', 'เหตุผลที่ไม่ผ่าน'
];
fputcsv($output, $headers);

// --- Write Data Rows ---
$sequence = 1;
while ($row = $result->fetch_assoc()) {
    $status_text = '';
    switch ($row['status']) {
        case 'pending': $status_text = 'รออนุมัติ'; break;
        case 'approved': $status_text = 'อนุมัติแล้ว'; break;
        case 'rejected': $status_text = 'ไม่ผ่าน'; break;
        default: $status_text = $row['status'];
    }
    
    $user_type_text = ($row['user_type'] === 'army') ? 'ข้าราชการ/ลูกจ้าง/พนักงานราชการ ทบ.' : 'บุคคลภายนอก';
    $card_type_text = ($row['card_type'] === 'internal') ? 'ภายใน' : (($row['card_type'] === 'external') ? 'ภายนอก' : '');
    $qr_filename = !empty($row['request_key']) ? $row['request_key'] . '.png' : '';

    // [REVISED] Re-introduce the single quote prepending for numeric-like strings
    $csv_row = [
        $sequence++,
        format_value($row['search_id']),
        format_value($status_text),
        format_value($row['user_title'] . $row['user_firstname'] . ' ' . $row['user_lastname']),
        format_value($user_type_text),
        "'" . format_value(format_phone($row['phone_number'])),
        "'" . format_value(format_nid($row['national_id'])),
        format_value($row['work_department']),
        format_value($row['position']),
        "'" . format_value($row['official_id']),
        format_value($row['full_address']),
        format_value($row['license_plate']),
        format_value($row['vehicle_province']),
        format_value($row['vehicle_type']),
        format_value($row['brand']),
        format_value($row['model']),
        format_value($row['color']),
        format_value($card_type_text),
        "'" . format_value(str_pad($row['card_number'], 4, '0', STR_PAD_LEFT)),
        format_value($qr_filename),
        format_value($row['created_at']),
        format_value($row['approved_at']),
        format_value($row['card_pickup_date']),
        format_value($row['card_expiry']),
        format_value($row['rejection_reason'])
    ];
    fputcsv($output, $csv_row);
}

fclose($output);
$stmt->close();
$conn->close();
exit();
?>

