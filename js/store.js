// Inventory Management JavaScript

document.addEventListener("DOMContentLoaded", function () {
  initializeInventory();
  initializeImageHandling();
});

// Custom alert function for inventory dashboard
function showAlert(message, type = "info", duration = 5000) {
  // Remove any existing alerts first
  const existingAlerts = document.querySelectorAll(".inventory-alert");
  existingAlerts.forEach((alert) => alert.remove());

  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${type} alert-dismissible inventory-alert`;

  // Try to use notification container first, then fall back to fixed positioning
  const notificationContainer = document.getElementById(
    "notification-container"
  );

  if (notificationContainer) {
    // Use in-page notification container
    alertDiv.style.cssText = `
            margin-bottom: 15px;
            animation: slideInDown 0.3s ease-out;
        `;
    notificationContainer.appendChild(alertDiv);
  } else {
    // Use fixed positioning as fallback
    alertDiv.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease-out;
        `;
    document.body.appendChild(alertDiv);
  }

  alertDiv.innerHTML = `
        <div class="alert-content">
            <i class="fas fa-${getAlertIcon(type)}"></i>
            <span class="alert-message">${message}</span>
        </div>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">×</button>
    `;

  // Auto-hide after duration
  if (duration > 0) {
    setTimeout(() => {
      if (alertDiv.parentElement) {
        alertDiv.style.animation = notificationContainer
          ? "slideOutUp 0.3s ease-in forwards"
          : "slideOut 0.3s ease-in forwards";
        setTimeout(() => alertDiv.remove(), 300);
      }
    }, duration);
  }
}

function getAlertIcon(type) {
  const icons = {
    success: "check-circle",
    danger: "exclamation-circle",
    warning: "exclamation-triangle",
    info: "info-circle",
  };
  return icons[type] || "info-circle";
}

function initializeInventory() {
  // Initialize form handlers
  initializeFormHandlers();

  // Initialize search and filters
  initializeSearchAndFilters();

  // Initialize item selection handlers
  initializeItemSelection();

  // Initialize date range handlers
  initializeDateRangeHandlers();

  // Initialize table sorting
  initializeTableSorting();

  // Load initial data
  updateAvailableQuantities();
}

function initializeFormHandlers() {
  // Borrow request form
  const borrowForm = document.getElementById("borrow-request-form");
  if (borrowForm) {
    borrowForm.addEventListener("submit", handleBorrowRequest);
  }

  // Item form (admin)
  const itemForm = document.getElementById("item-form");
  if (itemForm) {
    itemForm.addEventListener("submit", handleItemSave);
  }

  // Category form (admin)
  const categoryForm = document.getElementById("category-form");
  if (categoryForm) {
    categoryForm.addEventListener("submit", handleCategorySave);
  }

  // Approval form (staff)
  const approvalForm = document.getElementById("approval-form");
  if (approvalForm) {
    approvalForm.addEventListener("submit", handleApprovalSubmit);
  }
}

function initializeSearchAndFilters() {
  // Request search
  const requestSearch = document.getElementById("request-search");
  if (requestSearch) {
    requestSearch.addEventListener("input", filterRequests);
  }

  // Status filter
  const statusFilter = document.getElementById("status-filter");
  if (statusFilter) {
    statusFilter.addEventListener("change", filterRequests);
  }

  // Item filter (staff)
  const itemFilter = document.getElementById("item-filter");
  if (itemFilter) {
    itemFilter.addEventListener("change", filterRequests);
  }

  // Category filter (admin and student)
  const categoryFilter = document.getElementById("category-filter");
  if (categoryFilter) {
    categoryFilter.addEventListener("change", filterItems);
  }

  // Items search (admin and student)
  const itemsSearch = document.getElementById("items-search");
  if (itemsSearch) {
    itemsSearch.addEventListener("input", filterItems);
  }

  // Item search (admin - alternative name)
  const itemSearch = document.getElementById("item-search");
  if (itemSearch) {
    itemSearch.addEventListener("input", filterItems);
  }
}

