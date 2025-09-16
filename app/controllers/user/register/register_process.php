<?php
// controllers/user/register/register_process.php

session_start();
date_default_timezone_set('Asia/Bangkok');

require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

function handle_error($user_message, $log_message = '') {
    error_log("Register Error: " . ($log_message ?: $user_message));
    // For production, you might want a more user-friendly error page
    die("เกิดข้อผิดพลาดในการสมัคร: " . htmlspecialchars($user_message));
}

function process_profile_image_upload($file, $targetDir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์: ' . $file['error']];
    }
    $max_file_size = 5 * 1024 * 1024; // 5 MB
    if ($file["size"] > $max_file_size) {
        return ['error' => 'ไฟล์มีขนาดใหญ่เกิน 5 MB'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file["tmp_name"]);
    finfo_close($finfo);
    $allowed_mime_types = ['image/jpeg', 'image/png'];
    if (!in_array($mime_type, $allowed_mime_types)) {
        return ['error' => 'อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG) เท่านั้น'];
    }
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $baseFileName = bin2hex(random_bytes(16));
    
    $source_image = null;
    if ($mime_type == "image/jpeg") {
        $source_image = @imagecreatefromjpeg($file["tmp_name"]);
        if ($source_image && function_exists('exif_read_data')) {
            $exif = @exif_read_data($file["tmp_name"]);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3: $source_image = imagerotate($source_image, 180, 0); break;
                    case 6: $source_image = imagerotate($source_image, -90, 0); break;
                    case 8: $source_image = imagerotate($source_image, 90, 0); break;
                }
            }
        }
    } elseif ($mime_type == "image/png") {
        $source_image = @imagecreatefrompng($file["tmp_name"]);
    }
    if (!$source_image) return ['error' => 'ไม่สามารถประมวลผลไฟล์รูปภาพได้'];

    $versions = [
        'normal' => ['width' => 300, 'height' => 300, 'suffix' => ''],
        'thumb'  => ['width' => 64, 'height' => 64, 'suffix' => '_thumb'],
    ];
    $generated_files = [];
    list($original_width, $original_height) = getimagesize($file["tmp_name"]);

    foreach ($versions as $key => $version) {
        $target_width = $version['width'];
        $target_height = $version['height'];
        $newFileName = $baseFileName . $version['suffix'] . '.' . $extension;
        $finalTargetPath = $targetDir . $newFileName;
        $thumb = imagecreatetruecolor($target_width, $target_height);

        if ($mime_type == "image/png") {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
            imagefilledrectangle($thumb, 0, 0, $target_width, $target_height, $transparent);
        }

        $original_aspect = $original_width / $original_height;
        $target_aspect = $target_width / $target_height;
        if ($original_aspect >= $target_aspect) {
           $new_height = $target_height;
           $new_width = $original_width / ($original_height / $target_height);
           $crop_x = ($new_width - $target_width) / 2;
           $crop_y = 0;
        } else {
           $new_width = $target_width;
           $new_height = $original_height / ($original_width / $target_width);
           $crop_x = 0;
           $crop_y = ($new_height - $target_height) / 2;
        }

        imagecopyresampled($thumb, $source_image, -$crop_x, -$crop_y, 0, 0, $new_width, $new_height, $original_width, $original_height);

        if ($mime_type == "image/jpeg") {
            imagejpeg($thumb, $finalTargetPath, 75);
        } else {
            imagepng($thumb, $finalTargetPath, 7);
        }
        imagedestroy($thumb);
        $generated_files[$key] = $newFileName;
    }
    imagedestroy($source_image);
    return ['filenames' => $generated_files];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        handle_error('CSRF token ไม่ถูกต้อง');
    }
    unset($_SESSION['csrf_token']);

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        handle_error("เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล", "DB Connection Error: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    $user_key = bin2hex(random_bytes(10));
    $targetDir = "../../../../public/uploads/{$user_key}/profile/";
    $photoUploadResult = process_profile_image_upload($_FILES["photo_upload"], $targetDir);

    if (isset($photoUploadResult['error'])) {
        handle_error("อัปโหลดรูปโปรไฟล์ไม่สำเร็จ: " . $photoUploadResult['error']);
    }
    
    $photo_profile_filename = $photoUploadResult['filenames']['normal'];
    $photo_profile_thumb_filename = $photoUploadResult['filenames']['thumb'];

    $user_type = htmlspecialchars(strip_tags(trim($_POST['user_type'] ?? '')));
    $phone_number = preg_replace('/\D/', '', $_POST['form_phone'] ?? '');
    $national_id = preg_replace('/\D/', '', $_POST['personal_id'] ?? '');

    $sql_check = "SELECT id FROM users WHERE phone_number = ? OR national_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ss", $phone_number, $national_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
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
        ? "{$dob_year_ad}-{$dob_month}-{$dob_day}" : null;

    if (!$dob) handle_error("วันเดือนปีเกิดไม่ถูกต้อง");

    $gender = htmlspecialchars(strip_tags(trim($_POST['gender'] ?? '')));
    $address = htmlspecialchars(strip_tags(trim($_POST['address'] ?? '')));
    $subdistrict = htmlspecialchars(strip_tags(trim($_POST['subdistrict'] ?? '')));
    $district = htmlspecialchars(strip_tags(trim($_POST['district'] ?? '')));
    $province = htmlspecialchars(strip_tags(trim($_POST['province'] ?? '')));
    $zipcode = htmlspecialchars(strip_tags(trim($_POST['zipcode'] ?? '')));

    $work_department = null;
    $position = null;
    $official_id = null;
    if ($user_type === 'army') {
        $work_department_choice = htmlspecialchars(strip_tags(trim($_POST['work_department'] ?? '')));
        $work_department_other = htmlspecialchars(strip_tags(trim($_POST['work_department_other'] ?? '')));

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
                $sql_max_order = "SELECT MAX(display_order) as max_order FROM departments";
                $result_max_order = $conn->query($sql_max_order);
                $max_order_row = $result_max_order->fetch_assoc();
                $next_display_order = ($max_order_row['max_order'] ?? 0) + 1;

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

    $sql = "INSERT INTO users (user_key, user_type, phone_number, national_id, title, firstname, lastname, dob, gender, address, subdistrict, district, province, zipcode, photo_profile, photo_profile_thumb, work_department, position, official_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssssssssss",
        $user_key, $user_type, $phone_number, $national_id, $title, $firstname, $lastname, $dob, $gender,
        $address, $subdistrict, $district, $province, $zipcode, 
        $photo_profile_filename, $photo_profile_thumb_filename,
        $work_department, $position, $official_id
    );

    if ($stmt->execute()) {
        $new_user_id = $stmt->insert_id;
        log_activity($conn, 'register_success', ['user_id' => $new_user_id, 'phone' => $phone_number]);
        header("Location: ../../../views/user/login/login.php?status=success");
        exit();
    } else {
        log_activity($conn, 'register_fail', ['error' => $stmt->error]);
        handle_error("เกิดข้อผิดพลาดในการบันทึกข้อมูล", "SQL Error: " . $stmt->error);
    }
    
    $stmt->close();
    $conn->close();
} else {
    header("Location: ../../../views/user/register/register.php");
    exit();
}
?>

