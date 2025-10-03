<?php
// app/controllers/user/vehicle/edit_vehicle_process.php

session_start();
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../../views/user/login/login.php");
    exit;
}

require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8");

function handle_error($user_message) {
    $_SESSION['request_status'] = 'error';
    $_SESSION['request_message'] = $user_message;
    header("Location: ../../../views/user/home/dashboard.php");
    exit();
}

function processAndSaveImageVersions($file, $targetDir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return ['error' => 'No file uploaded or upload error'];
    if ($file["size"] > 5 * 1024 * 1024) return ['error' => 'ไฟล์มีขนาดใหญ่เกิน 5 MB'];
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $baseFileName = bin2hex(random_bytes(16));
    $mime_type = mime_content_type($file['tmp_name']);

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    if (!$request_id) handle_error("Request ID ไม่ถูกต้อง");

    $user_id = $_SESSION['user_id'];
    $conn->begin_transaction();
    try {
        $stmt_old_data = $conn->prepare("SELECT vr.vehicle_id, v.license_plate, v.province, vr.request_key FROM vehicle_requests vr JOIN vehicles v ON vr.vehicle_id = v.id WHERE vr.id = ? AND vr.user_id = ?");
        $stmt_old_data->bind_param("ii", $request_id, $user_id);
        $stmt_old_data->execute();
        $old_data_res = $stmt_old_data->get_result();
        if ($old_data_res->num_rows !== 1) throw new Exception("ไม่พบคำร้อง หรือคุณไม่มีสิทธิ์แก้ไข");
        $old_data = $old_data_res->fetch_assoc();
        $vehicle_id = $old_data['vehicle_id'];
        $request_key = $old_data['request_key'];
        $stmt_old_data->close();
        
        $user_key_res = $conn->query("SELECT user_key FROM users WHERE id = $user_id");
        if(!$user_key_res || $user_key_res->num_rows === 0) throw new Exception("Could not find user key.");
        $user_key = $user_key_res->fetch_assoc()['user_key'];

        // [NEW] Handle optional license plate and province update
        if (isset($_POST['can_edit_license']) && $_POST['can_edit_license'] === 'true') {
            $new_license_plate = htmlspecialchars(strip_tags(trim($_POST['license_plate'])));
            $new_province = htmlspecialchars(strip_tags(trim($_POST['license_province'])));

            if (empty($new_license_plate) || empty($new_province)) {
                throw new Exception("กรุณากรอกเลขทะเบียนและจังหวัดให้ครบถ้วน");
            }

            $stmt_check_exist = $conn->prepare("SELECT id FROM vehicles WHERE license_plate = ? AND province = ? AND id != ?");
            $stmt_check_exist->bind_param("ssi", $new_license_plate, $new_province, $vehicle_id);
            $stmt_check_exist->execute();
            if ($stmt_check_exist->get_result()->num_rows > 0) {
                throw new Exception("ทะเบียนรถยนต์นี้มีอยู่ในระบบแล้ว");
            }
            $stmt_check_exist->close();

            $stmt_update_license = $conn->prepare("UPDATE vehicles SET license_plate = ?, province = ? WHERE id = ?");
            $stmt_update_license->bind_param("ssi", $new_license_plate, $new_province, $vehicle_id);
            if (!$stmt_update_license->execute()) {
                throw new Exception("Error updating license plate: " . $stmt_update_license->error);
            }
            $stmt_update_license->close();
        }
        
        $stmt_update_vehicle = $conn->prepare("UPDATE vehicles SET brand = ?, model = ?, color = ? WHERE id = ?");
        $stmt_update_vehicle->bind_param("sssi", $_POST['vehicle_brand'], $_POST['vehicle_model'], $_POST['vehicle_color'], $vehicle_id);
        if (!$stmt_update_vehicle->execute()) throw new Exception("Error updating vehicle: " . $stmt_update_vehicle->error);
        $stmt_update_vehicle->close();

        $update_req_fields = [];
        $req_params = [];
        $req_types = "";
        
        $baseUploadDir = "../../../../public/uploads/" . $user_key . "/vehicle/" . $request_key . "/";
        $photo_fields = [
            'reg_copy_upload' => ['normal' => 'photo_reg_copy', 'thumb' => 'photo_reg_copy_thumb'],
            'tax_sticker_upload' => ['normal' => 'photo_tax_sticker', 'thumb' => 'photo_tax_sticker_thumb'],
            'front_view_upload' => ['normal' => 'photo_front', 'thumb' => 'photo_front_thumb'],
            'rear_view_upload' => ['normal' => 'photo_rear', 'thumb' => 'photo_rear_thumb']
        ];
        
        foreach($photo_fields as $input_name => $db_columns){
            if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == UPLOAD_ERR_OK) {
                $uploadResult = processAndSaveImageVersions($_FILES[$input_name], $baseUploadDir);
                if (isset($uploadResult['error'])) throw new Exception("Upload failed for $input_name: ".$uploadResult['error']);
                
                $update_req_fields[] = "{$db_columns['normal']} = ?";
                $req_params[] = $uploadResult['filenames']['normal'];
                $req_types .= "s";
                
                $update_req_fields[] = "{$db_columns['thumb']} = ?";
                $req_params[] = $uploadResult['filenames']['thumb'];
                $req_types .= "s";
            }
        }

        $tax_day = str_pad(htmlspecialchars(strip_tags(trim($_POST['tax_day']))), 2, '0', STR_PAD_LEFT);
        $tax_month = str_pad(htmlspecialchars(strip_tags(trim($_POST['tax_month']))), 2, '0', STR_PAD_LEFT);
        $tax_year_be = htmlspecialchars(strip_tags(trim($_POST['tax_year'])));
        $tax_year_ad = intval($tax_year_be) - 543;
        $tax_expiry_date = "$tax_year_ad-$tax_month-$tax_day";
        $owner_type = htmlspecialchars(strip_tags(trim($_POST['owner_type'])));
        $other_owner_name = ($owner_type === 'other') ? htmlspecialchars(strip_tags(trim($_POST['other_owner_name']))) : null;
        $other_owner_relation = ($owner_type === 'other') ? htmlspecialchars(strip_tags(trim($_POST['other_owner_relation']))) : null;

        array_push($update_req_fields, "tax_expiry_date = ?", "owner_type = ?", "other_owner_name = ?", "other_owner_relation = ?", "status = 'pending'", "edit_status = 1", "rejection_reason = NULL");
        array_push($req_params, $tax_expiry_date, $owner_type, $other_owner_name, $other_owner_relation);
        $req_types .= "ssss";
        
        $req_params[] = $request_id;
        $req_types .= "i";
        
        $sql_update_req = "UPDATE vehicle_requests SET " . implode(", ", $update_req_fields) . " WHERE id = ?";
        $stmt_update_req = $conn->prepare($sql_update_req);
        $stmt_update_req->bind_param($req_types, ...$req_params);
        if (!$stmt_update_req->execute()) throw new Exception("Error updating request: " . $stmt_update_req->error);
        $stmt_update_req->close();

        log_activity($conn, 'edit_vehicle_request', ['request_id' => $request_id, 'vehicle_id' => $vehicle_id]);
        $conn->commit();
        $_SESSION['request_status'] = 'success';
        $_SESSION['request_message'] = 'แก้ไขและส่งคำร้องใหม่สำเร็จแล้ว';

    } catch (Exception $e) {
        $conn->rollback();
        log_activity($conn, 'edit_vehicle_request_fail', ['request_id' => $request_id, 'error' => $e->getMessage()]);
        handle_error($e->getMessage());
    }
}

$conn->close();
header("Location: ../../../views/user/home/dashboard.php");
exit();
?>

