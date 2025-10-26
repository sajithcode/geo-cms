// Staff-specific inventory JavaScript

document.addEventListener("DOMContentLoaded", function () {
  initializeStaffFunctions();
});

function initializeStaffFunctions() {
  // Initialize bulk actions
  initializeBulkActions();

  // Initialize quick filters
  initializeQuickFilters();

  // Initialize auto-refresh
  initializeAutoRefresh();

  // Initialize notification handlers
  initializeNotifications();

  // Load initial statistics
  loadStaffStatistics();

  // Initialize staff items search/filter if present
  const staffItemSearch = document.getElementById("staff-item-search");
  if (staffItemSearch) {
    staffItemSearch.addEventListener("input", filterStaffItems);
  }

  const staffCategoryFilter = document.getElementById("staff-category-filter");
  if (staffCategoryFilter) {
    staffCategoryFilter.addEventListener("change", filterStaffItems);
  }
}

function initializeBulkActions() {
  const bulkSelectAll = document.getElementById("bulk-select-all");
  if (bulkSelectAll) {
    bulkSelectAll.addEventListener("change", toggleAllRequests);
  }

  const bulkApproveBtn = document.getElementById("bulk-approve-btn");
  if (bulkApproveBtn) {
    bulkApproveBtn.addEventListener("click", bulkApproveRequests);
  }

  const bulkRejectBtn = document.getElementById("bulk-reject-btn");
  if (bulkRejectBtn) {
    bulkRejectBtn.addEventListener("click", bulkRejectRequests);
  }

  // Add listeners to individual checkboxes
  document.addEventListener("change", function (e) {
    if (e.target.classList.contains("request-checkbox")) {
      updateBulkActionButtons();
    }
  });
}

// Open request modal and pre-select the item
function openRequestModal(itemId, name, available) {
  // Ensure the request modal exists
  const modal = document.getElementById("request-modal");
  if (!modal) {
    showAlert("Request form is not available on this page", "warning");
    return;
  }

  // Pre-select item in the borrow form select if present
  const itemSelect = document.getElementById("item_id");
  if (itemSelect) {
    const option = Array.from(itemSelect.options).find(
      (o) => parseInt(o.value) === parseInt(itemId)
    );
    if (option) {
      itemSelect.value = option.value;
      itemSelect.dispatchEvent(new Event("change"));
    }
  }

  // Update quantity max if available provided
  const maxQ = document.getElementById("max-quantity");
  const quantityInput = document.getElementById("quantity");
  if (maxQ && typeof available !== "undefined") {
    maxQ.textContent = available;
  }
  if (quantityInput && typeof available !== "undefined") {
    quantityInput.max = available;
    quantityInput.value = Math.min(1, available);
  }

  showModal("request-modal");
}

// Filter staff items table
function filterStaffItems() {
  const searchValue = document.getElementById("staff-item-search")?.value.toLowerCase() || "";
  const categoryValue = document.getElementById("staff-category-filter")?.value || "";

  const table = document.getElementById("staff-items-table");
  if (!table) return;
  const rows = table.querySelectorAll("tbody tr");

  rows.forEach((row) => {
    if (row.querySelector(".empty-state")) return;

    const cells = row.querySelectorAll("td");
    const rowText = Array.from(cells).map((cell) => cell.textContent.toLowerCase()).join(" ");
    const rowCategory = row.dataset.category?.toLowerCase() || "";

    const matchesSearch = searchValue === "" || rowText.includes(searchValue);
    const matchesCategory = categoryValue === "" || rowCategory === categoryValue.toLowerCase();

    row.style.display = matchesSearch && matchesCategory ? "" : "none";
  });

  updateEmptyState(document.getElementById("staff-items-table"), "No items match your filters");
}

function initializeQuickFilters() {
  // Priority filter
  const priorityFilter = document.getElementById("priority-filter");
  if (priorityFilter) {
    priorityFilter.addEventListener("change", applyQuickFilters);
  }

  // Date range filter
  const dateFromFilter = document.getElementById("date-from");
  const dateToFilter = document.getElementById("date-to");
  if (dateFromFilter && dateToFilter) {
    dateFromFilter.addEventListener("change", applyQuickFilters);
    dateToFilter.addEventListener("change", applyQuickFilters);
  }

  // Quick filter buttons
  document.querySelectorAll(".quick-filter-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      document
        .querySelectorAll(".quick-filter-btn")
        .forEach((b) => b.classList.remove("active"));
      this.classList.add("active");
      applyQuickFilter(this.dataset.filter);
    });
  });
}