function initializeItemSelection() {
  const itemSelect = document.getElementById("item_id");
  if (itemSelect) {
    itemSelect.addEventListener("change", handleItemSelection);
  }

  // Update quantity validation when item is selected
  const quantityInput = document.getElementById("quantity");
  if (quantityInput) {
    quantityInput.addEventListener("input", validateQuantity);
  }
}

function initializeDateRangeHandlers() {
  const startDateInput = document.getElementById("borrow_start_date");
  const endDateInput = document.getElementById("borrow_end_date");

  if (startDateInput && endDateInput) {
    startDateInput.addEventListener("change", function () {
      validateDateRange();
      updateEndDateMinimum();
    });

    endDateInput.addEventListener("change", function () {
      validateDateRange();
    });
  }
}

function updateEndDateMinimum() {
  const startDateInput = document.getElementById("borrow_start_date");
  const endDateInput = document.getElementById("borrow_end_date");

  if (startDateInput.value) {
    const startDate = new Date(startDateInput.value);
    const nextDay = new Date(startDate);
    nextDay.setDate(startDate.getDate() + 1);

    const minEndDate = nextDay.toISOString().split("T")[0];
    endDateInput.min = minEndDate;

    // Clear end date if it's before the new minimum
    if (endDateInput.value && endDateInput.value <= startDateInput.value) {
      endDateInput.value = "";
    }
  }
}

function validateDateRange() {
  const startDateInput = document.getElementById("borrow_start_date");
  const endDateInput = document.getElementById("borrow_end_date");

  if (startDateInput.value && endDateInput.value) {
    const startDate = new Date(startDateInput.value);
    const endDate = new Date(endDateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (startDate < today) {
      showAlert("Start date cannot be in the past", "warning");
      startDateInput.value = "";
      return false;
    }

    if (endDate <= startDate) {
      showAlert("End date must be after start date", "warning");
      endDateInput.value = "";
      return false;
    }

    // Check for maximum borrow period (optional - set to 30 days)
    const daysDifference = Math.ceil(
      (endDate - startDate) / (1000 * 60 * 60 * 24)
    );
    if (daysDifference > 30) {
      showAlert("Borrow period cannot exceed 30 days", "warning");
      endDateInput.value = "";
      return false;
    }

    return true;
  }

  return false;
}

function initializeTableSorting() {
  const tables = document.querySelectorAll(".table");
  tables.forEach((table) => {
    const headers = table.querySelectorAll("th");
    headers.forEach((header) => {
      if (header.textContent.trim() && !header.querySelector("input")) {
        header.style.cursor = "pointer";
        header.addEventListener("click", () => sortTable(table, header));
      }
    });
  });
}

// Borrow Request Functions
function handleBorrowRequest(e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"]');

  // Validate form
  if (!validateBorrowRequest(form)) {
    return;
  }

  setLoading(submitBtn, true);

  fetch("php/process_borrow_request.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Request submitted successfully!", "success");
        hideModal("request-modal");
        form.reset();
        setTimeout(() => location.reload(), 1500);
      } else {
        showAlert(data.message || "Failed to submit request", "danger");
      }
    })
    .catch((error) => {
      console.error("Error submitting request:", error);
      showAlert("An error occurred while submitting the request", "danger");
    })
    .finally(() => {
      setLoading(submitBtn, false);
    });
}

