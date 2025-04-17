console.log("order_management.js loaded");

function showToast(message, type = "info") {
  const toastEl = document.getElementById("liveToast");
  if (!toastEl) return;

  const toastBody = toastEl.querySelector(".toast-body");
  toastEl.classList.remove("bg-primary", "bg-success", "bg-danger");
  toastEl.classList.add(`bg-${type}`);
  toastBody.textContent = message;
  new bootstrap.Toast(toastEl).show();
}

// Update Status Button Handler
document.addEventListener("click", (e) => {
  if (e.target.classList.contains("btn-update-status")) {
    const orderId = e.target.dataset.orderId;
    const currentStatus = document
      .querySelector(`#status-${orderId}`)
      .textContent.trim();

    document.querySelectorAll(".status-card").forEach((card) => {
      card.classList.remove("selected");
      if (card.dataset.status.toLowerCase() === currentStatus.toLowerCase()) {
        card.classList.add("selected");
        card.querySelector("input").checked = true;
      }
    });

    new bootstrap.Modal(document.getElementById("updateStatusModal")).show();
  }
});

// Global Status Selection Function
window.selectStatus = function (status) {
  document.querySelectorAll(".status-card").forEach((card) => {
    card.classList.remove("selected");
    if (card.dataset.status === status) {
      card.classList.add("selected");
      card.querySelector("input").checked = true;
    }
  });
};

// Save Status Handler
document.getElementById("saveStatus").addEventListener("click", async () => {
  const orderId = document.getElementById("modalOrderId").value;
  const status = document.querySelector(
    'input[name="new_status"]:checked'
  )?.value;

  if (!status) {
    showToast("Please select a status", "danger");
    return;
  }

  try {
    // Adjusted path for update_order_status.php:
    const response = await fetch(`../../pages/ajax/update_order_status.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ order_id: orderId, new_status: status }),
    });

    const result = await response.json();

    if (result.success) {
      document.querySelector(`#status-${orderId}`).textContent = status;
      showToast("Status updated successfully!", "success");
      const modal = bootstrap.Modal.getInstance(
        document.getElementById("updateStatusModal")
      );
      if (modal) modal.hide();
    } else {
      showToast(result.error || "Update failed", "danger");
    }
  } catch (error) {
    showToast("Network error - please try again", "danger");
    console.error("Update error:", error);
  }
});

// Real-time Alerts System
let lastCheck = Math.floor(Date.now() / 1000);
const alertSound = new Audio("../../assets/sounds/new-order.mp3");

async function checkNewOrders() {
  try {
    // Adjusted path for get_new_orders.php:
    const response = await fetch(
      `../../pages/ajax/get_new_orders.php?lastCheck=${lastCheck}`
    );
    console.log("HTTP Status:", response.status);
    if (!response.ok) {
      const text = await response.text();
      console.error("Server response:", text);
      throw new Error("Network response was not ok");
    }

    const data = await response.json();
    console.log("Data received:", data);

    // Display new orders, if any
    if (Array.isArray(data.orders) && data.orders.length > 0) {
      data.orders.forEach((order) => {
        const date = new Date(order.timestamp * 1000);
        const alertHTML = `
                    <div class="list-group-item list-group-item-warning new-order-alert">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>New Order #${
                                  order.order_id
                                }</strong><br>
                                <small>${order.customer} - ₱${
          order.total_price
        }</small>
                            </div>
                            <small>${date.toLocaleTimeString()}</small>
                        </div>
                    </div>
                `;
        document
          .getElementById("liveAlerts")
          .insertAdjacentHTML("afterbegin", alertHTML);
        alertSound.play().catch(() => {}); // handle play error silently
        // Remove alert after 10s
        setTimeout(() => {
          const alertElem = document.querySelector(".new-order-alert");
          if (alertElem) alertElem.remove();
        }, 10000);
      });
    }

    // Update lastCheck based on server response
    lastCheck = data.lastCheck ? data.lastCheck : Math.floor(Date.now() / 1000);
  } catch (error) {
    console.error("Error fetching orders:", error);
    const errorAlert = `
          <div class="list-group-item list-group-item-danger">
              Connection Error: ${error.message}
          </div>
        `;
    document
      .getElementById("liveAlerts")
      .insertAdjacentHTML("afterbegin", errorAlert);
  }
}

setInterval(checkNewOrders, 5000);
checkNewOrders();

// --- Returns alert setup ---
let lastReturnCheck = Math.floor(Date.now() / 1000);
const returnSound = new Audio("../../assets/sounds/return-alert.mp3");

async function checkNewReturns() {
  try {
    const response = await fetch(
      `../../pages/ajax/get_new_returns.php?lastCheck=${lastReturnCheck}`
    );
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const data = await response.json();

    if (Array.isArray(data.returns) && data.returns.length) {
      data.returns.forEach((ret) => {
        const date = new Date(ret.timestamp * 1000);
        const alertHTML = `
                  <div class="list-group-item list-group-item-info new-return-alert">
                    <div class="d-flex justify-content-between align-items-center">
                      <div>
                        <strong>Return #${ret.return_id}</strong><br>
                        <small>Order #${ret.order_id} — ${ret.customer}</small>
                      </div>
                      <small>${date.toLocaleTimeString()}</small>
                    </div>
                  </div>
                `;
        document
          .getElementById("liveReturnAlerts")
          .insertAdjacentHTML("afterbegin", alertHTML);
        returnSound.play().catch(() => {});
        // auto‑remove after 10s
        setTimeout(() => {
          const el = document.querySelector(".new-return-alert");
          if (el) el.remove();
        }, 10000);
      });
    }

    // update lastReturnCheck
    lastReturnCheck = data.lastCheck || Math.floor(Date.now() / 1000);
  } catch (err) {
    console.error("Return alert error:", err);
    const errorHTML = `
          <div class="list-group-item list-group-item-danger">
            Connection Error (returns): ${err.message}
          </div>
        `;
    document
      .getElementById("liveReturnAlerts")
      .insertAdjacentHTML("afterbegin", errorHTML);
  }
}

// fire off every 5 seconds
setInterval(checkNewReturns, 5000);
checkNewReturns();

/*
function refreshOrdersTable() {
    console.log("Refreshing orders table at " + new Date().toLocaleTimeString());
    const url = `ajax/orders_table.php?filter=<?= urlencode($filterStatus) ?>&sort=<?= urlencode($sortBy) ?>&dir=<?= urlencode($sortDir) ?>&search=<?= urlencode($search) ?>`;
    console.log("Request URL: " + url);
    
    $.ajax({
        url: url,
        method: 'GET',
        success: function(html) {
            console.log("AJAX success, received HTML: ", html);
            $('#ordersTableBody').html(html);
        },
        error: function(xhr, status, error) {
            console.error('Error refreshing orders table:', error);
        }
    });
}
setInterval(refreshOrdersTable, 5000);
refreshOrdersTable();

DISABLED AUTO-REFRESH ORDER TABLE

*/