function initializeAutoRefresh() {
  // Auto-refresh every 30 seconds
  setInterval(() => {
    if (document.getElementById("auto-refresh").checked) {
      loadPendingRequests();
    }
  }, 30000);
}

function initializeNotifications() {
  // Check for new requests every minute
  setInterval(() => {
    checkNewRequests();
  }, 60000);
}

// Bulk Actions
function toggleAllRequests() {
  const selectAll = document.getElementById("bulk-select-all");
  const checkboxes = document.querySelectorAll(".request-checkbox");

  checkboxes.forEach((checkbox) => {
    checkbox.checked = selectAll.checked;
  });

  updateBulkActionButtons();
}

function updateBulkActionButtons() {
  const selectedCheckboxes = document.querySelectorAll(
    ".request-checkbox:checked"
  );
  const bulkActions = document.getElementById("bulk-actions");

  if (selectedCheckboxes.length > 0) {
    bulkActions.style.display = "block";
    document.getElementById("selected-count").textContent =
      selectedCheckboxes.length;
  } else {
    bulkActions.style.display = "none";
  }
}

function bulkApproveRequests() {
  const selectedIds = getSelectedRequestIds();
  if (selectedIds.length === 0) {
    showAlert("Please select requests to approve", "warning");
    return;
  }

  if (
    !confirm(
      `Are you sure you want to approve ${selectedIds.length} request(s)?`
    )
  ) {
    return;
  }

  processBulkAction(selectedIds, "approve");
}

function bulkRejectRequests() {
  const selectedIds = getSelectedRequestIds();
  if (selectedIds.length === 0) {
    showAlert("Please select requests to reject", "warning");
    return;
  }

  // Show bulk rejection modal with notes
  showBulkRejectionModal(selectedIds);
}

function getSelectedRequestIds() {
  const selectedCheckboxes = document.querySelectorAll(
    ".request-checkbox:checked"
  );
  return Array.from(selectedCheckboxes).map((cb) => cb.value);
}

function processBulkAction(requestIds, action, notes = "") {
  const formData = new FormData();
  formData.append("action", "bulk_" + action);
  formData.append("request_ids", JSON.stringify(requestIds));
  formData.append("notes", notes);
  formData.append(
    "csrf_token",
    document.querySelector('meta[name="csrf-token"]').content
  );

  fetch("../php/bulk_update_requests.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert(
          `${requestIds.length} request(s) ${action}d successfully`,
          "success"
        );
        setTimeout(() => location.reload(), 1000);
      } else {
        showAlert(data.message || `Failed to ${action} requests`, "danger");
      }
    })
    .catch((error) => {
      console.error(`Error processing bulk ${action}:`, error);
      showAlert(`Error processing bulk ${action}`, "danger");
    });
}

function showBulkRejectionModal(requestIds) {
  document.getElementById("bulk-rejection-count").textContent =
    requestIds.length;
  document.getElementById("bulk-rejection-ids").value =
    JSON.stringify(requestIds);
  showModal("bulk-rejection-modal");
}

function handleBulkRejection() {
  const requestIds = JSON.parse(
    document.getElementById("bulk-rejection-ids").value
  );
  const notes = document.getElementById("bulk-rejection-notes").value.trim();

  if (!notes) {
    showAlert("Please provide a reason for rejection", "warning");
    return;
  }

  processBulkAction(requestIds, "reject", notes);
  hideModal("bulk-rejection-modal");
}

// Quick Filters
function applyQuickFilters() {
  const priority = document.getElementById("priority-filter")?.value || "";
  const dateFrom = document.getElementById("date-from")?.value || "";
  const dateTo = document.getElementById("date-to")?.value || "";

  const table = document.getElementById("requests-table");
  const rows = table.querySelectorAll("tbody tr");

  rows.forEach((row) => {
    if (row.querySelector(".empty-state")) return;

    const rowPriority = row.dataset.priority || "";
    const rowDate = row.dataset.date || "";

    let show = true;

    // Priority filter
    if (priority && rowPriority !== priority) {
      show = false;
    }

    // Date range filter
    if (dateFrom && rowDate < dateFrom) {
      show = false;
    }

    if (dateTo && rowDate > dateTo) {
      show = false;
    }

    row.style.display = show ? "" : "none";
  });

  updateEmptyState(table, "No requests match your filters");
}