function validateBorrowRequest(form) {
  const itemId = form.querySelector("#item_id").value;
  const quantity = parseInt(form.querySelector("#quantity").value);
  const startDate = form.querySelector("#borrow_start_date").value;
  const endDate = form.querySelector("#borrow_end_date").value;
  const reason = form.querySelector("#reason").value.trim();

  if (!itemId) {
    showAlert("Please select an item to borrow", "danger");
    return false;
  }

  if (!quantity || quantity < 1) {
    showAlert("Please enter a valid quantity", "danger");
    return false;
  }

  const maxQuantity = parseInt(
    document.getElementById("max-quantity").textContent
  );
  if (quantity > maxQuantity) {
    showAlert(`Quantity cannot exceed ${maxQuantity}`, "danger");
    return false;
  }

  if (!startDate || !endDate) {
    showAlert("Please select both start and end dates", "danger");
    return false;
  }

  if (!validateDateRange()) {
    return false;
  }

  if (!reason || reason.length < 10) {
    showAlert(
      "Please provide a detailed reason (at least 10 characters)",
      "danger"
    );
    return false;
  }

  return true;
}

function handleItemSelection() {
  const itemSelect = document.getElementById("item_id");
  const selectedOption = itemSelect.options[itemSelect.selectedIndex];

  if (selectedOption.value) {
    const available = parseInt(selectedOption.dataset.available);
    const description = selectedOption.dataset.description;

    // Show item details
    document.getElementById("item-details").style.display = "block";
    document.getElementById("item-description").textContent =
      description || "No description available";
    document.getElementById("available-quantity").textContent = available;
    document.getElementById("max-quantity").textContent = available;

    // Update quantity input
    const quantityInput = document.getElementById("quantity");
    quantityInput.max = available;
    quantityInput.placeholder = `Max: ${available}`;

    if (available === 0) {
      quantityInput.disabled = true;
      showAlert("This item is currently out of stock", "warning");
    } else {
      quantityInput.disabled = false;
    }
  } else {
    document.getElementById("item-details").style.display = "none";
    const quantityInput = document.getElementById("quantity");
    quantityInput.max = "";
    quantityInput.disabled = false;
  }
}

function validateQuantity() {
  const quantityInput = document.getElementById("quantity");
  const maxQuantity = parseInt(
    document.getElementById("max-quantity").textContent
  );
  const quantity = parseInt(quantityInput.value);

  if (quantity > maxQuantity) {
    quantityInput.value = maxQuantity;
    showAlert(
      `Quantity adjusted to maximum available: ${maxQuantity}`,
      "warning"
    );
  }
}

// Function to open request modal with pre-selected item (for student dashboard)
function openRequestModalForItem(itemId, itemName, availableQty) {
  // Open the request modal
  showModal('request-modal');

  // Set the selected item in the dropdown
  const itemSelect = document.getElementById('item_id');
  if (itemSelect) {
    itemSelect.value = itemId;

    // Trigger change event to update item details
    const event = new Event('change');
    itemSelect.dispatchEvent(event);
  }
}

// Search and Filter Functions
function filterRequests() {
  const searchValue =
    document.getElementById("request-search")?.value.toLowerCase() || "";
  const statusValue = document.getElementById("status-filter")?.value || "";
  const itemValue = document.getElementById("item-filter")?.value || "";

  const table = document.getElementById("requests-table");
  const rows = table.querySelectorAll("tbody tr");

  rows.forEach((row) => {
    if (row.querySelector(".empty-state")) return;

    const cells = row.querySelectorAll("td");
    const rowText = Array.from(cells)
      .map((cell) => cell.textContent.toLowerCase())
      .join(" ");
    const rowStatus = row.dataset.status || "";
    const rowItem = row.dataset.item || "";

    const matchesSearch = searchValue === "" || rowText.includes(searchValue);
    const matchesStatus = statusValue === "" || rowStatus === statusValue;
    const matchesItem = itemValue === "" || rowItem === itemValue;

    row.style.display =
      matchesSearch && matchesStatus && matchesItem ? "" : "none";
  });

  updateEmptyState(table, "No requests match your filters");
}

