<?php
// app/controllers/user/vehicle/add_vehicle_process.php

session_start();
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../../views/user/login/login.php");
    exit;
}

require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';
require_once '../../../../lib/phpqrcode/qrlib.php';

// --- Helper Functions ---

function handle_error($user_message, $log_message = '') {
    $_SESSION['request_status'] = 'error';
    $_SESSION['request_message'] = $user_message;
    if ($log_message) error_log($log_message);
    header("Location: ../../../views/user/home/add_vehicle.php");
    exit();
}

function processAndSaveImageVersions($file, $targetDir) {
    if ($file['error'] !== UPLOAD_ERR_OK) return ['error' => 'File upload error: ' . $file['error']];
    if ($file["size"] > 5 * 1024 * 1024) return ['error' => 'ไฟล์มีขนาดใหญ่เกิน 5 MB'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file["tmp_name"]);
    finfo_close($finfo);
    if (!in_array($mime_type, ['image/jpeg', 'image/png'])) return ['error' => 'อนุญาตเฉพาะไฟล์ JPG, PNG เท่านั้น'];
    
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $baseFileName = bin2hex(random_bytes(16));

    $source_image = ($mime_type == "image/jpeg") ? @imagecreatefromjpeg($file["tmp_name"]) : @imagecreatefrompng($file["tmp_name"]);
    if (!$source_image) return ['error' => 'ไม่สามารถประมวลผลไฟล์รูปภาพได้'];

    if ($mime_type == "image/jpeg" && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file["tmp_name"]);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3: $source_image = imagerotate($source_image, 180, 0); break;
                case 6: $source_image = imagerotate($source_image, -90, 0); break;
                case 8: $source_image = imagerotate($source_image, 90, 0); break;
            }
        }
    }
    
    $versions = [
        'normal' => ['width' => 1280, 'height' => 1280, 'suffix' => ''],
        'thumb'  => ['width' => 400, 'height' => 400, 'suffix' => '_thumb'],
    ];
    $generated_files = [];
    list($original_width, $original_height) = getimagesize($file["tmp_name"]);

    foreach ($versions as $key => $version) {
        $max_width = $version['width'];
        $max_height = $version['height'];
        $new_width = $original_width;
        $new_height = $original_height;

        if ($original_width > $max_width || $original_height > $max_height) {
            $ratio = $original_width / $original_height;
            if ($max_width / $max_height > $ratio) {
                $new_width = $max_height * $ratio;
                $new_height = $max_height;
            } else {
                $new_height = $max_width / $ratio;
                $new_width = $max_width;
            }
        }
        
        $new_image = imagecreatetruecolor(floor($new_width), floor($new_height));
        if ($mime_type == "image/png") {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
        }
        imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, floor($new_width), floor($new_height), $original_width, $original_height);
        
        $newFileName = $baseFileName . $version['suffix'] . '.' . $extension;
        $finalTargetPath = $targetDir . $newFileName;

        if ($mime_type == "image/jpeg") imagejpeg($new_image, $finalTargetPath, 80);
        else imagepng($new_image, $finalTargetPath, 7);
        
        imagedestroy($new_image);
        $generated_files[$key] = $newFileName;
    }
    imagedestroy($source_image);
    return ['filenames' => $generated_files];
}

function calculate_pickup_date() {
    $holidays = ['2025-10-13', '2025-10-23', '2025-12-05', '2025-12-10', '2025-12-31', '2026-01-01'];
    $working_days_to_add = 15;
    $current_date = new DateTime();
    while ($working_days_to_add > 0) {
        $current_date->modify('+1 day');
        $day_of_week = $current_date->format('N');
        $date_string = $current_date->format('Y-m-d');
        if ($day_of_week < 6 && !in_array($date_string, $holidays)) {
            $working_days_to_add--;
        }
    }
    return $current_date->format('Y-m-d');
}

// --- Main Logic ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../../views/user/home/add_vehicle.php");
    exit();
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) handle_error("เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล");
$conn->set_charset("utf8");

$active_period = null;
$sql_period = "SELECT id, card_expiry_date FROM application_periods WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date LIMIT 1";
$result_period = $conn->query($sql_period);
if ($result_period->num_rows > 0) {
    $active_period = $result_period->fetch_assoc();
} else {
    log_activity($conn, 'create_vehicle_request_fail', ['error' => 'Attempted to submit outside of active period']);
    handle_error("ระบบปิดรับคำร้องชั่วคราว");
}

