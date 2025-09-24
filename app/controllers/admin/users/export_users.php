<?php
// app/controllers/admin/users/export_users.php (Upgraded to XLSX)

// 1. Load Composer's autoloader
require_once __DIR__ . '/../../../../vendor/autoload.php';

// 2. Use PhpSpreadsheet classes
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// 3. Start session and check for authentication
session_start();
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    // Prevent direct access if not logged in
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
// Add other filters as needed...

if (!empty($filters['search'])) {
    $where_clauses[] = "(u.firstname LIKE ? OR u.lastname LIKE ? OR u.phone_number LIKE ? OR u.national_id LIKE ?)";
    $search_term = "%" . $filters['search'] . "%";
    array_push($params, $search_term, $search_term, $search_term, $search_term);
    $types .= "ssss";
}

// 6. Fetch data from the database (without pagination limits)
$sql_users = "SELECT 
                u.id, u.title, u.firstname, u.lastname, u.user_type,
                u.phone_number, u.national_id, u.work_department, u.created_at
              FROM users u
              " . (!empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "") . "
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
$sheet->setTitle('ລາຍຊື່ຜູ້ໃຊ້');

// 8. Set Headers and apply styling
$headers = ['ลำดับ', 'คำนำหน้า', 'ชื่อจริง', 'นามสกุล', 'ประเภท', 'เบอร์โทรศัพท์', 'เลขบัตรประชาชน', 'สังกัด', 'วันที่สมัคร'];
$sheet->fromArray($headers, NULL, 'A1');

// Style the header row
$headerStyle = $sheet->getStyle('A1:I1');
$headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE));
$headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('4F81BD');
$headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// 9. Populate data rows
$rowIndex = 2;
$count = 1;
if ($result_users->num_rows > 0) {
    while($user = $result_users->fetch_assoc()) {
        $user_type_thai = ($user['user_type'] === 'army') ? 'กำลังพล ทบ.' : 'บุคคลภายนอก';
        $sheet->setCellValue('A' . $rowIndex, $count);
        $sheet->setCellValue('B' . $rowIndex, $user['title']);
        $sheet->setCellValue('C' . $rowIndex, $user['firstname']);
        $sheet->setCellValue('D' . $rowIndex, $user['lastname']);
        $sheet->setCellValue('E' . $rowIndex, $user_type_thai);
        $sheet->setCellValueExplicit('F' . $rowIndex, $user['phone_number'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('G' . $rowIndex, $user['national_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('H' . $rowIndex, $user['work_department'] ?? '-');
        $sheet->setCellValue('I' . $rowIndex, $user['created_at']);
        
        $rowIndex++;
        $count++;
    }
}

// 10. Auto-size columns for better readability
foreach (range('A', 'I') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// 11. Set Headers for download
$filename = 'export_users_' . date('Y-m-d') . '.xlsx';
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