function filterItems() {
  // Support both item-search (admin) and items-search (student) IDs
  const searchValue =
    (document.getElementById("item-search")?.value.toLowerCase() ||
      document.getElementById("items-search")?.value.toLowerCase() || "");
  const categoryValue = document.getElementById("category-filter")?.value || "";

  const table = document.getElementById("items-table");
  const rows = table.querySelectorAll("tbody tr");

  rows.forEach((row) => {
    if (row.querySelector(".empty-state")) return;

    const cells = row.querySelectorAll("td");
    const rowText = Array.from(cells)
      .map((cell) => cell.textContent.toLowerCase())
      .join(" ");
    const rowCategory = row.dataset.category || "";

    const matchesSearch = searchValue === "" || rowText.includes(searchValue);
    const matchesCategory =
      categoryValue === "" || rowCategory === categoryValue;

    row.style.display = matchesSearch && matchesCategory ? "" : "none";
  });

  updateEmptyState(table, "No items match your filters");
}

function updateEmptyState(table, message) {
  const tbody = table.querySelector("tbody");
  const visibleRows = Array.from(tbody.querySelectorAll("tr")).filter(
    (row) => row.style.display !== "none" && !row.querySelector(".empty-state")
  );

  let emptyRow = tbody.querySelector(".filter-empty-state");

  if (visibleRows.length === 0) {
    if (!emptyRow) {
      emptyRow = document.createElement("tr");
      emptyRow.className = "filter-empty-state";
      emptyRow.innerHTML = `
                <td colspan="100%" class="text-center">
                    <div class="empty-state">
                        <div class="empty-icon">🔍</div>
                        <h3>No Results Found</h3>
                        <p>${message}</p>
                    </div>
                </td>
            `;
      tbody.appendChild(emptyRow);
    }
  } else if (emptyRow) {
    emptyRow.remove();
  }
}

