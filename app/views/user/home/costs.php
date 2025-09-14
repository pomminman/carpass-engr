<?php
// app/views/user/home/costs.php
require_once '../shared/auth_check.php';
require_once '../layouts/header.php';
?>

<!-- Main Content for Costs Page -->
<div id="costs-section" class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">ขั้นตอนและค่าใช้จ่าย</h1>
        <p class="text-sm sm:text-base text-base-content/70">รายละเอียดค่าธรรมเนียมและขั้นตอนการรับบัตร</p>
    </div>

    <!-- Main Card -->
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <div role="alert" class="alert alert-info alert-soft">
                <i class="fa-solid fa-circle-info text-xl"></i>
                <span class="text-sm sm:text-base">บัตรผ่านยานพาหนะทุกประเภทมีค่าธรรมเนียม <strong>ราคาใบละ 30 บาท</strong></span>
            </div>
            
            <div class="divider my-6">ขั้นตอนการรับบัตรและชำระเงิน</div>

            <div class="space-y-4">
                <!-- Card for Army Personnel -->
                <div class="card bg-base-200 border shadow-inner">
                    <div class="card-body p-6">
                        <h3 class="font-semibold flex items-center gap-3 text-sm sm:text-base">
                            <i class="fa-solid fa-users-cog text-primary text-xl"></i>
                            สำหรับกำลังพลสังกัด กรมการทหารช่าง (กช.)
                        </h3>
                        <p class="text-sm mt-2">
                            กรุณาชำระเงินและรับบัตรผ่าน <strong>เจ้าหน้าที่ของหน่วยท่าน</strong> ซึ่งจะดำเนินการรวบรวมและติดต่อรับบัตรให้เป็นส่วนรวม
                        </p>
                    </div>
                </div>
                <!-- Card for External Users -->
                <div class="card bg-base-200 border shadow-inner">
                    <div class="card-body p-6">
                        <h3 class="font-semibold flex items-center gap-3 text-sm sm:text-base">
                            <i class="fa-solid fa-user-group text-secondary text-xl"></i>
                            สำหรับบุคคลภายนอก และกำลังพล ทบ. (นอกสังกัด กช.)
                        </h3>
                        <p class="text-sm mt-2">
                            เมื่อคำร้องของท่านได้รับการ <strong>"อนุมัติ"</strong> แล้ว ท่านสามารถติดต่อรับบัตรและชำระเงินได้ด้วยตนเอง ณ <strong>แผนกการข่าวและรักษาความปลอดภัย กองยุทธการและการข่าว กรมการทหารช่าง</strong> (ในวันและเวลาราชการ ตามวันที่นัดรับบัตร)
                        </p>
                    </div>
                </div>
            </div>

            <div role="alert" class="alert alert-warning alert-soft mt-6">
                <i class="fa-solid fa-coins"></i>
                <span class="text-sm">เพื่อความสะดวกรวดเร็ว กรุณาเตรียมเงินสดให้พอดีกับจำนวนค่าธรรมเนียม</span>
            </div>
        </div>
    </div>
</div>

<?php require_once '../layouts/footer.php'; ?>

