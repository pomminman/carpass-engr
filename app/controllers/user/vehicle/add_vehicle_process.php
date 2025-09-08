<?php
// --- add_vehicle_process.php ---

session_start();

// [แก้ไข] ตั้งค่าโซนเวลาให้เป็นของกรุงเทพฯ เพื่อให้แน่ใจว่าวันที่ถูกต้องเสมอ
date_default_timezone_set('Asia/Bangkok');

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือยัง
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../../views/user/login/login.php");
    exit;
}

require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

// --- 1. การตั้งค่าการเชื่อมต่อฐานข้อมูล ---
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    handle_error("เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล", "Database Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// --- 2. ฟังก์ชันจัดการข้อผิดพลาด ---
function handle_error($user_message, $log_message = '') {
    $_SESSION['request_status'] = 'error';
    $_SESSION['request_message'] = $user_message;
    // error_log($log_message); // Should be enabled in production
    header("Location: ../../../views/user/home/home.php");
    exit();
}

// --- 3. [แก้ไข] ฟังก์ชันสำหรับจัดการการอัปโหลดไฟล์ เพิ่มการตรวจสอบและแก้ไขการหมุนภาพอัตโนมัติ ---
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
        
        // --- ส่วนที่เพิ่มเข้ามา: แก้ไขการหมุนภาพจากข้อมูล EXIF ---
        if ($image && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file["tmp_name"]);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $image = imagerotate($image, 180, 0);
                        break;
                    case 6:
                        $image = imagerotate($image, -90, 0);
                        break;
                    case 8:
                        $image = imagerotate($image, 90, 0);
                        break;
                }
            }
        }
        // --- สิ้นสุดส่วนที่เพิ่ม ---
        
        if($image) {
            imagejpeg($image, $finalTargetPath, $quality);
        }

    } elseif ($mime_type == "image/png") {
        $image = imagecreatefrompng($file["tmp_name"]);
        if($image) {
            imagepng($image, $finalTargetPath, 7); // Quality for PNG is 0-9
        }
    }

    if ($image) {
        imagedestroy($image);
    } else {
        return ['error' => 'ไม่สามารถประมวลผลไฟล์รูปภาพได้'];
    }

    return ['filename' => $newFileName];
}