function applyQuickFilter(filter) {
  const table = document.getElementById("requests-table");
  const rows = table.querySelectorAll("tbody tr");

  const today = new Date();
  const todayStr = today.toISOString().split("T")[0];

  rows.forEach((row) => {
    if (row.querySelector(".empty-state")) return;

    let show = true;
    const rowDate = row.dataset.date || "";
    const rowStatus = row.dataset.status || "";

    switch (filter) {
      case "today":
        show = rowDate === todayStr;
        break;
      case "pending":
        show = rowStatus === "pending";
        break;
      case "urgent":
        show = row.dataset.priority === "high";
        break;
      case "overdue":
        const expectedReturn = row.dataset.expectedReturn || "";
        show =
          expectedReturn &&
          expectedReturn < todayStr &&
          rowStatus === "approved";
        break;
      case "all":
      default:
        show = true;
        break;
    }

    row.style.display = show ? "" : "none";
  });

  updateEmptyState(table, "No requests match this filter");
}

// Data Loading Functions
function loadPendingRequests() {
  fetch("../php/get_pending_requests.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updatePendingRequestsTable(data.requests);
        updateNotificationBadge(data.count);
      }
    })
    .catch((error) => console.error("Error loading pending requests:", error));
}

function updatePendingRequestsTable(requests) {
  const tbody = document
    .getElementById("requests-table")
    .querySelector("tbody");
  const existingRows = tbody.querySelectorAll("tr:not(.empty-state)");

  // Only update if there are changes
  if (existingRows.length !== requests.length) {
    // Rebuild table (simplified for real-time updates)
    location.reload();
  }
}

function checkNewRequests() {
  fetch("../php/check_new_requests.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.hasNew) {
        showNotification("New borrow requests received!", "info");
        updateNotificationBadge(data.totalPending);
      }
    })
    .catch((error) => console.error("Error checking new requests:", error));
}

function loadStaffStatistics() {
  fetch("../php/get_staff_statistics.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateStaffStatistics(data.stats);
      }
    })
    .catch((error) => console.error("Error loading staff statistics:", error));
}

function updateStaffStatistics(stats) {
  document.getElementById("pending-requests-count").textContent =
    stats.pending_requests || 0;
  document.getElementById("approved-today-count").textContent =
    stats.approved_today || 0;
  document.getElementById("overdue-items-count").textContent =
    stats.overdue_items || 0;
  document.getElementById("low-stock-count").textContent =
    stats.low_stock_items || 0;
}

// Quick Actions
function quickApprove(requestId) {
  if (!confirm("Quick approve this request?")) {
    return;
  }

  const formData = new FormData();
  formData.append("request_id", requestId);
  formData.append("action", "approve");
  formData.append("notes", "Quick approval");
  formData.append(
    "csrf_token",
    document.querySelector('meta[name="csrf-token"]').content
  );

  fetch("../php/update_request_status.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Request approved successfully", "success");
        updateRequestRow(requestId, "approved");
      } else {
        showAlert(data.message || "Failed to approve request", "danger");
      }
    })
    .catch((error) => {
      console.error("Error approving request:", error);
      showAlert("Error approving request", "danger");
    });
}

function updateRequestRow(requestId, newStatus) {
  const row = document.querySelector(`tr[data-request-id="${requestId}"]`);
  if (row) {
    const statusCell = row.querySelector(".status-badge");
    if (statusCell) {
      statusCell.textContent = newStatus.toUpperCase();
      statusCell.className = `badge badge-${getStatusBadgeClass(newStatus)}`;
    }

    // Update action buttons
    const actionsCell = row.querySelector(".actions");
    if (actionsCell && newStatus !== "pending") {
      actionsCell.innerHTML = `
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewRequestDetails(${requestId})">
                    <i class="fas fa-eye"></i> View
                </button>
            `;
    }
  }
}

