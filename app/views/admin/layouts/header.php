<?php
// app/views/admin/layouts/header.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../shared/auth_check.php';

$flash_message = '';
$flash_status = '';
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    $flash_status = $_SESSION['flash_status'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_status']);
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการคำร้อง</title>

    <!-- Favicons -->
    <link rel="icon" type="image/png" href="/public/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/public/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/public/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/public/assets/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="carpass engrdept" />
    <link rel="manifest" href="/public/assets/favicon/site.webmanifest" />

    
    <!-- Local JS -->
    <script src="/lib/jquery/jquery-3.7.1.min.js"></script>
    <script src="/lib/tailwindcss/tailwindcss.js"></script>

    <!-- Fancybox Library (New) -->
    <script src="/lib/fancybox/fancybox.umd.js"></script>
    <link rel="stylesheet" href="/lib/fancybox/fancybox.css" />

    <!-- [NEW] Select2 Library -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Local CSS -->
    <link rel="stylesheet" href="/lib/daisyui@4.12.10/dist/full.min.css" type="text/css" />
    <link rel="stylesheet" href="/lib/jquery.Thailand/dist/jquery.Thailand.min.css">
    <link rel="stylesheet" href="/lib/google-fonts-prompt/prompt.css">
    <link rel="stylesheet" href="/lib/fontawesome-free-7.0.1-web/css/all.min.css">

    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; }
        .menu a.active { background-color: #eef2ff; color: #4338ca; }
        .alert-soft { border-width: 1px; }
        .alert-error.alert-soft { background-color: #fee2e2; border-color: #fca5a5; color: #b91c1c; }
        .alert-success.alert-soft { background-color: #dcfce7; border-color: #86efac; color: #166534; }
        th[data-sort-by] { cursor: pointer; user-select: none; }
        th[data-sort-by] .fa-sort, th[data-sort-by] .fa-sort-up, th[data-sort-by] .fa-sort-down { color: #9ca3af; margin-left: 0.5rem; transition: color 0.2s ease-in-out; }
        th[data-sort-by]:hover .fa-sort { color: #1f2937; }
        th[data-sort-by].sort-asc .fa-sort-up, th[data-sort-by].sort-desc .fa-sort-down { color: #2563eb; }
        .modal-fade { transition: opacity 0.25s ease; }
        .modal-fade:not([open]) { opacity: 0; pointer-events: none; }
        .modal-fade .modal-box { transition: transform 0.25s ease, opacity 0.25s ease; transform: translateY(-20px); opacity: 0; }
        .modal-fade[open] .modal-box { transform: translateY(0); opacity: 1; }
        .twitter-typeahead .tt-menu { max-height: 200px; overflow-y: auto; display: block; scrollbar-width: thin; scrollbar-color: #a0aec0 #e2e8f0; border: 1px solid #ccc; border-radius: 0.25rem; z-index: 9999 !important; }
        .twitter-typeahead .tt-menu::-webkit-scrollbar { width: 8px; }
        .twitter-typeahead .tt-menu::-webkit-scrollbar-track { background: #e2e8f0; }
        .twitter-typeahead .tt-menu::-webkit-scrollbar-thumb { background-color: #a0aec0; border-radius: 4px; border: 2px solid #e2e8f0; }
        .tt-suggestion { padding: 8px 12px; border-bottom: 1px solid #eee; }
        .tt-cursor { background-color: #f0f2f5; }

        /* [NEW] Select2 Custom Styles */
        .select2-container--default .select2-selection--single {
            background-color: #fff;
            border: 1px solid #d1d5db; /* border-gray-300 */
            border-radius: 0.5rem; /* rounded-lg */
            height: 2.5rem; /* h-10 */
            padding-top: 4px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 2.375rem;
        }
        .select2-dropdown {
            border-color: #d1d5db;
        }
        .select2-container .select2-selection--single .select2-selection__rendered {
            padding-left: 0.75rem;
        }
        .select2-container--open .select2-dropdown--below {
            border-top: 1px solid #d1d5db;
        }
    </style>
</head>
<body data-flash-message="<?php echo htmlspecialchars($flash_message); ?>" data-flash-status="<?php echo htmlspecialchars($flash_status); ?>">
    
    <div id="alert-container" class="toast toast-top toast-center sm:toast-end z-50"></div>

    <div class="flex flex-col min-h-screen">
        <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
            <div class="navbar-start">
                <div class="dropdown">
                    <label tabindex="0" class="btn btn-ghost lg:hidden"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" /></svg></label>
                    <ul tabindex="0" id="mobile-menu" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                        <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-tachometer-alt w-4"></i> Dashboard</a></li>
                        <li><a href="manage_requests.php" class="<?php echo ($current_page == 'manage_requests.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-signature w-4"></i> จัดการคำร้อง</a></li>
                        <li><a href="manage_users.php" class="<?php echo in_array($current_page, ['manage_users.php', 'view_user.php', 'add_user.php']) ? 'active' : ''; ?>"><i class="fa-solid fa-users-cog w-4"></i> จัดการผู้ใช้</a></li>
                        <li><a href="manage_admins.php" class="<?php echo ($current_page == 'manage_admins.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-shield w-4"></i> จัดการเจ้าหน้าที่</a></li>
                    </ul>
                </div>
                 <div class="flex items-center gap-2 ml-2">
                    <img src="/public/assets/images/CARPASS logo.png" alt="Logo" class="h-12 w-12">
                    <div>
                        <div class="font-bold text-sm sm:text-base whitespace-nowrap">ระบบจัดการ</div>
                        <div class="text-xs font-normal text-gray-500 whitespace-nowrap">สำหรับเจ้าหน้าที่</div>
                    </div>
                </div>
            </div>
            <div class="navbar-center hidden lg:flex">
                <ul class="menu menu-horizontal px-1" id="desktop-menu">
                    <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="manage_requests.php" class="<?php echo ($current_page == 'manage_requests.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-signature"></i> จัดการคำร้อง</a></li>
                    <li><a href="manage_users.php" class="<?php echo in_array($current_page, ['manage_users.php', 'view_user.php', 'add_user.php']) ? 'active' : ''; ?>"><i class="fa-solid fa-users-cog"></i> จัดการผู้ใช้</a></li>
                    <li><a href="manage_admins.php" class="<?php echo ($current_page == 'manage_admins.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-shield"></i> จัดการเจ้าหน้าที่</a></li>
                </ul>
            </div>
            <div class="navbar-end">
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                        <div class="w-10 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2 flex items-center justify-center bg-base-300">
                             <i class="fa-solid fa-user text-xl text-slate-500 mt-1"></i>
                        </div>
                    </label>
                    <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-64 space-y-1">
                         <li class="p-2 text-center">
                            <div class="font-semibold"><?php echo $admin_info['name'] . ' ' . $admin_info['lastname']; ?></div>
                            <div class="text-xs text-slate-500">สังกัด: <?php echo $admin_info['department']; ?></div>
                            <div class="text-xs text-slate-500">ระดับสิทธิ์: <?php echo ucfirst($admin_info['role']); ?></div>
                            <div class="text-xs text-slate-500">สิทธิ์เข้าถึง: <?php echo $admin_info['view_permission_text']; ?></div>
                         </li>
                         <div class="divider my-0"></div>
                         <li><a href="manage_admins.php"><i class="fa-solid fa-user-pen"></i> แก้ไขข้อมูลส่วนตัว</a></li>
                         <li><a href="../../../controllers/admin/logout/logout.php" class="text-error"><i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ</a></li>
                    </ul>
                </div>
            </div>
        </div>