// --- [เพิ่ม] ฟังก์ชันคำนวณวันรับบัตร (15 วันทำการ) ---
function calculate_pickup_date($start_date) {
    $holidays = [
        // --- ปี 2568 ---
        '2025-01-01', // วันขึ้นปีใหม่
        '2025-02-12', // วันมาฆบูชา
        '2025-04-07', // วันหยุดชดเชยวันจักรี
        '2025-04-14', // วันสงกรานต์
        '2025-04-15', // วันสงกรานต์
        '2025-04-16', // วันหยุดชดเชยวันสงกรานต์
        '2025-05-01', // วันแรงงานแห่งชาติ
        '2025-05-05', // วันหยุดชดเชยวันฉัตรมงคล
        '2025-05-12', // วันหยุดชดเชยวันวิสาขบูชา
        '2025-06-03', // วันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าฯ พระบรมราชินี
        '2025-07-11', // วันอาสาฬหบูชา
        '2025-07-28', // วันเฉลิมพระชนมพรรษาพระบาทสมเด็จพระวชิรเกล้าเจ้าอยู่หัว
        '2025-08-12', // วันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าสิริกิติ์ พระบรมราชินีนาถ พระบรมราชชนนีพันปีหลวง
        '2025-10-13', // วันคล้ายวันสวรรคต พระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช บรมนาถบพิตร
        '2025-10-23', // วันปิยมหาราช
        '2025-12-05', // วันคล้ายวันพระบรมราชสมภพ พระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช บรมนาถบพิตร
        '2025-12-10', // วันรัฐธรรมนูญ
        '2025-12-31', // วันสิ้นปี

        // --- ปี 2569 ---
        '2026-01-01', // วันขึ้นปีใหม่
        '2026-03-02', // วันมาฆบูชา
        '2026-04-06', // วันจักรี
        '2026-04-13', // วันสงกรานต์
        '2026-04-14', // วันสงกรานต์
        '2026-04-15', // วันสงกรานต์
        '2026-05-01', // วันแรงงานแห่งชาติ
        '2026-05-04', // วันฉัตรมงคล
        '2026-06-01', // วันหยุดชดเชยวันวิสาขบูชา
        '2026-06-03', // วันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าฯ พระบรมราชินี
        '2026-07-28', // วันเฉลิมพระชนมพรรษาพระบาทสมเด็จพระวชิรเกล้าเจ้าอยู่หัว
        '2026-07-29', // วันอาสาฬหบูชา
        '2026-08-12', // วันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าสิริกิติ์ พระบรมราชินีนาถ พระบรมราชชนนีพันปีหลวง
        '2026-10-13', // วันคล้ายวันสวรรคต พระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช บรมนาถบพิตร
        '2026-10-23', // วันปิยมหาราช
        '2026-12-07', // วันหยุดชดเชยวันคล้ายวันพระบรมราชสมภพของรัชกาลที่ 9
        '2026-12-10', // วันรัฐธรรมนูญ
        '2026-12-31', // วันสิ้นปี

        // --- ปี 2570 ---
        '2027-01-01', // วันขึ้นปีใหม่
        '2027-02-19', // วันมาฆบูชา
        '2027-04-06', // วันจักรี
        '2027-04-13', // วันสงกรานต์
        '2027-04-14', // วันสงกรานต์
        '2027-04-15', // วันสงกรานต์
        '2027-05-03', // วันหยุดชดเชยวันแรงงานแห่งชาติ
        '2027-05-04', // วันฉัตรมงคล
        '2027-05-19', // วันวิสาขบูชา
        '2027-06-03', // วันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าฯ พระบรมราชินี
        '2027-07-19', // วันหยุดชดเชยวันอาสาฬหบูชา
        '2027-07-28', // วันเฉลิมพระชนมพรรษาพระบาทสมเด็จพระวชิรเกล้าเจ้าอยู่หัว
        '2027-08-12', // วันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าสิริกิติ์ พระบรมราชินีนาถ พระบรมราชชนนีพันปีหลวง
        '2027-10-13', // วันคล้ายวันสวรรคต พระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช บรมนาถบพิตร
        '2027-10-25', // วันหยุดชดเชยวันปิยมหาราช
        '2027-12-06', // วันหยุดชดเชยวันคล้ายวันพระบรมราชสมภพของรัชกาลที่ 9
        '2027-12-10', // วันรัฐธรรมนูญ
        '2027-12-31', // วันสิ้นปี

        // --- ปี 2571 ---
        '2028-01-03', // วันหยุดชดเชยวันขึ้นปีใหม่
        '2028-02-08', // วันมาฆบูชา
        '2028-04-06', // วันจักรี
        '2028-04-13', // วันสงกรานต์
        '2028-04-14', // วันสงกรานต์
        '2028-04-17', // วันหยุดชดเชยวันสงกรานต์
        '2028-05-01', // วันแรงงานแห่งชาติ
        '2028-05-04', // วันฉัตรมงคล
        '2028-05-08', // วันหยุดชดเชยวันวิสาขบูชา
        '2028-06-05', // วันหยุดชดเชยวันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าฯ พระบรมราชินี
        '2028-07-06', // วันอาสาฬหบูชา
        '2028-07-28', // วันเฉลิมพระชนมพรรษาพระบาทสมเด็จพระวชิรเกล้าเจ้าอยู่หัว
        '2028-08-14', // วันหยุดชดเชยวันเฉลิมพระชนมพรรษาสมเด็จพระนางเจ้าสิริกิติ์ พระบรมราชินีนาถ พระบรมราชชนนีพันปีหลวง
        '2028-10-13', // วันคล้ายวันสวรรคต พระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช บรมนาถบพิตร
        '2028-10-23', // วันปิยมหาราช
        '2028-12-05', // วันคล้ายวันพระบรมราชสมภพ พระบาทสมเด็จพระบรมชนกาธิเบศร มหาภูมิพลอดุลยเดชมหาราช บรมนาถบพิตร
        '2028-12-11', // วันหยุดชดเชยวันรัฐธรรมนูญ
    ];
    
    $working_days_count = 0;
    $current_date = clone $start_date;

    while ($working_days_count < 15) {
        $current_date->modify('+1 day');
        $day_of_week = $current_date->format('N'); // 1 (for Monday) through 7 (for Sunday)
        $date_string = $current_date->format('Y-m-d');

        // Check if it's a weekday and not a holiday
        if ($day_of_week < 6 && !in_array($date_string, $holidays)) {
            $working_days_count++;
        }
    }
    return $current_date->format('Y-m-d');
}

