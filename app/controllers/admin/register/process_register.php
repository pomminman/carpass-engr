<?php
// --- app/controllers/admin/register/process_register.php ---

session_start();
require_once '../../../models/db_config.php';
require_once '../../../models/log_helper.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        $_SESSION['register_error'] = "Database connection failed: " . $conn->connect_error;
        header("location: ../../../views/admin/register/register.php");
        exit;
    }
    $conn->set_charset("utf8");

    // รับค่าจากฟอร์ม
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $title_choice = trim($_POST['title']);
    $title_other = trim($_POST['title_other'] ?? '');
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $phone_number = trim($_POST['phone_number']);
    $position = trim($_POST['position']);
    $department_choice = trim($_POST['department']);
    $department_other = trim($_POST['department_other'] ?? '');
    $role = $_POST['role'];
    $view_permission = filter_var($_POST['view_permission'], FILTER_VALIDATE_INT);
    
    $final_department = '';
    $final_title = '';

    // --- Validation ---
    if (empty($username) || empty($password) || empty($confirm_password) || empty($title_choice) || empty($firstname) || empty($lastname) || empty($role)) {
        $_SESSION['register_error'] = "กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน";
        header("location: ../../../views/admin/register/register.php");
        exit;
    }
    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = "รหัสผ่านไม่ตรงกัน";
        header("location: ../../../views/admin/register/register.php");
        exit;
    }
    
    // --- [แก้ไข] ตรรกะจัดการ "คำนำหน้า" (ไม่ต้องยุ่งกับ DB) ---
    if ($title_choice === 'other') {
        if (empty($title_other)) {
            $_SESSION['register_error'] = "กรุณาระบุคำนำหน้าใหม่";
            header("location: ../../../views/admin/register/register.php");
            exit;
        }
        $final_title = $title_other;
    } else {
        $final_title = $title_choice;
    }

    // --- ตรรกะจัดการ "สังกัด" ---
    if ($department_choice === 'other') {
        if (empty($department_other)) {
            $_SESSION['register_error'] = "กรุณาระบุชื่อสังกัดใหม่";
            header("location: ../../../views/admin/register/register.php");
            exit;
        }
        $final_department = $department_other;
        
        $sql_check_dept = "SELECT id FROM departments WHERE name = ?";
        $stmt_check_dept = $conn->prepare($sql_check_dept);
        $stmt_check_dept->bind_param("s", $final_department);
        $stmt_check_dept->execute();
        $stmt_check_dept->store_result();

        if ($stmt_check_dept->num_rows == 0) {
            $sql_max_order = "SELECT MAX(display_order) as max_order FROM departments WHERE display_order < 999";
            $result_max_order = $conn->query($sql_max_order);
            $next_display_order = ($result_max_order->fetch_assoc()['max_order'] ?? 0) + 1;
            
            $sql_insert_dept = "INSERT INTO departments (name, display_order) VALUES (?, ?)";
            $stmt_insert_dept = $conn->prepare($sql_insert_dept);
            $stmt_insert_dept->bind_param("si", $final_department, $next_display_order);
            $stmt_insert_dept->execute();
            $stmt_insert_dept->close();
        }
        $stmt_check_dept->close();
    } else {
        $final_department = $department_choice;
    }


    // ตรวจสอบ Username ซ้ำ
    $sql_check = "SELECT id FROM admins WHERE username = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $username);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $_SESSION['register_error'] = "Username นี้มีผู้ใช้งานแล้ว";
        header("location: ../../../views/admin/register/register.php");
        exit;
    }
    $stmt_check->close();

    // --- Process Data ---
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $created_by = $_SESSION['admin_id'] ?? null; 

    $sql = "INSERT INTO admins (username, password, title, firstname, lastname, phone_number, position, department, role, view_permission, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssii", $username, $hashed_password, $final_title, $firstname, $lastname, $phone_number, $position, $final_department, $role, $view_permission, $created_by);

    if ($stmt->execute()) {
        $new_admin_id = $stmt->insert_id;
        log_activity($conn, 'admin_register_success', ['new_admin_id' => $new_admin_id, 'username' => $username, 'created_by_id' => $created_by]);
        $_SESSION['register_success'] = "สร้างบัญชีผู้ดูแลระบบ '$username' สำเร็จแล้ว";
    } else {
        log_activity($conn, 'admin_register_fail', ['username' => $username, 'error' => $stmt->error]);
        $_SESSION['register_error'] = "เกิดข้อผิดพลาดในการสร้างบัญชี: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    header("location: ../../../views/admin/register/register.php");
    exit;
} else {
    header("location: ../../../views/admin/register/register.php");
    exit;
}
?>

