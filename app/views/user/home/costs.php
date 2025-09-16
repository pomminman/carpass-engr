<?php
// app/views/user/home/costs.php
require_once '../shared/auth_check.php';
require_once '../layouts/header.php';
?>

<!-- Main Content for Costs Page -->
<div id="costs-section" class="space-y-4">
    <!-- Header -->
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">ขั้นตอนและค่าธรรมเนียม</h1>
        <p class="text-sm sm:text-base text-base-content/70">รายละเอียดค่าธรรมเนียมและขั้นตอนการรับบัตร</p>
    </div>

    <!-- Main Card -->
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4 sm:p-6">
            
            <!-- Step 1: Fee -->
            <div class="flex items-start gap-4">
                <div class="flex flex-col items-center">
                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-primary text-primary-content font-bold text-xl">1</div>
                </div>
                <div>
                    <h3 class="font-bold text-lg -mt-1">ค่าธรรมเนียมบัตรผ่าน</h3>
                    <p class="text-base-content/80 mt-1">บัตรผ่านยานพาหนะทุกประเภทมีค่าธรรมเนียมในราคาเดียว</p>
                    <div class="stat bg-primary/10 rounded-lg mt-2 p-3">
                        <div class="stat-title">ราคาต่อใบ</div>
                        <div class="stat-value text-primary">30 บาท</div>
                        <div class="stat-desc">กรุณาเตรียมเงินสดให้พอดี</div>
                    </div>
                </div>
            </div>

            <div class="divider my-6"></div>

            <!-- Step 2: Pickup/Payment -->
                <div class="flex items-start gap-4">
                <div class="flex flex-col items-center gap-2">
                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-primary text-primary-content font-bold text-xl">2</div>
                </div>
                <div>
                    <h3 class="font-bold text-lg -mt-1">ขั้นตอนการรับบัตรและชำระเงิน</h3>
                    <p class="text-base-content/80 mt-1">เมื่อคำร้องของท่านได้รับการ <span class="badge badge-success font-semibold">อนุมัติ</span> แล้ว สามารถติดต่อรับบัตรได้ตามประเภทผู้สมัคร ดังนี้</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                        <!-- Card for Army Personnel -->
                        <div class="card bg-base-200 border shadow-sm">
                            <div class="card-body p-4">
                                <h4 class="font-semibold flex items-center gap-2 ">
                                    <i class="fa-solid fa-users-cog text-primary"></i>
                                    กำลังพลสังกัด กรมการทหารช่าง
                                </h4>
                                <p class=" mt-2 text-base-content/80">
                                    กรุณาชำระเงินและรับบัตรผ่าน <strong>เจ้าหน้าที่ของหน่วยท่าน</strong> ซึ่งจะดำเนินการรวบรวมและติดต่อรับบัตรให้เป็นส่วนรวม
                                </p>
                            </div>
                        </div>
                        <!-- Card for External Users -->
                        <div class="card bg-base-200 border shadow-sm">
                            <div class="card-body p-4">
                                <h4 class="font-semibold flex items-center gap-2 ">
                                    <i class="fa-solid fa-user-group text-secondary"></i>
                                    บุคคลภายนอก และกำลังพลนอกสังกัด
                                </h4>
                                <p class=" mt-2 text-base-content/80">
                                    ติดต่อรับบัตรและชำระเงินได้ด้วยตนเอง ณ <strong>แผนกการข่าวและรักษาความปลอดภัย กองยุทธการและการข่าว กรมการทหารช่าง</strong> (ในวันและเวลาราชการ)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../layouts/footer.php'; ?>

