<?php
// app/views/admin/layouts/footer.php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
    </div> 

    <!-- MODALS -->
    <dialog id="loading_modal" class="modal modal-middle"><div class="modal-box text-center"><span class="loading loading-spinner loading-lg text-primary"></span><h3 class="font-bold text-lg mt-4">กรุณารอสักครู่...</h3></div></dialog>
    
    <dialog id="details_modal" class="modal modal-fade">
        <div class="modal-box w-11/12 max-w-7xl">
            <form method="dialog"><button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2 z-10">✕</button></form>
            
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between pb-2 border-b">
                <div>
                    <h3 id="modal-header-license" class="font-bold text-lg"></h3>
                    <p id="modal-header-vehicle" class="text-sm text-base-content/70"></p>
                </div>
                <div id="modal-header-status-badge" class="mt-2 sm:mt-0"></div>
            </div>

            <div class="py-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                
                <div class="flex flex-col gap-4">
                    <div class="card bg-base-100 shadow-sm border h-full"><div class="card-body p-4">
                        <h4 class="font-semibold text-base border-b pb-1 mb-2">ข้อมูลส่วนตัว</h4>
                        
                        <div class="flex items-center gap-4">
                            <a id="modal-user-photo-link" href="#" data-fancybox="request-gallery" data-caption="">
                                <div class="avatar">
                                    <div class="w-24 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2">
                                        <img id="modal-user-photo" src="https://placehold.co/100x100/e2e8f0/475569?text=..." />
                                    </div>
                                </div>
                            </a>
                            <div class="flex-grow">
                                <h4 id="modal-user-name" class="font-semibold text-base"></h4>
                                <div id="modal-user-type-badge" class="mt-1"></div>
                            </div>
                        </div>

                        <div class="space-y-2 mt-3">
                            <!-- [REVISED] Added flex and items-start for consistent alignment -->
                            <div class="flex items-start"><strong class="font-medium text-base-content/70 w-24 flex-shrink-0">เบอร์โทร:</strong> <span id="modal-user-phone"></span></div>
                            <div class="flex items-start"><strong class="font-medium text-base-content/70 w-24 flex-shrink-0">เลขบัตร ปชช.:</strong> <span id="modal-user-nid"></span></div>
                            <div class="flex items-start"><strong class="font-medium text-base-content/70 w-24 flex-shrink-0">ที่อยู่:</strong> <span id="modal-user-address" class="break-words"></span></div>
                        </div>
                        <div id="modal-work-info-container" class="hidden space-y-2 pt-2 border-t mt-2">
                            <div class="flex items-start"><strong class="font-medium text-base-content/70 w-24 flex-shrink-0">สังกัด:</strong> <span id="modal-user-department"></span></div>
                            <div class="flex items-start"><strong class="font-medium text-base-content/70 w-24 flex-shrink-0">ตำแหน่ง:</strong> <span id="modal-user-position"></span></div>
                            <div class="flex items-start"><strong class="font-medium text-base-content/70 w-24 flex-shrink-0">เลขบัตร ขรก.:</strong> <span id="modal-user-official-id"></span></div>
                        </div>
                        <div id="modal-creator-info-container" class="hidden pt-2 border-t mt-2">
                            <p class="text-xs text-center text-slate-500">
                                <i class="fa-solid fa-user-pen mr-1"></i>
                                สร้างโดย: <strong id="modal-creator-name" class="font-medium"></strong>
                            </p>
                        </div>
                    </div></div>
                </div>

                <div class="flex flex-col gap-4">
                    <div class="card bg-base-100 shadow-sm border"><div class="card-body p-4">
                        <h4 class="font-semibold text-base border-b pb-1 mb-2">ข้อมูลยานพาหนะ</h4>
                        <div class="space-y-1">
                            <div class="flex items-start"><strong class="font-medium text-base-content/70 w-28 flex-shrink-0">ประเภทรถ:</strong> <span id="modal-vehicle-type"></span></div>
                            <div class="flex items-start"><strong class="font-medium text-base-content/70 w-28 flex-shrink-0">ประเภทบัตร:</strong> <span id="modal-card-type"></span></div>
                            <div class="flex items-start"><strong class="font-medium text-base-content/70 w-28 flex-shrink-0">สีรถ:</strong> <span id="modal-vehicle-color"></span></div>
                            <div class="flex items-start"><strong class="font-medium text-base-content/70 w-28 flex-shrink-0">วันสิ้นอายุภาษี:</strong> <span id="modal-tax-expiry"></span></div>
                            <div class="flex items-start"><strong class="font-medium text-base-content/70 w-28 flex-shrink-0">การครอบครอง:</strong> <span id="modal-owner-details" class="break-words"></span></div>
                        </div>
                    </div></div>
                    
                    <div id="modal-rejection-info-container" class="hidden card bg-error/10 border border-error/20 shadow-sm"><div class="card-body p-4">
                        <h4 class="font-semibold text-error-content border-b border-error/30 pb-1 mb-2">เหตุผลที่ไม่ผ่าน</h4>
                        <p id="modal-rejection-reason" class="text-error-content whitespace-pre-wrap"></p>
                    </div></div>

                    <div id="modal-qrcode-container" class="hidden card bg-success/10 border border-success/20 shadow-sm"><div class="card-body p-4 items-center">
                         <h4 class="font-semibold text-success-content border-b border-success/30 pb-1 mb-2 w-full text-center">บัตรผ่าน (QR Code)</h4>
                         <img id="modal-qrcode-img" src="" class="w-40 h-40 border-4 border-base-300 p-1 rounded-lg">
                    </div></div>
                </div>

                <div class="flex flex-col gap-4">
                    <div class="card bg-base-100 shadow-sm border h-full"><div class="card-body p-4">
                        <h4 class="font-semibold text-base border-b pb-1 mb-2">หลักฐานประกอบ</h4>
                        <div class="grid grid-cols-2 gap-2">
                            <a class="modal-gallery-item" data-fancybox="request-gallery" data-base-caption="สำเนาทะเบียนรถ"><img id="modal-evidence-reg" class="w-full rounded-lg border bg-white aspect-video object-contain cursor-pointer hover:opacity-80 transition-opacity"/></a>
                            <a class="modal-gallery-item" data-fancybox="request-gallery" data-base-caption="ป้ายภาษี"><img id="modal-evidence-tax" class="w-full rounded-lg border bg-white aspect-video object-contain cursor-pointer hover:opacity-80 transition-opacity"/></a>
                            <a class="modal-gallery-item" data-fancybox="request-gallery" data-base-caption="รูปถ่ายด้านหน้า"><img id="modal-evidence-front" class="w-full rounded-lg border bg-white aspect-video object-contain cursor-pointer hover:opacity-80 transition-opacity"/></a>
                            <a class="modal-gallery-item" data-fancybox="request-gallery" data-base-caption="รูปถ่ายด้านหลัง"><img id="modal-evidence-rear" class="w-full rounded-lg border bg-white aspect-video object-contain cursor-pointer hover:opacity-80 transition-opacity"/></a>
                        </div>
                    </div></div>
                </div>
            </div>

            <div id="modal-action-buttons" class="modal-action"></div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>

    <dialog id="reject_reason_modal" class="modal modal-fade"><div class="modal-box"><h3 class="font-bold text-lg">ระบุเหตุผลที่ปฏิเสธคำร้อง</h3><form id="rejectReasonForm"><div class="py-4"><textarea id="rejection_reason_text" class="textarea textarea-bordered w-full" rows="3" placeholder="ตัวอย่าง: เอกสารไม่ชัดเจน" required></textarea></div><div class="modal-action"><button type="button" class="btn btn-sm btn-ghost" onclick="reject_reason_modal.close()">ยกเลิก</button><button type="submit" class="btn btn-sm btn-error">ยืนยันการปฏิเสธ</button></div></form></div><form method="dialog" class="modal-backdrop"><button>close</button></form></dialog>
    <dialog id="confirmation_modal" class="modal modal-fade"><div class="modal-box"><h3 id="confirmation-modal-title" class="font-bold text-lg"></h3><p id="confirmation-modal-text" class="py-4"></p><div class="modal-action"><button type="button" class="btn btn-sm" onclick="confirmation_modal.close()">ยกเลิก</button><button id="confirm-action-btn" class="btn btn-sm">ยืนยัน</button></div></div><form method="dialog" class="modal-backdrop"><button>close</button></form></dialog>
    <dialog id="result_modal" class="modal modal-fade"><div class="modal-box w-11/12 max-w-lg"><div id="result-modal-header" class="flex justify-between items-start gap-4 p-4"><div class="flex-grow"><h3 id="result-modal-title" class="font-bold text-lg flex items-center"></h3><p id="result-modal-subtitle" class="text-sm text-base-content/70"></p></div><button id="result-modal-close-btn-x" class="btn btn-sm btn-circle btn-ghost">✕</button></div><div class="p-4"><div id="result-modal-output" class="space-y-2"></div><div class="divider"></div><div class="text-xs space-y-1"><p><strong>ผู้ยื่น:</strong> <span id="result-modal-user-name"></span></p><p><strong>ที่อยู่:</strong> <span id="result-modal-user-address"></span></p><p><strong>ทะเบียน:</strong> <span id="result-modal-license"></span></p><p><strong>ยานพาหนะ:</strong> <span id="result-modal-vehicle"></span></p></div></div><div class="modal-action bg-base-200/60 p-2"><button id="result-modal-close-btn" class="btn btn-sm btn-primary">ปิดและรีเฟรชหน้า</button></div></div></dialog>
    <dialog id="stats_modal" class="modal modal-fade"><div class="modal-box"><h3 class="font-bold text-lg">สถิติคำร้อง</h3><p class="py-2 text-sm">สรุปจำนวนคำร้องทั้งหมด (ตามตัวกรองที่เลือก)</p><div id="stats-cards-container" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2"></div><div class="modal-action"><form method="dialog"><button class="btn btn-sm">ปิด</button></form></div></div><form method="dialog" class="modal-backdrop"><button>close</button></form></dialog>
    
    <footer class="fixed bottom-0 left-0 right-0 bg-base-200/80 backdrop-blur-sm text-base-content shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] p-1 text-center z-40"><p class="text-[10px] sm:text-xs whitespace-nowrap">Developed by ร.ท.พรหมินทร์ อินทมาตย์ (ผู้พัฒนาระบบ/กยข.กช.)</p></footer>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="/lib/jquery.Thailand/dependencies/JQL.min.js"></script>
    <script src="/lib/jquery.Thailand/dependencies/typeahead.bundle.js"></script>
    <script src="/lib/jquery.Thailand/jquery.Thailand.js"></script>

    <script src="/public/assets/js/admin/admin_core.js?v=<?php echo time(); ?>"></script>
    <?php
        $current_page = basename($_SERVER['PHP_SELF']);
        $page_script_path = '';
        switch ($current_page) {
            case 'manage_requests.php': $page_script_path = '/public/assets/js/admin/admin_manage_requests.js'; break;
            case 'manage_admins.php': $page_script_path = '/public/assets/js/admin/admin_manage_admins.js'; break;
            case 'view_user.php': $page_script_path = '/public/assets/js/admin/admin_view_user.js'; break;
            case 'add_user.php': $page_script_path = '/public/assets/js/admin/admin_add_user.js'; break;
            case 'add_request.php': $page_script_path = '/public/assets/js/admin/admin_add_request.js'; break;
        }
        if (!empty($page_script_path)) { echo '<script src="' . $page_script_path . '?v=' . time() . '"></script>'; }
    ?>
</body>
</html>