// Request Management Functions
function viewRequestDetails(requestId) {
  fetch(`php/get_request_details.php?id=${requestId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        displayRequestDetails(data.request);
        showModal("request-details-modal");
      } else {
        showAlert("Failed to load request details", "danger");
      }
    })
    .catch((error) => {
      console.error("Error loading request details:", error);
      showAlert("Error loading request details", "danger");
    });
}

function displayRequestDetails(request) {
  const content = document.getElementById("request-details-content");
  content.innerHTML = `
        <div class="request-details">
            <div class="detail-section">
                <h4>Request Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Request ID:</label>
                        <span>#${request.id}</span>
                    </div>
                    <div class="detail-item">
                        <label>Status:</label>
                        <span class="badge badge-${getStatusBadgeClass(
    request.status
  )}">${request.status.toUpperCase()}</span>
                    </div>
                    <div class="detail-item">
                        <label>Request Date:</label>
                        <span>${formatDate(
    request.request_date,
    "DD/MM/YYYY HH:mm"
  )}</span>
                    </div>
                    ${request.approved_date
      ? `
                    <div class="detail-item">
                        <label>Approved Date:</label>
                        <span>${formatDate(
        request.approved_date,
        "DD/MM/YYYY HH:mm"
      )}</span>
                    </div>
                    `
      : ""
    }
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Requester Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Name:</label>
                        <span>${request.requester_name}</span>
                    </div>
                    <div class="detail-item">
                        <label>ID:</label>
                        <span>${request.requester_id}</span>
                    </div>
                    <div class="detail-item">
                        <label>Email:</label>
                        <span>${request.requester_email}</span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Item Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Item:</label>
                        <span>${request.item_name}</span>
                    </div>
                    <div class="detail-item">
                        <label>Quantity:</label>
                        <span>${request.quantity}</span>
                    </div>
                    <div class="detail-item">
                        <label>Borrow Period:</label>
                        <span>
                            ${request.borrow_start_date &&
      request.borrow_end_date
      ? `${formatDate(
        request.borrow_start_date
      )} to ${formatDate(request.borrow_end_date)}`
      : request.expected_return_date
        ? `Expected Return: ${formatDate(
          request.expected_return_date
        )}`
        : "Not specified"
    }
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Reason for Borrowing</h4>
                <p>${request.reason}</p>
            </div>
            
            ${request.notes
      ? `
            <div class="detail-section">
                <h4>Notes</h4>
                <p>${request.notes}</p>
            </div>
            `
      : ""
    }
            
            ${request.approved_by_name
      ? `
            <div class="detail-section">
                <h4>Approved By</h4>
                <p>${request.approved_by_name}</p>
            </div>
            `
      : ""
    }
        </div>
    `;
}

function cancelRequest(requestId) {
  if (!confirm("Are you sure you want to cancel this request?")) {
    return;
  }

  const formData = new FormData();
  formData.append("request_id", requestId);
  formData.append("action", "cancel");
  formData.append(
    "csrf_token",
    document.querySelector('meta[name="csrf-token"]').content
  );

  fetch("php/update_request_status.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Request cancelled successfully", "success");
        setTimeout(() => location.reload(), 1000);
      } else {
        showAlert(data.message || "Failed to cancel request", "danger");
      }
    })
    .catch((error) => {
      console.error("Error cancelling request:", error);
      showAlert("Error cancelling request", "danger");
    });
}

// Staff Functions
function approveRequest(requestId) {
  showApprovalModal(requestId, "approve");
}

function rejectRequest(requestId) {
  showApprovalModal(requestId, "reject");
}

function showApprovalModal(requestId, action) {
  document.getElementById("approval-request-id").value = requestId;
  document.getElementById("approval-action").value = action;

  const title = action === "approve" ? "Approve Request" : "Reject Request";
  const submitBtn = document.getElementById("approval-submit-btn");

  document.getElementById("approval-title").textContent = title;
  submitBtn.textContent = action === "approve" ? "Approve" : "Reject";
  submitBtn.className = `btn ${action === "approve" ? "btn-success" : "btn-danger"
    }`;

  showModal("approval-modal");
}

function handleApprovalSubmit(e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"]');

  setLoading(submitBtn, true);

  fetch("php/update_request_status.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Request updated successfully", "success");
        hideModal("approval-modal");
        form.reset();
        setTimeout(() => location.reload(), 1000);
      } else {
        showAlert(data.message || "Failed to update request", "danger");
      }
    })
    .catch((error) => {
      console.error("Error updating request:", error);
      showAlert("Error updating request", "danger");
    })
    .finally(() => {
      setLoading(submitBtn, false);
    });
}

// Admin Functions
function editItem(itemId) {
  fetch(`php/get_item_details.php?id=${itemId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        populateItemForm(data.item);
        showModal("item-modal");
      } else {
        showAlert("Failed to load item details", "danger");
      }
    })
    .catch((error) => {
      console.error("Error loading item details:", error);
      showAlert("Error loading item details", "danger");
    });
}

function populateItemForm(item) {
  document.getElementById("item-modal-title").textContent = "Edit Item";
  document.getElementById("item-id").value = item.id;
  document.getElementById("item-name").value = item.name;
  document.getElementById("item-description").value = item.description || "";
  document.getElementById("item-category").value = item.category_id || "";
  document.getElementById("item-quantity-total").value = item.quantity_total;
  document.getElementById("item-quantity-available").value =
    item.quantity_available;
  document.getElementById("item-quantity-borrowed").value =
    item.quantity_borrowed;
  document.getElementById("item-quantity-maintenance").value =
    item.quantity_maintenance;

  // Handle current image
  const currentImagePreview = document.getElementById("current-image-preview");
  const currentImage = document.getElementById("current-image");
  const newImagePreview = document.getElementById("new-image-preview");
  const imageInput = document.getElementById("item-image");

  // Clear new image preview
  if (newImagePreview) {
    newImagePreview.style.display = "none";
  }
  if (imageInput) {
    imageInput.value = "";
    imageInput.removeAttribute("data-remove-current");
  }

  // Show current image if exists
  if (item.image_path && currentImagePreview && currentImage) {
    currentImage.src = "../" + item.image_path;
    currentImage.onerror = function () {
      currentImagePreview.style.display = "none";
    };
    currentImage.onload = function () {
      currentImagePreview.style.display = "block";
    };
  } else if (currentImagePreview) {
    currentImagePreview.style.display = "none";
  }
}

