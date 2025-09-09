<?php
// controllers/user/profile/edit_profile_process.php

session_start();
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../../views/user/login/login.php");
    exit;
}

// เรียกใช้ไฟล์ที่จำเป็น
require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// ฟังก์ชันสำหรับจัดการข้อผิดพลาดและ Redirect
function handle_error($user_message) {
    $_SESSION['request_status'] = 'error';
    $_SESSION['request_message'] = $user_message;
    header("Location: ../../../views/user/home/profile.php");
    exit();
}

// ฟังก์ชันสำหรับอัปโหลดและบีบอัดรูปภาพ พร้อมแก้ไขการหมุนภาพอัตโนมัติ
function uploadAndCompressImage($file, $targetDir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์: ' . $file['error']];
    }
    $max_file_size = 5 * 1024 * 1024; // 5 MB
    if ($file["size"] > $max_file_size) {
        return ['error' => 'ไฟล์มีขนาดใหญ่เกิน 5 MB'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file["tmp_name"]);
    $allowed_mime_types = ['image/jpeg', 'image/png'];
    if (!in_array($mime_type, $allowed_mime_types)) {
        finfo_close($finfo);
        return ['error' => 'อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG) เท่านั้น'];
    }
    finfo_close($finfo);
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;
    $finalTargetPath = $targetDir . $newFileName;
    $quality = 75;

    $image = null;
    if ($mime_type == "image/jpeg") {
        $image = imagecreatefromjpeg($file["tmp_name"]);
        
        if ($image && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file["tmp_name"]);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3: $image = imagerotate($image, 180, 0); break;
                    case 6: $image = imagerotate($image, -90, 0); break;
                    case 8: $image = imagerotate($image, 90, 0); break;
                }
            }
        }
        
        if($image) {
            imagejpeg($image, $finalTargetPath, $quality);
        }

    } elseif ($mime_type == "image/png") {
        $image = imagecreatefrompng($file["tmp_name"]);
        if($image) {
            imagepng($image, $finalTargetPath, 7);
        }
    }

    if ($image) {
        imagedestroy($image);
    } else {
        return ['error' => 'ไม่สามารถประมวลผลไฟล์รูปภาพได้'];
    }

    return ['filename' => $newFileName];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user_id = $_SESSION['user_id'];
    $update_fields = [];
    $params = [];
    $types = "";

    // 1. ดึง user_type และ user_key ปัจจุบันจาก DB เพื่อความปลอดภัย
    $user_info = [];
    $stmt_uinfo = $conn->prepare("SELECT user_type, user_key FROM users WHERE id = ?");
    $stmt_uinfo->bind_param("i", $user_id);
    $stmt_uinfo->execute();
    $result_uinfo = $stmt_uinfo->get_result();
    if($row_uinfo = $result_uinfo->fetch_assoc()){
        $user_info = $row_uinfo;
    } else {
        handle_error("ไม่พบข้อมูลผู้ใช้");
    }
    $stmt_uinfo->close();
    $user_type = $user_info['user_type'];
    $user_key = $user_info['user_key'];


    // 2. จัดการการอัปโหลดไฟล์ (ถ้ามี)
    if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "../../../../public/uploads/{$user_key}/profile/";
        $uploadResult = uploadAndCompressImage($_FILES['photo_upload'], $targetDir);

        if (isset($uploadResult['error'])) {
            handle_error("อัปโหลดรูปภาพโปรไฟล์ไม่สำเร็จ: " . $uploadResult['error']);
        }
        $update_fields[] = "photo_profile = ?";
        $params[] = $uploadResult['filename'];
        $types .= "s";
    }

    // 3. เตรียมข้อมูลอื่นๆ สำหรับการอัปเดต
    $title_choice = htmlspecialchars(strip_tags(trim($_POST['title'])));
    if ($title_choice === 'other') {
        $final_title = htmlspecialchars(strip_tags(trim($_POST['title_other'])));
    } else {
        $final_title = $title_choice;
    }

    $firstname = htmlspecialchars(strip_tags(trim($_POST['firstname'])));
    $lastname = htmlspecialchars(strip_tags(trim($_POST['lastname'])));
    $dob_day = str_pad(htmlspecialchars(strip_tags(trim($_POST['dob_day']))), 2, '0', STR_PAD_LEFT);
    $dob_month = str_pad(htmlspecialchars(strip_tags(trim($_POST['dob_month']))), 2, '0', STR_PAD_LEFT);
    $dob_year_be = htmlspecialchars(strip_tags(trim($_POST['dob_year'])));
    $dob_year_ad = intval($dob_year_be) - 543;
    $dob = "$dob_year_ad-$dob_month-$dob_day";
    $gender = htmlspecialchars(strip_tags(trim($_POST['gender'])));
    $phone_number = preg_replace('/\D/', '', $_POST['phone']);
    $address = htmlspecialchars(strip_tags(trim($_POST['address'])));
    $subdistrict = htmlspecialchars(strip_tags(trim($_POST['subdistrict'])));
    $district = htmlspecialchars(strip_tags(trim($_POST['district'])));
    $province = htmlspecialchars(strip_tags(trim($_POST['province'])));
    $zipcode = htmlspecialchars(strip_tags(trim($_POST['zipcode'])));

    array_push($update_fields, "title = ?", "firstname = ?", "lastname = ?", "dob = ?", "gender = ?", "phone_number = ?", "address = ?", "subdistrict = ?", "district = ?", "province = ?", "zipcode = ?");
    array_push($params, $final_title, $firstname, $lastname, $dob, $gender, $phone_number, $address, $subdistrict, $district, $province, $zipcode);
    $types .= "sssssssssss";

    // 4. เพิ่มข้อมูลการทำงานลงใน Array สำหรับอัปเดต (ถ้าเป็น 'army')
    if ($user_type === 'army') {
        
        $work_department_choice = htmlspecialchars(strip_tags(trim($_POST['work_department'])));
        $work_department_other = htmlspecialchars(strip_tags(trim($_POST['work_department_other'])));
        $final_department = '';

        if ($work_department_choice === 'other') {
            if (empty($work_department_other)) {
                handle_error("กรุณาระบุชื่อสังกัดใหม่");
            }
            $final_department = $work_department_other;
            
            $sql_check_dept = "SELECT id FROM departments WHERE name = ?";
            $stmt_check_dept = $conn->prepare($sql_check_dept);
            $stmt_check_dept->bind_param("s", $final_department);
            $stmt_check_dept->execute();
            $stmt_check_dept->store_result();

            if ($stmt_check_dept->num_rows == 0) {
                // [ใหม่] ค้นหา display_order สูงสุด (ที่ไม่ใช่ 999 ของ "อื่นๆ")
                $sql_max_order = "SELECT MAX(display_order) as max_order FROM departments WHERE display_order < 999";
                $result_max_order = $conn->query($sql_max_order);
                $max_order_row = $result_max_order->fetch_assoc();
                $next_display_order = ($max_order_row['max_order'] ?? 1) + 1;

                // [แก้ไข] เพิ่ม display_order เข้าไปในคำสั่ง INSERT
                $sql_insert_dept = "INSERT INTO departments (name, display_order) VALUES (?, ?)";
                $stmt_insert_dept = $conn->prepare($sql_insert_dept);
                $stmt_insert_dept->bind_param("si", $final_department, $next_display_order);
                $stmt_insert_dept->execute();
                $stmt_insert_dept->close();
            }
            $stmt_check_dept->close();
        } else {
            $final_department = $work_department_choice;
        }

        $position = htmlspecialchars(strip_tags(trim($_POST['position'])));
        $official_id = preg_replace('/\D/', '', $_POST['official_id']);

        array_push($update_fields, "work_department = ?", "position = ?", "official_id = ?");
        array_push($params, $final_department, $position, $official_id);
        $types .= "sss";
    }

    // 5. สร้างและ Execute SQL Query
    if (!empty($update_fields)) {
        $sql_update = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $params[] = $user_id;
        $types .= "i";
        
        $stmt_update = $conn->prepare($sql_update);
        if(!$stmt_update) handle_error("SQL Prepare Error: " . $conn->error);
        
        $stmt_update->bind_param($types, ...$params);

        if ($stmt_update->execute()) {
            log_activity($conn, 'edit_profile', ['user_id' => $user_id]);
            $_SESSION['request_status'] = 'success';
            $_SESSION['request_message'] = 'แก้ไขข้อมูลส่วนตัวสำเร็จ';
        } else {
            handle_error("เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $stmt_update->error);
        }
        $stmt_update->close();
    } else {
        // กรณีไม่มีอะไรให้อัปเดตเลย
        $_SESSION['request_status'] = 'info';
        $_SESSION['request_message'] = 'ไม่มีข้อมูลที่ถูกเปลี่ยนแปลง';
    }
    
} else {
    handle_error("Invalid request method.");
}

$conn->close();
header("Location: ../../../views/user/home/profile.php");
exit();
?>

