<?php
// --- app/controllers/admin/requests/check_requests.php ---
// This file acts as a central API endpoint for admin-related requests.
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';
require_once '../../../../lib/phpqrcode/qrlib.php';

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8");

function getRequestDetails($conn, $request_id) {
    $sql = "SELECT 
                vr.*,
                v.vehicle_type, v.brand, v.model, v.color, v.license_plate, v.province AS vehicle_province,
                u.user_key, u.id as user_original_id,
                COALESCE(aud.photo_profile, u.photo_profile) AS photo_profile,
                COALESCE(aud.title, u.title) AS user_title,
                COALESCE(aud.firstname, u.firstname) AS user_firstname,
                COALESCE(aud.lastname, u.lastname) AS user_lastname,
                COALESCE(aud.phone_number, u.phone_number) AS phone_number,
                COALESCE(aud.national_id, u.national_id) AS national_id,
                COALESCE(aud.user_type, u.user_type) AS user_type,
                COALESCE(aud.work_department, u.work_department) AS work_department,
                COALESCE(aud.position, u.position) AS position,
                COALESCE(aud.official_id, u.official_id) AS official_id,
                COALESCE(aud.address, u.address) as address,
                COALESCE(aud.subdistrict, u.subdistrict) as subdistrict,
                COALESCE(aud.district, u.district) as district,
                COALESCE(aud.province, u.province) as user_province,
                COALESCE(aud.zipcode, u.zipcode) as zipcode,
                COALESCE(aud.dob, u.dob) as dob,
                COALESCE(aud.gender, u.gender) as gender,
                creator.title as creator_title,
                creator.firstname as creator_firstname,
                creator.lastname as creator_lastname
            FROM vehicle_requests vr
            JOIN users u ON vr.user_id = u.id
            JOIN vehicles v ON vr.vehicle_id = v.id
            LEFT JOIN approved_user_data aud ON vr.id = aud.request_id
            LEFT JOIN admins creator ON vr.created_by_admin_id = creator.id
            WHERE vr.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_details':
        $request_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$request_id) { echo json_encode(['success' => false, 'message' => 'Invalid request ID']); break; }
        $data = getRequestDetails($conn, $request_id);
        if ($data) { echo json_encode(['success' => true, 'data' => $data]); } 
        else { echo json_encode(['success' => false, 'message' => 'Could not retrieve request details.']); }
        break;

    case 'process_request':
        $input = json_decode(file_get_contents('php://input'), true);
        $request_id = filter_var($input['request_id'] ?? 0, FILTER_VALIDATE_INT);
        $process_action = $input['action'] ?? '';
        $rejection_reason = htmlspecialchars(strip_tags(trim($input['reason'] ?? '')));
        $admin_id = $_SESSION['admin_id'];

        if (!$request_id || !in_array($process_action, ['approve', 'reject'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
            break;
        }

        $conn->begin_transaction();
        try {
            $response = [];
            if ($process_action === 'approve') {
                $current_request_data = getRequestDetails($conn, $request_id);
                if (!$current_request_data) throw new Exception("Request not found for snapshotting.");

                $qr_content = "http://localhost/verify.php?key=" . $current_request_data['request_key'];
                $qr_dir = "../../../../public/qr/";
                if (!file_exists($qr_dir)) mkdir($qr_dir, 0777, true);
                $qr_filename = $current_request_data['request_key'] . '.png';
                QRcode::png($qr_content, $qr_dir . $qr_filename, QR_ECLEVEL_L, 4, 0);

                $sql_approve = "UPDATE vehicle_requests SET status = 'approved', approved_by_id = ?, approved_at = NOW(), qr_code_path = ? WHERE id = ?";
                $stmt = $conn->prepare($sql_approve);
                $stmt->bind_param("isi", $admin_id, $qr_filename, $request_id);
                if (!$stmt->execute()) throw new Exception("Failed to approve request.");

                $sql_snapshot = "
                    INSERT INTO approved_user_data (request_id, original_user_id, user_type, phone_number, national_id, title, firstname, lastname, dob, gender, address, subdistrict, district, province, zipcode, photo_profile, work_department, position, official_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        original_user_id = VALUES(original_user_id),
                        user_type = VALUES(user_type),
                        phone_number = VALUES(phone_number),
                        national_id = VALUES(national_id),
                        title = VALUES(title),
                        firstname = VALUES(firstname),
                        lastname = VALUES(lastname),
                        dob = VALUES(dob),
                        gender = VALUES(gender),
                        address = VALUES(address),
                        subdistrict = VALUES(subdistrict),
                        district = VALUES(district),
                        province = VALUES(province),
                        zipcode = VALUES(zipcode),
                        photo_profile = VALUES(photo_profile),
                        work_department = VALUES(work_department),
                        position = VALUES(position),
                        official_id = VALUES(official_id),
                        snapshotted_at = NOW()
                ";
                $stmt_snapshot = $conn->prepare($sql_snapshot);
                $stmt_snapshot->bind_param("iisssssssssssssssss", $request_id, $current_request_data['user_original_id'], $current_request_data['user_type'], $current_request_data['phone_number'], $current_request_data['national_id'], $current_request_data['user_title'], $current_request_data['user_firstname'], $current_request_data['user_lastname'], $current_request_data['dob'], $current_request_data['gender'], $current_request_data['address'], $current_request_data['subdistrict'], $current_request_data['district'], $current_request_data['user_province'], $current_request_data['zipcode'], $current_request_data['photo_profile'], $current_request_data['work_department'], $current_request_data['position'], $current_request_data['official_id']);
                if (!$stmt_snapshot->execute()) throw new Exception("Failed to snapshot user data.");

                log_activity($conn, 'admin_approve_request', ['request_id' => $request_id]);
                $response = ['success' => true, 'message' => 'อนุมัติคำร้องสำเร็จแล้ว', 'qr_code_url' => '/public/qr/' . $qr_filename];

            } elseif ($process_action === 'reject') {
                if(empty($rejection_reason)) throw new Exception("Rejection reason is required.");
                $sql_reject = "UPDATE vehicle_requests SET status = 'rejected', rejection_reason = ?, approved_by_id = ?, approved_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql_reject);
                $stmt->bind_param("sii", $rejection_reason, $admin_id, $request_id);
                if (!$stmt->execute()) throw new Exception("Failed to reject request.");

                log_activity($conn, 'admin_reject_request', ['request_id' => $request_id, 'reason' => $rejection_reason]);
                $response = ['success' => true, 'message' => 'ปฏิเสธคำร้องสำเร็จแล้ว'];
            }
            
            $response['request_data'] = getRequestDetails($conn, $request_id);
            $conn->commit();
            echo json_encode($response);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage()]);
        }
        break;

    case 'check_vehicle_duplicate':
        $input = json_decode(file_get_contents('php://input'), true);
        $license_plate = $input['license_plate'] ?? '';
        $province = $input['province'] ?? '';
        if (empty($license_plate) || empty($province)) { echo json_encode(['exists' => false, 'message' => 'License plate and province are required.']); break; }
        $sql = "SELECT vr.id FROM vehicle_requests vr JOIN vehicles v ON vr.vehicle_id = v.id JOIN application_periods ap ON vr.period_id = ap.id WHERE v.license_plate = ? AND v.province = ? AND ap.is_active = 1 AND vr.status IN ('pending', 'approved')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $license_plate, $province);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode(['exists' => $result->num_rows > 0]);
        $stmt->close();
        break;

    case 'get_admin_details':
        $admin_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$admin_id) { echo json_encode(['success' => false, 'message' => 'Invalid Admin ID']); break; }
        $sql = "SELECT id, username, title, firstname, lastname, department, role, view_permission FROM admins WHERE id = ?";
        $stmt_admin = $conn->prepare($sql);
        $stmt_admin->bind_param("i", $admin_id);
        $stmt_admin->execute();
        $result = $stmt_admin->get_result();
        if ($data = $result->fetch_assoc()) { echo json_encode(['success' => true, 'data' => $data]); } 
        else { echo json_encode(['success' => false, 'message' => 'Admin not found']); }
        $stmt_admin->close();
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid API action specified']);
        break;
}

$conn->close();
?>

