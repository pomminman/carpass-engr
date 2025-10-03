<?php
// app/views/user/layouts/header.php
$page_identifier = str_replace('.php', '', basename($_SERVER['SCRIPT_NAME']));
?>
<!DOCTYPE html>
<html lang="th" data-theme="light" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบยื่นคำร้องขอบัตรผ่านยานพาหนะ - ค่ายภาณุรังษี</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/png" href="/public/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/public/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/public/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/public/assets/favicon/apple-touch-icon.png" />
    <meta name="apple-mobile-web-app-title" content="carpass engrdept" />
    <link rel="manifest" href="/public/assets/favicon/site.webmanifest" />

    <!-- [NEW] Preload critical fonts to prevent FOUT -->
    <link rel="preload" href="/lib/google-fonts-prompt/Prompt-Regular.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/lib/google-fonts-prompt/Prompt-SemiBold.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="/lib/google-fonts-prompt/Prompt-Bold.ttf" as="font" type="font/ttf" crossorigin>

    
    <!-- Local JS -->
    <script src="/lib/jquery/jquery-3.7.1.min.js"></script>
    <script src="/lib/tailwindcss/tailwindcss.js"></script>

    <!-- Fancybox Library (New) -->
    <script src="/lib/fancybox/fancybox.umd.js"></script>
    <link rel="stylesheet" href="/lib/fancybox/fancybox.css" />

    <!-- [NEW] Toastify.js -->
    <script type="text/javascript" src="/lib/toastify-js/toastify-js.js"></script>
    <link rel="stylesheet" type="text/css" href="/lib/toastify-js/toastify.min.css">

    <!-- Local CSS -->
    <link rel="stylesheet" href="/lib/daisyui@4.12.10/dist/full.min.css" type="text/css" />
    <link rel="stylesheet" href="/lib/jquery.Thailand/dist/jquery.Thailand.min.css">
    <link rel="stylesheet" href="/lib/google-fonts-prompt/prompt.css">
    <link rel="stylesheet" href="/lib/fontawesome-free-7.0.1-web/css/all.min.css">

    <!-- Custom Styles -->
    <style>
        body { font-family: 'Prompt', sans-serif; overflow-x: hidden; }
        :root { --rounded-box: 1rem; --rounded-btn: 0.8rem; --rounded-badge: 1.9rem; }
        .alert-soft { border-width: 1px; color: black; }
        .alert-error.alert-soft { background-color: #fee2e2; border-color: #fca5a5; color: #b91c1c; }
        .alert-success.alert-soft { background-color: #dcfce7; border-color: #86efac; color: #166534; }
        .alert-info.alert-soft { background-color: #e0f2fe; border-color: #7dd3fc; color: #0369a1; }
        .alert-warning.alert-soft { background-color: #fef9c3; border-color: #fde047; color: #a16207; }
        .break-words { word-wrap: break-word; overflow-wrap: break-word; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .drawer-content { animation: fadeIn 0.3s ease-in-out; }
        @media (min-width: 1024px) {
            .drawer.lg\:drawer-open .drawer-content { height: 100vh; overflow-y: auto; }
            .drawer.lg\:drawer-open .drawer-side { position: sticky; top: 0; height: 100vh; }
        }
        .modal-fade { transition: opacity 0.25s ease; }
        .modal-fade:not([open]) { opacity: 0; pointer-events: none; }
        .modal-fade .modal-box { transition: transform 0.25s ease, opacity 0.25s ease; transform: translateY(-20px); opacity: 0; }
        .modal-fade[open] .modal-box { transform: translateY(0); opacity: 1; }
    </style>
</head>
<body class="bg-base-200" data-page="<?php echo htmlspecialchars($page_identifier); ?>" data-flash-message="<?php echo isset($_SESSION['request_message']) ? htmlspecialchars($_SESSION['request_message']) : ''; ?>" data-flash-status="<?php echo isset($_SESSION['request_status']) ? htmlspecialchars($_SESSION['request_status']) : ''; ?>">
    <?php unset($_SESSION['request_message'], $_SESSION['request_status']); ?>
    <div class="drawer lg:drawer-open">
        <input id="my-drawer-2" type="checkbox" class="drawer-toggle" />
        <div class="drawer-content flex flex-col items-center">
            <!-- Navbar (for mobile) -->
            <div class="w-full navbar bg-base-100 lg:hidden sticky top-0 z-30 shadow">
                <div class="flex-1">
                     <a href="dashboard.php" class="text-base font-bold flex items-center gap-2">
                         <img src="/public/assets/images/CARPASS_logo.png" alt="Logo" class="h-12 w-12">
                         <div>
                             <span class="whitespace-nowrap text-sm">บัตรผ่านยานพาหนะ</span>
                             <span class="text-xs font-normal text-base-content/60 block">ค่ายภาณุรังษี</span>
                         </div>
                     </a>
                </div>
                <div class="flex-none">
                    <label for="my-drawer-2" aria-label="open sidebar" class="btn btn-square btn-ghost">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="inline-block w-6 h-6 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </label>
                </div>
            </div>
            <main class="w-full max-w-full px-4 md:px-4 lg:px-6 py-4 md:py-6">

