document.addEventListener('DOMContentLoaded', function() {
    const addRequestModal = document.getElementById('add_request_modal');
    const selectUserForm = document.getElementById('selectUserForm');
    const userSelect = $('#user-search-select');

    // Initialize Select2
    userSelect.select2({
        dropdownParent: $('#add_request_modal'),
        placeholder: 'คลิกเพื่อเลือก หรือพิมพ์เพื่อค้นหา...',
        ajax: {
            url: '../../../controllers/admin/requests/check_requests.php?action=search_users', 
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // search term
                };
            },
            processResults: function (data) {
                return {
                    results: data.items
                };
            },
            cache: true
        }
        // [REMOVED] minimumInputLength: 2 is no longer needed
    });

    // Handle form submission to go to the add request page
    if (selectUserForm) {
        selectUserForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const selectedUserId = userSelect.val();
            if (selectedUserId) {
                window.location.href = `add_request.php?user_id=${selectedUserId}`;
            } else {
                showAlert('โปรดเลือกผู้ใช้งานที่ต้องการสร้างคำร้อง', 'error');
            }
        });
    }
});

