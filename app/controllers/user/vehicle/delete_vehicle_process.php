<?php
// app/controllers/user/vehicle/delete_vehicle_process.php

session_start();
date_default_timezone_set('Asia/Bangkok');

// Check if user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: ../../../views/user/login/login.php");
    exit;
}

require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

// Helper function to recursively delete a directory
function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . DIRECTORY_SEPARATOR . $object)) {
                    rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                } else {
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        rmdir($dir);
    }
}

function handle_error($user_message, $log_message = '') {
    $_SESSION['request_status'] = 'error';
    $_SESSION['request_message'] = $user_message;
    if (!empty($log_message)) {
        error_log($log_message);
    }
    header("Location: ../../../views/user/home/dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$request_id) {
        handle_error("รหัสคำร้องไม่ถูกต้อง");
    }

    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        handle_error("เกิดข้อผิดพลาดในการเชื่อมต่อกับฐานข้อมูล", "DB Connection Error: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");

    $conn->begin_transaction();
    try {
        // Fetch request details to verify ownership, status, and get keys for folder deletion
        $sql_select = "SELECT vr.user_id, vr.vehicle_id, u.user_key, vr.request_key, vr.status 
                       FROM vehicle_requests vr
                       JOIN users u ON vr.user_id = u.id
                       WHERE vr.id = ?";
        $stmt_select = $conn->prepare($sql_select);
        $stmt_select->bind_param("i", $request_id);
        $stmt_select->execute();
        $result = $stmt_select->get_result();

        if ($result->num_rows !== 1) {
            throw new Exception("ไม่พบคำร้องที่ต้องการลบ");
        }

        $request_data = $result->fetch_assoc();
        $stmt_select->close();
        $vehicle_id_to_check = $request_data['vehicle_id'];

        // Security Check: Verify the user owns this request
        if ($request_data['user_id'] != $user_id) {
            throw new Exception("คุณไม่มีสิทธิ์ลบคำร้องนี้");
        }

        // Business Logic Check: Only allow deletion of 'pending' or 'rejected' requests
        if (!in_array($request_data['status'], ['pending', 'rejected'])) {
            throw new Exception("ไม่สามารถลบคำร้องที่อยู่นอกเหนือสถานะ 'รออนุมัติ' หรือ 'ไม่ผ่าน' ได้");
        }

        // Proceed with deletion
        // 1. Delete the folder and QR code
        $user_key = $request_data['user_key'];
        $request_key = $request_data['request_key'];
        $folder_path = "../../../../public/uploads/{$user_key}/vehicle/{$request_key}";
        $qr_code_path = "../../../../public/qr/{$request_key}.png";
        
        if (is_dir($folder_path)) {
            rrmdir($folder_path);
        }
        if (file_exists($qr_code_path)) {
            unlink($qr_code_path);
        }

        // 2. Delete the database record from vehicle_requests
        $sql_delete_req = "DELETE FROM vehicle_requests WHERE id = ?";
        $stmt_delete_req = $conn->prepare($sql_delete_req);
        $stmt_delete_req->bind_param("i", $request_id);
        
        if (!$stmt_delete_req->execute()) {
            throw new Exception("เกิดข้อผิดพลาดในการลบข้อมูลคำร้องจากฐานข้อมูล");
        }
        $stmt_delete_req->close();
        
        // 3. Check if the vehicle is associated with any other requests
        $sql_check_vehicle = "SELECT COUNT(*) as count FROM vehicle_requests WHERE vehicle_id = ?";
        $stmt_check_vehicle = $conn->prepare($sql_check_vehicle);
        $stmt_check_vehicle->bind_param("i", $vehicle_id_to_check);
        $stmt_check_vehicle->execute();
        $result_check = $stmt_check_vehicle->get_result()->fetch_assoc();
        $stmt_check_vehicle->close();
        
        // 4. If no other requests are associated, delete the vehicle record
        if ($result_check['count'] == 0) {
            $sql_delete_vehicle = "DELETE FROM vehicles WHERE id = ?";
            $stmt_delete_vehicle = $conn->prepare($sql_delete_vehicle);
            $stmt_delete_vehicle->bind_param("i", $vehicle_id_to_check);
            if (!$stmt_delete_vehicle->execute()) {
                 throw new Exception("เกิดข้อผิดพลาดในการลบข้อมูลยานพาหนะ");
            }
            $stmt_delete_vehicle->close();
        }
        
        // Log the successful deletion
        log_activity($conn, 'delete_vehicle_request', ['request_id' => $request_id, 'vehicle_id_deleted' => ($result_check['count'] == 0)]);
        
        $conn->commit();

        $_SESSION['request_status'] = 'success';
        $_SESSION['request_message'] = 'ลบคำร้องสำเร็จแล้ว';

    } catch (Exception $e) {
        $conn->rollback();
        handle_error($e->getMessage(), "Deletion Error: " . $e->getMessage());
    }
    
    $conn->close();
    header("Location: ../../../views/user/home/dashboard.php");
    exit();

} else {
    // Redirect if not a POST request
    header("Location: ../../../views/user/home/dashboard.php");
    exit();
}

