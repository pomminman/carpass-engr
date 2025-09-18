<?php
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

function uploadAndCompressImage($file, $targetDir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return ['error' => 'No file uploaded or upload error'];
    $max_file_size = 5 * 1024 * 1024;
    if ($file["size"] > $max_file_size) return ['error' => 'ไฟล์มีขนาดใหญ่เกิน 5 MB'];
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;
    $finalTargetPath = $targetDir . $newFileName;
    
    // Simple move file, compression logic can be added here if needed
    if(move_uploaded_file($file['tmp_name'], $finalTargetPath)){
         return ['filename' => $newFileName];
    } else {
        return ['error' => 'Failed to move uploaded file.'];
    }
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
        
        // Update vehicles table - ONLY editable fields
        $stmt_update_vehicle = $conn->prepare("UPDATE vehicles SET brand = ?, model = ?, color = ? WHERE id = ?");
        $stmt_update_vehicle->bind_param("sssi", $_POST['vehicle_brand'], $_POST['vehicle_model'], $_POST['vehicle_color'], $vehicle_id);
        if (!$stmt_update_vehicle->execute()) throw new Exception("Error updating vehicle: " . $stmt_update_vehicle->error);
        $stmt_update_vehicle->close();

        // Update vehicle_requests table
        $update_req_fields = [];
        $req_params = [];
        $req_types = "";
        
        $baseUploadDir = "../../../../public/uploads/" . $user_key . "/vehicle/" . $request_key . "/";
        $photo_fields = ['reg_copy_upload' => 'photo_reg_copy', 'tax_sticker_upload' => 'photo_tax_sticker', 'front_view_upload' => 'photo_front', 'rear_view_upload' => 'photo_rear'];
        foreach($photo_fields as $input_name => $db_column){
            if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == UPLOAD_ERR_OK) {
                $uploadResult = uploadAndCompressImage($_FILES[$input_name], $baseUploadDir);
                if (isset($uploadResult['error'])) throw new Exception("Upload failed for $input_name: ".$uploadResult['error']);
                $update_req_fields[] = "$db_column = ?";
                $req_params[] = $uploadResult['filename'];
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
        // [เพิ่ม] บันทึก Log เมื่อเกิดข้อผิดพลาด
        log_activity($conn, 'edit_vehicle_request_fail', ['request_id' => $request_id, 'error' => $e->getMessage()]);
        handle_error($e->getMessage());
    }
}

$conn->close();
header("Location: ../../../views/user/home/dashboard.php");
exit();
?>
