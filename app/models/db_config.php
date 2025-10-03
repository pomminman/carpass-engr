<?php
// app/models/db_config.php

// --- db_config.php (เวอร์ชันปรับปรุง) ---
// ไฟล์นี้ใช้สำหรับเก็บข้อมูลการตั้งค่าและการจัดการการเชื่อมต่อฐานข้อมูล

$servername = "localhost";    // ชื่อเซิร์ฟเวอร์ฐานข้อมูล
$username   = "root";         // ชื่อผู้ใช้ฐานข้อมูล
$password   = "";             // รหัสผ่านฐานข้อมูล
$dbname     = "carpass_2026"; // ชื่อฐานข้อมูล

/**
 * ตัวแปร Global สำหรับเก็บ instance ของการเชื่อมต่อ (Singleton Pattern)
 * @var mysqli|null
 */
$__db_connection = null;

/**
 * ฟังก์ชันสำหรับสร้างและคืนค่าการเชื่อมต่อฐานข้อมูล
 * จะสร้างการเชื่อมต่อใหม่เฉพาะครั้งแรก หรือเมื่อการเชื่อมต่อเดิมถูกตัดไปแล้ว
 *
 * @return mysqli อ็อบเจกต์การเชื่อมต่อฐานข้อมูล
 * @throws Exception หากการเชื่อมต่อล้มเหลว
 */
function get_db_connection() {
    global $__db_connection, $servername, $username, $password, $dbname;

    // 1. ตรวจสอบว่ายังไม่มีการเชื่อมต่อ หรือการเชื่อมต่อเดิม "หายไป" (gone away)
    if ($__db_connection === null || !$__db_connection->ping()) {
        
        // ปิดการเชื่อมต่อเก่า (ถ้ามี) ก่อนสร้างใหม่
        if ($__db_connection !== null) {
            $__db_connection->close();
        }

        // 2. สร้างการเชื่อมต่อใหม่
        $__db_connection = new mysqli($servername, $username, $password, $dbname);

        // 3. ตรวจสอบข้อผิดพลาดในการเชื่อมต่อ
        if ($__db_connection->connect_error) {
            // ในสถานการณ์จริง ควรบันทึก Log และแสดงหน้าข้อผิดพลาดที่เป็นมิตรต่อผู้ใช้
            throw new Exception("Database Connection Error: " . $__db_connection->connect_error);
        }

        // 4. ตั้งค่า Character Set
        $__db_connection->set_charset("utf8");
    }

    return $__db_connection;
}

?>
