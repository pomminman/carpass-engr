<?php
// controllers/user/vehicle/edit_vehicle_process.php

session_start();
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../../views/user/login/login.php");
    exit;
}

require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    handle_error("เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// ฟังก์ชันสำหรับจัดการข้อผิดพลาดและ Redirect
function handle_error($user_message) {
    $_SESSION['request_status'] = 'error';
    $_SESSION['request_message'] = $user_message;
    header("Location: ../../../views/user/home/dashboard.php");
    exit();
}

// ฟังก์ชันคำนวณวันรับบัตร
function calculate_pickup_date($start_date) {
    $holidays = ['2025-01-01', '2025-02-12', '2025-04-07', '2025-04-14', '2025-04-15', '2025-04-16', '2025-05-01', '2025-05-05', '2025-05-12', '2025-06-03', '2025-07-11', '2025-07-28', '2025-08-12', '2025-10-13', '2025-10-23', '2025-12-05', '2025-12-10', '2025-12-31', '2026-01-01', '2026-03-02', '2026-04-06', '2026-04-13', '2026-04-14', '2026-04-15', '2026-05-01', '2026-05-04', '2026-06-01', '2026-06-03', '2026-07-28', '2026-07-29', '2026-08-12', '2026-10-13', '2026-10-23', '2026-12-07', '2026-12-10', '2026-12-31'];
    $working_days_count = 0;
    $current_date = clone $start_date;
    while ($working_days_count < 15) {
        $current_date->modify('+1 day');
        $day_of_week = $current_date->format('N');
        $date_string = $current_date->format('Y-m-d');
        if ($day_of_week < 6 && !in_array($date_string, $holidays)) $working_days_count++;
    }
    return $current_date->format('Y-m-d');
}

// ฟังก์ชันสำหรับจัดการการอัปโหลดไฟล์
function uploadAndCompressImage($file, $targetDir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return ['error' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์: ' . $file['error']];
    $max_file_size = 5 * 1024 * 1024;
    if ($file["size"] > $max_file_size) return ['error' => 'ไฟล์มีขนาดใหญ่เกิน 5 MB'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file["tmp_name"]);
    $allowed_mime_types = ['image/jpeg', 'image/png'];
    if (!in_array($mime_type, $allowed_mime_types)) { finfo_close($finfo); return ['error' => 'อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG) เท่านั้น']; }
    finfo_close($finfo);
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
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
        if($image) imagejpeg($image, $finalTargetPath, $quality);
    } elseif ($mime_type == "image/png") {
        $image = imagecreatefrompng($file["tmp_name"]);
        if($image) imagepng($image, $finalTargetPath, 7);
    }
    if ($image) imagedestroy($image);
    else return ['error' => 'ไม่สามารถประมวลผลไฟล์รูปภาพได้'];
    return ['filename' => $newFileName];
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    if (!$request_id) handle_error("รหัสคำร้องไม่ถูกต้อง");

    // [แก้ไข] ดึง user_key และ request_key จาก POST แทนการ query
    $user_key = $_POST['user_key'] ?? null;
    $request_key = $_POST['request_key'] ?? null;

    if (!$user_key || !$request_key) {
        handle_error("ข้อมูลไม่ครบถ้วนสำหรับการแก้ไข");
    }

    // ตรวจสอบสิทธิ์ความเป็นเจ้าของคำร้อง
    $sql_owner_check = "SELECT user_id FROM vehicle_requests WHERE id = ?";
    $stmt_owner_check = $conn->prepare($sql_owner_check);
    $stmt_owner_check->bind_param("i", $request_id);
    $stmt_owner_check->execute();
    $result_owner_check = $stmt_owner_check->get_result();
    if ($result_owner_check->num_rows === 0) {
        handle_error("ไม่พบคำร้องดังกล่าว");
    }
    $owner = $result_owner_check->fetch_assoc();
    if ($owner['user_id'] != $_SESSION['user_id']) {
        handle_error("คุณไม่มีสิทธิ์แก้ไขคำร้องนี้");
    }
    $stmt_owner_check->close();

    $license_plate = htmlspecialchars(strip_tags(trim($_POST['license_plate'] ?? '')));
    $province = htmlspecialchars(strip_tags(trim($_POST['license_province'] ?? '')));
    $sql_check = "SELECT id FROM vehicle_requests WHERE license_plate = ? AND province = ? AND id != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ssi", $license_plate, $province, $request_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) { $stmt_check->close(); handle_error("ทะเบียนรถ " . $license_plate . " จังหวัด " . $province . " มีข้อมูลอยู่ในระบบแล้ว"); }
    $stmt_check->close();
    
    // กำหนด Path สำหรับอัปโหลดไฟล์
    $baseUploadDir = "../../../../public/uploads/" . $user_key . "/vehicle/" . $request_key . "/";

    $update_fields = [];
    $params = [];
    $types = "";

    $photo_fields = [
        'reg_copy_upload' => 'photo_reg_copy',
        'tax_sticker_upload' => 'photo_tax_sticker',
        'front_view_upload' => 'photo_front',
        'rear_view_upload' => 'photo_rear'
    ];

    foreach ($photo_fields as $file_input_name => $db_field) {
        if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == UPLOAD_ERR_OK) {
            $uploadResult = uploadAndCompressImage($_FILES[$file_input_name], $baseUploadDir);
            if (isset($uploadResult['error'])) handle_error("อัปโหลดรูปภาพไม่สำเร็จ: " . $uploadResult['error']);
            $update_fields[] = $db_field . " = ?";
            // [แก้ไข] บันทึกเฉพาะชื่อไฟล์ลง DB
            $params[] = $uploadResult['filename'];
            $types .= "s";
        }
    }

    $new_vehicle_type = htmlspecialchars(strip_tags(trim($_POST['vehicle_type'])));
    $sql_get_current = "SELECT vehicle_type, search_id FROM vehicle_requests WHERE id = ?";
    $stmt_get_current = $conn->prepare($sql_get_current);
    $stmt_get_current->bind_param("i", $request_id);
    $stmt_get_current->execute();
    $current_data = $stmt_get_current->get_result()->fetch_assoc();
    $stmt_get_current->close();

    if ($new_vehicle_type !== $current_data['vehicle_type']) {
        $new_prefix = ($new_vehicle_type === 'รถยนต์') ? 'C' : 'M';
        $id_suffix = substr($current_data['search_id'], 1); 
        $new_search_id = "{$new_prefix}{$id_suffix}";
        $update_fields[] = "search_id = ?";
        $params[] = $new_search_id;
        $types .= "s";
    }
    
    $brand = htmlspecialchars(strip_tags(trim($_POST['vehicle_brand'])));
    $model = htmlspecialchars(strip_tags(trim($_POST['vehicle_model'])));
    $color = htmlspecialchars(strip_tags(trim($_POST['vehicle_color'])));
    $tax_day = htmlspecialchars(strip_tags(trim($_POST['tax_day'])));
    $tax_month = htmlspecialchars(strip_tags(trim($_POST['tax_month'])));
    $tax_year_be = htmlspecialchars(strip_tags(trim($_POST['tax_year'])));
    $tax_year_ad = $tax_year_be ? intval($tax_year_be) - 543 : '';
    $tax_expiry_date = "{$tax_year_ad}-{$tax_month}-{$tax_day}";
    $owner_type = htmlspecialchars(strip_tags(trim($_POST['owner_type'])));
    $other_owner_name = ($owner_type === 'other') ? htmlspecialchars(strip_tags(trim($_POST['other_owner_name']))) : null;
    $other_owner_relation = ($owner_type === 'other') ? htmlspecialchars(strip_tags(trim($_POST['other_owner_relation']))) : null;
    $card_pickup_date = calculate_pickup_date(new DateTime()); 

    array_push($update_fields, "vehicle_type = ?", "brand = ?", "model = ?", "color = ?", "license_plate = ?", "province = ?", "tax_expiry_date = ?", "owner_type = ?", "other_owner_name = ?", "other_owner_relation = ?", "card_pickup_date = ?");
    array_push($params, $new_vehicle_type, $brand, $model, $color, $license_plate, $province, $tax_expiry_date, $owner_type, $other_owner_name, $other_owner_relation, $card_pickup_date);
    $types .= "sssssssssss";

    $update_fields[] = "status = 'pending'";
    $update_fields[] = "edit_status = 1";

    if (empty($update_fields)) {
        $_SESSION['request_status'] = 'info';
        $_SESSION['request_message'] = 'ไม่มีข้อมูลที่ถูกเปลี่ยนแปลง';
        header("Location: ../../../views/user/home/dashboard.php");
        exit();
    }

    $sql_update = "UPDATE vehicle_requests SET " . implode(", ", $update_fields) . " WHERE id = ?";
    $params[] = $request_id;
    $types .= "i";

    $stmt_update = $conn->prepare($sql_update);
    if ($stmt_update) {
        $stmt_update->bind_param($types, ...$params);
        if ($stmt_update->execute()) {
            log_activity($conn, 'edit_vehicle_request', ['request_id' => $request_id]);
            $_SESSION['request_status'] = 'success';
            $_SESSION['request_message'] = 'แก้ไขข้อมูลคำร้องสำเร็จแล้ว';
        } else {
            handle_error("เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $stmt_update->error);
        }
        $stmt_update->close();
    } else {
        handle_error("เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
    }
    
} else {
    handle_error("Invalid request method.");
}

$conn->close();
header("Location: ../../../views/user/home/dashboard.php");
exit();
?>

