// public/assets/js/admin/page_scripts/admin_view_user.js
/**
 * admin_view_user.js
 * Contains page-specific JavaScript for the View User page.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('view-user-page')) return;

    const App = window.App || {};
    const editBtn = document.getElementById('edit-user-btn');
    const editModal = document.getElementById('edit_user_modal');
    const form = document.getElementById('editUserFormInModal');

    // Initialize Fancybox for the profile picture
    Fancybox.bind('[data-fancybox="profile"]', {
        // Optional: Add any custom options here
    });

    if (!editBtn || !editModal || !form) return;

    editBtn.addEventListener('click', () => {
        editModal.showModal();
        // Initialize Thailand address lookup when modal opens
        $.Thailand({
            $zipcode: $(form).find('input[name="zipcode"]'),
            $district: $(form).find('input[name="subdistrict"]'), 
            $amphoe: $(form).find('input[name="district"]'),
            $province: $(form).find('input[name="province"]'),
        });
    });
    
    const titleSelect = form.querySelector('select[name="title"]');
    const titleOtherInput = form.querySelector('input[name="title_other"]');
    
    titleSelect.addEventListener('change', function() {
        const isOther = this.value === 'other';
        titleOtherInput.classList.toggle('hidden', !isOther);
        isOther ? titleOtherInput.setAttribute('required', '') : titleOtherInput.removeAttribute('required');
    });

    const photoInput = document.getElementById('modal-photo-upload');
    const photoPreview = document.getElementById('modal-photo-preview');
    photoInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files[0]) {
            photoPreview.src = URL.createObjectURL(e.target.files[0]);
        }
    });
    
    form.addEventListener('submit', (e) => {
        let isValid = true;
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('input-error');
            } else {
                field.classList.remove('input-error');
            }
        });
        if (!isValid) {
            e.preventDefault();
            App.showAlert('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน', 'error');
        }
    });
});
