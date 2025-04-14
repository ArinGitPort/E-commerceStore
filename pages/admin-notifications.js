document.addEventListener('DOMContentLoaded', () => {
    // Initialize DataTable with custom settings
    initDataTable();
    // Initialize modal interactions (populate edit modal and clear new modal on close)
    initModals();
    // Setup AJAX form submission handling for forms with data-ajax attribute
    initFormSubmissions();
    // Setup click handlers for quick template items
    initTemplateActions();
});

function initDataTable() {
    $('#notificationTable').DataTable({
        paging: false,
        searching: false,
        info: false,
        ordering: true,
        columnDefs: [
            { orderable: false, targets: [3, 4, 6] },
            { type: 'date', targets: 5 }
        ],
        order: [[0, 'desc']],
        language: { emptyTable: "No notifications available" }
    });
}

function initModals() {
    const editModal = document.getElementById('editNotificationModal');
    if (editModal) {
      editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // The button that triggered the modal
  
        // Debug: log the button's data attributes in console
        console.log("Edit modal triggered. Button data:", {
          id: button.getAttribute('data-id'),
          title: button.getAttribute('data-title'),
          message: button.getAttribute('data-message'),
          startDate: button.getAttribute('data-start-date'),
          expiryDate: button.getAttribute('data-expiry-date')
        });
  
        // Grab references to your form fields
        const form = editModal.querySelector('form');
        form.querySelector('#editNotificationId').value = button.getAttribute('data-id') || '';
        form.querySelector('#editTitle').value         = button.getAttribute('data-title') || '';
        form.querySelector('#editMessage').value       = button.getAttribute('data-message') || '';
  
        // For date fields, if there's time portion in DB, remove it
        const startDate = button.getAttribute('data-start-date') || '';
        const expiryDate = button.getAttribute('data-expiry-date') || '';
        form.querySelector('#editStartDate').value  = startDate.split(' ')[0];
        form.querySelector('#editExpiryDate').value = expiryDate.split(' ')[0];
      });
    }
  
    // Reset the New Notification modal form when it closes
    $('#newNotificationModal').on('hidden.bs.modal', function() {
      this.querySelector('form').reset();
    });
  }
  
  


function initFormSubmissions() {
    document.querySelectorAll('form[data-ajax]').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            try {
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Processing...`;
                }
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                if (data.success) {
                    handleFormSuccess(form, data);
                } else {
                    throw new Error(data.message || 'Action failed');
                }
            } catch (error) {
                showAlert('danger', error.message);
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = getButtonText(form);
                }
            }
        });
    });
}

function initTemplateActions() {
    document.querySelectorAll('.template-item').forEach(item => {
        item.addEventListener('click', function() {
            const template = JSON.parse(this.dataset.template);
            const form = document.querySelector('#newNotificationModal form');
            form.querySelector('input[name="title"]').value = template.title;
            form.querySelector('textarea[name="message"]').value = template.message;
            const modalEl = document.getElementById('newNotificationModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });
    });
}

function handleFormSuccess(form, data) {
    showAlert('success', data.message);
    const modalInstance = bootstrap.Modal.getInstance(form.closest('.modal'));
    if (modalInstance) {
        modalInstance.hide();
    }
    if (data.redirect) {
        window.location.reload();
    } else {
        // Optionally update UI dynamically here if desired
    }
}

function showAlert(type, message, duration = 5000) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    const container = document.querySelector('.main-content') || document.body;
    container.prepend(alert);
    setTimeout(() => bootstrap.Alert.getOrCreateInstance(alert).close(), duration);
}

function getButtonText(form) {
    if (form.id && form.id.includes('Template')) return 'Save Template';
    if (form.querySelector('#editNotificationId')) return 'Update Notification';
    return 'Submit';
}
