<?php
// app/controllers/admin/users/export_users.php

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

// 4. Include database configuration
require_once '../../../models/db_config.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// 5. Reuse the filtering logic from manage_users.php
$filters = [
    'type' => $_GET['type'] ?? 'all',
    'department' => $_GET['department'] ?? 'all',
    'date' => $_GET['date'] ?? 'all',
    'vehicles' => $_GET['vehicles'] ?? 'all',
    'search' => $_GET['search'] ?? ''
];

$where_clauses = [];
$params = [];
$types = '';

if ($filters['type'] !== 'all') {
    $where_clauses[] = 'u.user_type = ?';
    $params[] = $filters['type'];
    $types .= 's';
}
if ($filters['department'] !== 'all') {
    $where_clauses[] = 'u.work_department = ?';
    $params[] = $filters['department'];
    $types .= 's';
}
if ($filters['date'] !== 'all') {
    $date_conditions = [
        'today' => "DATE(u.created_at) = CURDATE()",
        'this_month' => "YEAR(u.created_at) = YEAR(CURDATE()) AND MONTH(u.created_at) = MONTH(CURDATE())"
    ];
    if (isset($date_conditions[$filters['date']])) {
        $where_clauses[] = $date_conditions[$filters['date']];
    }
}
$having_clauses = [];
if ($filters['vehicles'] !== 'all') {
    $having_clauses[] = ($filters['vehicles'] === 'yes') ? 'COUNT(vr.id) > 0' : 'COUNT(vr.id) = 0';
}

if (!empty($filters['search'])) {
    $where_clauses[] = "(u.firstname LIKE ? OR u.lastname LIKE ? OR u.phone_number LIKE ? OR u.national_id LIKE ?)";
    $search_term = "%" . $filters['search'] . "%";
    array_push($params, $search_term, $search_term, $search_term, $search_term);
    $types .= "ssss";
}

// 6. Fetch data from the database (without pagination limits)
$sql_users = "SELECT 
                u.*, COUNT(vr.id) as vehicle_count
              FROM users u
              LEFT JOIN vehicle_requests vr ON u.id = vr.user_id
              " . (!empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "") . "
              GROUP BY u.id
              " . (!empty($having_clauses) ? "HAVING " . implode(' AND ', $having_clauses) : "") . "
              ORDER BY u.created_at DESC";

$stmt_users = $conn->prepare($sql_users);
if (!empty($params)) {
    $stmt_users->bind_param($types, ...$params);
}
$stmt_users->execute();
$result_users = $stmt_users->get_result();

// 7. Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('รายชื่อผู้ใช้งาน');

// 8. Set Headers and apply styling
$headers = [
    'ลำดับ', 'ประเภท', 'เบอร์โทรศัพท์', 'เลขบัตรประชาชน', 
    'คำนำหน้า', 'ชื่อจริง', 'นามสกุล', 'วันเกิด', 'เพศ', 
    'ที่อยู่', 'ตำบล/แขวง', 'อำเภอ/เขต', 'จังหวัด', 'รหัสไปรษณีย์',
    'รูปโปรไฟล์', 'สังกัด', 'ตำแหน่ง', 'เลขบัตร ขรก.',
    'วันที่สร้าง'
];
$sheet->fromArray($headers, NULL, 'A1');

$headerStyle = $sheet->getStyle('A1:S1');
$headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
$headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('4F81BD');
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// 9. Populate data rows
$rowIndex = 2;
$count = 1;
if ($result_users->num_rows > 0) {
    while($user = $result_users->fetch_assoc()) {
        $user_type_thai = ($user['user_type'] === 'army') ? 'กำลังพล ทบ.' : 'บุคคลภายนอก';
        
        $colIndex = 'A';
        $sheet->setCellValue($colIndex++ . $rowIndex, $count);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user_type_thai);
        $sheet->setCellValueExplicit($colIndex++ . $rowIndex, $user['phone_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit($colIndex++ . $rowIndex, $user['national_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['title']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['firstname']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['lastname']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['dob']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['gender']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['address']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['subdistrict']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['district']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['province']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['zipcode']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['photo_profile']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['work_department']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['position']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['official_id']);
        $sheet->setCellValue($colIndex++ . $rowIndex, $user['created_at']);
        
        $rowIndex++;
        $count++;
    }
}

// 10. Auto-size columns for better readability
foreach (range('A', 'S') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// 11. Set Headers for download
$filename = 'ผู้ใช้งาน_' . date('Y-m-d_H-i-s') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// 12. Create Writer and output the file to the browser
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// 13. Clean up
$stmt_users->close();
$conn->close();
exit;

?>

