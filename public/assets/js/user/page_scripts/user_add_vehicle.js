// public/assets/js/user/page_scripts/user_add_vehicle.js
CarPassApp.registerPageScript('add_vehicle', function(app) {
    const form = document.getElementById('addVehicleForm');
    if (!form) return;

    const elements = {
        checkSection: document.getElementById('vehicle-check-section'),
        detailsSection: document.getElementById('vehicle-details-section'),
        checkBtn: document.getElementById('check-vehicle-btn'),
        submitBtn: document.getElementById('submit-request-btn'),
        backBtn: document.getElementById('back-to-step1-btn'),
        loadingModal: document.getElementById('loading_modal'),
        reviewModal: document.getElementById('review_request_modal'),
        finalSubmitBtn: document.getElementById('final-submit-btn'),
        step1Indicator: document.getElementById('step1-indicator'),
        step2Indicator: document.getElementById('step2-indicator'),
    };

    const setStep = (stepNumber) => {
        const activeClasses = ['bg-primary', 'text-primary-content'];
        const inactiveClasses = ['bg-base-200', 'text-base-content/60'];
        elements.step1Indicator.classList.remove(...activeClasses, ...inactiveClasses);
        elements.step2Indicator.classList.remove(...activeClasses, ...inactiveClasses);
        if (stepNumber === 1) {
            elements.step1Indicator.classList.add(...activeClasses);
            elements.step2Indicator.classList.add(...inactiveClasses);
            elements.checkSection.classList.remove('hidden');
            elements.detailsSection.classList.add('hidden');
        } else {
            elements.step1Indicator.classList.add(...inactiveClasses);
            elements.step2Indicator.classList.add(...activeClasses);
            elements.checkSection.classList.add('hidden');
            elements.detailsSection.classList.remove('hidden');
        }
    };

    const attachValidationListeners = (container) => {
        const validationHandler = (event) => {
            const field = event.target;
            if (field.closest('.form-control')) {
                app.validateField(field);
            }
        };
        container.addEventListener('input', validationHandler);
        container.addEventListener('change', validationHandler);
    };
    
    attachValidationListeners(elements.checkSection);
    attachValidationListeners(elements.detailsSection);

    elements.checkBtn.addEventListener('click', async () => {
        let isStep1Valid = true;
        const fieldsToCheck = elements.checkSection.querySelectorAll('input, select');
        fieldsToCheck.forEach(field => {
            if (!app.validateField(field)) isStep1Valid = false;
        });

        if (!isStep1Valid) {
            app.showAlert('กรุณากรอกข้อมูลในขั้นตอนที่ 1 ให้ถูกต้อง', 'error');
            return;
        }

        elements.loadingModal.showModal();
        try {
            const response = await fetch('../../../controllers/user/vehicle/check_vehicle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    license_plate: document.getElementById('check-license-plate').value,
                    province: document.getElementById('check-license-province').value
                }),
            });
            const result = await response.json();
            elements.loadingModal.close();

            if (result.exists) {
                app.showAlert('ยานพาหนะนี้มีคำร้องอยู่ในระบบสำหรับรอบปัจจุบันแล้ว', 'error');
            } else {
                const vehicleType = document.getElementById('check-vehicle-type').value;
                document.getElementById('display-vehicle-type').textContent = vehicleType;
                document.getElementById('display-license-plate').textContent = document.getElementById('check-license-plate').value;
                document.getElementById('display-license-province').textContent = document.getElementById('check-license-province').value;
                form.querySelector('input[name="vehicle_type"]').value = vehicleType;
                form.querySelector('input[name="license_plate"]').value = document.getElementById('check-license-plate').value;
                form.querySelector('input[name="license_province"]').value = document.getElementById('check-license-province').value;
                
                const typeIcon = document.getElementById('display-vehicle-type-icon');
                if (typeIcon) typeIcon.className = vehicleType === 'รถยนต์' ? 'fa-solid fa-car-side text-3xl opacity-80' : 'fa-solid fa-motorcycle text-3xl opacity-80';
                
                setStep(2);
            }
        } catch (error) {
            elements.loadingModal.close();
            app.showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        }
    });

    // --- [FIXED] Restored full implementation of populateRequestReviewModal ---
    const populateRequestReviewModal = () => {
        const summaryContent = elements.reviewModal.querySelector('#summary-content');
        const formData = new FormData(form);
        const formatFullThaiDate = (dateString) => {
            if (!dateString) return '-';
            const date = new Date(dateString);
            const thaiMonths = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
            return `${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}`;
        };
        const pickupDateFormatted = formatFullThaiDate(form.dataset.pickupDate);
        const getSelectText = (name) => { const select = form.querySelector(`select[name="${name}"]`); return select ? select.options[select.selectedIndex].text : '-'; };
        const vehicleType = document.getElementById('display-vehicle-type').textContent;
        const licensePlate = document.getElementById('display-license-plate').textContent;
        const licenseProvince = document.getElementById('display-license-province').textContent;
        const brand = getSelectText('vehicle_brand');
        const model = formData.get('vehicle_model');
        const color = formData.get('vehicle_color');
        const taxDate = `${formData.get('tax_day')} ${getSelectText('tax_month')} ${formData.get('tax_year')}`;
        const ownerType = getSelectText('owner_type');
        const otherOwnerName = formData.get('other_owner_name');
        const otherOwnerRelation = formData.get('other_owner_relation');
        const regCopySrc = document.getElementById('reg-copy-preview').src;
        const taxStickerSrc = document.getElementById('tax-sticker-preview').src;
        const frontViewSrc = document.getElementById('front-view-preview').src;
        const rearViewSrc = document.getElementById('rear-view-preview').src;
        let ownerHtml = `<div><strong>ความเป็นเจ้าของ:</strong> ${ownerType}</div>`;
        if (ownerType === 'รถคนอื่น') {
            ownerHtml += `<div><strong>ชื่อเจ้าของ:</strong> ${otherOwnerName || '-'}</div>`;
            ownerHtml += `<div><strong>เกี่ยวข้องเป็น:</strong> ${otherOwnerRelation || '-'}</div>`;
        }
        const html = `<div class="grid grid-cols-1 md:grid-cols-2 gap-6"><div class="space-y-4"><div><div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">ข้อมูลยานพาหนะ</div><div class="p-3 bg-base-200 rounded-box text-sm space-y-1"><div><strong>ประเภท:</strong> ${vehicleType}</div><div><strong>ทะเบียน:</strong> ${licensePlate} ${licenseProvince}</div><div><strong>ยี่ห้อ/รุ่น:</strong> ${brand} / ${model}</div><div><strong>สี:</strong> ${color}</div><div><strong>วันสิ้นอายุภาษี:</strong> ${taxDate}</div></div></div><div><div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">ข้อมูลเจ้าของ</div><div class="p-3 bg-base-200 rounded-box text-sm space-y-1">${ownerHtml}</div></div></div><div class="space-y-2"><div class="font-bold text-base-content/70 text-xs uppercase tracking-wider mb-1">หลักฐานประกอบ</div><div class="grid grid-cols-2 gap-3"><div class="card bg-base-200/50 p-2 border"><div class="flex items-center justify-center h-28 overflow-hidden rounded-md bg-white"><img src="${regCopySrc}" class="max-w-full max-h-full object-contain"></div><p class="text-xs text-center font-semibold mt-1">สำเนาทะเบียนรถ</p></div><div class="card bg-base-200/50 p-2 border"><div class="flex items-center justify-center h-28 overflow-hidden rounded-md bg-white"><img src="${taxStickerSrc}" class="max-w-full max-h-full object-contain"></div><p class="text-xs text-center font-semibold mt-1">ป้ายภาษี</p></div><div class="card bg-base-200/50 p-2 border"><div class="flex items-center justify-center h-28 overflow-hidden rounded-md bg-white"><img src="${frontViewSrc}" class="max-w-full max-h-full object-contain"></div><p class="text-xs text-center font-semibold mt-1">รูปถ่ายด้านหน้า</p></div><div class="card bg-base-200/50 p-2 border"><div class="flex items-center justify-center h-28 overflow-hidden rounded-md bg-white"><img src="${rearViewSrc}" class="max-w-full max-h-full object-contain"></div><p class="text-xs text-center font-semibold mt-1">รูปถ่ายด้านหลัง</p></div></div></div><div class="md:col-span-2 alert alert-info alert-soft mt-4"><i class="fa-solid fa-calendar-check"></i><div><h3 class="font-bold">วันที่คาดว่าจะได้รับบัตร</h3><div class="text-sm">หากคำร้องได้รับการอนุมัติ ท่านจะสามารถรับบัตรได้ตั้งแต่วันที่ <strong>${pickupDateFormatted}</strong> เป็นต้นไป</div></div></div></div>`;
        summaryContent.innerHTML = html;
    };

    elements.submitBtn.addEventListener('click', () => {
        let isAllValid = true;
        form.querySelectorAll('[required]').forEach(field => {
            if (field.offsetParent !== null) {
                if (!app.validateField(field)) isAllValid = false;
            }
        });

        const dateFields = [form.querySelector('select[name="tax_day"]'), form.querySelector('select[name="tax_month"]'), form.querySelector('select[name="tax_year"]')];
        if (dateFields[0].offsetParent !== null) {
            const allDatesSelected = dateFields.every(field => field.value);
            if (!allDatesSelected) {
                isAllValid = false;
                const firstEmptyDate = dateFields.find(field => !field.value);
                if (firstEmptyDate) app.showError(firstEmptyDate, 'กรุณาเลือกข้อมูลให้ครบถ้วน');
            } else {
                app.clearError(dateFields[0]);
            }
        }

        if (isAllValid) {
            populateRequestReviewModal(); // This will now work correctly
            elements.reviewModal.showModal();
        } else {
            app.showAlert('กรุณากรอกข้อมูลให้ครบถ้วนและถูกต้อง', 'error');
            const firstErrorField = form.querySelector('.input-error, .select-error, .file-input-error');
            if (firstErrorField) firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    elements.backBtn.addEventListener('click', () => setStep(1));
    elements.finalSubmitBtn.addEventListener('click', () => {
        elements.loadingModal.showModal();
        form.submit();
    });

    const ownerSelect = form.querySelector('select[name="owner_type"]');
    ownerSelect.addEventListener('change', () => {
        const otherDetails = document.getElementById('other-owner-details');
        const isOther = ownerSelect.value === 'other';
        otherDetails.classList.toggle('hidden', !isOther);
        otherDetails.querySelectorAll('input').forEach(input => {
            if (isOther) input.setAttribute('required', '');
            else {
                input.removeAttribute('required');
                app.clearError(input);
            }
        });
    });
    
    const populateDateSelects = (dayEl, monthEl, yearEl, selectedDate) => {
        dayEl.innerHTML = '<option value="">วัน</option>';
        monthEl.innerHTML = '<option value="">เดือน</option>';
        yearEl.innerHTML = '<option value="">ปี (พ.ศ.)</option>';
        for (let i = 1; i <= 31; i++) dayEl.add(new Option(i, i));
        const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
        months.forEach((m, i) => monthEl.add(new Option(m, i + 1)));
        const currentYearBE = new Date().getFullYear() + 543;
        for (let i = currentYearBE; i <= currentYearBE + 10; i++) yearEl.add(new Option(i, i));
        if (selectedDate && selectedDate !== '0000-00-00') {
            try {
                const d = new Date(selectedDate);
                dayEl.value = d.getDate();
                monthEl.value = d.getMonth() + 1;
                yearEl.value = d.getFullYear() + 543;
            } catch (e) {
                console.error("Invalid date for populating selects:", selectedDate);
            }
        }
    };

    const taxDayEl = form.querySelector('select[name="tax_day"]');
    const taxMonthEl = form.querySelector('select[name="tax_month"]');
    const taxYearEl = form.querySelector('select[name="tax_year"]');
    populateDateSelects(taxDayEl, taxMonthEl, taxYearEl, form.dataset.renewalTaxDate || null);

    app.setupImagePreview('reg_copy_upload', 'reg-copy-preview');
    app.setupImagePreview('tax_sticker_upload', 'tax-sticker-preview');
    app.setupImagePreview('front_view_upload', 'front-view-preview');
    app.setupImagePreview('rear_view_upload', 'rear-view-preview');

    if (form.dataset.isRenewal === 'true') {
        setStep(2);
        const vehicleType = document.getElementById('display-vehicle-type').textContent;
        const typeIcon = document.getElementById('display-vehicle-type-icon');
        if (typeIcon) typeIcon.className = vehicleType === 'รถยนต์' ? 'fa-solid fa-car-side text-3xl opacity-80' : 'fa-solid fa-motorcycle text-3xl opacity-80';
    } else {
        setStep(1);
    }
});

