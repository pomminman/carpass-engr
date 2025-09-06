<?php
// --- app/controllers/admin/requests/process_request.php ---

session_start();
header('Content-Type: application/json');

// --- 1. ตรวจสอบสิทธิ์และข้อมูลนำเข้า ---
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$request_id = filter_var($data['request_id'] ?? null, FILTER_VALIDATE_INT);
$action = filter_var($data['action'] ?? null, FILTER_SANITIZE_STRING);
$admin_id = $_SESSION['admin_id'];

if (!$request_id || !$action || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

// --- 2. เรียกใช้ไฟล์ที่จำเป็น ---
require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';
require_once '../../../../lib/phpqrcode/qrlib.php';

// --- 3. เชื่อมต่อฐานข้อมูล ---
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8");

// --- 4. ประมวลผลตาม Action ---
$conn->begin_transaction();

try {
    if ($action === 'approve') {
        
        // --- ดึงข้อมูล User และ Request ---
        $sql_info = "SELECT u.*, vr.created_at, vr.request_key FROM users u JOIN vehicle_requests vr ON u.id = vr.user_id WHERE vr.id = ?";
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("i", $request_id);
        $stmt_info->execute();
        $result_info = $stmt_info->get_result();
        $user_data = $result_info->fetch_assoc();
        $stmt_info->close();
        
        if(!$user_data) throw new Exception("User or request info not found.");

        // --- [ใหม่] สร้าง Snapshot ของข้อมูลผู้ใช้ ---
        $sql_snapshot = "INSERT INTO approved_user_data (
                            request_id, original_user_id, user_type, phone_number, national_id, title, firstname, lastname,
                            dob, gender, address, subdistrict, district, province, zipcode, photo_profile,
                            work_department, position, official_id
                         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_snapshot = $conn->prepare($sql_snapshot);
        $stmt_snapshot->bind_param(
            "iisssssssssssssssss",
            $request_id, $user_data['id'], $user_data['user_type'], $user_data['phone_number'], $user_data['national_id'],
            $user_data['title'], $user_data['firstname'], $user_data['lastname'], $user_data['dob'], $user_data['gender'],
            $user_data['address'], $user_data['subdistrict'], $user_data['district'], $user_data['province'], $user_data['zipcode'],
            $user_data['photo_profile'], $user_data['work_department'], $user_data['position'], $user_data['official_id']
        );
        if (!$stmt_snapshot->execute()) {
            // กรณีมีข้อมูลอยู่แล้ว (อาจเกิดจากการกดอนุมัติซ้ำ) ให้ทำการอัปเดตแทน
            if ($stmt_snapshot->errno == 1062) { // 1062 is the error code for duplicate entry
                $sql_update_snapshot = "UPDATE approved_user_data SET 
                                            original_user_id=?, user_type=?, phone_number=?, national_id=?, title=?, firstname=?, lastname=?,
                                            dob=?, gender=?, address=?, subdistrict=?, district=?, province=?, zipcode=?, photo_profile=?,
                                            work_department=?, position=?, official_id=?, snapshotted_at=NOW()
                                        WHERE request_id=?";
                $stmt_update_snapshot = $conn->prepare($sql_update_snapshot);
                $stmt_update_snapshot->bind_param(
                    "issssssssssssssssi",
                    $user_data['id'], $user_data['user_type'], $user_data['phone_number'], $user_data['national_id'],
                    $user_data['title'], $user_data['firstname'], $user_data['lastname'], $user_data['dob'], $user_data['gender'],
                    $user_data['address'], $user_data['subdistrict'], $user_data['district'], $user_data['province'], $user_data['zipcode'],
                    $user_data['photo_profile'], $user_data['work_department'], $user_data['position'], $user_data['official_id'],
                    $request_id
                );
                if(!$stmt_update_snapshot->execute()) throw new Exception("Snapshot update failed: " . $stmt_update_snapshot->error);
                $stmt_update_snapshot->close();
            } else {
                throw new Exception("Snapshot insert failed: " . $stmt_snapshot->error);
            }
        }
        $stmt_snapshot->close();


        // --- กำหนด Card Type และ Card Expiry Year ---
        $card_type = ($user_data['user_type'] === 'army') ? 'internal' : 'external';
        $creation_year = date('Y', strtotime($user_data['created_at']));
        $card_expiry_year = (string)((int)$creation_year + 543);

        // --- สร้าง Card Number ---
        $sql_max_card = "SELECT MAX(CAST(card_number AS UNSIGNED)) as max_num FROM vehicle_requests";
        $result_max_card = $conn->query($sql_max_card);
        $max_card_row = $result_max_card->fetch_assoc();
        $next_card_num = ($max_card_row['max_num'] ?? 0) + 1;
        $card_number = str_pad($next_card_num, 4, '0', STR_PAD_LEFT);

        // --- สร้าง QR Code ---
        $qr_content = "http://" . $_SERVER['HTTP_HOST'] . "/public/app/verify.php?key=" . $user_data['request_key'];
        $qr_dir = "../../../../public/uploads/vehicle/QR/";
        if (!file_exists($qr_dir)) {
            mkdir($qr_dir, 0777, true);
        }
        $qr_file_path_relative = $qr_dir . $user_data['request_key'] . '.png';
        QRcode::png($qr_content, $qr_file_path_relative, QR_ECLEVEL_L, 4, 1); // Margin = 1

        // --- อัปเดตตาราง vehicle_requests ---
        $sql = "UPDATE vehicle_requests SET 
                    status = 'approved', 
                    approved_by_id = ?, 
                    approved_at = NOW(), 
                    card_type = ?, 
                    card_number = ?, 
                    card_expiry_year = ?,
                    rejection_reason = NULL
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssi", $admin_id, $card_type, $card_number, $card_expiry_year, $request_id);
        if(!$stmt->execute()) throw new Exception("Database update failed: " . $stmt->error);
        
        log_activity($conn, 'admin_approve_request', ['request_id' => $request_id]);
        $response = ['success' => true, 'message' => 'อนุมัติคำร้องสำเร็จแล้ว', 'qr_code_url' => '/public/uploads/vehicle/QR/' . $user_data['request_key'] . '.png'];

    } elseif ($action === 'reject') {
        $rejection_reason = filter_var($data['reason'] ?? 'ไม่ระบุเหตุผล', FILTER_SANITIZE_STRING);
        if (empty($rejection_reason)) {
            throw new Exception("กรุณาระบุเหตุผลที่ไม่ผ่านการอนุมัติ");
        }
        
        $sql = "UPDATE vehicle_requests SET 
                    status = 'rejected', 
                    approved_by_id = ?, 
                    approved_at = NOW(),
                    rejection_reason = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $admin_id, $rejection_reason, $request_id);
        if(!$stmt->execute()) throw new Exception("Database update failed: " . $stmt->error);
        
        log_activity($conn, 'admin_reject_request', ['request_id' => $request_id, 'reason' => $rejection_reason]);
        $response = ['success' => true, 'message' => 'ปฏิเสธคำร้องสำเร็จแล้ว'];
    }
    
    if(isset($stmt)) $stmt->close();
    $conn->commit();

} catch (Exception $e) {
    $conn->rollback();
    $response = ['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage()];
}

$conn->close();
echo json_encode($response);
?>

