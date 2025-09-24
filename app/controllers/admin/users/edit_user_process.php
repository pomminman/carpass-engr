<?php
// app/controllers/admin/users/edit_user_process.php
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

function handle_error($user_message, $user_id) {
    global $conn;
    log_activity($conn, 'admin_edit_user_fail', ['edited_user_id' => $user_id, 'error' => $user_message]);
    $_SESSION['flash_message'] = $user_message;
    $_SESSION['flash_status'] = 'error';
    header("Location: ../../../views/admin/home/view_user.php?id=" . $user_id);
    exit();
}

// Function to process image upload (can be moved to a helper file in a larger app)
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
         // For simplicity, we'll just copy the file for the thumb for now.
         // A real implementation should resize the image.
        copy($targetDir . $normalFile, $targetDir . $thumbFile);
        return ['filenames' => ['normal' => $normalFile, 'thumb' => $thumbFile]];
    }
    return ['error' => 'Failed to move uploaded file.'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user_id_to_edit = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    if (!$user_id_to_edit) {
        // Redirect to a general page if ID is missing
        header("Location: ../../../views/admin/home/manage_users.php");
        exit();
    }

    $update_fields = [];
    $params = [];
    $types = "";

    // Fetch user_key for file path
    $stmt_ukey = $conn->prepare("SELECT user_key, user_type FROM users WHERE id = ?");
    $stmt_ukey->bind_param("i", $user_id_to_edit);
    $stmt_ukey->execute();
    $user_info = $stmt_ukey->get_result()->fetch_assoc();
    if(!$user_info) handle_error("ไม่พบข้อมูลผู้ใช้ที่ต้องการแก้ไข", $user_id_to_edit);
    $stmt_ukey->close();
    
    // Handle photo upload
    if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "../../../../public/uploads/{$user_info['user_key']}/profile/";
        $uploadResult = process_profile_image_upload($_FILES['photo_upload'], $targetDir);

        if (isset($uploadResult['error'])) handle_error("อัปโหลดรูปภาพไม่สำเร็จ: " . $uploadResult['error'], $user_id_to_edit);
        
        $update_fields[] = "photo_profile = ?";
        $params[] = $uploadResult['filenames']['normal'];
        $types .= "s";

        $update_fields[] = "photo_profile_thumb = ?";
        $params[] = $uploadResult['filenames']['thumb'];
        $types .= "s";
    }

    // Sanitize and collect other form data
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
    $phone_number = preg_replace('/\D/', '', $_POST['phone_number']);
    $national_id = preg_replace('/\D/', '', $_POST['national_id']);
    $address = htmlspecialchars(strip_tags(trim($_POST['address'])));
    $subdistrict = htmlspecialchars(strip_tags(trim($_POST['subdistrict'])));
    $district = htmlspecialchars(strip_tags(trim($_POST['district'])));
    $province = htmlspecialchars(strip_tags(trim($_POST['province'])));
    $zipcode = htmlspecialchars(strip_tags(trim($_POST['zipcode'])));
    
    array_push($update_fields, "title = ?", "firstname = ?", "lastname = ?", "dob = ?", "gender = ?", "phone_number = ?", "national_id = ?", "address = ?", "subdistrict = ?", "district = ?", "province = ?", "zipcode = ?");
    array_push($params, $final_title, $firstname, $lastname, $dob, $gender, $phone_number, $national_id, $address, $subdistrict, $district, $province, $zipcode);
    $types .= "ssssssssssss";

    if ($user_info['user_type'] === 'army') {
        $work_department = htmlspecialchars(strip_tags(trim($_POST['work_department'])));
        $position = htmlspecialchars(strip_tags(trim($_POST['position'])));
        $official_id = preg_replace('/\D/', '', $_POST['official_id']);
        array_push($update_fields, "work_department = ?", "position = ?", "official_id = ?");
        array_push($params, $work_department, $position, $official_id);
        $types .= "sss";
    }

    if (!empty($update_fields)) {
        $sql_update = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $params[] = $user_id_to_edit;
        $types .= "i";
        
        $stmt_update = $conn->prepare($sql_update);
        if(!$stmt_update) handle_error("SQL Prepare Error: " . $conn->error, $user_id_to_edit);
        
        $stmt_update->bind_param($types, ...$params);

        if ($stmt_update->execute()) {
            log_activity($conn, 'admin_edit_user_success', ['admin_id' => $_SESSION['admin_id'], 'edited_user_id' => $user_id_to_edit]);
            $_SESSION['flash_message'] = 'แก้ไขข้อมูลผู้ใช้สำเร็จ';
            $_SESSION['flash_status'] = 'success';
        } else {
            handle_error("เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $stmt_update->error, $user_id_to_edit);
        }
        $stmt_update->close();
    } else {
        $_SESSION['flash_message'] = 'ไม่มีข้อมูลที่ถูกเปลี่ยนแปลง';
        $_SESSION['flash_status'] = 'info';
    }
    
} else {
    // Should not happen if coming from the form
    header("Location: ../../../views/admin/home/manage_users.php");
    exit();
}

$conn->close();
header("Location: ../../../views/admin/home/view_user.php?id=" . $user_id_to_edit);
exit();
?>
