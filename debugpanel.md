  <!-- Debug Panel -->
  <div id="debug-panel" style="
  position: fixed;
  top: 20px;
  right: 20px;
  width: 400px;
  max-height: 400px;
  overflow-y: auto;
  z-index: 9999;
  background: rgba(0,0,0,0.85);
  color: #fff;
  font-size: 12px;
  font-family: monospace;
  border-radius: 5px;
  box-shadow: 0 0 10px rgba(0,0,0,0.3);
  display: none;
  padding: 10px;
">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
      <strong>🛠️ Debug Log</strong>
      <button onclick="document.getElementById('debug-panel').style.display='none'" style="
      background: red; border: none; color: white; font-size: 12px; padding: 2px 8px; cursor: pointer;
    ">X</button>
    </div>
    <div id="debug-log"></div>
  </div>

  <!-- Toggle Debug Button -->
  <button onclick="toggleDebug()" style="
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 9999;
  background: #343a40;
  color: white;
  border: none;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  cursor: pointer;
">
    🔧 Toggle Debug
  </button>

  <script>
    function toggleDebug() {
      const panel = document.getElementById('debug-panel');
      panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }

    function debugLog(...args) {
      const logDiv = document.getElementById('debug-log');
      const entry = document.createElement('div');
      entry.textContent = `[${new Date().toLocaleTimeString()}] ` + args.map(a =>
        typeof a === 'object' ? JSON.stringify(a) : a
      ).join(' | ');
      logDiv.prepend(entry);
    }

    // Patch fetch to log all AJAX requests
    const originalFetch = window.fetch;
    window.fetch = async function(...args) {
      debugLog('📤 Request:', args[0], args[1]);
      const response = await originalFetch(...args);
      try {
        const clone = response.clone();
        const data = await clone.json();
        debugLog('📥 Response:', data);
      } catch (e) {
        debugLog('⚠️ Non-JSON Response');
      }
      return response;
    };
  </script>


  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <script>
    // Load template into the "New Notification" form
    function loadTemplate(template) {
      const modalEl = document.getElementById('newNotificationModal');
      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

      const form = modalEl.querySelector('form');
      form.querySelector('input[name="title"]').value = template.title;
      form.querySelector('textarea[name="message"]').value = template.message;

      // Show the modal
      modal.show();
    }

    // Edit modal logic
    const editModal = document.getElementById('editNotificationModal');
    editModal.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      editModal.querySelector('#editNotificationId').value = button.getAttribute('data-id');
      editModal.querySelector('#editTitle').value = button.getAttribute('data-title');
      editModal.querySelector('#editMessage').value = button.getAttribute('data-message');

      const startDate = button.getAttribute('data-start-date');
      const expiryDate = button.getAttribute('data-expiry-date');
      editModal.querySelector('#editStartDate').value = startDate ? startDate.split(' ')[0] : '';
      editModal.querySelector('#editExpiryDate').value = expiryDate ? expiryDate.split(' ')[0] : '';
    });

    // Auto-focus first input in modals
    document.querySelectorAll('.modal').forEach(modal => {
      modal.addEventListener('shown.bs.modal', () => {
        const input = modal.querySelector('input[type="text"], textarea');
        if (input) input.focus();
      });
    });

    // Initialize DataTable (no paging to avoid conflict with custom paging)
    $(document).ready(function() {
      $('#notificationTable').DataTable({
        paging: false,
        searching: false,
        info: false,
        ordering: false
      });
    });
  </script>