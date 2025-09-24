<?php
// app/controllers/admin/users/add_user_process.php
session_start();
date_default_timezone_set('Asia/Bangkok');

// --- Security Check: Must be a logged-in admin ---
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../../../views/admin/login/login.php");
    exit;
}

require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection Error: " . $conn->connect_error);
}
$conn->set_charset("utf8");

function handle_error($user_message) {
    global $conn;
    log_activity($conn, 'admin_add_user_fail', ['error' => $user_message]);
    $_SESSION['flash_message'] = $user_message;
    $_SESSION['flash_status'] = 'error';
    header("Location: ../../../views/admin/home/add_user.php");
    exit();
}

// Function to process image upload
function process_profile_image_upload($file, $targetDir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return ['error' => 'Error during file upload: ' . ($file['error'] ?? 'Unknown error')];
    $max_size = 5 * 1024 * 1024;
    if ($file["size"] > $max_size) return ['error' => 'File size exceeds 5 MB limit.'];
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $baseName = bin2hex(random_bytes(16));
    $normalFile = $baseName . '.' . $extension;
    $thumbFile = $baseName . '_thumb.' . $extension;
    if (move_uploaded_file($file['tmp_name'], $targetDir . $normalFile)) {
        copy($targetDir . $normalFile, $targetDir . $thumbFile);
        return ['filenames' => ['normal' => $normalFile, 'thumb' => $thumbFile]];
    }
    return ['error' => 'Failed to move uploaded file.'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Data Sanitization and Preparation ---
    $user_key = bin2hex(random_bytes(10));

    // [FIX] Handle optional photo upload, defaulting to NULL
    $photo_profile_filename = null;
    $photo_profile_thumb_filename = null;
    if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] === UPLOAD_ERR_OK && $_FILES['photo_upload']['size'] > 0) {
        $targetDir = "../../../../public/uploads/{$user_key}/profile/";
        $photoUploadResult = process_profile_image_upload($_FILES["photo_upload"], $targetDir);
        
        if (isset($photoUploadResult['error'])) {
            handle_error("อัปโหลดรูปโปรไฟล์ไม่สำเร็จ: " . $photoUploadResult['error']);
        }
        
        $photo_profile_filename = $photoUploadResult['filenames']['normal'];
        $photo_profile_thumb_filename = $photoUploadResult['filenames']['thumb'];
    }

    $user_type = htmlspecialchars(strip_tags(trim($_POST['user_type'])));

    // [FIX] Convert empty strings for unique fields (phone, nid) to NULL
    $phone_raw = preg_replace('/\D/', '', $_POST['phone_number'] ?? '');
    $phone_number = !empty($phone_raw) ? $phone_raw : null;

    $nid_raw = preg_replace('/\D/', '', $_POST['national_id'] ?? '');
    $national_id = !empty($nid_raw) ? $nid_raw : null;

    $title_choice = htmlspecialchars(strip_tags(trim($_POST['title'])));
    $final_title = ($title_choice === 'other') ? htmlspecialchars(strip_tags(trim($_POST['title_other']))) : $title_choice;
    $firstname = htmlspecialchars(strip_tags(trim($_POST['firstname'])));
    $lastname = htmlspecialchars(strip_tags(trim($_POST['lastname'])));
    $dob = null;
    if (!empty($_POST['dob_day']) && !empty($_POST['dob_month']) && !empty($_POST['dob_year'])) {
        $dob_year_ad = intval($_POST['dob_year']) - 543;
        $dob = "{$dob_year_ad}-{$_POST['dob_month']}-{$_POST['dob_day']}";
    }
    $gender = htmlspecialchars(strip_tags(trim($_POST['gender'])));
    
    $address = htmlspecialchars(strip_tags(trim($_POST['address'])));
    $subdistrict = htmlspecialchars(strip_tags(trim($_POST['subdistrict'])));
    $district = htmlspecialchars(strip_tags(trim($_POST['district'])));
    $province = htmlspecialchars(strip_tags(trim($_POST['province'])));
    $zipcode = htmlspecialchars(strip_tags(trim($_POST['zipcode'])));

    $work_department = null;
    $position = null;
    $official_id = null;
    if ($user_type === 'army') {
        $work_department = htmlspecialchars(strip_tags(trim($_POST['work_department'])));
        if ($work_department === 'other') {
            $work_department = htmlspecialchars(strip_tags(trim($_POST['work_department_other'])));
        }
        $position = htmlspecialchars(strip_tags(trim($_POST['position'])));
        $official_id = preg_replace('/\D/', '', $_POST['official_id']);
    }

    $created_by_admin_id = $_SESSION['admin_id'];

    // --- Database Insertion ---
    $sql = "INSERT INTO users (user_key, user_type, phone_number, national_id, title, firstname, lastname, dob, gender, address, subdistrict, district, province, zipcode, photo_profile, photo_profile_thumb, work_department, position, official_id, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        handle_error("SQL Prepare Error: " . $conn->error);
    }
    
    $stmt->bind_param("sssssssssssssssssssi", 
        $user_key, $user_type, $phone_number, $national_id, $final_title, $firstname, $lastname, $dob, $gender, 
        $address, $subdistrict, $district, $province, $zipcode, 
        $photo_profile_filename, $photo_profile_thumb_filename,
        $work_department, $position, $official_id, 
        $created_by_admin_id
    );

    if (!$stmt->execute()) {
        if ($conn->errno == 1062) { // 1062 is the error code for Duplicate entry
            handle_error("ข้อมูลผิดพลาด: เบอร์โทรศัพท์หรือเลขบัตรประชาชนนี้มีอยู่ในระบบแล้ว");
        } else {
            handle_error("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error);
        }
    }
    
    $new_user_id = $stmt->insert_id;
    log_activity($conn, 'admin_add_user_success', ['admin_id' => $created_by_admin_id, 'new_user_id' => $new_user_id]);
    
    $_SESSION['flash_message'] = 'เพิ่มผู้ใช้งานใหม่สำเร็จ!';
    $_SESSION['flash_status'] = 'success';
    
    $stmt->close();
    $conn->close();
    header("Location: ../../../views/admin/home/manage_users.php");
    exit();

} else {
    header("Location: ../../../views/admin/home/add_user.php");
    exit();
}
?>

