<?php
session_start();

// 1. ถ้าแอดมินล็อกอินอยู่แล้ว ให้ redirect ไปที่ dashboard
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    header("Location: ../home/dashboard.php"); // สมมติว่าหน้า dashboard อยู่ที่นี่
    exit;
}

// 2. จัดการข้อความ error
$login_error = '';
if (isset($_SESSION['admin_login_error'])) {
    $login_error = $_SESSION['admin_login_error'];
    unset($_SESSION['admin_login_error']);
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin ระบบยื่นคำร้องขอบัตรผ่านยานพาหนะ เข้า-ออก ค่ายภาณุรังษี</title>

    <link rel="icon" type="image/png" href="/public/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/public/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/public/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/public/assets/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="carpass engrdept" />
    <link rel="manifest" href="/public/assets/favicon/site.webmanifest" />
    
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f1f5f9; /* slate-100 */
        }
        .login-card {
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
        }
         .alert-soft {
            border-width: 1px;
        }
        .alert-error.alert-soft {
            background-color: #fee2e2;
            border-color: #fca5a5;
            color: #b91c1c;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <!-- Logo and Title -->
            <div class="flex flex-col items-center justify-center mb-6">
                <img src="https://img2.pic.in.th/pic/CARPASS-logo11af8574a9cc9906.png" alt="Logo" class="h-24 w-auto" onerror="this.onerror=null;this.src='https://placehold.co/100x100/CCCCCC/FFFFFF?text=Logo';">
                <h1 class="text-xl font-bold text-slate-700 mt-4">ระบบจัดการคำร้อง (สำหรับเจ้าหน้าที่)</h1>
                <p class="text-sm text-slate-500">กรุณาลงชื่อเข้าใช้เพื่อดำเนินการต่อ</p>
            </div>

            <!-- Login Card -->
            <div class="card bg-base-100 login-card border border-base-300/50">
                <div class="card-body">
                    <form action="../../../controllers/admin/login/process_login.php" method="POST" id="adminLoginForm" novalidate>
                        <div class="space-y-4">
                            <!-- Username Input -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">ชื่อผู้ใช้งาน</span>
                                </label>
                                <label class="input input-sm input-bordered flex items-center gap-2">
                                    <i class="fa-solid fa-user text-slate-400"></i>
                                    <input type="text" name="username" class="grow" placeholder="Username" required />
                                </label>
                            </div>
                            <!-- Password Input -->
                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">รหัสผ่าน</span>
                                </label>
                                <label class="input input-sm input-bordered flex items-center gap-2">
                                    <i class="fa-solid fa-lock text-slate-400"></i>
                                    <input type="password" name="password" class="grow" placeholder="Password" required />
                                </label>
                            </div>
                        </div>

                        <!-- Error Message Display -->
                        <?php if (!empty($login_error)): ?>
                        <div role="alert" class="alert alert-error alert-soft text-xs p-2 mt-4">
                            <div class="flex items-center">
                                <i class="fa-solid fa-circle-xmark mr-2"></i>
                                <span><?php echo htmlspecialchars($login_error); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Submit Button -->
                        <div class="form-control mt-6">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-right-to-bracket"></i>
                                เข้าสู่ระบบ
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <footer class="text-center text-slate-500 mt-8">
                <p class="text-xs">Developed by กยข.กช.</p>
                <p class="text-xs">ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ)</p>
            </footer>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('adminLoginForm');
            form.addEventListener('submit', function(event) {
                let isValid = true;
                form.querySelectorAll('[required]').forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        // You can add visual feedback here if you want
                        input.closest('label').classList.add('input-error');
                    } else {
                        input.closest('label').classList.remove('input-error');
                    }
                });

                if (!isValid) {
                    event.preventDefault();
                    // Optionally show a generic alert
                    // alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
            });
        });
    </script>
</body>
</html>
