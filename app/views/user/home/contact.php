<?php
// app/views/user/home/contact.php
require_once '../shared/auth_check.php';
require_once '../layouts/header.php';
?>

<!-- Main Content for Contact Page -->
<div id="contact-section" class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">ติดต่อสอบถาม</h1>
        <p class="text-sm sm:text-base text-base-content/70">ช่องทางการติดต่อสำหรับข้อสงสัยหรือปัญหาการใช้งาน</p>
    </div>

    <!-- Main Card -->
    <div class="card bg-base-100 shadow">
        <div class="card-body p-4 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column: Contact Info -->
                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold text-base flex items-center gap-2"><i class="fa-solid fa-building text-primary"></i> สถานที่ติดต่อ</h3>
                        <div class="mt-2 pl-7 text-base-content/90 text-sm">
                            <p>แผนกการข่าวและรักษาความปลอดภัย</p>
                            <p>กองยุทธการและการข่าว กรมการทหารช่าง</p>
                            <p>ค่ายภาณุรังษี ต.โคกหม้อ อ.เมือง จ.ราชบุรี 70000</p>
                        </div>
                    </div>
                    <div class="divider my-2"></div>
                     <div>
                        <h3 class="font-semibold text-base flex items-center gap-2"><i class="fa-solid fa-clock text-primary"></i> วันและเวลาทำการ</h3>
                        <div class="mt-2 pl-7 text-base-content/90 text-sm">
                            <p>จันทร์ - ศุกร์ (เว้นวันหยุดราชการ)</p>
                            <p>เวลา 08:30 - 16:30 น.</p>
                        </div>
                    </div>
                </div>
                <!-- Right Column: Channels -->
                <div class="space-y-4">
                    <div>
                        <h3 class="font-semibold text-base flex items-center gap-2"><i class="fa-solid fa-headset text-primary"></i> ช่องทางติดต่อ</h3>
                        <div class="mt-2 space-y-3">
                            <div class="card card-compact bg-base-200 border">
                                <div class="card-body p-3">
                                    <p class="font-semibold text-sm">พบปัญหาการใช้งานระบบ:</p>
                                    <p class="text-xs">ร.ท. พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ)</p>
                                    <p class="text-xs break-words"><i class="fa-solid fa-envelope w-4 text-base-content/60"></i> oid.engrdept@gmail.com</p>
                                </div>
                            </div>
                             <div class="card card-compact bg-base-200 border">
                                <div class="card-body p-3">
                                    <p class="font-semibold text-sm">สอบถามเรื่องเอกสารและติดตามสถานะ:</p>
                                    <a href="https://line.me/ti/p/~YOUR_LINE_ID" target="_blank" class="btn btn-success no-underline w-full mt-1">
                                        <i class="fab fa-line text-base"></i> Line Official: บัตรผ่านยานพาหนะ กช.
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../layouts/footer.php'; ?>
