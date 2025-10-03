// public/assets/js/user/user_core.js
const CarPassApp = {
    // Object to hold page-specific initialization functions
    pageScripts: {},

    /**
     * Registers a script to be executed for a specific page.
     * @param {string} pageName - The name of the page (e.g., 'dashboard').
     * @param {function} scriptFunction - The function to execute for that page.
     */
    registerPageScript: function(pageName, scriptFunction) {
        this.pageScripts[pageName] = scriptFunction;
    },

    /**
     * Main initializer for the application.
     * It sets up global helpers and runs the script for the current page.
     */
    init: function() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initGlobalHelpers();
            const pageName = document.body.dataset.page;
            if (pageName && this.pageScripts[pageName]) {
                // Execute the registered script for the current page, passing the app itself for context
                this.pageScripts[pageName](this);
            }
        });
    },

    /**
     * Initializes global functionalities like flash messages and Fancybox.
     */
    initGlobalHelpers: function() {
        const flashMessage = document.body.dataset.flashMessage;
        const flashStatus = document.body.dataset.flashStatus;
        if (flashMessage && flashStatus) {
            this.showAlert(flashMessage, flashStatus);
        }
        Fancybox.bind("[data-fancybox]", {
            Toolbar: {
                display: {
                    left: ["infobar"],
                    middle: ["zoomIn", "zoomOut", "toggle1to1", "rotateCCW", "rotateCW", "flipX", "flipY"],
                    right: ["slideshow", "thumbs", "close"],
                },
            },
        });
    },

    //======================================================================
    // SHARED HELPER & VALIDATION METHODS
    //======================================================================

    showAlert: function(message, type = 'success') {
        const typeBackgrounds = {
            success: "linear-gradient(to right, #00b09b, #96c93d)",
            error: "linear-gradient(to right, #ff5f6d, #ffc371)",
            info: "linear-gradient(to right, #00d2ff, #3a7bd5)",
            warning: "linear-gradient(to right, #f8b500, #f87217)"
        };

        Toastify({
            text: message,
            duration: 3000,
            close: true,
            gravity: "top", // `top` or `bottom`
            position: "center", // `left`, `center` or `right`
            stopOnFocus: true, // Prevents dismissing of toast on hover
            style: {
                background: typeBackgrounds[type] || typeBackgrounds.info,
            },
            onClick: function(){} // Callback after click
        }).showToast();
    },

    formatThaiDate: function(dateString) {
        if (!dateString || dateString.startsWith('0000-00-00')) return '-';
        try {
            const date = new Date(dateString);
            const thaiMonths = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
            return `${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}`;
        } catch (e) { return '-'; }
    },

    formatThaiDateTime: function(dateTimeString) {
        if (!dateTimeString || dateTimeString.startsWith('0000-00-00')) return '-';
        try {
            const date = new Date(dateTimeString);
            const thaiDate = this.formatThaiDate(dateTimeString);
            const time = date.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
            return `${thaiDate}, ${time} น.`;
        } catch (e) { return '-'; }
    },
    
    setupImagePreview: function(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        if(input && preview){
            const fancyboxLink = preview.closest('a');
            input.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const newSrc = URL.createObjectURL(file);
                    preview.src = newSrc;
                    if (fancyboxLink) {
                        fancyboxLink.href = newSrc;
                    }
                }
            });
        }
    },

    showError: function(element, message) {
        const parent = element.closest('.form-control');
        const errorEl = parent?.querySelector('.error-message');
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
        if (element.name === 'dob_day' || element.name === 'dob_month' || element.name === 'dob_year') {
            const dobContainer = element.closest('.grid');
            if (dobContainer) dobContainer.querySelectorAll('select').forEach(sel => sel.classList.add('select-error'));
            return;
        }
        if (element.type === 'file') element.classList.add('file-input-error');
        else if (element.tagName === 'SELECT') element.classList.add('select-error');
        else element.classList.add('input-error');
    },

    clearError: function(element) {
        const parent = element.closest('.form-control');
        const errorEl = parent?.querySelector('.error-message');
        if (errorEl) {
            errorEl.textContent = '';
            errorEl.classList.add('hidden');
        }
         if (element.name === 'dob_day' || element.name === 'dob_month' || element.name === 'dob_year') {
            const dobContainer = element.closest('.grid');
            if (dobContainer) dobContainer.querySelectorAll('select').forEach(sel => sel.classList.remove('select-error'));
            return;
        }
        element.classList.remove('input-error', 'select-error', 'file-input-error');
    },
    
    validateField: function(field) {
        let isValid = true;
        const value = field.value.trim();
        this.clearError(field);
        
        if (field.hasAttribute('required')) {
            if (field.type === 'checkbox') {
                if (!field.checked) { this.showError(field, 'กรุณายอมรับเงื่อนไข'); isValid = false; }
            } else if (field.type === 'file') {
                if (field.files.length === 0) {
                    if (field.id !== 'profile-photo-upload') { this.showError(field, 'กรุณาแนบไฟล์'); isValid = false; }
                } else if (field.files[0].size > 5 * 1024 * 1024) { this.showError(field, 'ขนาดไฟล์ต้องไม่เกิน 5 MB'); isValid = false; }
            } else if (value === '') { this.showError(field, 'กรุณากรอกข้อมูล'); isValid = false; }
        } else if (field.type === 'file' && field.files.length > 0) {
             if (field.files[0].size > 5 * 1024 * 1024) { this.showError(field, 'ขนาดไฟล์ต้องไม่เกิน 5 MB'); isValid = false; }
        }

        if (isValid && field.id === 'check-license-plate' && value !== '') {
            if (!(/[ก-๙]/.test(value) && /[0-9]/.test(value))) { this.showError(field, 'ต้องมีทั้งตัวอักษรไทยและตัวเลข'); isValid = false; }
        }
        
        if (isValid && field.name === 'official_id' && value !== '' && value.length !== 10) {
             this.showError(field, 'กรุณากรอกเลขบัตรให้ครบ 10 หลัก'); isValid = false;
        }
        return isValid;
    }
};

// Start the application
CarPassApp.init();

