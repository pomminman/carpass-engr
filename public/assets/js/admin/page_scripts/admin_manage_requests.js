/**
 * admin_manage_requests.js
 * Contains page-specific JavaScript for the Manage Requests page.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('manage-requests-page')) return;

    const statsModal = document.getElementById('stats_modal');
    const showStatsBtn = document.getElementById('show-stats-btn');
    
    if (showStatsBtn) {
        showStatsBtn.addEventListener('click', () => {
            const container = document.getElementById('stats-cards-container');
            if (!container || typeof window.requestStatsData === 'undefined') return;
            const stats = window.requestStatsData;
            container.innerHTML = `
                <div class="card bg-info text-info-content text-center"><div class="card-body p-4"><div class="text-3xl font-bold">${stats.total}</div><div class="text-sm">ทั้งหมด</div></div></div>
                <div class="card bg-warning text-warning-content text-center"><div class="card-body p-4"><div class="text-3xl font-bold">${stats.pending}</div><div class="text-sm">รออนุมัติ</div></div></div>
                <div class="card bg-success text-success-content text-center"><div class="card-body p-4"><div class="text-3xl font-bold">${stats.approved}</div><div class="text-sm">อนุมัติแล้ว</div></div></div>
                <div class="card bg-error text-error-content text-center"><div class="card-body p-4"><div class="text-3xl font-bold">${stats.rejected}</div><div class="text-sm">ไม่ผ่าน</div></div></div>
            `;
            if(statsModal) statsModal.showModal();
        });
    }
});
