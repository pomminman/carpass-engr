<?php
// app/controllers/admin/requests/export_requests.php

// 1. Load Composer's autoloader
require_once __DIR__ . '/../../../../vendor/autoload.php';

// 2. Use PhpSpreadsheet classes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// 3. Start session and check for authentication
session_start();
date_default_timezone_set('Asia/Bangkok');
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../../../views/admin/login/login.php");
    exit;
}

// 4. Include database configuration and define helper function locally
require_once '../../../models/db_config.php';

if (!function_exists('format_thai_datetime')) {
    function format_thai_datetime($datetime) {
        if (empty($datetime) || strpos($datetime, '0000-00-00') !== false) return '-';
        $timestamp = strtotime($datetime);
        $thai_months = [1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'];
        return date('d', $timestamp) . ' ' . $thai_months[date('n', $timestamp)] . ' ' . substr(date('Y', $timestamp) + 543, -2) . ' ' . date('H:i', $timestamp);
    }
}


$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// 5. Build query based on ALL filters from GET parameters
$filters = [
    'period_id' => $_GET['period_id'] ?? 'all',
    'status' => $_GET['status'] ?? 'all',
    'payment_pickup_status' => $_GET['payment_pickup_status'] ?? 'all',
    'department' => $_GET['department'] ?? 'all',
    'vehicle_type' => $_GET['vehicle_type'] ?? 'all',
    'date_start' => $_GET['date_start'] ?? '',
    'date_end' => $_GET['date_end'] ?? '',
    'search' => $_GET['search'] ?? ''
];

$where_clauses = [];
$params = [];
$types = '';

if ($filters['period_id'] !== 'all' && is_numeric($filters['period_id'])) {
    $where_clauses[] = 'vr.period_id = ?';
    $params[] = (int)$filters['period_id'];
    $types .= 'i';
}
if ($filters['status'] !== 'all') {
    $where_clauses[] = 'vr.status = ?';
    $params[] = $filters['status'];
    $types .= 's';
}
if ($filters['department'] !== 'all') {
    $where_clauses[] = 'u.work_department = ?';
    $params[] = $filters['department'];
    $types .= 's';
}
if ($filters['vehicle_type'] !== 'all') {
    $where_clauses[] = 'v.vehicle_type = ?';
    $params[] = $filters['vehicle_type'];
    $types .= 's';
}
if ($filters['payment_pickup_status'] === 'paid') {
    $where_clauses[] = "vr.payment_status = 'paid' AND vr.card_pickup_status = 1";
} elseif ($filters['payment_pickup_status'] === 'unpaid') {
    $where_clauses[] = "vr.payment_status = 'unpaid' AND vr.card_pickup_status = 0";
}
if (!empty($filters['date_start'])) {
    $where_clauses[] = 'DATE(vr.created_at) >= ?';
    $params[] = $filters['date_start'];
    $types .= 's';
}
if (!empty($filters['date_end'])) {
    $where_clauses[] = 'DATE(vr.created_at) <= ?';
    $params[] = $filters['date_end'];
    $types .= 's';
}
if (!empty($filters['search'])) {
    $where_clauses[] = "(vr.search_id LIKE ? OR vr.card_number LIKE ? OR CONCAT(u.firstname, ' ', u.lastname) LIKE ? OR v.license_plate LIKE ?)";
    $search_term = "%" . $filters['search'] . "%";
    array_push($params, $search_term, $search_term, $search_term, $search_term);
    $types .= "ssss";
}

$where_sql = empty($where_clauses) ? '' : 'WHERE ' . implode(' AND ', $where_clauses);

// 6. Fetch data from the database
$sql_requests = "SELECT 
                    vr.search_id, vr.status, vr.card_number,
                    u.title, u.firstname, u.lastname, u.work_department,
                    v.license_plate, v.province, v.vehicle_type,
                    vr.created_at, vr.approved_at, vr.qr_code_path, vr.card_type,
                    vr.owner_type, vr.other_owner_name, vr.other_owner_relation
                 FROM vehicle_requests vr
                 JOIN users u ON vr.user_id = u.id
                 JOIN vehicles v ON vr.vehicle_id = v.id
                 $where_sql
                 ORDER BY vr.created_at DESC";

$stmt_requests = $conn->prepare($sql_requests);
if (!empty($params)) {
    $stmt_requests->bind_param($types, ...$params);
}
$stmt_requests->execute();
$result_requests = $stmt_requests->get_result();

// 7. Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('รายการคำร้อง');

// 8. Set Headers and style
$headers = [
    'ลำดับ', 'รหัสคำร้อง', 'สถานะ', 'วันที่ยื่น', 'วันที่อนุมัติ', 
    'เลขที่บัตร', 'ประเภทบัตรผ่าน', 'ชื่อไฟล์ QR-Code', 
    'ชื่อผู้ยื่น', 'สังกัด', 
    'ทะเบียนรถ', 'จังหวัด', 'ประเภทรถ', 'ชื่อเจ้าของรถ'
];
$sheet->fromArray($headers, NULL, 'A1');
$headerStyle = $sheet->getStyle('A1:N1');
$headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
$headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('4F81BD');
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// 9. Populate data rows
$rowIndex = 2;
$count = 1;
$status_map = ['pending' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ไม่ผ่าน'];
if ($result_requests->num_rows > 0) {
    while($req = $result_requests->fetch_assoc()) {
        $owner_details = '-';
        if ($req['owner_type'] === 'self') {
            $owner_details = 'รถชื่อตนเอง';
        } elseif ($req['owner_type'] === 'other') {
            $owner_details = ($req['other_owner_name'] ?? '') . ' (เกี่ยวข้องเป็น ' . ($req['other_owner_relation'] ?? '-') . ')';
        }

        $qr_filename_display = '-';
        if (!empty($req['qr_code_path'])) {
            $card_type_thai = $req['card_type'] === 'internal' ? 'ภายใน' : 'ภายนอก';
            $filename_parts = [
                $req['license_plate'],
                $req['province'],
                '(' . $req['vehicle_type'] . '_' . $card_type_thai . ')'
            ];
            $qr_filename_display = preg_replace('/[\s\/\\:*?"<>|]+/', '_', implode('_', $filename_parts)) . '.png';
        }

        $sheet->setCellValue('A' . $rowIndex, $count);
        $sheet->setCellValue('B' . $rowIndex, $req['search_id']);
        $sheet->setCellValue('C' . $rowIndex, $status_map[$req['status']] ?? $req['status']);
        $sheet->setCellValue('D' . $rowIndex, format_thai_datetime($req['created_at']));
        $sheet->setCellValue('E' . $rowIndex, format_thai_datetime($req['approved_at']));
        $sheet->setCellValueExplicit('F' . $rowIndex, $req['card_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('G' . $rowIndex, $req['card_type'] === 'internal' ? 'ภายใน' : ($req['card_type'] === 'external' ? 'ภายนอก' : '-'));
        $sheet->setCellValue('H' . $rowIndex, $qr_filename_display);
        $sheet->setCellValue('I' . $rowIndex, $req['title'] . $req['firstname'] . ' ' . $req['lastname']);
        $sheet->setCellValue('J' . $rowIndex, $req['work_department'] ?? '-');
        $sheet->setCellValue('K' . $rowIndex, $req['license_plate']);
        $sheet->setCellValue('L' . $rowIndex, $req['province']);
        $sheet->setCellValue('M' . $rowIndex, $req['vehicle_type']);
        $sheet->setCellValue('N' . $rowIndex, $owner_details);

        $rowIndex++;
        $count++;
    }
}

// 10. Auto-size columns
foreach (range('A', 'N') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

ob_start(); // Start output buffering

// 11. Set Headers for download
$filename = 'คำร้องยานพาหนะ_' . date('Y-m-d_H-i-s') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// 12. Create Writer and output the file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// 13. Clean up
$stmt_requests->close();
$conn->close();
ob_end_flush();
exit;
?>

