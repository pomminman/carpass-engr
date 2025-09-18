<?php
// app/views/user/shared/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: /app/views/user/login/login.php");
    exit;
}

require_once __DIR__ . '/../../../models/db_config.php';
require_once __DIR__ . '/../../../models/log_helper.php'; // [เพิ่ม] เรียกใช้ log_helper

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    session_destroy();
    header("Location: /app/views/user/login/login.php");
    exit;
}
$conn->set_charset("utf8");

$user_id = $_SESSION['user_id'];
$user = [];
$sql = "SELECT * FROM users WHERE id = ?";
if ($stmt_user = $conn->prepare($sql)) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($user_data = $result_user->fetch_assoc()) {
        $user = $user_data;
    } else {
        session_destroy();
        header("Location: /app/views/user/login/login.php");
        exit;
    }
    $stmt_user->close();
}

$current_page = basename($_SERVER['PHP_SELF']);

// [เพิ่ม] บันทึก Log การเข้าชมหน้า
// ตรวจสอบว่าเป็นหน้าที่ต้องการบันทึก Log และไม่ใช่การเรียกจาก AJAX ที่มี script ของตัวเอง
if (in_array($current_page, ['dashboard.php', 'add_vehicle.php', 'profile.php', 'costs.php', 'contact.php'])) {
    log_activity($conn, 'view_page', ['page' => $current_page]);
}


$user_photo_path = "/public/uploads/{$user['user_key']}/profile/{$user['photo_profile_thumb']}";
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $user_photo_path) || empty($user['photo_profile_thumb'])) {
    $user_photo_path = "https://placehold.co/100x100/e2e8f0/475569?text=Profile";
}

$user_type_thai = '';
$user_type_icon = '';
switch ($user['user_type']) {
    case 'army': 
        $user_type_thai = 'กำลังพล ทบ.'; 
        $user_type_icon = '<i class="fa-solid fa-shield-halved text-slate-500"></i>';
        break;
    case 'external': 
        $user_type_thai = 'บุคคลภายนอก'; 
        $user_type_icon = '<i class="fa-solid fa-user text-slate-500"></i>';
        break;
}

$car_brands = [];
$sql_brands = "SELECT name FROM car_brands ORDER BY display_order ASC, name ASC";
$result_brands = $conn->query($sql_brands);
if ($result_brands && $result_brands->num_rows > 0) {
    while($row = $result_brands->fetch_assoc()) {
        $car_brands[] = $row['name'];
    }
}

$provinces = ['กระบี่', 'กรุงเทพมหานคร', 'กาญจนบุรี', 'กาฬสินธุ์', 'กำแพงเพชร', 'ขอนแก่น', 'จันทบุรี', 'ฉะเชิงเทรา', 'ชลบุรี', 'ชัยนาท', 'ชัยภูมิ', 'ชุมพร', 'เชียงราย', 'เชียงใหม่', 'ตรัง', 'ตราด', 'ตาก', 'นครนายก', 'นครปฐม', 'นครพนม', 'นครราชสีมา', 'นครศรีธรรมราช', 'นครสวรรค์', 'นนทบุรี', 'นราธิวาส', 'น่าน', 'บึงกาฬ', 'บุรีรัมย์', 'ปทุมธานี', 'ประจวบคีรีขันธ์', 'ปราจีนบุรี', 'ปัตตานี', 'พระนครศรีอยุธยา', 'พะเยา', 'พังงา', 'พัทลุง', 'พิจิตร', 'พิษณุโลก', 'เพชรบุรี', 'เพชรบูรณ์', 'แพร่', 'ภูเก็ต', 'มหาสารคาม', 'มุกดาหาร', 'แม่ฮ่องสอน', 'ยโสธร', 'ยะลา', 'ร้อยเอ็ด', 'ระนอง', 'ระยอง', 'ราชบุรี', 'ลพบุรี', 'ลำปาง', 'ลำพูน', 'เลย', 'ศรีสะเกษ', 'สกลนคร', 'สงขลา', 'สตูล', 'สมุทรปราการ', 'สมุทรสงคราม', 'สมุทรสาคร', 'สระแก้ว', 'สระบุรี', 'สิงห์บุรี', 'สุโขทัย', 'สุพรรณบุรี', 'สุราษฎร์ธานี', 'สุรินทร์', 'หนองคาย', 'หนองบัวลำภู', 'อ่างทอง', 'อำนาจเจริญ', 'อุดรธานี', 'อุตรดิตถ์', 'อุทัยธานี', 'อุบลราชธานี'];
?>
