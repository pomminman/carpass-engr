<?php
// app/controllers/admin/requests/check_requests.php

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
                ap.card_fee,
                ap.period_name,
                t.created_at as transaction_created_at,
                t.amount as transaction_amount,
                t.payment_method as transaction_method,
                t.reference_code as transaction_ref,
                t.notes as transaction_notes,
                payment_admin.title as payment_admin_title,
                payment_admin.firstname as payment_admin_firstname,
                payment_admin.lastname as payment_admin_lastname,
                approver.title as approver_title,
                approver.firstname as approver_firstname,
                approver.lastname as approver_lastname,
                pickup_admin.title as pickup_admin_title,
                pickup_admin.firstname as pickup_admin_firstname,
                pickup_admin.lastname as pickup_admin_lastname,
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
            LEFT JOIN application_periods ap ON vr.period_id = ap.id
            LEFT JOIN approved_user_data aud ON vr.id = aud.request_id
            LEFT JOIN admins creator ON vr.created_by_admin_id = creator.id
            LEFT JOIN transactions t ON vr.id = t.request_id AND t.transaction_type = 'payment'
            LEFT JOIN admins payment_admin ON t.admin_id = payment_admin.id
            LEFT JOIN admins pickup_admin ON vr.card_pickup_by_admin_id = pickup_admin.id
            LEFT JOIN admins approver ON vr.approved_by_id = approver.id
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
                if (!$current_request_data) throw new Exception("Request not found for processing.");
                
                $period_id = $current_request_data['period_id'];
                $stmt_max_card = $conn->prepare("SELECT MAX(CAST(card_number AS UNSIGNED)) as max_card FROM vehicle_requests WHERE period_id = ?");
                $stmt_max_card->bind_param("i", $period_id);
                $stmt_max_card->execute();
                $max_card_result = $stmt_max_card->get_result()->fetch_assoc();
                $stmt_max_card->close();
                
                $next_card_number_int = ($max_card_result['max_card'] ?? 0) + 1;
                $new_card_number = str_pad($next_card_number_int, 4, '0', STR_PAD_LEFT);

                $qr_content = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/public/app/verify.php?key=" . $current_request_data['request_key'];
                $qr_dir = "../../../../public/qr/";
                if (!file_exists($qr_dir)) mkdir($qr_dir, 0777, true);
                $qr_filename = $current_request_data['request_key'] . '.png';
                QRcode::png($qr_content, $qr_dir . $qr_filename, QR_ECLEVEL_L, 4, 0);

                $sql_approve = "UPDATE vehicle_requests SET status = 'approved', card_number = ?, approved_by_id = ?, approved_at = NOW(), qr_code_path = ? WHERE id = ?";
                $stmt = $conn->prepare($sql_approve);
                $stmt->bind_param("sisi", $new_card_number, $admin_id, $qr_filename, $request_id);
                if (!$stmt->execute()) throw new Exception("Failed to approve request.");

                $sql_snapshot = "
                    INSERT INTO approved_user_data (request_id, original_user_id, user_type, phone_number, national_id, title, firstname, lastname, dob, gender, address, subdistrict, district, province, zipcode, photo_profile, work_department, position, official_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        original_user_id = VALUES(original_user_id), user_type = VALUES(user_type), phone_number = VALUES(phone_number), national_id = VALUES(national_id), title = VALUES(title), firstname = VALUES(firstname), lastname = VALUES(lastname), dob = VALUES(dob), gender = VALUES(gender), address = VALUES(address), subdistrict = VALUES(subdistrict), district = VALUES(district), province = VALUES(province), zipcode = VALUES(zipcode), photo_profile = VALUES(photo_profile), work_department = VALUES(work_department), position = VALUES(position), official_id = VALUES(official_id), snapshotted_at = NOW()
                ";
                $stmt_snapshot = $conn->prepare($sql_snapshot);
                $stmt_snapshot->bind_param("iisssssssssssssssss", $request_id, $current_request_data['user_original_id'], $current_request_data['user_type'], $current_request_data['phone_number'], $current_request_data['national_id'], $current_request_data['user_title'], $current_request_data['user_firstname'], $current_request_data['user_lastname'], $current_request_data['dob'], $current_request_data['gender'], $current_request_data['address'], $current_request_data['subdistrict'], $current_request_data['district'], $current_request_data['user_province'], $current_request_data['zipcode'], $current_request_data['photo_profile'], $current_request_data['work_department'], $current_request_data['position'], $current_request_data['official_id']);
                if (!$stmt_snapshot->execute()) throw new Exception("Failed to snapshot user data.");

                log_activity($conn, 'admin_approve_request', ['request_id' => $request_id, 'card_number' => $new_card_number]);
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

    case 'process_payment_pickup':
        $input = json_decode(file_get_contents('php://input'), true);
        $request_id = filter_var($input['request_id'] ?? 0, FILTER_VALIDATE_INT);
        $sub_action = $input['sub_action'] ?? '';
        $admin_id = $_SESSION['admin_id'];
        $message = '';
        
        if (!$request_id || !in_array($sub_action, ['record_payment'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
            break;
        }

        $conn->begin_transaction();
        try {
            if ($sub_action === 'record_payment') {
                $amount = filter_var($input['amount'], FILTER_VALIDATE_FLOAT);
                $method = htmlspecialchars(strip_tags(trim($input['method'])));
                $ref = htmlspecialchars(strip_tags(trim($input['ref'])));
                $notes = isset($input['notes']) ? htmlspecialchars(strip_tags(trim($input['notes']))) : null;

                $stmt_trans = $conn->prepare("INSERT INTO transactions (request_id, admin_id, amount, payment_method, reference_code, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_trans->bind_param("iidsss", $request_id, $admin_id, $amount, $method, $ref, $notes);
                if (!$stmt_trans->execute()) throw new Exception("Failed to record transaction.");
                $stmt_trans->close();

                $stmt_update_req = $conn->prepare("UPDATE vehicle_requests SET payment_status = 'paid', card_pickup_status = 1, card_pickup_by_admin_id = ?, card_pickup_at = NOW() WHERE id = ?");
                $stmt_update_req->bind_param("ii", $admin_id, $request_id);
                if (!$stmt_update_req->execute()) throw new Exception("Failed to update request status.");
                $stmt_update_req->close();
                
                log_activity($conn, 'admin_record_payment_pickup', ['request_id' => $request_id, 'amount' => $amount, 'notes' => $notes]);
                $message = "บันทึกการชำระเงินและรับบัตรสำเร็จ";
            }
            
            $conn->commit();
            $updated_data = getRequestDetails($conn, $request_id);
            echo json_encode(['success' => true, 'message' => $message, 'data' => $updated_data]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'search_payable_requests':
        $searchTerm = $_GET['q'] ?? '';
        $requests_list = [];
        $sql = "SELECT vr.id, vr.search_id, u.firstname, u.lastname, v.license_plate, v.province, u.work_department
                FROM vehicle_requests vr
                JOIN users u ON vr.user_id = u.id
                JOIN vehicles v ON vr.vehicle_id = v.id
                WHERE vr.status = 'approved' AND vr.payment_status = 'unpaid'
                AND (vr.search_id LIKE ? OR CONCAT(u.firstname, ' ', u.lastname) LIKE ? OR v.license_plate LIKE ? OR u.work_department LIKE ?)
                ORDER BY vr.created_at DESC
                LIMIT 20";
        $stmt = $conn->prepare($sql);
        $likeTerm = "%" . $searchTerm . "%";
        $stmt->bind_param("ssss", $likeTerm, $likeTerm, $likeTerm, $likeTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($req = $result->fetch_assoc()) {
            $requests_list[] = [
                'id' => $req['id'],
                'text' => htmlspecialchars($req['search_id'] . ' - ' . $req['firstname'] . ' ' . $req['lastname'] . ' (' . $req['license_plate'] . ') - ' . ($req['work_department'] ?? 'บุคคลภายนอก'))
            ];
        }
        $stmt->close();
        echo json_encode(['items' => $requests_list]);
        break;

    case 'process_bulk_payment':
        $input = json_decode(file_get_contents('php://input'), true);
        $request_ids = $input['request_ids'] ?? [];
        if (empty($request_ids) || !is_array($request_ids)) {
            echo json_encode(['success' => false, 'message' => 'ไม่มีคำร้องที่ถูกเลือก']);
            break;
        }

        $admin_id = $_SESSION['admin_id'];
        $amount = filter_var($input['amount'], FILTER_VALIDATE_FLOAT);
        $method = htmlspecialchars(strip_tags(trim($input['method'])));
        $notes = isset($input['notes']) ? htmlspecialchars(strip_tags(trim($input['notes']))) : null;
        
        $conn->begin_transaction();
        try {
            $sql_trans = "INSERT INTO transactions (request_id, admin_id, amount, payment_method, notes) VALUES (?, ?, ?, ?, ?)";
            $stmt_trans = $conn->prepare($sql_trans);

            $sql_req = "UPDATE vehicle_requests SET payment_status = 'paid', card_pickup_status = 1, card_pickup_by_admin_id = ?, card_pickup_at = NOW() WHERE id = ?";
            $stmt_req = $conn->prepare($sql_req);

            foreach ($request_ids as $req_id) {
                $id = (int)$req_id;
                // Insert transaction
                $stmt_trans->bind_param("iidss", $id, $admin_id, $amount, $method, $notes);
                if (!$stmt_trans->execute()) throw new Exception("Failed to record transaction for request ID " . $id);
                
                // Update request status
                $stmt_req->bind_param("ii", $admin_id, $id);
                if (!$stmt_req->execute()) throw new Exception("Failed to update request status for ID " . $id);
            }
            
            $stmt_trans->close();
            $stmt_req->close();
            $conn->commit();

            log_activity($conn, 'admin_bulk_payment_success', ['request_ids' => $request_ids, 'count' => count($request_ids)]);
            echo json_encode(['success' => true, 'message' => 'ดำเนินการชำระเงินและรับบัตรจำนวน ' . count($request_ids) . ' รายการสำเร็จ']);

        } catch (Exception $e) {
            $conn->rollback();
            log_activity($conn, 'admin_bulk_payment_fail', ['error' => $e->getMessage()]);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'search_users':
        $searchTerm = $_GET['q'] ?? '';
        $users_list = [];
        $stmt_search = null;

        if (empty($searchTerm)) {
            $sql_search = "SELECT id, firstname, lastname, national_id, title FROM users 
                           ORDER BY firstname ASC, lastname ASC
                           LIMIT 50";
            $stmt_search = $conn->prepare($sql_search);
        } else {
            $sql_search = "SELECT id, firstname, lastname, national_id, title FROM users 
                           WHERE CONCAT(firstname, ' ', lastname) LIKE ? 
                           OR national_id LIKE ?
                           ORDER BY firstname ASC
                           LIMIT 20";
            $stmt_search = $conn->prepare($sql_search);
            $likeTerm = "%" . $searchTerm . "%";
            $stmt_search->bind_param("ss", $likeTerm, $likeTerm);
        }

        if ($stmt_search) {
            $stmt_search->execute();
            $result = $stmt_search->get_result();
            while ($user = $result->fetch_assoc()) {
                $users_list[] = [
                    'id' => $user['id'],
                    'text' => htmlspecialchars($user['title'] . $user['firstname'] . ' ' . $user['lastname'] . ' (' . ($user['national_id'] ?? 'N/A') . ')')
                ];
            }
            $stmt_search->close();
        }
        
        echo json_encode(['items' => $users_list]);
        break;

    case 'check_user_duplicate':
        $input = json_decode(file_get_contents('php://input'), true);
        $phone = $input['phone'] ?? null;
        $nid = $input['nid'] ?? null;

        if (!$phone && !$nid) {
            echo json_encode(['exists' => false, 'message' => 'Phone or NID is required.']);
            break;
        }
        
        $sql = "SELECT id FROM users WHERE ";
        $params = [];
        $types = '';

        if ($phone) {
            $sql .= "phone_number = ?";
            $params[] = $phone;
            $types .= 's';
        } else if ($nid) {
            $sql .= "national_id = ?";
            $params[] = $nid;
            $types .= 's';
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
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
    
    case 'search_approved_requests':
        $searchTerm = $_GET['q'] ?? '';
        $requests_list = [];
        $sql = "SELECT vr.id, vr.search_id, u.firstname, u.lastname, v.license_plate, v.province, u.work_department
                FROM vehicle_requests vr
                JOIN users u ON vr.user_id = u.id
                JOIN vehicles v ON vr.vehicle_id = v.id
                WHERE vr.status = 'approved' AND vr.qr_code_path IS NOT NULL
                AND (vr.search_id LIKE ? OR CONCAT(u.firstname, ' ', u.lastname) LIKE ? OR v.license_plate LIKE ? OR u.work_department LIKE ?)
                ORDER BY vr.created_at DESC
                LIMIT 20";
        $stmt = $conn->prepare($sql);
        $likeTerm = "%" . $searchTerm . "%";
        $stmt->bind_param("ssss", $likeTerm, $likeTerm, $likeTerm, $likeTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($req = $result->fetch_assoc()) {
            $requests_list[] = [
                'id' => $req['id'],
                'text' => htmlspecialchars($req['search_id'] . ' - ' . $req['firstname'] . ' ' . $req['lastname'] . ' (' . $req['license_plate'] . ') - ' . ($req['work_department'] ?? 'บุคคลภายนอก'))
            ];
        }
        $stmt->close();
        echo json_encode(['items' => $requests_list]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid API action specified']);
        break;
}

$conn->close();
?>

