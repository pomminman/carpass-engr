<?php
// app/views/admin/layouts/header.php
// This is the shared header for all admin pages.
require_once __DIR__ . '/../shared/auth_check.php';
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการคำร้อง</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; }
        .menu a.active { background-color: #eef2ff; color: #4338ca; }
        .alert-soft { border-width: 1px; }
        .alert-error.alert-soft { background-color: #fee2e2; border-color: #fca5a5; color: #b91c1c; }
        .alert-success.alert-soft { background-color: #dcfce7; border-color: #86efac; color: #166534; }
        th[data-sort-by] { cursor: pointer; user-select: none; }
        th[data-sort-by] .fa-sort, th[data-sort-by] .fa-sort-up, th[data-sort-by] .fa-sort-down {
            color: #9ca3af; margin-left: 0.5rem; transition: color 0.2s ease-in-out;
        }
        th[data-sort-by]:hover .fa-sort { color: #1f2937; }
        th[data-sort-by].sort-asc .fa-sort-up, th[data-sort-by].sort-desc .fa-sort-down { color: #2563eb; }
        #zoomed-image-container { position: relative; }
        #zoomed-image { max-height: 85vh; width: auto; margin: auto; object-fit: contain; }
    </style>
</head>
<body>
    <div class="flex flex-col min-h-screen">
        <!-- Navbar -->
        <div class="navbar bg-base-100 shadow-md sticky top-0 z-30">
            <div class="navbar-start">
                <div class="dropdown">
                    <label tabindex="0" class="btn btn-ghost lg:hidden">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" /></svg>
                    </label>
                    <ul tabindex="0" id="mobile-menu" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                        <li><a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-tachometer-alt w-4"></i> Dashboard</a></li>
                        <li><a href="manage_requests.php" class="<?php echo ($current_page == 'manage_requests.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-signature w-4"></i> จัดการคำร้อง</a></li>
                        <li><a href="manage_users.php" class="<?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>"><i class="fa-solid fa-users-cog w-4"></i> จัดการผู้ใช้</a></li>
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
                    <li><a href="manage_users.php" class="<?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>"><i class="fa-solid fa-users-cog"></i> จัดการผู้ใช้</a></li>
                    <li><a href="manage_admins.php" class="<?php echo ($current_page == 'manage_admins.php') ? 'active' : ''; ?>"><i class="fa-solid fa-user-shield"></i> จัดการเจ้าหน้าที่</a></li>
                </ul>
            </div>
            <div class="navbar-end">
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost btn-circle avatar">
                        <div class="w-10 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                             <i class="fa-solid fa-user text-xl text-primary flex items-center justify-center h-full"></i>
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
