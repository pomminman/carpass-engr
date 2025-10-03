<?php
// maintenance.php
// ส่ง HTTP Status Code 503 Service Unavailable
// ซึ่งจะบอก search engines ว่าเว็บไซต์กำลังปิดปรับปรุงชั่วคราว
header('HTTP/1.1 503 Service Temporarily Unavailable');
header('Status: 503 Service Temporarily Unavailable');
// บอกให้ลองกลับมาใหม่ในอีก 1 ชั่วโมง (3600 วินาที) - สามารถปรับเวลาได้ตามต้องการ
header('Retry-After: 3600'); 
?>
<!DOCTYPE html>
<html lang="th" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

    <!-- Local JS -->
    <script src="/lib/tailwindcss/tailwindcss.js"></script>

    <style>
        body {
            font-family: 'Prompt', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col items-center justify-center text-center p-4">
        <div class="card w-full max-w-lg bg-base-100 shadow-xl border border-base-300/50">
            <div class="card-body items-center text-center">
                <img src="/public/assets/images/CARPASS_logo.png" alt="Logo" class="h-32 w-auto mx-auto mb-4" onerror="this.onerror=null;this.src='https://placehold.co/200x200/CCCCCC/FFFFFF?text=Logo';">
                
                <h1 class="card-title text-2xl sm:text-3xl font-bold text-warning-content whitespace-nowrap">
                    <i class="fas fa-tools mr-2"></i>
                    ปิดปรับปรุงระบบชั่วคราว
                </h1>

                <p class="mt-4 text-base text-gray-600">
                    ขณะนี้เรากำลังปรับปรุงและบำรุงรักษาระบบ<br>เพื่อให้การทำงานมีประสิทธิภาพดียิ่งขึ้น
                </p>
                <p class="text-sm text-gray-500 mt-2">
                    ขออภัยในความไม่สะดวกมา ณ ที่นี้
                </p>

                <div class="mt-6 p-4 bg-base-200 rounded-lg w-full">
                    <p class="text-sm font-semibold">คาดว่าจะแล้วเสร็จและเปิดให้บริการอีกครั้งในเวลาประมาณ</p>
                    <p class="text-xl font-bold text-primary mt-1">12:00 น.</p>
                </div>
                
                 <footer class="text-center text-slate-500 mt-8">
                    <p class="text-xs">Developed by กยข.กช.</p>
                    <p class="text-xs">ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ)</p>
                </footer>
            </div>
        </div>
    </div>
</body>
</html>

