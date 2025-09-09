<?php
// app/views/user/layouts/header.php
// ส่วนหัวของเว็บไซต์ (Header) และ Navbar สำหรับผู้ใช้งาน
?>
<!DOCTYPE html>
<html lang="th" data-theme="light" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>ระบบยื่นคำร้อง - ค่ายภาณุรังษี</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://earthchie.github.io/jquery.Thailand.js/jquery.Thailand.js/dist/jquery.Thailand.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { font-family: 'Prompt', sans-serif; }
        .menu a.active, .menu li > a:hover {
            background-color: #e0e7ff; /* indigo-100 */
            color: #4f46e5; /* indigo-600 */
        }
        .error-message { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }
        .alert-soft { border-width: 1px; }
        .alert-error.alert-soft { background-color: #fee2e2; border-color: #fca5a5; color: #b91c1c; }
        .alert-success.alert-soft { background-color: #dcfce7; border-color: #86efac; color: #166534; }
        .alert-info.alert-soft { background-color: #e0f2fe; border-color: #7dd3fc; color: #0369a1; }
        .alert-warning.alert-soft { background-color: #fef9c3; border-color: #fde047; color: #a16207; }
        .input-disabled, .select-disabled, .textarea-disabled, input[disabled], select[disabled] {
            background-color: #f3f4f6 !important;
            border-color: #e5e7eb !important;
            cursor: not-allowed;
            color: #111827 !important;
            -webkit-text-fill-color: #111827 !important;
            opacity: 1 !important;
        }
        #zoomed-image-container { display: inline-block; position: relative; }
        #zoomed-image { max-height: 85vh; width: auto; margin: auto; object-fit: contain; }
    </style>
</head>
<body class="bg-gray-100">

    <div class="drawer">
        <input id="my-drawer-3" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col min-h-screen">
            <!-- Navbar ด้านบน -->
            <div class="w-full navbar bg-base-100 shadow-md z-30 sticky top-0">
                <div class="flex-1 px-2 mx-2">
                    <a href="dashboard.php" class="text-base font-bold flex items-center gap-2">
                        <img src="https://img2.pic.in.th/pic/CARPASS-logo11af8574a9cc9906.png" alt="Logo" class="h-16 w-16" onerror="this.onerror=null;this.src='https://placehold.co/64x64/CCCCCC/FFFFFF?text=L';">
                        <div>
                            <span class="whitespace-nowrap text-sm sm:text-base">ระบบยื่นคำร้องขอบัตรผ่านยานพาหนะ</span>
                            <span class="text-xs font-normal text-gray-500 block">เข้า-ออก ค่ายภาณุรังษี</span>
                        </div>
                    </a>
                </div>
                <div class="flex-none hidden xl:flex items-center">
                    <ul class="menu menu-horizontal gap-1" id="desktop-menu">
                        <!-- เมนูสำหรับหน้าจอคอมพิวเตอร์ -->
                        <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie w-4"></i> ภาพรวม</a></li>
                        <li><a href="add_vehicle.php" class="<?php echo ($current_page == 'add_vehicle.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-circle-plus w-4"></i> เพิ่มยานพาหนะ</a></li>
                        <li><a href="costs.php" class="<?php echo ($current_page == 'costs.php') ? 'active' : ''; ?>"><i class="fa-solid fa-hand-holding-dollar w-4"></i> ค่าใช้จ่าย</a></li>
                        <li><a href="contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>"><i class="fa-solid fa-address-book w-4"></i> ติดต่อ</a></li>
                        <li><a href="profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-pen w-4"></i> ข้อมูลส่วนตัว</a></li>
                    </ul>
                    <div class="divider xl:divider-horizontal mx-2"></div>
                    <a href="../../../controllers/user/logout/logout.php" class="btn btn-ghost btn-sm">
                        <i class="fa-solid fa-right-from-bracket w-4"></i>
                        ออกจากระบบ
                    </a>
                </div>
                <div class="flex-none xl:hidden">
                    <label for="my-drawer-3" aria-label="open sidebar" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </label>
                </div>
            </div>
