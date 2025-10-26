// Admin-specific store JavaScript

document.addEventListener("DOMContentLoaded", function () {
  initializeAdminFunctions();
});

function initializeAdminFunctions() {
  // Initialize advanced analytics
  initializeAnalytics();

  // Initialize drag and drop for image uploads
  initializeDragDrop();

  // Initialize advanced filters
  initializeAdvancedFilters();

  // Initialize data validation
  initializeDataValidation();

  // Initialize import/export functions
  initializeImportExport();

  // Load dashboard data
  loadDashboardData();
}

function initializeAnalytics() {
  // Initialize charts if Chart.js is available
  if (typeof Chart !== "undefined") {
    initializeCharts();
  }

  // Initialize analytics refresh
  setInterval(() => {
    if (document.getElementById("auto-refresh-analytics").checked) {
      refreshAnalytics();
    }
  }, 60000); // Refresh every minute
}

function initializeCharts() {
  // Usage trends chart
  const usageCtx = document.getElementById("usage-trend-chart");
  if (usageCtx) {
    createUsageTrendChart(usageCtx);
  }

  // Category distribution chart
  const categoryCtx = document.getElementById("category-chart");
  if (categoryCtx) {
    createCategoryChart(categoryCtx);
  }

  // Status distribution chart
  const statusCtx = document.getElementById("status-chart");
  if (statusCtx) {
    createStatusChart(statusCtx);
  }
}

function createUsageTrendChart(ctx) {
  fetch("../php/get_usage_trends.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        new Chart(ctx, {
          type: "line",
          data: {
            labels: data.labels,
            datasets: [
              {
                label: "Requests",
                data: data.requests,
                borderColor: "#007bff",
                backgroundColor: "rgba(0, 123, 255, 0.1)",
                tension: 0.4,
              },
              {
                label: "Approvals",
                data: data.approvals,
                borderColor: "#28a745",
                backgroundColor: "rgba(40, 167, 69, 0.1)",
                tension: 0.4,
              },
            ],
          },
          options: {
            responsive: true,
            plugins: {
              title: {
                display: true,
                text: "Usage Trends (Last 30 Days)",
              },
            },
            scales: {
              y: {
                beginAtZero: true,
              },
            },
          },
        });
      }
    })
    .catch((error) => console.error("Error loading usage trends:", error));
}

function createCategoryChart(ctx) {
  fetch("../php/get_category_distribution.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        new Chart(ctx, {
          type: "doughnut",
          data: {
            labels: data.labels,
            datasets: [
              {
                data: data.values,
                backgroundColor: [
                  "#007bff",
                  "#28a745",
                  "#ffc107",
                  "#dc3545",
                  "#6f42c1",
                  "#20c997",
                  "#fd7e14",
                  "#e83e8c",
                ],
              },
            ],
          },
          options: {
            responsive: true,
            plugins: {
              title: {
                display: true,
                text: "Items by Category",
              },
              legend: {
                position: "bottom",
              },
            },
          },
        });
      }
    })
    .catch((error) =>
      console.error("Error loading category distribution:", error)
    );
}

function createStatusChart(ctx) {
  fetch("../php/get_status_distribution.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        new Chart(ctx, {
          type: "bar",
          data: {
            labels: data.labels,
            datasets: [
              {
                label: "Items",
                data: data.values,
                backgroundColor: [
                  "#28a745", // Available
                  "#ffc107", // Borrowed
                  "#dc3545", // Maintenance
                  "#6c757d", // Other
                ],
              },
            ],
          },
          options: {
            responsive: true,
            plugins: {
              title: {
                display: true,
                text: "Item Status Distribution",
              },
            },
            scales: {
              y: {
                beginAtZero: true,
              },
            },
          },
        });
      }
    })
    .catch((error) =>
      console.error("Error loading status distribution:", error)
    );
}

function initializeDragDrop() {
  const dropZone = document.getElementById("image-drop-zone");
  if (!dropZone) return;

  ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
    dropZone.addEventListener(eventName, preventDefaults, false);
  });

  ["dragenter", "dragover"].forEach((eventName) => {
    dropZone.addEventListener(eventName, highlight, false);
  });

  ["dragleave", "drop"].forEach((eventName) => {
    dropZone.addEventListener(eventName, unhighlight, false);
  });

  dropZone.addEventListener("drop", handleDrop, false);

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  function highlight(e) {
    dropZone.classList.add("drag-over");
  }

  function unhighlight(e) {
    dropZone.classList.remove("drag-over");
  }

  function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleImageFiles(files);
  }
}

function handleImageFiles(files) {
  Array.from(files).forEach((file) => {
    if (file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = function (e) {
        displayImagePreview(file.name, e.target.result);
      };
      reader.readAsDataURL(file);
    }
  });
}

