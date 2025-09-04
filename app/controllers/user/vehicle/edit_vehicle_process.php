<?php
// controllers/user/vehicle/edit_vehicle_process.php

session_start();

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
    header("Location: ../../../views/user/home/home.php");
    exit();
}

// [เพิ่ม] ฟังก์ชันคำนวณวันรับบัตร โดยข้ามวันหยุดราชการ
function calculate_pickup_date($start_date) {
    // กำหนดวันหยุดนักขัตฤกษ์ (พ.ศ. 2568 - 2571)
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

// [แก้ไข] ฟังก์ชันสำหรับจัดการการอัปโหลดไฟล์ เพิ่มการตรวจสอบและแก้ไขการหมุนภาพอัตโนมัติ
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


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับและกรองข้อมูลจากฟอร์ม
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    if (!$request_id) {
        handle_error("รหัสคำร้องไม่ถูกต้อง");
    }

    // ตรวจสอบว่าผู้ใช้เป็นเจ้าของคำร้องหรือไม่
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

    // กรองข้อมูลอื่น ๆ
    $new_vehicle_type = htmlspecialchars(strip_tags(trim($_POST['vehicle_type'])));
    $license_plate = htmlspecialchars(strip_tags(trim($_POST['license_plate'] ?? '')));
    $province = htmlspecialchars(strip_tags(trim($_POST['license_province'] ?? '')));

    // ตรวจสอบข้อมูลซ้ำซ้อน (ยกเว้น ID ปัจจุบัน)
    $sql_check = "SELECT id FROM vehicle_requests WHERE license_plate = ? AND province = ? AND id != ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ssi", $license_plate, $province, $request_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $stmt_check->close();
        handle_error("ทะเบียนรถ " . $license_plate . " จังหวัด " . $province . " มีข้อมูลอยู่ในระบบแล้ว");
    }
    $stmt_check->close();
    
    // จัดการการอัปโหลดไฟล์ (ถ้ามี)
    $update_fields = [];
    $params = [];
    $types = "";

    // --- [เพิ่ม] ตรรกะสร้าง search_id ใหม่หากมีการเปลี่ยนประเภทรถ ---
    $sql_get_current = "SELECT vehicle_type, search_id FROM vehicle_requests WHERE id = ?";
    $stmt_get_current = $conn->prepare($sql_get_current);
    $stmt_get_current->bind_param("i", $request_id);
    $stmt_get_current->execute();
    $result_current = $stmt_get_current->get_result();
    $current_data = $result_current->fetch_assoc();
    $current_vehicle_type = $current_data['vehicle_type'];
    $current_search_id = $current_data['search_id'];
    $stmt_get_current->close();

    if ($new_vehicle_type !== $current_vehicle_type) {
        $new_prefix = ($new_vehicle_type === 'รถยนต์') ? 'C' : 'M';
        $date_part = substr($current_search_id, 1, 6); // ดึง yymmdd จากรหัสเดิม

        // นับจำนวนคำร้องของประเภทใหม่ในวันเดิม
        $sql_count = "SELECT COUNT(*) as count FROM vehicle_requests WHERE search_id LIKE ?";
        $stmt_count = $conn->prepare($sql_count);
        $search_pattern = "{$new_prefix}{$date_part}-%";
        $stmt_count->bind_param("s", $search_pattern);
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $count_on_date = $result_count->fetch_assoc()['count'];
        $stmt_count->close();
        
        $next_seq = str_pad($count_on_date + 1, 3, '0', STR_PAD_LEFT);
        $new_search_id = "{$new_prefix}{$date_part}-{$next_seq}";
        
        $update_fields[] = "search_id = ?";
        $params[] = $new_search_id;
        $types .= "s";
    }
    // --- สิ้นสุดตรรกะสร้าง search_id ใหม่ ---

    $photo_fields = [
        'reg_copy_upload' => ['path' => "../../../../public/uploads/vehicle/registration/", 'db_field' => 'photo_reg_copy'],
        'tax_sticker_upload' => ['path' => "../../../../public/uploads/vehicle/tax_sticker/", 'db_field' => 'photo_tax_sticker'],
        'front_view_upload' => ['path' => "../../../../public/uploads/vehicle/front_view/", 'db_field' => 'photo_front'],
        'rear_view_upload' => ['path' => "../../../../public/uploads/vehicle/rear_view/", 'db_field' => 'photo_rear']
    ];

    foreach ($photo_fields as $file_input_name => $info) {
        if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == UPLOAD_ERR_OK) {
            $uploadResult = uploadAndCompressImage($_FILES[$file_input_name], $info['path']);
            if (isset($uploadResult['error'])) {
                handle_error("อัปโหลดรูปภาพไม่สำเร็จ: " . $uploadResult['error']);
            }
            $update_fields[] = $info['db_field'] . " = ?";
            $params[] = $uploadResult['filename'];
            $types .= "s";
        }
    }

    // เตรียมข้อมูลอื่น ๆ สำหรับการอัปเดต
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
    
    // [แก้ไข] สร้าง DateTime object ก่อนส่งเข้าฟังก์ชัน
    $card_pickup_date = calculate_pickup_date(new DateTime()); 

    // เพิ่ม fields อื่นๆ ลงใน query
    array_push($update_fields, "vehicle_type = ?", "brand = ?", "model = ?", "color = ?", "license_plate = ?", "province = ?", "tax_expiry_date = ?", "owner_type = ?", "other_owner_name = ?", "other_owner_relation = ?", "card_pickup_date = ?");
    array_push($params, $new_vehicle_type, $brand, $model, $color, $license_plate, $province, $tax_expiry_date, $owner_type, $other_owner_name, $other_owner_relation, $card_pickup_date);
    $types .= "sssssssssss";

    // รีเซ็ตสถานะเป็น 'pending' หลังแก้ไข
    $update_fields[] = "status = 'pending'";
    $update_fields[] = "edit_status = 1"; // ตั้งค่าสถานะการแก้ไข

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
header("Location: ../../../views/user/home/home.php#overview-section");
exit();
?>