function handleItemSave(e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"]');

  setLoading(submitBtn, true);

  const endpoint = document.getElementById("item-id").value
    ? "php/save_item.php"
    : "php/save_item.php";

  fetch(endpoint, {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Item saved successfully", "success");
        hideModal("item-modal");
        form.reset();
        setTimeout(() => location.reload(), 1000);
      } else {
        showAlert(data.message || "Failed to save item", "danger");
      }
    })
    .catch((error) => {
      console.error("Error saving item:", error);
      showAlert("Error saving item", "danger");
    })
    .finally(() => {
      setLoading(submitBtn, false);
    });
}

function deleteItem(itemId) {
  if (
    !confirm(
      "Are you sure you want to delete this item? This action cannot be undone."
    )
  ) {
    return;
  }

  const formData = new FormData();
  formData.append("item_id", itemId);
  formData.append(
    "csrf_token",
    document.querySelector('meta[name="csrf-token"]').content
  );

  fetch("php/delete_item.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Item deleted successfully", "success");
        setTimeout(() => location.reload(), 1000);
      } else {
        showAlert(data.message || "Failed to delete item", "danger");
      }
    })
    .catch((error) => {
      console.error("Error deleting item:", error);
      showAlert("Error deleting item", "danger");
    });
}

function handleCategorySave(e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"]');

  setLoading(submitBtn, true);

  fetch("php/create_category.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Category created successfully", "success");
        hideModal("category-modal");
        form.reset();
        setTimeout(() => location.reload(), 1000);
      } else {
        showAlert(data.message || "Failed to create category", "danger");
      }
    })
    .catch((error) => {
      console.error("Error creating category:", error);
      showAlert("Error creating category", "danger");
    })
    .finally(() => {
      setLoading(submitBtn, false);
    });
}

