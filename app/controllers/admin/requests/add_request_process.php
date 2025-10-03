<?php
// app/controllers/admin/requests/add_request_process.php

session_start();
date_default_timezone_set('Asia/Bangkok');

// --- Security & Initialization ---
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("Location: ../../../views/admin/login/login.php");
    exit;
}

require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';
require_once '../../../../lib/phpqrcode/qrlib.php';

// --- Helper Functions ---

/**
 * Handles errors by setting a session flash message and redirecting.
 * @param string $user_message The message to show to the user.
 * @param string $log_message The message to log for debugging.
 * @param int|null $user_id The user ID to redirect back to if applicable.
 */
function handle_error($user_message, $log_message = '', $user_id = null) {
    $_SESSION['flash_message'] = $user_message;
    $_SESSION['flash_status'] = 'error';
    if ($log_message) {
        error_log($log_message);
    }
    $redirect_url = $user_id ? "../../../views/admin/home/add_request.php?user_id=" . $user_id : "../../../views/admin/home/manage_requests.php";
    header("Location: " . $redirect_url);
    exit();
}

/**
 * Processes image uploads: validation, compression, and saving.
 * @param array $file The $_FILES['input_name'] array.
 * @param string $targetDir The destination directory.
 * @return array An array with either a 'filename' on success or 'error' on failure.
 */
function uploadAndCompressImage($file, $targetDir) {
    // This check is now redundant but kept for safety within the function
    if ($file['error'] !== UPLOAD_ERR_OK) return ['error' => 'File upload error: ' . $file['error']];
    if ($file["size"] > 5 * 1024 * 1024) return ['error' => 'ไฟล์มีขนาดใหญ่เกิน 5 MB'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file["tmp_name"]);
    finfo_close($finfo);
    if (!in_array($mime_type, ['image/jpeg', 'image/png'])) return ['error' => 'อนุญาตเฉพาะไฟล์ JPG, PNG เท่านั้น'];
    
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;
    $finalTargetPath = $targetDir . $newFileName;
    $quality = 75;

    $image = ($mime_type == "image/jpeg") ? @imagecreatefromjpeg($file["tmp_name"]) : @imagecreatefrompng($file["tmp_name"]);
    if (!$image) return ['error' => 'ไม่สามารถประมวลผลไฟล์รูปภาพได้'];

    // Auto-rotate based on EXIF data for JPEGs
    if ($mime_type == "image/jpeg" && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file["tmp_name"]);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3: $image = imagerotate($image, 180, 0); break;
                case 6: $image = imagerotate($image, -90, 0); break;
                case 8: $image = imagerotate($image, 90, 0); break;
            }
        }
    }
    
    if ($mime_type == "image/jpeg") {
        imagejpeg($image, $finalTargetPath, $quality);
    } else {
        imagepng($image, $finalTargetPath, 7); // Compression level for PNG
    }
    imagedestroy($image);
    return ['filename' => $newFileName];
}

/**
 * Calculates the estimated pickup date for the card.
 * @return string The calculated date in 'Y-m-d' format.
 */
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
    header("Location: ../../../views/admin/home/manage_requests.php");
    exit();
}

$user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
if (!$user_id) {
    handle_error("ข้อมูลผู้ใช้ไม่ถูกต้อง");
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    handle_error("เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล", "DB Connection Error: " . $conn->connect_error, $user_id);
}
$conn->set_charset("utf8");

$active_period = null;
$sql_period = "SELECT id, card_expiry_date FROM application_periods WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date LIMIT 1";
$result_period = $conn->query($sql_period);
if ($result_period->num_rows > 0) {
    $active_period = $result_period->fetch_assoc();
} else {
    log_activity($conn, 'admin_create_request_fail', ['error' => 'No active period']);
    handle_error("ระบบปิดรับคำร้องชั่วคราว ไม่สามารถสร้างคำร้องได้", null, $user_id);
}

