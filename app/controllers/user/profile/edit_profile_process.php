<?php
// controllers/user/profile/edit_profile_process.php

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
    die("Connection Error: " . $conn->connect_error);
}
$conn->set_charset("utf8");

function handle_error($user_message) {
    $_SESSION['request_status'] = 'error';
    $_SESSION['request_message'] = $user_message;
    header("Location: ../../../views/user/home/profile.php");
    exit();
}

function process_profile_image_upload($file, $targetDir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์: ' . $file['error']];
    }
    $max_file_size = 5 * 1024 * 1024;
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
        'normal' => ['width' => 1024, 'height' => 1024, 'suffix' => ''],
        'thumb'  => ['width' => 256, 'height' => 256, 'suffix' => '_thumb'], // [แก้ไข] เพิ่มขนาด Thumbnail
    ];
    $generated_files = [];
    list($original_width, $original_height) = getimagesize($file["tmp_name"]);

    foreach ($versions as $key => $version) {
        $max_width = $version['width'];
        $max_height = $version['height'];

        $new_width = $original_width;
        $new_height = $original_height;

        // ย่อขนาดเฉพาะเมื่อรูปใหญ่กว่าที่กำหนด (สำหรับ 'normal') หรือย่อเสมอ (สำหรับ 'thumb')
        if ($key === 'thumb' || ($original_width > $max_width || $original_height > $max_height)) {
            $ratio = $original_width / $original_height;
            if ($max_width / $max_height > $ratio) {
                $new_width = $max_height * $ratio;
                $new_height = $max_height;
            } else {
                $new_height = $max_width / $ratio;
                $new_width = $max_width;
            }
        }
        
        $new_width = floor($new_width);
        $new_height = floor($new_height);

        $new_image = imagecreatetruecolor($new_width, $new_height);

        if ($mime_type == "image/png") {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }

        imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);

        $newFileName = $baseFileName . $version['suffix'] . '.' . $extension;
        $finalTargetPath = $targetDir . $newFileName;

        if ($mime_type == "image/jpeg") {
            imagejpeg($new_image, $finalTargetPath, 85);
        } else {
            imagepng($new_image, $finalTargetPath, 7);
        }
        imagedestroy($new_image);
        $generated_files[$key] = $newFileName;
    }
    imagedestroy($source_image);
    return ['filenames' => $generated_files];
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user_id = $_SESSION['user_id'];
    $update_fields = [];
    $params = [];
    $types = "";

    $stmt_uinfo = $conn->prepare("SELECT user_type, user_key FROM users WHERE id = ?");
    $stmt_uinfo->bind_param("i", $user_id);
    $stmt_uinfo->execute();
    $result_uinfo = $stmt_uinfo->get_result();
    $user_info = $result_uinfo->fetch_assoc();
    if(!$user_info) handle_error("ไม่พบข้อมูลผู้ใช้");
    $stmt_uinfo->close();
    
    $user_type = $user_info['user_type'];
    $user_key = $user_info['user_key'];

    if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "../../../../public/uploads/{$user_key}/profile/";
        $uploadResult = process_profile_image_upload($_FILES['photo_upload'], $targetDir);

        if (isset($uploadResult['error'])) {
            handle_error("อัปโหลดรูปภาพโปรไฟล์ไม่สำเร็จ: " . $uploadResult['error']);
        }
        $update_fields[] = "photo_profile = ?";
        $params[] = $uploadResult['filenames']['normal'];
        $types .= "s";

        $update_fields[] = "photo_profile_thumb = ?";
        $params[] = $uploadResult['filenames']['thumb'];
        $types .= "s";
    }

    $title_choice = htmlspecialchars(strip_tags(trim($_POST['title'])));
    $final_title = ($title_choice === 'other') ? htmlspecialchars(strip_tags(trim($_POST['title_other']))) : $title_choice;

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

    if ($user_type === 'army') {
        $position = htmlspecialchars(strip_tags(trim($_POST['position'])));
        $official_id = preg_replace('/\D/', '', $_POST['official_id']);
        array_push($update_fields, "position = ?", "official_id = ?");
        array_push($params, $position, $official_id);
        $types .= "ss";
    }

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