function displayImagePreview(filename, src) {
  const previewContainer = document.getElementById("image-preview");
  const preview = document.createElement("div");
  preview.className = "image-preview-item";
  preview.innerHTML = `
        <img src="${src}" alt="${filename}">
        <div class="image-info">
            <span class="filename">${filename}</span>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeImagePreview(this)">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
  previewContainer.appendChild(preview);
}

function removeImagePreview(button) {
  button.closest(".image-preview-item").remove();
}

function initializeAdvancedFilters() {
  // Advanced search modal
  const advancedSearchBtn = document.getElementById("advanced-search-btn");
  if (advancedSearchBtn) {
    advancedSearchBtn.addEventListener("click", showAdvancedSearch);
  }

  // Filter presets
  document.querySelectorAll(".filter-preset").forEach((preset) => {
    preset.addEventListener("click", function () {
      applyFilterPreset(this.dataset.preset);
    });
  });

  // Custom date ranges
  const customDateRange = document.getElementById("custom-date-range");
  if (customDateRange) {
    customDateRange.addEventListener("change", function () {
      if (this.value === "custom") {
        document.getElementById("custom-date-inputs").style.display = "block";
      } else {
        document.getElementById("custom-date-inputs").style.display = "none";
        applyDateRange(this.value);
      }
    });
  }
}

function showAdvancedSearch() {
  showModal("advanced-search-modal");
}

function applyAdvancedSearch() {
  const formData = new FormData(
    document.getElementById("advanced-search-form")
  );
  const params = new URLSearchParams(formData);

  // Apply filters to current page
  window.location.search = params.toString();
}

function applyFilterPreset(preset) {
  const filters = {
    "low-stock": { quantity_filter: "low" },
    "high-usage": { usage_filter: "high" },
    "needs-maintenance": { status_filter: "maintenance" },
    "recently-added": { date_filter: "recent" },
  };

  const filter = filters[preset];
  if (filter) {
    Object.keys(filter).forEach((key) => {
      const element = document.getElementById(key);
      if (element) {
        element.value = filter[key];
        element.dispatchEvent(new Event("change"));
      }
    });
  }
}

function applyDateRange(range) {
  const today = new Date();
  const params = new URLSearchParams();

  switch (range) {
    case "today":
      params.set("date_from", formatDate(today));
      params.set("date_to", formatDate(today));
      break;
    case "week":
      const weekAgo = new Date(today);
      weekAgo.setDate(today.getDate() - 7);
      params.set("date_from", formatDate(weekAgo));
      params.set("date_to", formatDate(today));
      break;
    case "month":
      const monthAgo = new Date(today);
      monthAgo.setMonth(today.getMonth() - 1);
      params.set("date_from", formatDate(monthAgo));
      params.set("date_to", formatDate(today));
      break;
  }

  if (params.toString()) {
    window.location.search = params.toString();
  }
}

function initializeDataValidation() {
  // Real-time validation for item forms
  const itemNameInput = document.getElementById("item-name");
  if (itemNameInput) {
    itemNameInput.addEventListener("input", validateItemName);
  }

  const quantityInputs = document.querySelectorAll('input[type="number"]');
  quantityInputs.forEach((input) => {
    input.addEventListener("input", validateQuantity);
  });

  // Category name validation
  const categoryNameInput = document.getElementById("category-name");
  if (categoryNameInput) {
    categoryNameInput.addEventListener("input", validateCategoryName);
  }
}

function validateItemName() {
  const input = document.getElementById("item-name");
  const value = input.value.trim();
  const feedback = document.getElementById("item-name-feedback");

  if (value.length < 2) {
    showValidationError(
      input,
      feedback,
      "Item name must be at least 2 characters"
    );
    return false;
  } else if (value.length > 100) {
    showValidationError(
      input,
      feedback,
      "Item name must be less than 100 characters"
    );
    return false;
  } else {
    showValidationSuccess(input, feedback);
    return true;
  }
}

function validateCategoryName() {
  const input = document.getElementById("category-name");
  const value = input.value.trim();
  const feedback = document.getElementById("category-name-feedback");

  if (value.length < 2) {
    showValidationError(
      input,
      feedback,
      "Category name must be at least 2 characters"
    );
    return false;
  } else {
    showValidationSuccess(input, feedback);
    return true;
  }
}

function showValidationError(input, feedback, message) {
  input.classList.add("is-invalid");
  input.classList.remove("is-valid");
  if (feedback) {
    feedback.textContent = message;
    feedback.style.display = "block";
  }
}

function showValidationSuccess(input, feedback) {
  input.classList.add("is-valid");
  input.classList.remove("is-invalid");
  if (feedback) {
    feedback.style.display = "none";
  }
}

function initializeImportExport() {
  // Import CSV functionality
  const importBtn = document.getElementById("import-csv-btn");
  if (importBtn) {
    importBtn.addEventListener("click", showImportModal);
  }

  const importForm = document.getElementById("import-form");
  if (importForm) {
    importForm.addEventListener("submit", handleImport);
  }

  // Export functionality
  const exportButtons = document.querySelectorAll(".export-btn");
  exportButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      exportData(this.dataset.type);
    });
  });
}

function showImportModal() {
  showModal("import-modal");
}

function handleImport(e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);
  const submitBtn = form.querySelector('button[type="submit"]');

  setLoading(submitBtn, true);

  fetch("../php/import_items.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert(`Successfully imported ${data.imported} items`, "success");
        if (data.errors && data.errors.length > 0) {
          showAlert(`${data.errors.length} items had errors`, "warning");
          displayImportErrors(data.errors);
        }
        hideModal("import-modal");
        setTimeout(() => location.reload(), 2000);
      } else {
        showAlert(data.message || "Import failed", "danger");
      }
    })
    .catch((error) => {
      console.error("Error importing data:", error);
      showAlert("Error importing data", "danger");
    })
    .finally(() => {
      setLoading(submitBtn, false);
    });
}

function displayImportErrors(errors) {
  const errorList = document.getElementById("import-errors");
  errorList.innerHTML = errors
    .map((error) => `<li>Row ${error.row}: ${error.message}</li>`)
    .join("");
  document.getElementById("import-errors-section").style.display = "block";
}

function exportData(type) {
  const formats = {
    csv: "../php/export_items_csv.php",
    excel: "../php/export_items_excel.php",
    pdf: "../php/export_items_pdf.php",
    report: "../php/export_inventory_report.php",
  };

  const url = formats[type];
  if (url) {
    // Add current filters to export
    const params = new URLSearchParams(window.location.search);
    const exportUrl = `${url}${params.toString() ? "?" + params.toString() : ""
      }`;
    window.open(exportUrl, "_blank");
  }
}

// Advanced Item Management
function duplicateItem(itemId) {
  if (!confirm("Create a duplicate of this item?")) {
    return;
  }

  fetch(`../php/duplicate_item.php?id=${itemId}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Item duplicated successfully", "success");
        setTimeout(() => location.reload(), 1000);
      } else {
        showAlert(data.message || "Failed to duplicate item", "danger");
      }
    })
    .catch((error) => {
      console.error("Error duplicating item:", error);
      showAlert("Error duplicating item", "danger");
    });
}

