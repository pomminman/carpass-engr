<?php
// app/views/user/home/contact.php (หน้าติดต่อ)

// 1. ตรวจสอบสิทธิ์และดึงข้อมูลผู้ใช้
require_once '../shared/auth_check.php';
// [แก้ไข] ลบบรรทัดนี้ออกเพื่อป้องกันการปิดการเชื่อมต่อซ้ำซ้อน
// $conn->close();

// 2. เรียกใช้ Header
require_once '../layouts/header.php';
?>

<!-- Main Content -->
<main class="flex-grow container mx-auto max-w-6xl p-6 pb-24">

    <!-- User Welcome -->
    <div class="block sm:flex sm:items-baseline sm:gap-2">
        <h1 class="text-xl sm:text-2xl font-bold mb-1">ยินดีต้อนรับ,</h1>
        <h1 class="text-xl sm:text-2xl font-bold"><?php echo htmlspecialchars($title . ' ' . $firstname . ' ' . $lastname); ?></h1>
    </div>
    <div class="flex flex-wrap gap-2 mt-2 mb-6">
        <div class="badge badge-lg badge-outline gap-2"><?php echo $user_type_icon; ?><?php echo htmlspecialchars($user_type_thai); ?></div>
        <?php if ($user['user_type'] === 'army' && !empty($user['work_department'])): ?>
        <div class="badge badge-lg badge-outline gap-2"><i class="fa-solid fa-sitemap text-slate-500"></i>สังกัด: <?php echo htmlspecialchars($user['work_department']); ?></div>
        <?php endif; ?>
    </div>

    <div class="card bg-base-100 shadow-xl border border-base-300/50">
        <div class="card-body">
            <h2 class="card-title text-xl flex items-center gap-2"><i class="fa-solid fa-address-book"></i> ติดต่อสอบถาม</h2>
            <div class="divider"></div>
            <div class="space-y-4 text-sm">
                <div>
                    <p class="font-semibold">แผนกการข่าวและรักษาความปลอดภัย</p>
                    <p>กองยุทธการและการข่าว กรมการทหารช่าง</p>
                    <p>ค่ายภาณุรังษี ต.โค้กหม้อ อ.เมือง จ.ราชบุรี 70000</p>
                </div>
                <div class="divider my-2"></div>
                <div>
                    <p class="font-semibold">พบปัญหาการใช้งานระบบ ติดต่อ:</p>
                    <p>ร.ท. พรหมินทร์  อินทมาตย์ (ผู้พัฒนาระบบ)</p>
                    <p><i class="fa-solid fa-envelope w-4 text-slate-500"></i> E-mail : oid.engrdept@gmail.com</p>
                </div>
                <div class="divider my-2"></div>
                <div>
                    <p><i class="fa-solid fa-clock w-4 text-slate-500"></i> <span class="font-semibold">วันเวลาทำการ :</span> จันทร์-ศุกร์ 08.30-16.30 น. (เว้นวันหยุดราชการ)</p>
                </div>
                <div>
                    <a href="#" class="btn btn-sm btn-success btn-outline no-underline">
                    <i class="fab fa-line text-lg"></i> Line Official: บัตรผ่านยานพาหนะ กช.
                    </a>
                </div>
            </div>
        </div>
    </div>

</main>

<?php
// 3. เรียกใช้ Footer
require_once '../layouts/footer.php';
?>