$conn->begin_transaction();
try {
    $user_id = $_SESSION['user_id'];
    $stmt_user = $conn->prepare("SELECT user_key, user_type FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user_data = $stmt_user->get_result()->fetch_assoc();
    $stmt_user->close();
    if (!$user_data) throw new Exception("ไม่พบข้อมูลผู้ใช้");

    // --- 1. Find or Create Vehicle Record ---
    $license_plate = htmlspecialchars(strip_tags(trim($_POST['license_plate'])));
    $province = htmlspecialchars(strip_tags(trim($_POST['license_province'])));
    $vehicle_id = null;

    $stmt_find_vehicle = $conn->prepare("SELECT id FROM vehicles WHERE license_plate = ? AND province = ?");
    $stmt_find_vehicle->bind_param("ss", $license_plate, $province);
    $stmt_find_vehicle->execute();
    $result_vehicle = $stmt_find_vehicle->get_result();
    if ($row = $result_vehicle->fetch_assoc()) {
        $vehicle_id = $row['id'];
    } else {
        $stmt_insert_vehicle = $conn->prepare("INSERT INTO vehicles (user_id, license_plate, province, vehicle_type, brand, model, color) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert_vehicle->bind_param("issssss", $user_id, $license_plate, $province, $_POST['vehicle_type'], $_POST['vehicle_brand'], $_POST['vehicle_model'], $_POST['vehicle_color']);
        if (!$stmt_insert_vehicle->execute()) throw new Exception("ไม่สามารถบันทึกข้อมูลยานพาหนะได้: " . $stmt_insert_vehicle->error);
        $vehicle_id = $stmt_insert_vehicle->insert_id;
        $stmt_insert_vehicle->close();
    }
    $stmt_find_vehicle->close();

    // --- 2. Handle File Uploads ---
    $request_key = bin2hex(random_bytes(10));
    $baseUploadDir = "../../../../public/uploads/" . $user_data['user_key'] . "/vehicle/" . $request_key . "/";
    
    $photo_reg_copy = processAndSaveImageVersions($_FILES["reg_copy_upload"], $baseUploadDir);
    $photo_tax_sticker = processAndSaveImageVersions($_FILES["tax_sticker_upload"], $baseUploadDir);
    $photo_front = processAndSaveImageVersions($_FILES["front_view_upload"], $baseUploadDir);
    $photo_rear = processAndSaveImageVersions($_FILES["rear_view_upload"], $baseUploadDir);

    if (isset($photo_reg_copy['error'])) throw new Exception("อัปโหลดสำเนาทะเบียนรถไม่สำเร็จ: " . $photo_reg_copy['error']);
    if (isset($photo_tax_sticker['error'])) throw new Exception("อัปโหลดป้ายภาษีไม่สำเร็จ: " . $photo_tax_sticker['error']);
    if (isset($photo_front['error'])) throw new Exception("อัปโหลดรูปถ่ายด้านหน้าไม่สำเร็จ: " . $photo_front['error']);
    if (isset($photo_rear['error'])) throw new Exception("อัปโหลดรูปถ่ายด้านหลังไม่สำเร็จ: " . $photo_rear['error']);
    
    // --- 3. Generate Search ID ---
    $prefix = ($_POST['vehicle_type'] === 'รถยนต์') ? 'C' : 'M';
    $buddhist_year_short = substr(date('Y') + 543, -2);
    $today_md = date('md');
    $sql_count = "SELECT COUNT(*) as count FROM vehicle_requests WHERE DATE(created_at) = CURDATE()";
    $count_today = $conn->query($sql_count)->fetch_assoc()['count'];
    $next_seq = str_pad($count_today + 1, 3, '0', STR_PAD_LEFT);
    $search_id = "{$prefix}{$buddhist_year_short}{$today_md}-{$next_seq}";

    // --- 4. Generate QR Code ---
    $qr_dir = "../../../../public/qr/";
    if (!file_exists($qr_dir)) mkdir($qr_dir, 0777, true);
    $qr_file_path = $qr_dir . $request_key . '.png';
    $qr_content = "http://" . $_SERVER['HTTP_HOST'] . "/public/app/verify.php?key=" . $request_key;
    QRcode::png($qr_content, $qr_file_path, QR_ECLEVEL_L, 4, 0);

    // --- 5. Prepare data and Insert Request ---
    $tax_day = str_pad($_POST['tax_day'], 2, '0', STR_PAD_LEFT);
    $tax_month = str_pad($_POST['tax_month'], 2, '0', STR_PAD_LEFT);
    $tax_year_ad = intval($_POST['tax_year']) - 543;
    $tax_expiry_date = "$tax_year_ad-$tax_month-$tax_day";
    $owner_type = $_POST['owner_type'];
    $other_owner_name = ($owner_type === 'other') ? $_POST['other_owner_name'] : null;
    $other_owner_relation = ($owner_type === 'other') ? $_POST['other_owner_relation'] : null;
    $card_pickup_date = calculate_pickup_date();
    $card_type = ($user_data['user_type'] === 'army') ? 'internal' : 'external';

    $sql_insert_req = "INSERT INTO vehicle_requests (user_id, vehicle_id, period_id, request_key, search_id, tax_expiry_date, owner_type, other_owner_name, other_owner_relation, photo_reg_copy, photo_reg_copy_thumb, photo_tax_sticker, photo_tax_sticker_thumb, photo_front, photo_front_thumb, photo_rear, photo_rear_thumb, card_pickup_date, card_expiry, card_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert_req = $conn->prepare($sql_insert_req);
    $stmt_insert_req->bind_param("iiisssssssssssssssss", 
        $user_id, $vehicle_id, $active_period['id'], $request_key, $search_id, $tax_expiry_date, 
        $owner_type, $other_owner_name, $other_owner_relation, 
        $photo_reg_copy['filenames']['normal'], $photo_reg_copy['filenames']['thumb'],
        $photo_tax_sticker['filenames']['normal'], $photo_tax_sticker['filenames']['thumb'],
        $photo_front['filenames']['normal'], $photo_front['filenames']['thumb'],
        $photo_rear['filenames']['normal'], $photo_rear['filenames']['thumb'],
        $card_pickup_date, $active_period['card_expiry_date'], $card_type);
    
    if (!$stmt_insert_req->execute()) throw new Exception("ไม่สามารถบันทึกข้อมูลคำร้องได้: " . $stmt_insert_req->error);
    
    $new_request_id = $stmt_insert_req->insert_id;
    log_activity($conn, 'create_vehicle_request', ['request_id' => $new_request_id, 'vehicle_id' => $vehicle_id]);
    $stmt_insert_req->close();
    
    $conn->commit();
    $_SESSION['request_status'] = 'success';
    $_SESSION['request_message'] = 'ยื่นคำร้องขอเพิ่มยานพาหนะสำเร็จแล้ว';
    header("Location: ../../../views/user/home/dashboard.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    log_activity($conn, 'create_vehicle_request_fail', ['error' => $e->getMessage()]);
    handle_error($e->getMessage());
}

$conn->close();
?>