$conn->begin_transaction();
try {
    $admin_id = $_SESSION['admin_id'];

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
        $stmt_insert_vehicle = $conn->prepare("INSERT INTO vehicles (user_id, license_plate, province, vehicle_type, brand, model, color, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_insert_vehicle->bind_param("issssssi", $user_id, $license_plate, $province, $_POST['vehicle_type'], $_POST['vehicle_brand'], $_POST['vehicle_model'], $_POST['vehicle_color'], $admin_id);
        if (!$stmt_insert_vehicle->execute()) throw new Exception("ไม่สามารถบันทึกข้อมูลยานพาหนะได้: " . $stmt_insert_vehicle->error);
        $vehicle_id = $stmt_insert_vehicle->insert_id;
        $stmt_insert_vehicle->close();
    }
    $stmt_find_vehicle->close();

    // --- 2. Handle File Uploads (Conditionally) ---
    $request_key = bin2hex(random_bytes(10));
    $baseUploadDir = "../../../../public/uploads/" . $user_data['user_key'] . "/vehicle/" . $request_key . "/";
    
    $photo_reg_copy_filename = null;
    $photo_tax_sticker_filename = null;
    $photo_front_filename = null;
    $photo_rear_filename = null;

    if (isset($_FILES["reg_copy_upload"]) && $_FILES["reg_copy_upload"]['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadAndCompressImage($_FILES["reg_copy_upload"], $baseUploadDir);
        if (isset($uploadResult['error'])) throw new Exception("อัปโหลดสำเนาทะเบียนรถไม่สำเร็จ: " . $uploadResult['error']);
        $photo_reg_copy_filename = $uploadResult['filename'];
    }
    if (isset($_FILES["tax_sticker_upload"]) && $_FILES["tax_sticker_upload"]['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadAndCompressImage($_FILES["tax_sticker_upload"], $baseUploadDir);
        if (isset($uploadResult['error'])) throw new Exception("อัปโหลดป้ายภาษีไม่สำเร็จ: " . $uploadResult['error']);
        $photo_tax_sticker_filename = $uploadResult['filename'];
    }
    if (isset($_FILES["front_view_upload"]) && $_FILES["front_view_upload"]['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadAndCompressImage($_FILES["front_view_upload"], $baseUploadDir);
        if (isset($uploadResult['error'])) throw new Exception("อัปโหลดรูปถ่ายด้านหน้าไม่สำเร็จ: " . $uploadResult['error']);
        $photo_front_filename = $uploadResult['filename'];
    }
    if (isset($_FILES["rear_view_upload"]) && $_FILES["rear_view_upload"]['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadAndCompressImage($_FILES["rear_view_upload"], $baseUploadDir);
        if (isset($uploadResult['error'])) throw new Exception("อัปโหลดรูปถ่ายด้านหลังไม่สำเร็จ: " . $uploadResult['error']);
        $photo_rear_filename = $uploadResult['filename'];
    }
    
    // --- 3. Generate Search ID ---
    $prefix = ($_POST['vehicle_type'] === 'รถยนต์') ? 'C' : 'M';
    $buddhist_year_short = substr(date('Y') + 543, -2);
    $today_md = date('md');
    $sql_count = "SELECT COUNT(*) as count FROM vehicle_requests WHERE DATE(created_at) = CURDATE()";
    $count_today = $conn->query($sql_count)->fetch_assoc()['count'];
    $next_seq = str_pad($count_today + 1, 3, '0', STR_PAD_LEFT);
    $search_id = "{$prefix}{$buddhist_year_short}{$today_md}-{$next_seq}";

    // --- 4. Prepare data and Insert Request ---
    $tax_expiry_date = null;
    if (!empty($_POST['tax_day']) && !empty($_POST['tax_month']) && !empty($_POST['tax_year'])) {
        $tax_day = str_pad($_POST['tax_day'], 2, '0', STR_PAD_LEFT);
        $tax_month = str_pad($_POST['tax_month'], 2, '0', STR_PAD_LEFT);
        $tax_year_ad = intval($_POST['tax_year']) - 543;
        $tax_expiry_date = "$tax_year_ad-$tax_month-$tax_day";
    }
    
    $owner_type = !empty($_POST['owner_type']) ? $_POST['owner_type'] : null;
    $other_owner_name = ($owner_type === 'other') ? htmlspecialchars(strip_tags(trim($_POST['other_owner_name']))) : null;
    $other_owner_relation = ($owner_type === 'other') ? htmlspecialchars(strip_tags(trim($_POST['other_owner_relation']))) : null;
    $card_pickup_date = calculate_pickup_date();
    $card_type = ($user_data['user_type'] === 'army') ? 'internal' : 'external';

    $sql_insert_req = "INSERT INTO vehicle_requests (user_id, vehicle_id, period_id, request_key, search_id, tax_expiry_date, owner_type, other_owner_name, other_owner_relation, photo_reg_copy, photo_tax_sticker, photo_front, photo_rear, card_pickup_date, card_expiry, card_type, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert_req = $conn->prepare($sql_insert_req);
    $stmt_insert_req->bind_param("iiisssssssssssssi", 
        $user_id, $vehicle_id, $active_period['id'], $request_key, $search_id, $tax_expiry_date, 
        $owner_type, $other_owner_name, $other_owner_relation, 
        $photo_reg_copy_filename, $photo_tax_sticker_filename, $photo_front_filename, $photo_rear_filename, 
        $card_pickup_date, $active_period['card_expiry_date'], $card_type, $admin_id);
    
    if (!$stmt_insert_req->execute()) {
        throw new Exception("ไม่สามารถบันทึกข้อมูลคำร้องได้: " . $stmt_insert_req->error);
    }
    
    $new_request_id = $stmt_insert_req->insert_id;
    log_activity($conn, 'admin_create_request', ['request_id' => $new_request_id, 'for_user_id' => $user_id]);
    $stmt_insert_req->close();
    
    // --- 5. Commit and Redirect ---
    $conn->commit();
    $_SESSION['flash_message'] = 'สร้างคำร้องใหม่สำเร็จแล้ว';
    $_SESSION['flash_status'] = 'success';
    header("Location: ../../../views/admin/home/manage_requests.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    log_activity($conn, 'admin_create_request_fail', ['for_user_id' => $user_id, 'error' => $e->getMessage()]);
    handle_error($e->getMessage(), "Transaction Error: " . $e->getMessage(), $user_id);
}

$conn->close();
?>
