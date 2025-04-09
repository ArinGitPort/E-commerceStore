function showToast(message, type = 'info') {
    const toastEl = document.getElementById('liveToast');
    if (!toastEl) return;
    
    const toastBody = toastEl.querySelector('.toast-body');
    toastEl.classList.remove('bg-primary', 'bg-success', 'bg-danger');
    toastEl.classList.add(`bg-${type}`);
    toastBody.textContent = message;
    new bootstrap.Toast(toastEl).show();
}

// Event Delegation for Update Status Buttons
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('btn-update-status')) {
        const orderId = e.target.dataset.orderId;
        const currentStatus = document.querySelector(`#status-${orderId}`).textContent.trim();
        
        document.querySelectorAll('.status-card').forEach(card => {
            card.classList.remove('selected');
            if(card.dataset.status.toLowerCase() === currentStatus.toLowerCase()) {
                card.classList.add('selected');
                card.querySelector('input').checked = true;
            }
        });
        
        new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
    }
});

// Global Status Selection Function
window.selectStatus = function(status) {
    document.querySelectorAll('.status-card').forEach(card => {
        card.classList.remove('selected');
        if(card.dataset.status === status) {
            card.classList.add('selected');
            card.querySelector('input').checked = true;
        }
    });
};

// Save Status Handler
document.getElementById('saveStatus').addEventListener('click', async () => {
    const orderId = document.getElementById('modalOrderId').value;
    const status = document.querySelector('input[name="new_status"]:checked')?.value;
    
    if (!status) {
        showToast('Please select a status', 'danger');
        return;
    }

    try {
        const response = await fetch('ajax/update_order_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: orderId, new_status: status })
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.querySelector(`#status-${orderId}`).textContent = status;
            showToast('Status updated successfully!', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('updateStatusModal'));
            if (modal) modal.hide();
        } else {
            showToast(result.error || 'Update failed', 'danger');
        }
    } catch (error) {
        showToast('Network error - please try again', 'danger');
        console.error('Update error:', error);
    }
});

// Real-time Alerts System
let lastCheck = Math.floor(Date.now() / 1000);
const alertSound = new Audio('assets/sounds/new-order.mp3');

async function checkNewOrders() {
    try {
        const response = await fetch(`ajax/get_new_orders.php?lastCheck=${lastCheck}`);
        if (!response.ok) throw new Error('Network response was not ok');
        
        const orders = await response.json();
        
        orders.forEach(order => {
            const alert = createAlertElement(order);
            document.getElementById('liveAlerts').prepend(alert);
            alertSound.play().catch(() => {}); // Mute play() error
            setTimeout(() => alert.remove(), 10000);
        });
        
        lastCheck = Math.floor(Date.now() / 1000);
    } catch (error) {
        console.error('Alerts error:', error);
    }
}

function createAlertElement(order) {
    const alert = document.createElement('div');
    alert.className = 'list-group-item list-group-item-warning new-order-alert';
    alert.innerHTML = `
        <div class="d-flex justify-content-between">
            <div>
                <strong>New Order #${order.id}</strong><br>
                <small>${order.customer} - ₱${order.total}</small>
            </div>
            <small>${new Date(order.timestamp * 1000).toLocaleTimeString()}</small>
        </div>
    `;
    return alert;
}

// Initialize polling
setInterval(checkNewOrders, 5000);
document.getElementById('refreshAlerts').addEventListener('click', checkNewOrders);

// Initial check
checkNewOrders();