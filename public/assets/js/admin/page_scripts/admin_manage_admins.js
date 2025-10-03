/**
 * admin_manage_admins.js
 * Contains page-specific JavaScript for the Manage Admins page.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('manage-admins-page')) return;

    const App = window.App || {};
    const addAdminModal = document.getElementById('add_admin_modal');
    const viewAdminModal = document.getElementById('view_admin_modal');
    const loadingModal = document.getElementById('loading_modal');
    const adminsTable = document.getElementById('adminsTable');
    const filterForm = document.getElementById('filterForm');
    
    // --- Auto-submit Filter Form Logic ---
    if (filterForm) {
        const inputs = filterForm.querySelectorAll('.filter-input');
        let debounceTimer;

        inputs.forEach(input => {
            const eventType = input.tagName.toLowerCase() === 'select' ? 'change' : 'input';
            
            input.addEventListener(eventType, () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    filterForm.submit();
                }, input.type === 'text' ? 400 : 0);
            });
        });
    }

    if (!addAdminModal || !viewAdminModal || !adminsTable) return;

    const showError = (input, message) => {
        const formControl = input.closest('.form-control');
        const errorElement = formControl.querySelector('.error-message');
        input.classList.add('input-error', 'select-error');
        if (errorElement) { errorElement.textContent = message; errorElement.classList.remove('hidden'); }
    };

    const clearError = (input) => {
        const formControl = input.closest('.form-control');
        const errorElement = formControl.querySelector('.error-message');
        input.classList.remove('input-error', 'select-error');
        if (errorElement) { errorElement.textContent = ''; errorElement.classList.add('hidden'); }
    };

    const validateAdminForm = (form) => {
        let isValid = true;
        form.querySelectorAll('input[required], select[required]').forEach(input => {
            clearError(input);
            if (!input.value.trim()) { isValid = false; showError(input, 'กรุณากรอกข้อมูล'); }
        });
        const password = form.querySelector('input[name="password"]');
        const confirmPassword = form.querySelector('input[name="confirm_password"]');
        if (password && confirmPassword) {
            if (form.id === 'addAdminForm' && !password.value) { isValid = false; showError(password, 'กรุณากำหนดรหัสผ่าน'); }
            if (password.value && password.value.length < 6) { isValid = false; showError(password, 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร'); }
            if (password.value !== confirmPassword.value) { isValid = false; showError(confirmPassword, 'รหัสผ่านไม่ตรงกัน'); }
        }
        return isValid;
    };

    const addForm = document.getElementById('addAdminForm');
    if(addForm) {
        addForm.addEventListener('submit', (e) => {
            if (!validateAdminForm(addForm)) { e.preventDefault(); App.showAlert('กรุณากรอกข้อมูลให้ถูกต้อง', 'error'); }
        });
    }

    const populateAdminModal = (data) => {
        const setText = (selector, text) => { viewAdminModal.querySelector(selector).textContent = text || '-'; };
        let roleBadgeClass = 'badge-primary';
        if(data.role === 'superadmin') roleBadgeClass = 'badge-secondary';
        setText('#modal-admin-fullname', `${data.title} ${data.firstname} ${data.lastname}`);
        setText('#modal-admin-username', data.username);
        setText('#modal-admin-department', data.department);
        setText('#modal-admin-permission', data.view_permission == 1 ? 'ดูได้ทุกสังกัด' : 'เฉพาะสังกัดตนเอง');
        viewAdminModal.querySelector('#modal-admin-role').innerHTML = `<div class="badge ${roleBadgeClass}">${data.role}</div>`;
    };

    adminsTable.addEventListener('click', async (e) => {
        const inspectButton = e.target.closest('.inspect-admin-btn');
        if (!inspectButton) return;
        const adminId = inspectButton.dataset.id;
        loadingModal.showModal();
        try {
            const response = await fetch(`/app/controllers/admin/requests/check_requests.php?action=get_admin_details&id=${adminId}`);
            const result = await response.json();
            if (result.success) {
                populateAdminModal(result.data);
                viewAdminModal.showModal();
            } else { throw new Error(result.message); }
        } catch (error) { App.showAlert(error.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล', 'error');
        } finally { loadingModal.close(); }
    });
});