function viewHistory(itemId) {
  fetch(`php/get_item_history.php?id=${itemId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        displayItemHistory(data.history);
      } else {
        showAlert("Failed to load item history", "danger");
      }
    })
    .catch((error) => {
      console.error("Error loading item history:", error);
      showAlert("Error loading item history", "danger");
    });
}

// Utility Functions
function updateAvailableQuantities() {
  // Update real-time quantities (if needed)
  const quantityBadges = document.querySelectorAll(".quantity-badge");
  quantityBadges.forEach((badge) => {
    const quantity = parseInt(badge.textContent);
    badge.className = `quantity-badge ${quantity > 0 ? "available" : "unavailable"
      }`;
  });
}

function sortTable(table, header) {
  const tbody = table.querySelector("tbody");
  const rows = Array.from(tbody.querySelectorAll("tr")).filter(
    (row) => !row.querySelector(".empty-state")
  );

  const headerIndex = Array.from(header.parentNode.children).indexOf(header);
  const isNumeric =
    header.textContent.includes("Quantity") ||
    header.textContent.includes("Total") ||
    header.textContent.includes("Available");

  const sortedRows = rows.sort((a, b) => {
    const aVal = a.children[headerIndex].textContent.trim();
    const bVal = b.children[headerIndex].textContent.trim();

    if (isNumeric) {
      return parseInt(aVal) - parseInt(bVal);
    } else {
      return aVal.localeCompare(bVal);
    }
  });

  // Toggle sort direction
  const isAscending = header.classList.contains("sort-desc");
  if (isAscending) {
    sortedRows.reverse();
    header.classList.remove("sort-desc");
    header.classList.add("sort-asc");
  } else {
    header.classList.remove("sort-asc");
    header.classList.add("sort-desc");
  }

  // Remove sort classes from other headers
  header.parentNode.querySelectorAll("th").forEach((th) => {
    if (th !== header) {
      th.classList.remove("sort-asc", "sort-desc");
    }
  });

  // Reorder rows
  sortedRows.forEach((row) => tbody.appendChild(row));
}

function refreshData() {
  showAlert("Refreshing data...", "info");
  setTimeout(() => location.reload(), 500);
}

function exportRequests() {
  window.open("../php/export_requests.php", "_blank");
}

function exportReport() {
  window.open("../php/export_inventory_report.php", "_blank");
}

function generateReport() {
  window.open("../php/generate_inventory_report.php", "_blank");
}

function getStatusBadgeClass(status) {
  const classes = {
    pending: "warning",
    approved: "success",
    rejected: "danger",
    returned: "secondary",
  };
  return classes[status] || "secondary";
}

// Clear form when modal is closed
document.addEventListener("click", function (e) {
  if (e.target.classList.contains("modal")) {
    const forms = e.target.querySelectorAll("form");
    forms.forEach((form) => {
      if (form.id !== "approval-form") {
        // Don't reset approval form automatically
        form.reset();
      }

      // Clear image previews for item form
      if (form.id === "item-form") {
        const currentImagePreview = document.getElementById(
          "current-image-preview"
        );
        const newImagePreview = document.getElementById("new-image-preview");
        if (currentImagePreview) {
          currentImagePreview.style.display = "none";
        }
        if (newImagePreview) {
          newImagePreview.style.display = "none";
        }

        // Remove image removal flags
        const removeImageInput = form.querySelector(
          'input[name="remove_current_image"]'
        );
        if (removeImageInput) {
          removeImageInput.remove();
        }
      }
    });

    // Reset item modal title
    const itemModalTitle = document.getElementById("item-modal-title");
    if (itemModalTitle) {
      itemModalTitle.textContent = "Add New Item";
    }

    // Clear item ID for new items
    const itemIdInput = document.getElementById("item-id");
    if (itemIdInput) {
      itemIdInput.value = "";
    }

    // Clear image previews when opening modal for new item
    const currentImagePreview = document.getElementById(
      "current-image-preview"
    );
    const newImagePreview = document.getElementById("new-image-preview");
    if (currentImagePreview) {
      currentImagePreview.style.display = "none";
    }
    if (newImagePreview) {
      newImagePreview.style.display = "none";
    }
  }
});

// Image handling functions
function initializeImageHandling() {
  // Image preview for item form
  const imageInput = document.getElementById("item-image");
  if (imageInput) {
    imageInput.addEventListener("change", handleImagePreview);
  }

  // Item selection change for student dashboard
  const itemSelect = document.getElementById("item_id");
  if (itemSelect) {
    itemSelect.addEventListener("change", handleItemSelection);
  }
}

function handleImagePreview(event) {
  const file = event.target.files[0];
  const previewContainer = document.getElementById("new-image-preview");
  const previewImage = document.getElementById("preview-image");

  if (file && previewContainer && previewImage) {
    // Validate file type
    if (!file.type.startsWith("image/")) {
      showAlert("Please select a valid image file", "danger");
      event.target.value = "";
      return;
    }

    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
      showAlert("Image size too large. Maximum 5MB allowed", "danger");
      event.target.value = "";
      return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
      previewImage.src = e.target.result;
      previewContainer.style.display = "block";
    };
    reader.readAsDataURL(file);
  }
}

function removeNewImage() {
  const imageInput = document.getElementById("item-image");
  const previewContainer = document.getElementById("new-image-preview");

  if (imageInput) {
    imageInput.value = "";
  }
  if (previewContainer) {
    previewContainer.style.display = "none";
  }
}

function removeCurrentImage() {
  const currentImageContainer = document.getElementById(
    "current-image-preview"
  );

  if (currentImageContainer) {
    currentImageContainer.style.display = "none";
  }

  // Add a hidden input to indicate image should be removed
  const form = document.getElementById("item-form");
  if (form) {
    // Remove existing hidden input if any
    const existingInput = form.querySelector(
      'input[name="remove_current_image"]'
    );
    if (existingInput) {
      existingInput.remove();
    }

    // Add hidden input to indicate removal
    const hiddenInput = document.createElement("input");
    hiddenInput.type = "hidden";
    hiddenInput.name = "remove_current_image";
    hiddenInput.value = "true";
    form.appendChild(hiddenInput);
  }
}

function handleItemSelection(event) {
  const selectedOption = event.target.selectedOptions[0];
  const itemDetails = document.getElementById("item-details");
  const itemDescription = document.getElementById("item-description");
  const availableQuantity = document.getElementById("available-quantity");
  const maxQuantity = document.getElementById("max-quantity");
  const quantityInput = document.getElementById("quantity");
  const imagePreview = document.getElementById("item-image-preview");
  const itemImage = document.getElementById("selected-item-image");

  if (selectedOption && selectedOption.value) {
    const available = selectedOption.getAttribute("data-available");
    const description = selectedOption.getAttribute("data-description");
    const imagePath = selectedOption.getAttribute("data-image");

    // Show item details
    if (itemDetails) {
      itemDetails.style.display = "block";
    }

    // Update description
    if (itemDescription) {
      itemDescription.textContent = description || "No description available";
    }

    // Update available quantity
    if (availableQuantity) {
      availableQuantity.textContent = available;
    }

    if (maxQuantity) {
      maxQuantity.textContent = available;
    }

    // Update quantity input
    if (quantityInput) {
      quantityInput.max = available;
      quantityInput.value = Math.min(1, available);
    }

    // Show item image if available
    if (imagePreview && itemImage && imagePath) {
      itemImage.src = "../" + imagePath;
      itemImage.onerror = function () {
        imagePreview.style.display = "none";
      };
      itemImage.onload = function () {
        imagePreview.style.display = "block";
        // Make image clickable for preview
        itemImage.style.cursor = "pointer";
        itemImage.onclick = function () {
          showImagePreview(
            "../" + imagePath,
            selectedOption.textContent.split(" (")[0]
          );
        };
      };
    } else if (imagePreview) {
      imagePreview.style.display = "none";
    }
  } else {
    // Hide item details
    if (itemDetails) {
      itemDetails.style.display = "none";
    }
    if (imagePreview) {
      imagePreview.style.display = "none";
    }
  }
}

// Image preview functionality
function showImagePreview(imageSrc, itemName) {
  const modal = document.getElementById("image-preview-modal");
  const title = document.getElementById("image-preview-title");
  const image = document.getElementById("preview-modal-image");

  if (modal && title && image) {
    title.textContent = itemName || "Item Image";
    image.src = imageSrc;
    image.alt = itemName || "Item image";

    // Handle image load error
    image.onerror = function () {
      this.alt = "Image failed to load";
      this.style.opacity = "0.5";
    };

    // Reset image properties
    image.style.opacity = "1";

    showModal("image-preview-modal");
  }
}

// Initialize image handling
function initializeImageHandling() {
  // Add image preview functionality to existing images
  document.querySelectorAll(".clickable-image").forEach((img) => {
    img.style.cursor = "pointer";
  });

  // Image upload preview
  const imageInput = document.getElementById("item-image");
  if (imageInput) {
    imageInput.addEventListener("change", function (e) {
      const file = e.target.files[0];
      if (file && file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = function (e) {
          const previewContainer = document.getElementById("new-image-preview");
          const previewImage = document.getElementById("preview-image");

          if (previewContainer && previewImage) {
            previewImage.src = e.target.result;
            previewContainer.style.display = "block";
          }
        };
        reader.readAsDataURL(file);
      }
    });
  }
}

// Remove new image preview
function removeNewImage() {
  const newImagePreview = document.getElementById("new-image-preview");
  const imageInput = document.getElementById("item-image");

  if (newImagePreview) {
    newImagePreview.style.display = "none";
  }
  if (imageInput) {
    imageInput.value = "";
  }
}
