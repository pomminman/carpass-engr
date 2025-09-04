<?php
// controllers/user/register/register_process.php

session_start();
require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

// --- 1. ฟังก์ชันจัดการข้อผิดพลาด ---
function handle_error($user_message, $log_message = '') {
    // ในสถานการณ์จริง ควร redirect กลับไปหน้า register พร้อมแสดงข้อความ error
    // แต่เพื่อความเรียบง่าย จะแสดงข้อความและหยุดการทำงานที่นี่
    // error_log($log_message); // ควรเปิดใช้งานใน production environment
    die("เกิดข้อผิดพลาดในการสมัคร: " . htmlspecialchars($user_message));
}

// --- 2. ฟังก์ชันสำหรับจัดการการอัปโหลดไฟล์ เพิ่มการตรวจสอบและแก้ไขการหมุนภาพอัตโนมัติ ---
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


// --- 3. ตรวจสอบและประมวลผลข้อมูล ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ตรวจสอบ CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        handle_error('CSRF token ไม่ถูกต้อง');
    }
    unset($_SESSION['csrf_token']); // ใช้ Token ได้ครั้งเดียว

    // เชื่อมต่อฐานข้อมูล
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        handle_error("เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล", "Database Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    // --- จัดการการอัปโหลดไฟล์รูปโปรไฟล์ ---
    $photoUploadResult = uploadAndCompressImage($_FILES["photo_upload"], "../../../../public/uploads/user_photos/");
    if (isset($photoUploadResult['error'])) {
        handle_error("อัปโหลดรูปโปรไฟล์ไม่สำเร็จ: " . $photoUploadResult['error']);
    }
    $photo_profile_filename = $photoUploadResult['filename'];

    // --- กรองและเตรียมข้อมูล ---
    $user_key = bin2hex(random_bytes(10));
    $user_type = htmlspecialchars(strip_tags(trim($_POST['user_type'] ?? '')));
    
    $phone_number = preg_replace('/\D/', '', $_POST['form_phone'] ?? '');
    $national_id = preg_replace('/\D/', '', $_POST['personal_id'] ?? '');

    // ตรวจสอบข้อมูลซ้ำฝั่ง Server
    $sql_check = "SELECT id FROM users WHERE phone_number = ? OR national_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $phone_number, $national_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $stmt_check->close();
        handle_error("เบอร์โทรศัพท์หรือเลขบัตรประชาชนนี้มีอยู่ในระบบแล้ว");
    }
    $stmt_check->close();

    $title = htmlspecialchars(strip_tags(trim($_POST['title'] ?? '')));
    if ($title === 'other') {
        $title = htmlspecialchars(strip_tags(trim($_POST['title_other'] ?? '')));
    }
    $firstname = htmlspecialchars(strip_tags(trim($_POST['firstname'] ?? '')));
    $lastname = htmlspecialchars(strip_tags(trim($_POST['lastname'] ?? '')));

    $dob_day = htmlspecialchars(strip_tags(trim($_POST['dob_day'] ?? '')));
    $dob_month = htmlspecialchars(strip_tags(trim($_POST['dob_month'] ?? '')));
    $dob_year_be = htmlspecialchars(strip_tags(trim($_POST['dob_year'] ?? '')));
    $dob_year_ad = $dob_year_be ? intval($dob_year_be) - 543 : '';
    $dob = ($dob_year_ad && $dob_month && $dob_day && checkdate($dob_month, $dob_day, $dob_year_ad)) 
        ? "{$dob_year_ad}-{$dob_month}-{$dob_day}" 
        : null;

    if (!$dob) {
        handle_error("วันเดือนปีเกิดไม่ถูกต้อง");
    }

    $gender = htmlspecialchars(strip_tags(trim($_POST['gender'] ?? '')));
    $address = htmlspecialchars(strip_tags(trim($_POST['address'] ?? '')));
    $subdistrict = htmlspecialchars(strip_tags(trim($_POST['subdistrict'] ?? '')));
    $district = htmlspecialchars(strip_tags(trim($_POST['district'] ?? '')));
    $province = htmlspecialchars(strip_tags(trim($_POST['province'] ?? '')));
    $zipcode = htmlspecialchars(strip_tags(trim($_POST['zipcode'] ?? '')));

    // ข้อมูลการทำงาน (สำหรับ 'army' เท่านั้น)
    $work_department = null;
    $position = null;
    $official_id = null;
    if ($user_type === 'army') {
        // --- [ใหม่] ตรรกะจัดการสังกัด ---
        $work_department_choice = htmlspecialchars(strip_tags(trim($_POST['work_department'])));
        $work_department_other = htmlspecialchars(strip_tags(trim($_POST['work_department_other'])));

        if ($work_department_choice === 'other') {
            if (empty($work_department_other)) {
                handle_error("กรุณาระบุชื่อสังกัดใหม่");
            }
            $work_department = $work_department_other;
            
            $sql_check_dept = "SELECT id FROM departments WHERE name = ?";
            $stmt_check_dept = $conn->prepare($sql_check_dept);
            $stmt_check_dept->bind_param("s", $work_department);
            $stmt_check_dept->execute();
            $stmt_check_dept->store_result();

            if ($stmt_check_dept->num_rows == 0) {
                // ค้นหา display_order สูงสุด (ที่ไม่ใช่ 999 ของ "อื่นๆ")
                $sql_max_order = "SELECT MAX(display_order) as max_order FROM departments WHERE display_order < 999";
                $result_max_order = $conn->query($sql_max_order);
                $max_order_row = $result_max_order->fetch_assoc();
                $next_display_order = ($max_order_row['max_order'] ?? 0) + 1;

                // เพิ่มสังกัดใหม่พร้อม display_order
                $sql_insert_dept = "INSERT INTO departments (name, display_order) VALUES (?, ?)";
                $stmt_insert_dept = $conn->prepare($sql_insert_dept);
                $stmt_insert_dept->bind_param("si", $work_department, $next_display_order);
                $stmt_insert_dept->execute();
                $stmt_insert_dept->close();
            }
            $stmt_check_dept->close();
        } else {
            $work_department = $work_department_choice;
        }

        $position = htmlspecialchars(strip_tags(trim($_POST['position'] ?? '')));
        $official_id = preg_replace('/\D/', '', $_POST['official_id'] ?? '');
    }

    // --- สร้าง SQL INSERT Statement ---
    $sql = "INSERT INTO users (user_key, user_type, phone_number, national_id, title, firstname, lastname, dob, gender, address, subdistrict, district, province, zipcode, photo_profile, work_department, position, official_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssssssss",
        $user_key, $user_type, $phone_number, $national_id, $title, $firstname, $lastname, $dob, $gender,
        $address, $subdistrict, $district, $province, $zipcode, $photo_profile_filename,
        $work_department, $position, $official_id
    );

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;

        // บันทึก Log การสมัครสำเร็จ
        log_activity($conn, 'register_success', [
            'user_id' => $new_user_id,
            'phone' => $phone_number
        ]);

        // Redirect ไปยังหน้า login พร้อมสถานะสำเร็จ
        header("Location: ../../../views/user/login/login.php?status=success");
        exit();

    } else {
        // บันทึก Log การสมัครล้มเหลว
        log_activity($conn, 'register_fail', ['error' => $stmt->error]);
        handle_error("เกิดข้อผิดพลาดในการบันทึกข้อมูล", "SQL Execute Error: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();

} else {
    // Redirect หากไม่ได้เข้ามาด้วยวิธี POST
    header("Location: ../../../views/user/register/register.php");
    exit();
}
?>
