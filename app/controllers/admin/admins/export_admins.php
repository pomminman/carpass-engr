<?php
// app/controllers/admin/admins/export_admins.php

// 1. Load Composer's autoloader
require_once __DIR__ . '/../../../../vendor/autoload.php';

// 2. Use PhpSpreadsheet classes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// 3. Start session and check for authentication
session_start();
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

// 5. Reuse the filtering logic from manage_admins.php
$filters = [
    'role' => $_GET['role'] ?? 'all',
    'department' => $_GET['department'] ?? 'all',
    'search' => $_GET['search'] ?? ''
];

$where_clauses = [];
$params = [];
$types = '';

if ($filters['role'] !== 'all') {
    $where_clauses[] = 'a.role = ?';
    $params[] = $filters['role'];
    $types .= 's';
}
if ($filters['department'] !== 'all') {
    $where_clauses[] = 'a.department = ?';
    $params[] = $filters['department'];
    $types .= 's';
}
if (!empty($filters['search'])) {
    $where_clauses[] = "(a.firstname LIKE ? OR a.lastname LIKE ? OR a.username LIKE ?)";
    $search_term = "%" . $filters['search'] . "%";
    array_push($params, $search_term, $search_term, $search_term);
    $types .= "sss";
}

// 6. Fetch data from the database
$sql_admins = "SELECT 
                a.username, a.title, a.firstname, a.lastname, a.department, 
                a.role, a.view_permission, a.created_at
              FROM admins a
              " . (!empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "") . "
              ORDER BY a.created_at DESC";

$stmt_admins = $conn->prepare($sql_admins);
if (!empty($params)) {
    $stmt_admins->bind_param($types, ...$params);
}
$stmt_admins->execute();
$result_admins = $stmt_admins->get_result();

// 7. Create a new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('รายชื่อเจ้าหน้าที่');

// 8. Set Headers and apply styling
$headers = ['ลำดับ', 'ชื่อผู้ใช้', 'คำนำหน้า', 'ชื่อจริง', 'นามสกุล', 'สังกัด', 'ระดับสิทธิ์', 'สิทธิ์เข้าถึงข้อมูล', 'วันที่เพิ่ม'];
$sheet->fromArray($headers, NULL, 'A1');

$headerStyle = $sheet->getStyle('A1:I1');
$headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
$headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('4F81BD');
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// 9. Populate data rows
$rowIndex = 2;
$count = 1;
if ($result_admins->num_rows > 0) {
    while($admin = $result_admins->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowIndex, $count);
        $sheet->setCellValue('B' . $rowIndex, $admin['username']);
        $sheet->setCellValue('C' . $rowIndex, $admin['title']);
        $sheet->setCellValue('D' . $rowIndex, $admin['firstname']);
        $sheet->setCellValue('E' . $rowIndex, $admin['lastname']);
        $sheet->setCellValue('F' . $rowIndex, $admin['department']);
        $sheet->setCellValue('G' . $rowIndex, ucfirst($admin['role']));
        $sheet->setCellValue('H' . $rowIndex, $admin['view_permission'] == 1 ? 'ดูได้ทุกสังกัด' : 'เฉพาะสังกัดตนเอง');
        $sheet->setCellValue('I' . $rowIndex, $admin['created_at']);
        
        $rowIndex++;
        $count++;
    }
}

// 10. Auto-size columns
foreach (range('A', 'I') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// 11. Set Headers for download
$filename = 'export_admins_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// 12. Create Writer and output the file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

// 13. Clean up
$stmt_admins->close();
$conn->close();
exit;
?>
