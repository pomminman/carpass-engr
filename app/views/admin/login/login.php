<?php
// app/views/admin/login/login.php

session_start();

// 1. ถ้าแอดมินล็อกอินอยู่แล้ว ให้ redirect ไปที่ dashboard
if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true) {
    header("Location: ../home/dashboard.php");
    exit;
}

// 2. จัดการข้อความ error
$login_error = '';
if (isset($_SESSION['admin_login_error'])) {
    $login_error = $_SESSION['admin_login_error'];
    unset($_SESSION['admin_login_error']);
}

// [NEW] จัดการข้อความ Flash Message (เช่น จากการ Logout)
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
    <title>Admin ระบบยื่นคำร้องขอบัตรผ่านยานพาหนะ เข้า-ออก ค่ายภาณุรังษี</title>

    <!-- Favicons -->
    <link rel="icon" type="image/png" href="/public/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/public/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/public/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/public/assets/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="carpass engrdept" />
    <link rel="manifest" href="/public/assets/favicon/site.webmanifest" />

    <!-- Local CSS -->
    <link rel="stylesheet" href="/lib/daisyui@4.12.10/dist/full.min.css" type="text/css" />
    <link rel="stylesheet" href="/lib/google-fonts-prompt/prompt.css">
    <link rel="stylesheet" href="/lib/fontawesome-free-7.0.1-web/css/all.min.css">
    
    <!-- [NEW] Toastify.js Library -->
    <link rel="stylesheet" type="text/css" href="/lib/toastify-js/toastify.min.css">
    <script type="text/javascript" src="/lib/toastify-js/toastify-js.js"></script>

    <!-- Local JS -->
    <script src="/lib/tailwindcss/tailwindcss.js"></script>

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
        .error-message {
            color: #ef4444; /* text-red-500 */
        }
    </style>
</head>
<body data-flash-message="<?php echo htmlspecialchars($flash_message); ?>" data-flash-status="<?php echo htmlspecialchars($flash_status); ?>">
    <div class="min-h-screen flex flex-col items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <!-- Logo and Title -->
            <div class="flex flex-col items-center justify-center mb-4">
                <img src="/public/assets/images/CARPASS_logo.png" alt="Logo" class="h-32 w-auto" onerror="this.onerror=null;this.src='https://placehold.co/128x128/CCCCCC/FFFFFF?text=Logo';">
                <h1 class="text-xl font-bold text-slate-700 mt-2">ระบบจัดการคำร้อง (สำหรับเจ้าหน้าที่)</h1>
                <p class="text-sm text-slate-500">กรุณาลงชื่อเข้าใช้เพื่อดำเนินการต่อ</p>
            </div>

            <!-- Login Card -->
            <div class="card bg-base-100 login-card border border-base-300/50">
                <div class="card-body">
                    <form action="../../../controllers/admin/login/process_login.php" method="POST" id="adminLoginForm" novalidate>
                        <div class="space-y-2">
                            <!-- Username Input -->
                            <div class="form-control">
                                <label class="label py-1">
                                    <span class="label-text">ชื่อผู้ใช้งาน</span>
                                </label>
                                <label class="input input-sm input-bordered flex items-center gap-2">
                                    <i class="fa-solid fa-user text-slate-400"></i>
                                    <input type="text" name="username" class="grow" placeholder="Username" required />
                                </label>
                                <p class="text-xs mt-1 h-4 error-message hidden"></p>
                            </div>
                            <!-- Password Input -->
                            <div class="form-control">
                                <label class="label py-1">
                                    <span class="label-text">รหัสผ่าน</span>
                                </label>
                                <label class="input input-sm input-bordered flex items-center gap-2">
                                    <i class="fa-solid fa-lock text-slate-400"></i>
                                    <input type="password" name="password" class="grow" placeholder="Password" required />
                                </label>
                                <p class="text-xs mt-1 h-4 error-message hidden"></p>
                            </div>
                        </div>

                        <!-- Server-side Error Message Display -->
                        <?php if (!empty($login_error)): ?>
                        <div id="server-error-container" role="alert" class="alert alert-error alert-soft text-xs p-2 mt-4">
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
            <footer class="text-center text-slate-500 mt-6">
                <p class="text-xs">Developed by กยข.กช.</p>
                <p class="text-xs">ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ)</p>
            </footer>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('adminLoginForm');
            
            // [NEW] Trigger Toastify for flash messages (e.g., from logout)
            const flashMessage = document.body.dataset.flashMessage;
            const flashStatus = document.body.dataset.flashStatus;
            if (flashMessage && flashStatus) {
                const colors = {
                    success: "linear-gradient(to right, #00b09b, #96c93d)",
                    error: "linear-gradient(to right, #ff5f6d, #ffc371)",
                    info: "linear-gradient(to right, #2193b0, #6dd5ed)",
                    warning: "linear-gradient(to right, #f39c12, #f1c40f)"
                };
                Toastify({
                    text: flashMessage,
                    duration: 3000,
                    newWindow: true,
                    close: true,
                    gravity: "top",
                    position: "right",
                    stopOnFocus: true,
                    style: {
                        background: colors[flashStatus] || colors['info'],
                    },
                }).showToast();
            }

            const showError = (input, message) => {
                const formControl = input.closest('.form-control');
                const errorElement = formControl.querySelector('.error-message');
                const inputWrapper = input.closest('label');
                if (inputWrapper) inputWrapper.classList.add('input-error');
                if (errorElement) {
                    errorElement.textContent = message;
                    errorElement.classList.remove('hidden');
                }
            };

            const clearError = (input) => {
                const formControl = input.closest('.form-control');
                const errorElement = formControl.querySelector('.error-message');
                const inputWrapper = input.closest('label');
                if (inputWrapper) inputWrapper.classList.remove('input-error');
                if (errorElement) {
                    errorElement.textContent = '';
                    errorElement.classList.add('hidden');
                }
            };

            form.addEventListener('submit', function(event) {
                let isValid = true;
                const serverError = document.getElementById('server-error-container');
                if (serverError) {
                    serverError.style.display = 'none';
                }

                form.querySelectorAll('[required]').forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        showError(input, 'กรุณากรอกข้อมูล');
                    } else {
                        clearError(input);
                    }
                });

                if (!isValid) {
                    event.preventDefault();
                }
            });

            form.querySelectorAll('[required]').forEach(input => {
                input.addEventListener('input', () => clearError(input));
            });
        });
    </script>
</body>
</html>