function bulkUpdateItems() {
  const selectedItems = getSelectedItems();
  if (selectedItems.length === 0) {
    showAlert("Please select items to update", "warning");
    return;
  }

  showBulkUpdateModal(selectedItems);
}

function getSelectedItems() {
  const checkboxes = document.querySelectorAll(".item-checkbox:checked");
  return Array.from(checkboxes).map((cb) => cb.value);
}

function showBulkUpdateModal(itemIds) {
  document.getElementById("bulk-update-count").textContent = itemIds.length;
  document.getElementById("bulk-update-ids").value = JSON.stringify(itemIds);
  showModal("bulk-update-modal");
}

function handleBulkUpdate() {
  const itemIds = JSON.parse(document.getElementById("bulk-update-ids").value);
  const updateType = document.getElementById("bulk-update-type").value;
  const updateValue = document.getElementById("bulk-update-value").value;

  if (!updateType || !updateValue) {
    showAlert("Please select update type and value", "warning");
    return;
  }

  const formData = new FormData();
  formData.append("item_ids", JSON.stringify(itemIds));
  formData.append("update_type", updateType);
  formData.append("update_value", updateValue);
  formData.append(
    "csrf_token",
    document.querySelector('meta[name="csrf-token"]').content
  );

  fetch("../php/bulk_update_items.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert(`${itemIds.length} items updated successfully`, "success");
        hideModal("bulk-update-modal");
        setTimeout(() => location.reload(), 1000);
      } else {
        showAlert(data.message || "Failed to update items", "danger");
      }
    })
    .catch((error) => {
      console.error("Error updating items:", error);
      showAlert("Error updating items", "danger");
    });
}

function archiveItem(itemId) {
  if (!confirm("Archive this item? It will be hidden from regular views.")) {
    return;
  }

  const formData = new FormData();
  formData.append("item_id", itemId);
  formData.append("action", "archive");
  formData.append(
    "csrf_token",
    document.querySelector('meta[name="csrf-token"]').content
  );

  fetch("../php/archive_item.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Item archived successfully", "success");
        setTimeout(() => location.reload(), 1000);
      } else {
        showAlert(data.message || "Failed to archive item", "danger");
      }
    })
    .catch((error) => {
      console.error("Error archiving item:", error);
      showAlert("Error archiving item", "danger");
    });
}

