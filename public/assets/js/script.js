document.addEventListener('DOMContentLoaded', function () {
    
    /**
     * Main application object.
     */
    const App = {
        /**
         * Initializes all functionalities.
         */
        init: function() {
            this.initGlobalHelpers();
            if (document.getElementById('vehicle-grid')) {
                this.initDashboard();
            }
        },

        /**
         * Initializes global helper functions and listeners.
         */
        initGlobalHelpers: function() {
            window.showAlert = (message, type = 'success') => {
                const container = document.getElementById('alert-container');
                if (!container) return;
                
                const icons = {
                    success: 'fa-solid fa-circle-check',
                    error: 'fa-solid fa-circle-xmark',
                    info: 'fa-solid fa-circle-info',
                    warning: 'fa-solid fa-triangle-exclamation',
                };
                
                const alertEl = document.createElement('div');
                alertEl.className = `alert alert-${type} alert-soft shadow-lg`;
                alertEl.innerHTML = `<div class="flex items-center"><i class="${icons[type]}"></i><span class="ml-2 text-xs sm:text-sm">${message}</span></div>`;
                container.appendChild(alertEl);

                setTimeout(() => {
                    alertEl.style.transition = 'opacity 0.3s ease';
                    alertEl.style.opacity = '0';
                    setTimeout(() => alertEl.remove(), 300);
                }, 3000);
            };

            // Automatically show flash messages from PHP sessions
            const flashMessage = document.body.dataset.flashMessage;
            const flashStatus = document.body.dataset.flashStatus;
            if (flashMessage && flashStatus) {
                showAlert(flashMessage, flashStatus);
            }
        },

        /**
         * Initializes functionalities for the dashboard page.
         */
        initDashboard: function() {
            const detailsModalEl = document.getElementById('request_details_modal');
            if (!detailsModalEl) return;

            const zoomModalEl = document.getElementById('image_zoom_modal');
            const deleteModalEl = document.getElementById('delete_confirm_modal');
            const loadingModalEl = document.getElementById('loading_modal');
            const vehicleCards = document.querySelectorAll('.vehicle-card');
            const statFilters = document.querySelectorAll('.stat-filter');
            const noResultsMessage = document.getElementById('no-results-message');
            const searchInput = document.getElementById('search-input');
            
            let currentCardData = null;

            const formatThaiDate = (dateString) => {
                if (!dateString || dateString === '0000-00-00 00:00:00' || dateString === '0000-00-00') return '-';
                try {
                    const date = new Date(dateString);
                    const thaiMonths = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
                    return `${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}`;
                } catch (e) { return '-'; }
            };

            const formatThaiDateTime = (dateTimeString) => {
                if (!dateTimeString || dateTimeString === '0000-00-00 00:00:00') return '-';
                try {
                    const date = new Date(dateTimeString);
                    const thaiDate = formatThaiDate(dateTimeString);
                    const time = date.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                    return `${thaiDate}, ${time} น.`;
                } catch (e) { return '-'; }
            };

            const populateDateSelects = (daySelect, monthSelect, yearSelect, selectedDate) => {
                daySelect.innerHTML = ''; monthSelect.innerHTML = ''; yearSelect.innerHTML = '';
                for (let i = 1; i <= 31; i++) daySelect.add(new Option(i, i));
                const months = ["มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฎาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
                months.forEach((m, i) => monthSelect.add(new Option(m, i + 1)));
                const currentYearBE = new Date().getFullYear() + 543;
                for (let i = currentYearBE; i <= currentYearBE + 10; i++) yearSelect.add(new Option(i, i));
                if (selectedDate) {
                    daySelect.value = selectedDate.getDate();
                    monthSelect.value = selectedDate.getMonth() + 1;
                    yearSelect.value = selectedDate.getFullYear() + 543;
                }
            };
            
            const createInfoRow = (label, value) => {
                if (!value || value === '-') return '';
                return `<div class="flex justify-between items-start gap-4">
                            <span class="text-base-content/70 flex-shrink-0">${label}:</span>
                            <span class="font-semibold text-right break-words">${value}</span>
                        </div>`;
            };
            
            const openDetailsModal = (card) => {
                currentCardData = card.dataset;
                const data = currentCardData;
                const basePath = `/public/uploads/${data.userKey}/vehicle/${data.requestKey}/`;
                const qrPath = `/public/qr/${data.requestKey}.png`;

                detailsModalEl.querySelector('#modal-status-badge').className = `badge badge-lg ${data.statusClass}`;
                detailsModalEl.querySelector('#modal-status-badge').innerHTML = `<i class="${data.statusIcon} mr-2"></i> ${data.statusText}`;
                detailsModalEl.querySelector('#modal-license-plate').textContent = `${data.licensePlate} ${data.province}`;
                detailsModalEl.querySelector('#modal-brand-model').textContent = `${data.brand} / ${data.model}`;

                const rejectionBox = detailsModalEl.querySelector('#modal-rejection-reason-box');
                rejectionBox.classList.toggle('hidden', !(data.statusKey === 'rejected' && data.rejectionReason));
                if (data.statusKey === 'rejected' && data.rejectionReason) {
                    detailsModalEl.querySelector('#modal-rejection-reason-text').textContent = data.rejectionReason;
                }
                
                const vehicleInfoList = detailsModalEl.querySelector('#modal-vehicle-info-list');
                vehicleInfoList.innerHTML = createInfoRow('ประเภท', data.vehicleType) +
                                          createInfoRow('สี', data.color) +
                                          createInfoRow('วันสิ้นอายุภาษี', formatThaiDate(data.taxExpiry));

                const ownerInfoList = detailsModalEl.querySelector('#modal-owner-info-list');
                let ownerHtml = createInfoRow('สถานะ', data.ownerType === 'self' ? 'รถชื่อตนเอง' : 'รถคนอื่น');
                if (data.ownerType === 'other') {
                    ownerHtml += createInfoRow('ชื่อเจ้าของ', data.otherOwnerName || '-');
                    ownerHtml += createInfoRow('เกี่ยวข้องเป็น', data.otherOwnerRelation || '-');
                }
                ownerInfoList.innerHTML = ownerHtml;
                
                const cardInfoList = detailsModalEl.querySelector('#modal-card-info-list');
                const cardTypeThai = data.cardType === 'internal' ? 'ภายใน' : (data.cardType === 'external' ? 'ภายนอก' : '-');
                let cardHtml = createInfoRow('รหัสคำร้อง', data.searchId);
                if (data.cardType) cardHtml += createInfoRow('ประเภทบัตร', cardTypeThai);
                cardHtml += createInfoRow('วันยื่นคำร้อง', formatThaiDateTime(data.createdAt));
                if (data.createdAt.substring(0, 19) !== data.updatedAt.substring(0, 19)) {
                    cardHtml += createInfoRow('แก้ไขล่าสุด', formatThaiDateTime(data.updatedAt));
                }
                if (data.statusKey === 'approved' || data.statusKey === 'expired') {
                    cardHtml += createInfoRow('เลขที่บัตร', data.cardNumber);
                    cardHtml += createInfoRow('ผู้อนุมัติ', data.approvedBy);
                    cardHtml += createInfoRow('วันอนุมัติ', formatThaiDateTime(data.approvedAt));
                    cardHtml += createInfoRow('วันหมดอายุ', formatThaiDate(data.cardExpiry));
                }
                cardInfoList.innerHTML = cardHtml;
                
                const qrContainer = detailsModalEl.querySelector('#modal-qr-code-container');
                qrContainer.classList.toggle('hidden', data.statusKey !== 'approved' && data.statusKey !== 'expired');
                if (data.statusKey === 'approved' || data.statusKey === 'expired') {
                    qrContainer.querySelector('img').src = qrPath;
                }

                detailsModalEl.querySelector('#modal-photo-reg').src = basePath + data.photoReg;
                detailsModalEl.querySelector('#modal-photo-tax').src = basePath + data.photoTax;
                detailsModalEl.querySelector('#modal-photo-front').src = basePath + data.photoFront;
                detailsModalEl.querySelector('#modal-photo-rear').src = basePath + data.photoRear;

                const actionContainer = detailsModalEl.querySelector('#modal-action-buttons');
                actionContainer.innerHTML = '';
                actionContainer.innerHTML += '<div class="flex-grow"></div>';
                if (data.canRenew === 'true') {
                    actionContainer.innerHTML += `<a href="add_vehicle.php?renew_id=${data.vehicleId}" class="btn btn-sm btn-success"><i class="fa-solid fa-calendar-check"></i>ต่ออายุบัตร</a>`;
                }
                if (data.statusKey === 'pending' || data.statusKey === 'rejected') {
                    actionContainer.innerHTML += `<button id="modal-edit-btn" class="btn btn-sm btn-warning"><i class="fa-solid fa-pencil"></i>แก้ไข</button>`;
                }
                 if (data.statusKey !== 'approved') {
                    actionContainer.innerHTML += `<button id="modal-delete-btn" class="btn btn-sm btn-error"><i class="fa-solid fa-trash-can"></i>ลบ</button>`;
                }
                actionContainer.innerHTML += `<form method="dialog"><button class="btn btn-sm btn-ghost">ปิด</button></form>`;


                detailsModalEl.querySelector('#modal-content-wrapper').classList.remove('hidden');
                detailsModalEl.querySelector('#modal-edit-form-wrapper').classList.add('hidden');
                
                detailsModalEl.showModal();
            };

            const switchToEditMode = () => {
                const data = currentCardData;
                const editForm = detailsModalEl.querySelector('#editVehicleForm');
                
                editForm.querySelector('#edit-request-id').value = data.requestId;
                editForm.querySelector('#edit-vehicle-brand').value = data.brand;
                editForm.querySelector('#edit-vehicle-model').value = data.model;
                editForm.querySelector('#edit-vehicle-color').value = data.color;
                
                const taxDate = data.taxExpiry ? new Date(data.taxExpiry) : null;
                populateDateSelects(
                    editForm.querySelector('#edit-tax-day'),
                    editForm.querySelector('#edit-tax-month'),
                    editForm.querySelector('#edit-tax-year'),
                    taxDate
                );
                
                const ownerSelect = editForm.querySelector('#edit-owner-type');
                ownerSelect.value = data.ownerType;
                if (data.ownerType === 'other') {
                    editForm.querySelector('#edit-other-owner-name').value = data.otherOwnerName;
                    editForm.querySelector('#edit-other-owner-relation').value = data.otherOwnerRelation;
                }
                ownerSelect.dispatchEvent(new Event('change'));

                detailsModalEl.querySelector('#modal-content-wrapper').classList.add('hidden');
                detailsModalEl.querySelector('#modal-edit-form-wrapper').classList.remove('hidden');
            };

            const filterAndSearchCards = () => {
                const filterKey = document.querySelector('.stat-filter.active')?.dataset.filter || 'all';
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;

                vehicleCards.forEach(card => {
                    const cardStatus = card.dataset.statusKey;
                    const cardText = card.textContent.toLowerCase();
                    const isStatusVisible = (filterKey === 'all' || cardStatus === filterKey);
                    const isSearchVisible = (searchTerm === '' || cardText.includes(searchTerm));
                    const isVisible = isStatusVisible && isSearchVisible;
                    
                    card.style.display = isVisible ? 'flex' : 'none';
                    if (isVisible) visibleCount++;
                });

                if (noResultsMessage) {
                    noResultsMessage.style.display = visibleCount === 0 ? 'block' : 'none';
                }
            };

            // --- Event Listeners ---
            vehicleCards.forEach(card => card.addEventListener('click', () => openDetailsModal(card)));
            
            statFilters.forEach(filter => {
                filter.addEventListener('click', () => {
                    statFilters.forEach(f => f.classList.remove('active', 'ring-2', 'ring-primary', 'ring-offset-base-100', 'ring-offset-2'));
                    filter.classList.add('active', 'ring-2', 'ring-primary', 'ring-offset-base-100', 'ring-offset-2');
                    filterAndSearchCards();
                });
            });
            
            if (searchInput) {
                searchInput.addEventListener('input', filterAndSearchCards);
            }

            const defaultFilter = document.querySelector('.stat-filter[data-filter="all"]');
            if (defaultFilter) defaultFilter.click();

            detailsModalEl.addEventListener('click', (e) => {
                if (e.target.matches('.cursor-zoom-in')) {
                    const zoomedImage = zoomModalEl.querySelector('#zoomed_image');
                    zoomedImage.src = e.target.src;
                    zoomModalEl.showModal();
                }
                if (e.target.id === 'modal-edit-btn') {
                    switchToEditMode();
                }
                if (e.target.id === 'modal-delete-btn') {
                    deleteModalEl.querySelector('#delete-request-id').value = currentCardData.requestId;
                    deleteModalEl.showModal();
                }
                if (e.target.id === 'cancel-edit-btn') {
                    detailsModalEl.querySelector('#modal-edit-form-wrapper').classList.add('hidden');
                    detailsModalEl.querySelector('#modal-content-wrapper').classList.remove('hidden');
                }
            });

            if (zoomModalEl) {
                zoomModalEl.addEventListener('click', (e) => {
                    const imageContainer = zoomModalEl.querySelector('#zoomed_image_container');
                    if (imageContainer && !imageContainer.contains(e.target)) {
                        zoomModalEl.close();
                    }
                });
            }

            const editForm = detailsModalEl.querySelector('#editVehicleForm');
            if(editForm) {
                editForm.addEventListener('submit', () => loadingModalEl.showModal());
                
                const editOwnerSelect = editForm.querySelector('#edit-owner-type');
                if (editOwnerSelect) {
                    editOwnerSelect.addEventListener('change', e => {
                        const otherDetails = editForm.querySelector('#edit-other-owner-details');
                        otherDetails.classList.toggle('hidden', e.target.value !== 'other');
                        otherDetails.querySelectorAll('input').forEach(input => {
                             e.target.value === 'other' ? input.setAttribute('required', '') : input.removeAttribute('required');
                        });
                    });
                }
            }
            
            const deleteForm = deleteModalEl.querySelector('#deleteRequestForm');
            if(deleteForm) {
                deleteForm.addEventListener('submit', () => {
                    deleteModalEl.close();
                    loadingModalEl.showModal();
                });
            }
        }
    };

    // Run the app
    App.init();
});