// Inventory Management
function markMaintenance(itemId) {
  const quantity = prompt("How many items need maintenance?");
  if (!quantity || isNaN(quantity) || parseInt(quantity) <= 0) {
    return;
  }

  const formData = new FormData();
  formData.append("item_id", itemId);
  formData.append("quantity", quantity);
  formData.append("action", "maintenance");
  formData.append(
    "csrf_token",
    document.querySelector('meta[name="csrf-token"]').content
  );

  fetch("../php/update_item_status.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Item status updated", "success");
        setTimeout(() => location.reload(), 1000);
      } else {
        showAlert(data.message || "Failed to update item status", "danger");
      }
    })
    .catch((error) => {
      console.error("Error updating item status:", error);
      showAlert("Error updating item status", "danger");
    });
}

function markRepaired(itemId) {
  const quantity = prompt("How many items are repaired and available?");
  if (!quantity || isNaN(quantity) || parseInt(quantity) <= 0) {
    return;
  }

  const formData = new FormData();
  formData.append("item_id", itemId);
  formData.append("quantity", quantity);
  formData.append("action", "repair");
  formData.append(
    "csrf_token",
    document.querySelector('meta[name="csrf-token"]').content
  );

  fetch("../php/update_item_status.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Item status updated", "success");
        setTimeout(() => location.reload(), 1000);
      } else {
        showAlert(data.message || "Failed to update item status", "danger");
      }
    })
    .catch((error) => {
      console.error("Error updating item status:", error);
      showAlert("Error updating item status", "danger");
    });
}

// Return Management
function markReturned(requestId) {
  const condition = prompt("Item condition (Good/Damaged/Needs Repair):");
  if (!condition) {
    return;
  }

  const formData = new FormData();
  formData.append("request_id", requestId);
  formData.append("action", "return");
  formData.append("condition", condition);
  formData.append(
    "csrf_token",
    document.querySelector('meta[name="csrf-token"]').content
  );

  fetch("../php/process_return.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Item marked as returned", "success");
        updateRequestRow(requestId, "returned");
      } else {
        showAlert(data.message || "Failed to process return", "danger");
      }
    })
    .catch((error) => {
      console.error("Error processing return:", error);
      showAlert("Error processing return", "danger");
    });
}

// Notifications
function updateNotificationBadge(count) {
  const badge = document.querySelector(".notification-badge");
  if (badge) {
    badge.textContent = count;
    badge.style.display = count > 0 ? "block" : "none";
  }
}

function showNotification(message, type = "info") {
  // Create notification element
  const notification = document.createElement("div");
  notification.className = `alert alert-${type} notification-popup`;
  notification.innerHTML = `
        <i class="fas fa-bell"></i>
        ${message}
        <button type="button" class="close" onclick="this.parentElement.remove()">
            <span>&times;</span>
        </button>
    `;

  // Add to page
  const container =
    document.querySelector(".notification-container") || document.body;
  container.appendChild(notification);

  // Auto-remove after 5 seconds
  setTimeout(() => {
    if (notification.parentElement) {
      notification.remove();
    }
  }, 5000);
}

// Export Functions
function exportStaffReport() {
  const params = new URLSearchParams({
    type: "staff",
    date_from: document.getElementById("date-from")?.value || "",
    date_to: document.getElementById("date-to")?.value || "",
    status: document.getElementById("status-filter")?.value || "",
  });

  window.open(`../php/export_report.php?${params.toString()}`, "_blank");
}

function printRequestsList() {
  const printContent = document.getElementById("requests-table").outerHTML;
  const printWindow = window.open("", "_blank");

  printWindow.document.write(`
        <html>
            <head>
                <title>Borrow Requests - ${new Date().toLocaleDateString()}</title>
                <link rel="stylesheet" href="../css/store.css">
                <style>
                    @media print {
                        .btn, .checkbox { display: none !important; }
                        .table { font-size: 12px; }
                    }
                </style>
            </head>
            <body>
                <h1>Borrow Requests Report</h1>
                <p>Generated on: ${new Date().toLocaleString()}</p>
                ${printContent}
                <script>window.print();</script>
            </body>
        </html>
    `);

  printWindow.document.close();
}
