<?php
// app/views/user/login/login.php

session_start();

// 1. ถ้าล็อกอินอยู่แล้ว ให้ redirect ไปที่ home
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: ../home/dashboard.php");
    exit;
}

// 2. จัดการข้อความ error และ success สำหรับ Toastify
$flash_message = '';
$flash_status = '';

if (isset($_SESSION['login_error'])) {
    $flash_message = $_SESSION['login_error'];
    $flash_status = 'error';
    unset($_SESSION['login_error']);
}

if (isset($_SESSION['logout_message'])) {
    $flash_message = $_SESSION['logout_message'];
    $flash_status = 'success';
    unset($_SESSION['logout_message']);
}
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>ระบบยื่นคำร้องขอบัตรผ่านยานพาหนะ เข้า-ออก ค่ายภาณุรังษี</title>

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
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <!-- Local JS -->
    <script src="/lib/tailwindcss/tailwindcss.js"></script>

    <style>
        body {
            font-family: 'Prompt', sans-serif;
        }
        .error-message { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }
    </style>
</head>
<body data-flash-message="<?php echo htmlspecialchars($flash_message); ?>" data-flash-status="<?php echo htmlspecialchars($flash_status); ?>">

    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Left Side: Branding -->
        <div class="w-full md:w-1/2 bg-slate-50 flex flex-col justify-center items-center p-8 sm:p-12 text-center order-1 md:order-1">
            <div class="w-full max-w-md">
                <img src="https://img2.pic.in.th/pic/CARPASS-logo11af8574a9cc9906.png" alt="Logo" class="h-28 sm:h-32 md:h-64 lg:h-80 w-auto mx-auto" onerror="this.onerror=null;this.src='https://placehold.co/400x400/CCCCCC/FFFFFF?text=Logo';">
                <h1 class="font-bold text-lg sm:text-xl md:text-lg lg:text-2xl text-slate-800">ระบบยื่นคำร้องขอบัตรผ่านยานพาหนะ</h1>
                <p class="text-lg sm:text-xl md:text-lg lg:text-2xl text-slate-600 mt-1">เข้า-ออก ค่ายภาณุรังษี</p>
            </div>
        </div>

        <!-- Right Side: Login/Register -->
        <div class="w-full md:w-1/2 bg-white flex flex-col justify-center items-center p-8 sm:p-12 order-2 md:order-2">
            <div class="w-full max-w-sm">
                <h2 class="text-2xl font-bold text-slate-800 mb-6 text-center">เข้าสู่ระบบ</h2>
                
                <form action="../../../controllers/user/login/process_login.php" method="POST" id="loginForm">
                    <div class="form-control w-full mb-4">
                        <label class="input input-sm input-bordered flex items-center gap-2">
                            <i class="fa-solid fa-phone text-slate-400"></i>
                            <input type="tel" id="phone" name="phone" class="grow" placeholder="เบอร์โทรศัพท์" maxlength="12" />
                        </label>
                         <p class="error-message hidden"></p>
                    </div>
                     <div class="form-control w-full">
                        <label class="input input-sm input-bordered flex items-center gap-2">
                            <i class="fa-solid fa-id-card text-slate-400"></i>
                             <input id="national-id" name="national_id" type="tel" class="grow" placeholder="เลขบัตรประชาชน" maxlength="17" />
                        </label>
                         <p class="error-message hidden"></p>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-sm btn-primary w-full">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            เข้าสู่ระบบ
                        </button>
                    </div>
                </form>

                <div class="divider text-slate-400 my-6">ยังไม่มีบัญชี?</div>

                <div class="text-center">
                    <a href="../register/register.php" class="btn btn-sm btn-outline btn-primary w-full">
                        <i class="fa-solid fa-user-plus"></i>
                        สมัครใช้งานที่นี่
                    </a>
                </div>
                <footer class="text-center text-slate-500 mt-8">
                    <p class="text-xs">Developed by กยข.กช.</p>
                    <p class="text-xs">ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ)</p>
                </footer>
            </div>
        </div>
    </div>
    
    <dialog id="successModal" class="modal">
      <div class="modal-box text-center">
        <i class="fa-solid fa-circle-check text-5xl text-success"></i>
        <h3 class="font-bold text-lg mt-4">สมัครสมาชิกสำเร็จ!</h3>
        <p class="py-4">ข้อมูลของท่านถูกบันทึกเรียบร้อยแล้ว<br>กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ</p>
        <div class="modal-action justify-center">
          <form method="dialog">
            <button class="btn btn-success">รับทราบ</button>
          </form>
        </div>
      </div>
    </dialog>

    <!-- Contact Button -->
    <button onclick="contactModal.showModal()" class="fixed bottom-4 right-4 z-50 bg-blue-600 w-12 h-12 rounded-full flex items-center justify-center shadow-lg hover:bg-blue-700 transition-colors duration-300">
        <i class="fa-solid fa-headset text-white text-2xl"></i>
    </button>

    <!-- Contact Modal -->
    <dialog id="contactModal" class="modal">
        <div class="modal-box">
            <form method="dialog">
                <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
            </form>
            <h3 class="font-bold text-lg mb-3"><i class="fa-solid fa-headset mr-2"></i>ติดต่อสอบถาม</h3>
            <div class="space-y-3 text-sm">
                <div class="card bg-base-200 border">
                    <div class="card-body p-3">
                        <div class="space-y-2">
                             <div>
                                <h4 class="font-semibold flex items-center gap-2"><i class="fa-solid fa-building text-primary w-4"></i> สถานที่ติดต่อ</h4>
                                <div class="pl-7 text-base-content/90">
                                    <p>แผนกการข่าวและรักษาความปลอดภัย</p>
                                    <p>กองยุทธการและการข่าว กรมการทหารช่าง (กยข.กช.)</p>
                                    <p>ค่ายภาณุรังษี ต.โคกหม้อ อ.เมือง จ.ราชบุรี 70000</p>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold flex items-center gap-2"><i class="fa-solid fa-clock text-primary w-4"></i> วันและเวลาทำการ</h4>
                                <div class="pl-7 text-base-content/90">
                                    <p>จันทร์ - ศุกร์ (เว้นวันหยุดราชการ), 08:30 - 16:30 น.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card bg-base-200 border">
                    <div class="card-body p-3 space-y-2">
                        <div>
                            <p class="font-semibold">สอบถามเรื่องเอกสารและติดตามสถานะ:</p>
                            <div class="pl-2">
                                <p><i class="fa-solid fa-phone w-4 text-base-content/60"></i> <a href="tel:032337014" class="link link-hover">032-337-014</a> ต่อ 5-3132 (กยข.กช.)</p>
                                <a href="https://lin.ee/NeGjmgs" target="_blank" class="btn btn-xs btn-success no-underline mt-1"><span class="text-white"><i class="fab fa-line"></i> Line: บัตรผ่านยานพาหนะ กรมการทหารช่าง</span></a>
                            </div>
                        </div>
                        <div>
                            <p class="font-semibold">พบปัญหาการใช้งานระบบ:</p>
                            <div class="pl-2">
                                <p>ร.ท. พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ)</p>
                                <p><i class="fa-solid fa-envelope w-4 text-base-content/60"></i> oid.engrdept@gmail.com</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Toastify Notification Function ---
            function showToast(message, type = 'info') {
                const colors = {
                    success: "linear-gradient(to right, #00b09b, #96c93d)",
                    error: "linear-gradient(to right, #ff5f6d, #ffc371)",
                    info: "linear-gradient(to right, #2193b0, #6dd5ed)"
                };
                Toastify({
                    text: message,
                    duration: 3000,
                    newWindow: true,
                    close: true,
                    gravity: "top",
                    position: "right",
                    stopOnFocus: true,
                    style: { background: colors[type] || colors['info'] },
                }).showToast();
            }

            // --- Field Validation & Formatting ---
            function showError(element, message) {
                const parent = element.closest('.form-control');
                const errorElement = parent.querySelector('.error-message');
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
                element.closest('label.input').classList.add('input-error');
            }

            function clearFeedback(element) {
                const parent = element.closest('.form-control');
                const errorElement = parent.querySelector('.error-message');
                errorElement.classList.add('hidden');
                element.closest('label.input').classList.remove('input-error');
            }
            
            function formatInput(input, pattern) {
                const numbers = input.value.replace(/\D/g, '');
                let result = '';
                let patternIndex = 0;
                let numbersIndex = 0;
                while(patternIndex < pattern.length && numbersIndex < numbers.length) {
                    result += pattern[patternIndex] === '-' ? '-' : numbers[numbersIndex++];
                    patternIndex++;
                }
                input.value = result;
            }

            // --- Initial Page Load Actions ---
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status') === 'success') {
                document.getElementById('successModal').showModal();
                const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({path: newUrl}, '', newUrl);
            }

            const flashMessage = document.body.dataset.flashMessage;
            const flashStatus = document.body.dataset.flashStatus;
            if (flashMessage) {
                showToast(flashMessage, flashStatus);
            }

            // --- Form Handling ---
            const loginForm = document.getElementById('loginForm');
            const submitButton = loginForm.querySelector('button[type="submit"]');
            const phoneInput = document.getElementById('phone');
            const nationalIdInput = document.getElementById('national-id');

            phoneInput.addEventListener('input', () => formatInput(phoneInput, 'xxx-xxx-xxxx'));
            nationalIdInput.addEventListener('input', () => formatInput(nationalIdInput, 'x-xxxx-xxxxx-xx-x'));
            [phoneInput, nationalIdInput].forEach(input => input.addEventListener('input', () => clearFeedback(input)));
            
            loginForm.addEventListener('submit', async function(event) {
                event.preventDefault();
                let isValid = true;
                
                if (phoneInput.value.replace(/\D/g, '').length !== 10) {
                    showError(phoneInput, 'กรุณากรอกเบอร์โทรศัพท์ 10 หลัก');
                    isValid = false;
                }
                if (nationalIdInput.value.replace(/\D/g, '').length !== 13) {
                    showError(nationalIdInput, 'กรุณากรอกเลขบัตรประชาชน 13 หลัก');
                    isValid = false;
                }
                if (!isValid) return;

                const originalButtonContent = submitButton.innerHTML;
                submitButton.innerHTML = '<span class="loading loading-spinner loading-sm"></span> กำลังเข้าสู่ระบบ...';
                submitButton.disabled = true;

                try {
                    const formData = new FormData(loginForm);
                    const response = await fetch(loginForm.action, { method: 'POST', body: formData });
                    const result = await response.json();

                    if (result.success) {
                        window.location.href = result.redirect_url;
                    } else {
                        showToast(result.message, 'error');
                    }
                } catch (error) {
                    showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง', 'error');
                } finally {
                     submitButton.innerHTML = originalButtonContent;
                     submitButton.disabled = false;
                }
            });
        });
    </script>
</body>
</html>

