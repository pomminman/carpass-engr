<?php
// app/views/user/home/costs.php (หน้าขั้นตอนและค่าใช้จ่าย)

// 1. ตรวจสอบสิทธิ์และดึงข้อมูลผู้ใช้
require_once '../shared/auth_check.php';
// [แก้ไข] ลบบรรทัดนี้ออกเพื่อป้องกันการปิดการเชื่อมต่อซ้ำซ้อน
// $conn->close();

// 2. เรียกใช้ Header
require_once '../layouts/header.php';
?>

<!-- Main Content -->
<main class="flex-grow container mx-auto max-w-4xl p-4">
    <div id="costs-section">
        <div class="card bg-base-100 shadow-lg">
            <div class="card-body">
                <h2 class="card-title text-xl flex items-center gap-2"><i class="fa-solid fa-hand-holding-dollar"></i> ขั้นตอนและค่าใช้จ่าย</h2>
                <div class="divider"></div>
                <div class="alert alert-info alert-soft">
                    <div class="flex items-center">
                        <i class="fa-solid fa-circle-info mr-2"></i>
                        <span>บัตรผ่านยานพาหนะทุกประเภทมีค่าธรรมเนียม <strong class="font-bold">ราคาใบละ 30 บาท</strong></span>
                    </div>
                </div>
               
                <div class="divider mt-6 mb-4">ขั้นตอนการรับบัตรและชำระเงิน</div>
                <div class="space-y-4">
                    <div class="card bg-base-200 border shadow-inner">
                        <div class="card-body p-4">
                            <h3 class="font-semibold flex items-center gap-2">
                                <i class="fa-solid fa-users-cog text-primary"></i>
                                สำหรับกำลังพลสังกัด กรมการทหารช่าง (กช.)
                            </h3>
                            <p class="text-sm mt-2">
                                กรุณาชำระเงินและรับบัตรผ่าน <strong>เจ้าหน้าที่ของหน่วยท่าน</strong> ซึ่งจะดำเนินการรวบรวมและติดต่อรับบัตรให้เป็นส่วนรวม
                            </p>
                        </div>
                    </div>
                    <div class="card bg-base-200 border shadow-inner">
                        <div class="card-body p-4">
                            <h3 class="font-semibold flex items-center gap-2">
                                <i class="fa-solid fa-user-group text-secondary"></i>
                                สำหรับบุคคลภายนอก และกำลังพล ทบ. (นอกสังกัด กช.)
                            </h3>
                            <p class="text-sm mt-2">
                                เมื่อคำร้องของท่านได้รับการ <strong>"อนุมัติ"</strong> แล้ว ท่านสามารถติดต่อรับบัตรและชำระเงินได้ด้วยตนเอง ณ <strong>แผนกการข่าวและรักษาความปลอดภัย กองยุทธการและการข่าว กรมการทหารช่าง</strong> (ในวันและเวลาราชการ ตามวันที่ระบุในคำร้อง)
                            </p>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning alert-soft mt-6">
                    <div class="flex items-center">
                        <i class="fa-solid fa-coins mr-2"></i>
                        <span>เพื่อความสะดวกรวดเร็ว กรุณาเตรียมเงินสดให้พอดีกับจำนวนค่าธรรมเนียม</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// 3. เรียกใช้ Footer
require_once '../layouts/footer.php';
?>