// Dashboard Functions
function loadDashboardData() {
  loadAnalyticsData();
  loadRecentActivity();
  loadSystemAlerts();
}

function loadAnalyticsData() {
  fetch("../php/get_admin_analytics.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateAnalyticsCards(data.analytics);
      }
    })
    .catch((error) => console.error("Error loading analytics:", error));
}

function updateAnalyticsCards(analytics) {
  document.getElementById("total-items-count").textContent =
    analytics.total_items || 0;
  document.getElementById("total-borrowed-count").textContent =
    analytics.total_borrowed || 0;
  document.getElementById("pending-requests-count").textContent =
    analytics.pending_requests || 0;
  document.getElementById("maintenance-items-count").textContent =
    analytics.maintenance_items || 0;
  document.getElementById("monthly-requests-count").textContent =
    analytics.monthly_requests || 0;
  document.getElementById("approval-rate").textContent = `${analytics.approval_rate || 0
    }%`;
}

function loadRecentActivity() {
  fetch("../php/get_recent_activity.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateRecentActivity(data.activities);
      }
    })
    .catch((error) => console.error("Error loading recent activity:", error));
}

function updateRecentActivity(activities) {
  const container = document.getElementById("recent-activity-list");
  if (!container) return;

  container.innerHTML = activities
    .map(
      (activity) => `
        <div class="activity-item">
            <div class="activity-icon ${activity.type}">
                <i class="fas fa-${getActivityIcon(activity.type)}"></i>
            </div>
            <div class="activity-content">
                <div class="activity-text">${activity.description}</div>
                <div class="activity-time">${formatTimeAgo(
        activity.created_at
      )}</div>
            </div>
        </div>
    `
    )
    .join("");
}

function getActivityIcon(type) {
  const icons = {
    request: "plus-circle",
    approval: "check-circle",
    rejection: "times-circle",
    return: "undo",
    item_added: "box",
    item_updated: "edit",
    maintenance: "wrench",
  };
  return icons[type] || "info-circle";
}

function loadSystemAlerts() {
  fetch("../php/get_system_alerts.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        displaySystemAlerts(data.alerts);
      }
    })
    .catch((error) => console.error("Error loading system alerts:", error));
}

function displaySystemAlerts(alerts) {
  const container = document.getElementById("system-alerts");
  if (!container) return;

  if (alerts.length === 0) {
    container.innerHTML =
      '<div class="alert alert-success">No system alerts</div>';
    return;
  }

  container.innerHTML = alerts
    .map(
      (alert) => `
        <div class="alert alert-${alert.type}">
            <div class="alert-content">
                <strong>${alert.title}</strong>
                <p>${alert.message}</p>
            </div>
            <button type="button" class="btn-close" onclick="dismissAlert(${alert.id})"></button>
        </div>
    `
    )
    .join("");
}

function dismissAlert(alertId) {
  fetch("../php/dismiss_alert.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ alert_id: alertId }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        loadSystemAlerts(); // Reload alerts
      }
    })
    .catch((error) => console.error("Error dismissing alert:", error));
}

function refreshAnalytics() {
  loadAnalyticsData();
  if (typeof Chart !== "undefined") {
    // Refresh charts
    Chart.instances.forEach((chart) => {
      chart.destroy();
    });
    initializeCharts();
  }
}

// Report Generation
function generateCustomReport() {
  showModal("custom-report-modal");
}

function handleCustomReport() {
  const form = document.getElementById("custom-report-form");
  const formData = new FormData(form);

  // Validate form
  if (!formData.get("report_type")) {
    showAlert("Please select a report type", "warning");
    return;
  }

  // Generate report
  const params = new URLSearchParams(formData);
  window.open(
    `../php/generate_custom_report.php?${params.toString()}`,
    "_blank"
  );

  hideModal("custom-report-modal");
}

// Utility Functions
function formatTimeAgo(dateString) {
  const date = new Date(dateString);
  const now = new Date();
  const diffInSeconds = Math.floor((now - date) / 1000);

  if (diffInSeconds < 60) return "Just now";
  if (diffInSeconds < 3600)
    return `${Math.floor(diffInSeconds / 60)} minutes ago`;
  if (diffInSeconds < 86400)
    return `${Math.floor(diffInSeconds / 3600)} hours ago`;
  return `${Math.floor(diffInSeconds / 86400)} days ago`;
}

function formatDate(date, format = "YYYY-MM-DD") {
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");

  switch (format) {
    case "DD/MM/YYYY":
      return `${day}/${month}/${year}`;
    case "DD/MM/YYYY HH:mm":
      const hours = String(d.getHours()).padStart(2, "0");
      const minutes = String(d.getMinutes()).padStart(2, "0");
      return `${day}/${month}/${year} ${hours}:${minutes}`;
    default:
      return `${year}-${month}-${day}`;
  }
}
