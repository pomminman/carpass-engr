// public/assets/js/admin/page_scripts/admin_add_request.js
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('addRequestForm');
    if (!form) return;

    const App = window.App || {};
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB

    const showError = (input, message) => {
        const formControl = input.closest('.form-control');
        const errorElement = formControl.querySelector('.error-message');
        input.classList.add('input-error', 'select-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        }
    };

    const clearError = (input) => {
        const formControl = input.closest('.form-control');
        const errorElement = formControl.querySelector('.error-message');
        input.classList.remove('input-error', 'select-error');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.classList.add('hidden');
        }
    };

    const validateField = (field) => {
        clearError(field);
        if (field.hasAttribute('required') && !field.value.trim()) {
            showError(field, 'กรุณากรอกข้อมูล');
            return false;
        }
        return true;
    };

    const validateFileSize = (fileInput) => {
        clearError(fileInput);
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            if (file.size > MAX_FILE_SIZE) {
                showError(fileInput, `ขนาดไฟล์ต้องไม่เกิน ${MAX_FILE_SIZE / 1024 / 1024} MB`);
                return false;
            }
        }
        // If the field is not required, it's valid if empty
        if (!fileInput.hasAttribute('required') && fileInput.files.length === 0) {
            return true;
        }
        // If it is required, check if a file is selected
        if (fileInput.hasAttribute('required') && fileInput.files.length === 0) {
            showError(fileInput, 'กรุณาอัปโหลดไฟล์');
            return false;
        }
        return true; 
    };

    // --- Attach event listeners for real-time validation ---
    const fieldsToValidate = [
        'vehicle_type', 'vehicle_brand', 'license_plate', 
        'license_province', 'vehicle_model', 'vehicle_color'
    ];

    fieldsToValidate.forEach(fieldName => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            const eventType = field.tagName.toLowerCase() === 'select' ? 'change' : 'input';
            field.addEventListener(eventType, () => validateField(field));
        }
    });

    // --- Specific Input Filters ---
    const vehicleModelInput = form.querySelector('input[name="vehicle_model"]');
    vehicleModelInput.addEventListener('input', () => {
        vehicleModelInput.value = vehicleModelInput.value.replace(/[^a-zA-Z0-9\s-]/g, '');
    });

    const licensePlateInput = form.querySelector('input[name="license_plate"]');
    licensePlateInput.addEventListener('input', () => {
        licensePlateInput.value = licensePlateInput.value.replace(/[a-zA-Z\s]/g, '');
    });

    const vehicleColorInput = form.querySelector('input[name="vehicle_color"]');
    vehicleColorInput.addEventListener('input', () => {
        vehicleColorInput.value = vehicleColorInput.value.replace(/[a-zA-Z]/g, '');
    });

    // --- File Size Validation ---
    const fileInputs = [
        'reg_copy_upload', 'tax_sticker_upload', 
        'front_view_upload', 'rear_view_upload'
    ];
    fileInputs.forEach(inputId => {
        const fileInput = document.getElementById(inputId);
        if (fileInput) {
            fileInput.addEventListener('change', () => validateFileSize(fileInput));
        }
    });


    // --- Form submission validation ---
    form.addEventListener('submit', function(e) {
        let isFormValid = true;
        
        form.querySelectorAll('[required]').forEach(field => {
            if (field.offsetParent !== null) { 
                if (!validateField(field)) {
                    isFormValid = false;
                }
            }
        });

        fileInputs.forEach(inputId => {
            const fileInput = document.getElementById(inputId);
            if (fileInput && !validateFileSize(fileInput)) {
                isFormValid = false;
            }
        });

        if (!isFormValid) {
            e.preventDefault();
            App.showAlert('กรุณากรอกข้อมูลให้ถูกต้องและครบถ้วน', 'error');
            const firstErrorField = form.querySelector('.input-error, .select-error');
            if (firstErrorField) {
                firstErrorField.focus();
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    // --- Other dynamic form logic ---
    const ownerTypeSelect = form.querySelector('select[name="owner_type"]');
    const otherOwnerDetails = document.getElementById('other-owner-details');
    if (ownerTypeSelect && otherOwnerDetails) {
        ownerTypeSelect.addEventListener('change', function() {
            const isOther = this.value === 'other';
            otherOwnerDetails.classList.toggle('hidden', !isOther);
            otherOwnerDetails.querySelectorAll('input').forEach(input => {
                if (isOther) {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                    clearError(input);
                }
            });
        });
    }

    function setupImagePreview(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const link = preview ? preview.closest('a') : null;

        if (input && preview && link) {
            input.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const objectURL = URL.createObjectURL(file);
                    preview.src = objectURL;
                    link.href = objectURL; // Update link for Fancybox
                }
            });
        }
    }
    setupImagePreview('reg_copy_upload', 'reg-copy-preview');
    setupImagePreview('tax_sticker_upload', 'tax-sticker-preview');
    setupImagePreview('front_view_upload', 'front-view-preview');
    setupImagePreview('rear_view_upload', 'rear-view-preview');

    // --- Populate Date Selects ---
    const daySelect = form.querySelector('select[name="tax_day"]');
    const monthSelect = form.querySelector('select[name="tax_month"]');
    const yearSelect = form.querySelector('select[name="tax_year"]');
    const months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    for (let i = 1; i <= 31; i++) { daySelect.innerHTML += `<option value="${i}">${i}</option>`; }
    months.forEach((month, i) => { monthSelect.innerHTML += `<option value="${String(i + 1).padStart(2, '0')}">${month}</option>`; });
    const currentYearBE = new Date().getFullYear() + 543;
    for (let i = currentYearBE + 5; i >= currentYearBE; i--) { yearSelect.innerHTML += `<option value="${i}">${i}</option>`; }

    // [FIX] Initialize Fancybox for the evidence gallery on this page
    Fancybox.bind('[data-fancybox="evidence-gallery"]', {
        // Optional: Add any custom options here
    });
});

