<?php
// --- add_vehicle_process.php ---

session_start();
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../../views/user/login/login.php");
    exit;
}

require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    handle_error("เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล", "Database Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

$active_period = null;
$sql_period = "SELECT id, card_expiry_date FROM application_periods WHERE is_active = 1 AND CURDATE() BETWEEN start_date AND end_date LIMIT 1";
$result_period = $conn->query($sql_period);
if ($result_period->num_rows > 0) {
    $active_period = $result_period->fetch_assoc();
} else {
    handle_error("ระบบปิดรับคำร้องชั่วคราว", "No active application period found.");
}
$period_id = $active_period['id'];
$card_expiry_date = $active_period['card_expiry_date'];

function handle_error($user_message, $log_message = '') {
    $_SESSION['request_status'] = 'error';
    $_SESSION['request_message'] = $user_message;
    header("Location: ../../../views/user/home/add_vehicle.php");
    exit();
}

function uploadAndCompressImage($file, $targetDir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return ['error' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์: ' . $file['error']];
    $max_file_size = 5 * 1024 * 1024;
    if ($file["size"] > $max_file_size) return ['error' => 'ไฟล์มีขนาดใหญ่เกิน 5 MB'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file["tmp_name"]);
    $allowed_mime_types = ['image/jpeg', 'image/png'];
    if (!in_array($mime_type, $allowed_mime_types)) {
        finfo_close($finfo);
        return ['error' => 'อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG) เท่านั้น'];
    }
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $stmt_key = $conn->prepare("SELECT user_key FROM users WHERE id = ?");
    $stmt_key->bind_param("i", $user_id);
    $stmt_key->execute();
    $user_key = $stmt_key->get_result()->fetch_assoc()['user_key'];
    $stmt_key->close();
    if (empty($user_key)) handle_error("ไม่พบข้อมูลผู้ใช้");

    $conn->begin_transaction();
    try {
        $license_plate = htmlspecialchars(strip_tags(trim($_POST['license_plate'])));
        $province = htmlspecialchars(strip_tags(trim($_POST['license_province'])));

        $stmt_check_vehicle = $conn->prepare("SELECT id FROM vehicles WHERE license_plate = ? AND province = ?");
        $stmt_check_vehicle->bind_param("ss", $license_plate, $province);
        $stmt_check_vehicle->execute();
        $result_vehicle = $stmt_check_vehicle->get_result();
        $vehicle_id = null;

        if ($result_vehicle->num_rows > 0) {
            $vehicle_id = $result_vehicle->fetch_assoc()['id'];
            $stmt_check_req = $conn->prepare("SELECT id FROM vehicle_requests WHERE vehicle_id = ? AND period_id = ? AND status IN ('pending', 'approved')");
            $stmt_check_req->bind_param("ii", $vehicle_id, $period_id);
            $stmt_check_req->execute();
            if ($stmt_check_req->get_result()->num_rows > 0) {
                throw new Exception("ยานพาหนะนี้ได้ยื่นคำร้องสำหรับรอบปัจจุบันไปแล้ว");
            }
            $stmt_check_req->close();
        } else {
            $vehicle_type = htmlspecialchars(strip_tags(trim($_POST['vehicle_type'])));
            $brand = htmlspecialchars(strip_tags(trim($_POST['vehicle_brand'])));
            $model = htmlspecialchars(strip_tags(trim($_POST['vehicle_model'])));
            $color = htmlspecialchars(strip_tags(trim($_POST['vehicle_color'])));
            $sql_insert_vehicle = "INSERT INTO vehicles (user_id, license_plate, province, vehicle_type, brand, model, color) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert_vehicle = $conn->prepare($sql_insert_vehicle);
            $stmt_insert_vehicle->bind_param("issssss", $user_id, $license_plate, $province, $vehicle_type, $brand, $model, $color);
            if (!$stmt_insert_vehicle->execute()) throw new Exception("ไม่สามารถบันทึกข้อมูลยานพาหนะได้: " . $stmt_insert_vehicle->error);
            $vehicle_id = $stmt_insert_vehicle->insert_id;
            $stmt_insert_vehicle->close();
        }
        $stmt_check_vehicle->close();

        $request_key = bin2hex(random_bytes(10));
        $baseUploadDir = "../../../../public/uploads/" . $user_key . "/vehicle/" . $request_key . "/";
        $qr_dir = "../../../../public/qr/";

        $regCopyResult = uploadAndCompressImage($_FILES["reg_copy_upload"], $baseUploadDir);
        $taxStickerResult = uploadAndCompressImage($_FILES["tax_sticker_upload"], $baseUploadDir);
        $frontViewResult = uploadAndCompressImage($_FILES["front_view_upload"], $baseUploadDir);
        $rearViewResult = uploadAndCompressImage($_FILES["rear_view_upload"], $baseUploadDir);

        if (isset($regCopyResult['error'])) throw new Exception("อัปโหลดสำเนาทะเบียนรถไม่สำเร็จ: " . $regCopyResult['error']);
        if (isset($taxStickerResult['error'])) throw new Exception("อัปโหลดป้ายภาษีไม่สำเร็จ: " . $taxStickerResult['error']);
        if (isset($frontViewResult['error'])) throw new Exception("อัปโหลดรูปถ่ายด้านหน้าไม่สำเร็จ: " . $frontViewResult['error']);
        if (isset($rearViewResult['error'])) throw new Exception("อัปโหลดรูปถ่ายด้านหลังไม่สำเร็จ: " . $rearViewResult['error']);
        
        $tax_day = htmlspecialchars(strip_tags(trim($_POST['tax_day'])));
        $tax_month = htmlspecialchars(strip_tags(trim($_POST['tax_month'])));
        $tax_year_be = htmlspecialchars(strip_tags(trim($_POST['tax_year'])));
        $tax_year_ad = intval($tax_year_be) - 543;
        $tax_expiry_date = "$tax_year_ad-$tax_month-$tax_day";
        $owner_type = htmlspecialchars(strip_tags(trim($_POST['owner_type'])));
        $other_owner_name = ($owner_type === 'other') ? htmlspecialchars(strip_tags(trim($_POST['other_owner_name']))) : null;
        $other_owner_relation = ($owner_type === 'other') ? htmlspecialchars(strip_tags(trim($_POST['other_owner_relation']))) : null;
        $photo_reg_copy = $regCopyResult['filename'];
        $photo_tax_sticker = $taxStickerResult['filename'];
        $photo_front = $frontViewResult['filename'];
        $photo_rear = $rearViewResult['filename'];
        $card_pickup_date = calculate_pickup_date(new DateTime());

        $vehicle_type_for_search = $conn->query("SELECT vehicle_type FROM vehicles WHERE id = $vehicle_id")->fetch_assoc()['vehicle_type'];
        $prefix = ($vehicle_type_for_search === 'รถยนต์') ? 'C' : 'M';
        $buddhist_year_short = substr(date('Y') + 543, -2);
        $today_md = date('md');
        $today_ymd = $buddhist_year_short . $today_md;
        $sql_count = "SELECT COUNT(*) as count FROM vehicle_requests WHERE DATE(created_at) = CURDATE()";
        $count_today = $conn->query($sql_count)->fetch_assoc()['count'];
        $next_seq = str_pad($count_today + 1, 3, '0', STR_PAD_LEFT);
        $search_id = "{$prefix}{$today_ymd}-{$next_seq}";
        
        require_once '../../../../lib/phpqrcode/qrlib.php'; 
        $qr_content = "http://" . $_SERVER['HTTP_HOST'] . "/public/app/verify.php?key=" . $request_key;
        if (!file_exists($qr_dir)) mkdir($qr_dir, 0777, true);
        $qr_file_path = $qr_dir . $request_key . '.png';
        QRcode::png($qr_content, $qr_file_path, QR_ECLEVEL_L, 4, 0);

        $sql_insert_req = "INSERT INTO vehicle_requests (user_id, vehicle_id, period_id, request_key, search_id, tax_expiry_date, owner_type, other_owner_name, other_owner_relation, photo_reg_copy, photo_tax_sticker, photo_front, photo_rear, card_pickup_date, card_expiry) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_req = $conn->prepare($sql_insert_req);
        $stmt_insert_req->bind_param("iiissssssssssss", $user_id, $vehicle_id, $period_id, $request_key, $search_id, $tax_expiry_date, $owner_type, $other_owner_name, $other_owner_relation, $photo_reg_copy, $photo_tax_sticker, $photo_front, $photo_rear, $card_pickup_date, $card_expiry_date);
        
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
        handle_error($e->getMessage());
    }
}

$conn->close();
header("Location: ../../../views/user/home/add_vehicle.php");
exit();
?>