// --- 4. ตรวจสอบและประมวลผลข้อมูล ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- จัดการการอัปโหลดไฟล์ ---
    $regCopyPath = "../../../../public/uploads/vehicle/registration/";
    $taxStickerPath = "../../../../public/uploads/vehicle/tax_sticker/";
    $frontViewPath = "../../../../public/uploads/vehicle/front_view/";
    $rearViewPath = "../../../../public/uploads/vehicle/rear_view/";

    $regCopyResult = uploadAndCompressImage($_FILES["reg_copy_upload"], $regCopyPath);
    $taxStickerResult = uploadAndCompressImage($_FILES["tax_sticker_upload"], $taxStickerPath);
    $frontViewResult = uploadAndCompressImage($_FILES["front_view_upload"], $frontViewPath);
    $rearViewResult = uploadAndCompressImage($_FILES["rear_view_upload"], $rearViewPath);

    if (isset($regCopyResult['error'])) handle_error("อัปโหลดสำเนาทะเบียนรถไม่สำเร็จ: " . $regCopyResult['error']);
    if (isset($taxStickerResult['error'])) handle_error("อัปโหลดป้ายภาษีไม่สำเร็จ: " . $taxStickerResult['error']);
    if (isset($frontViewResult['error'])) handle_error("อัปโหลดรูปถ่ายด้านหน้าไม่สำเร็จ: " . $frontViewResult['error']);
    if (isset($rearViewResult['error'])) handle_error("อัปโหลดรูปถ่ายด้านหลังไม่สำเร็จ: " . $rearViewResult['error']);

    // --- กรองและเตรียมข้อมูล ---
    $user_id = $_SESSION['user_id'];
    $request_key = bin2hex(random_bytes(10));
    $vehicle_type = htmlspecialchars(strip_tags(trim($_POST['vehicle_type'] ?? '')));
    $brand = htmlspecialchars(strip_tags(trim($_POST['vehicle_brand'] ?? '')));
    $model = htmlspecialchars(strip_tags(trim($_POST['vehicle_model'] ?? '')));
    $color = htmlspecialchars(strip_tags(trim($_POST['vehicle_color'] ?? '')));
    $license_plate = htmlspecialchars(strip_tags(trim($_POST['license_plate'] ?? '')));
    $province = htmlspecialchars(strip_tags(trim($_POST['license_province'] ?? '')));
    
    $tax_day = htmlspecialchars(strip_tags(trim($_POST['tax_day'] ?? '')));
    $tax_month = htmlspecialchars(strip_tags(trim($_POST['tax_month'] ?? '')));
    $tax_year_be = htmlspecialchars(strip_tags(trim($_POST['tax_year'] ?? '')));
    $tax_year_ad = $tax_year_be ? intval($tax_year_be) - 543 : '';
    $tax_expiry_date = ($tax_year_ad && $tax_month && $tax_day && checkdate($tax_month, $tax_day, $tax_year_ad)) 
        ? "{$tax_year_ad}-{$tax_month}-{$tax_day}" 
        : null;

    if (!$tax_expiry_date) {
        handle_error("วันที่สิ้นสุดภาษีไม่ถูกต้อง");
    }

    $owner_type = htmlspecialchars(strip_tags(trim($_POST['owner_type'] ?? '')));
    $other_owner_name = ($owner_type === 'other') ? htmlspecialchars(strip_tags(trim($_POST['other_owner_name'] ?? ''))) : null;
    $other_owner_relation = ($owner_type === 'other') ? htmlspecialchars(strip_tags(trim($_POST['other_owner_relation'] ?? ''))) : null;

    $photo_reg_copy = $regCopyResult['filename'];
    $photo_tax_sticker = $taxStickerResult['filename'];
    $photo_front = $frontViewResult['filename'];
    $photo_rear = $rearViewResult['filename'];
    
    // --- [เพิ่ม] คำนวณวันรับบัตร ---
    $card_pickup_date = calculate_pickup_date(new DateTime());

    // --- [แก้ไข] สร้าง Search ID ตามลำดับ ---
    $prefix = ($vehicle_type === 'รถยนต์') ? 'C' : 'M';
    
    // [แก้ไข] เปลี่ยนการดึงวันที่เป็นปี พ.ศ. เพื่อให้รหัสคำร้องถูกต้อง
    $buddhist_year_full = date('Y') + 543;
    $buddhist_year_short = substr($buddhist_year_full, -2);
    $today_md = date('md');
    $today_ymd = $buddhist_year_short . $today_md; // YYMMDD format from Buddhist year (พ.ศ.)

    // นับจำนวนคำร้อง *ทั้งหมด* ที่สร้างในวันนี้ เพื่อหาลำดับถัดไป
    $sql_count = "SELECT COUNT(*) as count FROM vehicle_requests WHERE DATE(created_at) = CURDATE()";
    $result_count = $conn->query($sql_count);
    $count_today = 0;
    if ($result_count) {
        $count_today = $result_count->fetch_assoc()['count'];
    }

    // สร้างลำดับถัดไป (เช่น 001, 002)
    $next_seq = str_pad($count_today + 1, 3, '0', STR_PAD_LEFT);
    
    $search_id = "{$prefix}{$today_ymd}-{$next_seq}";

    // --- [เพิ่ม] ตรวจสอบข้อมูลซ้ำซ้อนฝั่ง Server ---
    $sql_check = "SELECT id FROM vehicle_requests WHERE license_plate = ? AND province = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $license_plate, $province);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $stmt_check->close();
        handle_error("ทะเบียนรถ " . $license_plate . " จังหวัด " . $province . " มีข้อมูลอยู่ในระบบแล้ว");
    }
    $stmt_check->close();
    // --- สิ้นสุดการตรวจสอบ ---

    // --- สร้าง SQL INSERT Statement ---
    $sql = "INSERT INTO vehicle_requests (user_id, request_key, search_id, vehicle_type, brand, model, color, license_plate, province, tax_expiry_date, owner_type, other_owner_name, other_owner_relation, photo_reg_copy, photo_tax_sticker, photo_front, photo_rear, card_pickup_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "isssssssssssssssss",
        $user_id, $request_key, $search_id, $vehicle_type, $brand, $model, $color, $license_plate, $province,
        $tax_expiry_date, $owner_type, $other_owner_name, $other_owner_relation,
        $photo_reg_copy, $photo_tax_sticker, $photo_front, $photo_rear, $card_pickup_date
    );

    if ($stmt->execute()) {
        $new_request_id = $stmt->insert_id; // ดึง ID ของคำร้องที่เพิ่งสร้าง

        // --- [เพิ่ม] บันทึก Log การสร้างคำร้องสำเร็จ ---
        log_activity($conn, 'create_vehicle_request', [
            'request_id' => $new_request_id,
            'license_plate' => $license_plate
        ]);

        $_SESSION['request_status'] = 'success';
        $_SESSION['request_message'] = 'ยื่นคำร้องขอเพิ่มยานพาหนะสำเร็จแล้ว';
        header("Location: ../../../views/user/home/home.php#overview-section");
        exit();
    } else {
        handle_error("เกิดข้อผิดพลาดในการบันทึกข้อมูล", "SQL Execute Error: " . $stmt->error);
    }
    $stmt->close();

} else {
    header("Location: ../../../views/user/home/home.php");
    exit();
}

$conn->close();
?>

