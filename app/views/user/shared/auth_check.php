<?php
// app/views/user/shared/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: /app/views/user/login/login.php");
    exit;
}

require_once __DIR__ . '/../../../models/db_config.php';

if (!function_exists('format_thai_date_helper')) {
    function format_thai_date_helper($date) {
        if (empty($date) || $date === '0000-00-00') return '-';
        $timestamp = strtotime($date);
        $thai_months = [1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.', 5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.', 9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'];
        return date('d', $timestamp) . ' ' . $thai_months[date('n', $timestamp)] . ' ' . (date('Y', $timestamp) + 543);
    }
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    session_destroy();
    header("Location: /app/views/user/login/login.php");
    exit;
}
$conn->set_charset("utf8");

$user_id = $_SESSION['user_id'];
$user = null;
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($result_user->num_rows === 1) {
    $user = $result_user->fetch_assoc();
} else {
    session_destroy();
    header("Location: /app/views/user/login/login.php");
    exit;
}
$stmt_user->close();

// Prepare variables for use in views
$title = $user['title'];
$firstname = $user['firstname'];
$lastname = $user['lastname'];
$user_key = $user['user_key'];
$photo_profile_filename = $user['photo_profile'];
$photo_profile_thumb_filename = $user['photo_profile_thumb'];
$user_type_eng = $user['user_type'];

$user_photo_path = '/public/assets/images/default-profile.png';
if (!empty($user_key) && !empty($photo_profile_filename)) {
    $user_photo_path = "/public/uploads/{$user_key}/profile/{$photo_profile_filename}";
}

$user_photo_thumb_path = '/public/assets/images/default-profile.png';
if (!empty($user_key) && !empty($photo_profile_thumb_filename)) {
    $user_photo_thumb_path = "/public/uploads/{$user_key}/profile/{$photo_profile_thumb_filename}";
}

$user_type_thai = '';
$user_type_icon = '';
switch ($user_type_eng) {
    case 'army': 
        $user_type_thai = 'ข้าราชการ/ลูกจ้าง/พนักงานราชการ ทบ.'; 
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

$provinces = ['กระบี่', 'กรุงเทพมหานคร', 'กาญจนบุรี', 'กาฬสินธุ์', 'กำแพงเพชร', 'ขอนแก่น', 'จันทบุรี', 'ฉะเชิงเทรา', 'ชลบุรี', 'ชัยนาท', 'ชัยภูมิ', 'ชุมพร', 'เชียงราย', 'เชียงใหม่', 'ตรัง', 'ตราด', 'ตาก', 'นครนายก', 'นครปฐม', 'นครพนม', 'นครราชสีมา', 'นครศรีธรรมราช', 'นครสวรรค์', 'นนทบุรี', 'นราธิวาส', 'น่าน', 'บึงกาฬ', 'บุรีรัมย์', 'ปทุมธานี', 'ประจวบคีรีขันธ์', 'ปราจีนบุรี', 'ปัตตานี', 'พระนครศรีอยุธยา', 'พะเยา', 'พังงา', 'พัทลุง', 'พิจิตร', 'พิษณุโลก', 'เพชรบุรี', 'เพชรบูรณ์', 'แพร่', 'ภูเก็ต', 'มหาสารคาม', 'มุกดาหาร', 'แม่ฮ่องสอน', 'ยโสธร', 'ยะลา', 'ร้อยเอ็ด', 'ระนอง', 'ระยอง', 'ราชบุรี', 'ลพบุรี', 'ลำปาง', 'ลำพูน', 'เลย', 'ศรีสะเกษ', 'สกลนคร', 'สงขลา', 'สตูล', 'สมุทรปราการ', 'สมุทรสงคราม', 'สมุทรสาคร', 'สระแก้ว', 'สระบุรี', 'สิงห์บุรี', 'สุโขทัย', 'สุพรรณบุรี', 'สุราษฎร์ธานี', 'สุรินทร์', 'หนองคาย', 'หนองบัวลำภู', 'อ่างทอง', 'อำนาจเจริญ', 'อุดรธานี', 'อุตรดิตถ์', 'อุทัยธานี', 'อุบลราชานี'];

$current_page = basename($_SERVER['PHP_SELF']);
?>
