// Admin Labs Management JavaScript

// Notification system for labs
function showNotification(message, type = "info", duration = 5000) {
  // Remove any existing notifications first
  const existingNotifications = document.querySelectorAll(".labs-notification");
  existingNotifications.forEach((notification) => notification.remove());

  const notificationDiv = document.createElement("div");
  notificationDiv.className = `alert alert-${type} alert-dismissible labs-notification`;

  // Try to use notification container first, then fall back to fixed positioning
  const notificationContainer = document.getElementById(
    "notification-container"
  );

  if (notificationContainer) {
    // Use in-page notification container
    notificationDiv.style.cssText = `
            margin-bottom: 15px;
            animation: slideInDown 0.3s ease-out;
        `;
    notificationContainer.appendChild(notificationDiv);
  } else {
    // Use fixed positioning as fallback
    notificationDiv.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            max-width: 500px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease-out;
        `;
    document.body.appendChild(notificationDiv);
  }

  notificationDiv.innerHTML = `
        <div class="alert-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span class="alert-message">${message}</span>
        </div>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">×</button>
    `;

  // Auto-hide after duration
  if (duration > 0) {
    setTimeout(() => {
      if (notificationDiv.parentElement) {
        notificationDiv.style.animation = notificationContainer
          ? "slideOutUp 0.3s ease-in forwards"
          : "slideOut 0.3s ease-in forwards";
        setTimeout(() => notificationDiv.remove(), 300);
      }
    }, duration);
  }
}

function getNotificationIcon(type) {
  const icons = {
    success: "check-circle",
    error: "exclamation-circle",
    danger: "exclamation-circle",
    warning: "exclamation-triangle",
    info: "info-circle",
  };
  return icons[type] || "info-circle";
}

function showLoading(message = "Loading...") {
  // Remove existing loading indicators
  const existingLoading = document.querySelectorAll(".loading-indicator");
  existingLoading.forEach((loading) => loading.remove());

  const loadingDiv = document.createElement("div");
  loadingDiv.className = "loading-indicator";
  loadingDiv.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 15px;
    `;

  loadingDiv.innerHTML = `
        <div class="spinner-border text-primary" role="status"></div>
        <span>${message}</span>
    `;

  // Create overlay
  const overlay = document.createElement("div");
  overlay.className = "loading-overlay";
  overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
    `;

  document.body.appendChild(overlay);
  document.body.appendChild(loadingDiv);
}

function hideLoading() {
  const loadingIndicator = document.querySelector(".loading-indicator");
  const loadingOverlay = document.querySelector(".loading-overlay");

  if (loadingIndicator) {
    loadingIndicator.remove();
  }
  if (loadingOverlay) {
    loadingOverlay.remove();
  }
}

function getCSRFToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute("content") : "";
}

// Additional functions for admin dashboard

// Toggle all pending checkboxes
function toggleAllPending() {
  const selectAll = document.getElementById("select-all-pending");
  const checkboxes = document.querySelectorAll(".pending-checkbox");

  checkboxes.forEach((checkbox) => {
    checkbox.checked = selectAll.checked;
  });
}

// Approve all selected reservations
async function approveAllVisible() {
  const selectedCheckboxes = document.querySelectorAll(
    ".pending-checkbox:checked"
  );

  if (selectedCheckboxes.length === 0) {
    showNotification("Please select reservations to approve", "warning");
    return;
  }

  showConfirmModal(
    "Bulk Approval",
    `Are you sure you want to approve ${selectedCheckboxes.length} reservation(s)?`,
    async () => {
      const reservationIds = Array.from(selectedCheckboxes).map(
        (cb) => cb.value
      );

      // Approve each reservation individually
      let successCount = 0;
      for (const reservationId of reservationIds) {
        try {
          const formData = new FormData();
          formData.append("action", "approve_reservation");
          formData.append("reservation_id", reservationId);
          formData.append("csrf_token", getCSRFToken());

          const response = await fetch("php/labs_api.php", {
            method: "POST",
            body: formData,
          });

          const result = await response.json();
          if (result.success) {
            successCount++;
          }
        } catch (error) {
          console.error(`Error approving reservation ${reservationId}:`, error);
        }
      }

      showNotification(
        `${successCount} of ${reservationIds.length} reservations approved successfully`,
        successCount > 0 ? "success" : "error"
      );
      setTimeout(() => location.reload(), 2000);
    }
  );
}

// Edit lab (placeholder - can be expanded)
function editLab(labId) {
  // This would open a modal to edit lab details
  // For now, just show the lab modal with pre-filled data
  showNotification(
    'Edit lab feature - click "Add Lab" to modify settings',
    "info"
  );
}

// Manage timetable
function manageTimetable(labId) {
  viewTimetable(labId);
}

// Change lab status
async function changeLabStatus(labId) {
  // Set the lab ID in the form
  document.getElementById("status-lab-id").value = labId;

  // Show the status modal
  showModal("lab-status-modal");
}

// View pending requests for specific lab
function viewPendingRequests(labId) {
  // Filter the table to show only requests for this lab
  const rows = document.querySelectorAll(
    "#pending-reservations-table tbody tr"
  );

  rows.forEach((row) => {
    const labInfo = row.querySelector(".lab-info strong");
    if (labInfo) {
      // This is a simple implementation - could be enhanced
      row.style.display = "";
    }
  });

  showNotification(`Showing requests for lab ${labId}`, "info");
}

// Generate report (placeholder)
function generateReport() {
  showNotification("Generating lab utilization report...", "info");

  // This would generate a comprehensive report
  setTimeout(() => {
    showNotification("Report generation feature coming soon", "warning");
  }, 2000);
}

// Export report (placeholder)
function exportReport() {
  showNotification("Exporting lab data...", "info");

  // This would export data to CSV/Excel
  setTimeout(() => {
    showNotification("Export feature coming soon", "warning");
  }, 2000);
}

// Approve all pending (bulk action)
function approveAllPending() {
  const pendingCheckboxes = document.querySelectorAll(".pending-checkbox");

  if (pendingCheckboxes.length === 0) {
    showNotification("No pending reservations to approve", "info");
    return;
  }

  // Select all checkboxes
  pendingCheckboxes.forEach((checkbox) => {
    checkbox.checked = true;
  });

  // Update the select all checkbox
  const selectAll = document.getElementById("select-all-pending");
  if (selectAll) {
    selectAll.checked = true;
  }

  // Call the approve function
  approveAllVisible();
}

// Manage issues (placeholder)
function manageIssues() {
  showNotification("Issue management feature coming soon", "info");
}

// View all issues
function viewAllIssues() {
  showNotification("Comprehensive issue view coming soon", "info");
}

// Assign issue
function assignIssue(issueId) {
  showNotification("Issue assignment feature coming soon", "info");
}

// Update issue status
function updateIssueStatus(issueId) {
  showNotification("Issue status update feature coming soon", "info");
}

// View issue details
function viewIssueDetails(issueId) {
  showNotification("Issue details view coming soon", "info");
}

// Reservation management functions
async function approveReservation(reservationId) {
  // Show approval modal
  showApprovalModal(reservationId, "approve");
}

async function rejectReservation(reservationId) {
  // Show rejection modal
  showApprovalModal(reservationId, "reject");
}

// Show approval/rejection modal
function showApprovalModal(reservationId, action) {
  const modal = document.getElementById("approval-modal");
  const title = document.getElementById("approval-title");
  const submitBtn = document.getElementById("approval-submit-btn");
  const notesLabel = document.querySelector(
    '#approval-modal label[for="approval-notes"]'
  );
  const notesTextarea = document.getElementById("approval-notes");

  document.getElementById("approval-reservation-id").value = reservationId;
  document.getElementById("approval-action").value = action;

  // Reset textarea
  notesTextarea.value = "";
  notesTextarea.required = false;

  if (action === "approve") {
    title.textContent = "Approve Reservation";
    notesLabel.innerHTML = "Notes (Optional)";
    notesTextarea.placeholder =
      "Add any notes or conditions for this approval...";
    submitBtn.className = "btn btn-success";
    submitBtn.textContent = "Approve";
  } else {
    title.textContent = "Reject Reservation";
    notesLabel.innerHTML =
      'Rejection Reason <span style="color: red;">*</span>';
    notesTextarea.placeholder = "Provide a reason for rejection...";
    notesTextarea.required = true;
    submitBtn.className = "btn btn-danger";
    submitBtn.textContent = "Reject";
  }

  showModal("approval-modal");
}

// Handle approval form submission
document
  .getElementById("approval-form")
  ?.addEventListener("submit", async function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const action = formData.get("action");
    const reservationId = formData.get("reservation_id");
    const notes = formData.get("notes");

    if (action === "reject" && !notes.trim()) {
      showNotification("Rejection reason is required", "error");
      return;
    }

    try {
      showLoading(
        action === "approve"
          ? "Approving reservation..."
          : "Rejecting reservation..."
      );

      const apiFormData = new FormData();
      apiFormData.append(
        "action",
        action === "approve" ? "approve_reservation" : "reject_reservation"
      );
      apiFormData.append("reservation_id", reservationId);
      apiFormData.append("csrf_token", getCSRFToken());

      if (action === "approve") {
        apiFormData.append("notes", notes);
      } else {
        apiFormData.append("reason", notes);
      }

      const response = await fetch("php/labs_api.php", {
        method: "POST",
        body: apiFormData,
      });

      const result = await response.json();
      hideLoading();

      if (result.success) {
        showNotification(result.message, "success");
        hideModal("approval-modal");
        setTimeout(() => location.reload(), 1500);
      } else {
        showNotification(
          result.message || "Failed to process reservation",
          "error"
        );
      }
    } catch (error) {
      hideLoading();
      showNotification(
        "An error occurred while processing the request",
        "error"
      );
      console.error("Approval error:", error);
    }
  });

async function viewReservationDetails(reservationId) {
  try {
    showLoading("Loading reservation details...");

    const response = await fetch(
      `php/labs_api.php?action=get_reservation_details&reservation_id=${reservationId}`
    );
    const result = await response.json();

    hideLoading();

    if (result.success) {
      displayReservationDetails(result.reservation);
      showModal("reservation-details-modal");
    } else {
      showNotification("Failed to load reservation details", "error");
    }
  } catch (error) {
    hideLoading();
    showNotification("Error loading reservation details", "error");
    console.error("Details error:", error);
  }
}

function displayReservationDetails(reservation) {
  // Populate the reservation details modal
  const detailsContent = document.getElementById("reservation-details-content");

  detailsContent.innerHTML = `
    <div class="details-grid">
      <div class="detail-row">
        <strong>Reservation ID:</strong>
        <span>#${reservation.id}</span>
      </div>
      <div class="detail-row">
        <strong>Lab:</strong>
        <span>${reservation.lab_name || "N/A"}</span>
      </div>
      <div class="detail-row">
        <strong>Requester:</strong>
        <span>${reservation.requester_name || "N/A"}</span>
      </div>
      <div class="detail-row">
        <strong>Date:</strong>
        <span>${reservation.reservation_date || "N/A"}</span>
      </div>
      <div class="detail-row">
        <strong>Time:</strong>
        <span>${reservation.start_time || "N/A"} - ${
    reservation.end_time || "N/A"
  }</span>
      </div>
      <div class="detail-row">
        <strong>Purpose:</strong>
        <span>${reservation.purpose || "N/A"}</span>
      </div>
      <div class="detail-row">
        <strong>Status:</strong>
        <span class="badge badge-${getStatusBadgeClass(reservation.status)}">${
    reservation.status || "N/A"
  }</span>
      </div>
      ${
        reservation.notes
          ? `
      <div class="detail-row">
        <strong>Notes:</strong>
        <span>${reservation.notes}</span>
      </div>
      `
          : ""
      }
      ${
        reservation.rejection_reason
          ? `
      <div class="detail-row">
        <strong>Rejection Reason:</strong>
        <span class="text-danger">${reservation.rejection_reason}</span>
      </div>
      `
          : ""
      }
    </div>
  `;
}

function getStatusBadgeClass(status) {
  const statusClasses = {
    pending: "warning",
    approved: "success",
    rejected: "danger",
    cancelled: "secondary",
    completed: "info",
  };
  return statusClasses[status] || "secondary";
}

// Refresh lab status function
async function refreshLabStatus() {
  try {
    showLoading("Refreshing lab status...");

    const response = await fetch("php/labs_api.php?action=refresh_lab_status");
    const result = await response.json();

    hideLoading();

    if (result.success) {
      showNotification("Lab status refreshed successfully", "success");
      setTimeout(() => location.reload(), 1000);
    } else {
      showNotification("Failed to refresh lab status", "error");
    }
  } catch (error) {
    hideLoading();
    showNotification("Error refreshing lab status", "error");
    console.error("Refresh error:", error);
  }
}

// Timetable management
function viewTimetable(labId) {
  showNotification(`Loading timetable for lab ${labId}...`, "info");
  // This would typically open a timetable modal or navigate to a timetable page
  setTimeout(() => {
    showNotification("Timetable feature coming soon", "warning");
  }, 1000);
}

// Handle lab form submission
document
  .getElementById("lab-form")
  ?.addEventListener("submit", async function (e) {
    e.preventDefault();

    try {
      showLoading("Saving lab...");

      const formData = new FormData(this);
      formData.append("action", "manage_lab");
      formData.append("csrf_token", getCSRFToken());

      const response = await fetch("php/labs_api.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();
      hideLoading();

      if (result.success) {
        showNotification(result.message, "success");
        hideModal("add-lab-modal");
        setTimeout(() => location.reload(), 1500);
      } else {
        showNotification(result.message || "Failed to save lab", "error");
      }
    } catch (error) {
      hideLoading();
      showNotification("An error occurred while saving the lab", "error");
      console.error("Lab save error:", error);
    }
  });

// Handle timetable upload form submission
document
  .getElementById("timetable-upload-form")
  ?.addEventListener("submit", async function (e) {
    e.preventDefault();

    try {
      showLoading("Uploading and processing timetable...");

      const formData = new FormData(this);
      formData.append("action", "upload_timetable");
      formData.append("csrf_token", getCSRFToken());

      const response = await fetch("php/labs_api.php", {
        method: "POST",
        body: formData,
      });

      const result = await response.json();
      hideLoading();

      if (result.success) {
        showNotification(result.message, "success");
        hideModal("upload-timetable-modal");
        setTimeout(() => location.reload(), 2000);
      } else {
        showNotification(
          result.message || "Failed to upload timetable",
          "error"
        );
      }
    } catch (error) {
      hideLoading();
      showNotification(
        "An error occurred while uploading the timetable",
        "error"
      );
      console.error("Timetable upload error:", error);
    }
  });

// Generic confirm modal function
function showConfirmModal(title, message, onConfirm) {
  const modal = document.getElementById("confirm-modal");
  const titleEl = document.getElementById("confirm-title");
  const messageEl = document.getElementById("confirm-message");
  const confirmBtn = document.getElementById("confirm-yes-btn");

  titleEl.textContent = title;
  messageEl.textContent = message;

  // Remove old event listeners by cloning
  const newConfirmBtn = confirmBtn.cloneNode(true);
  confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

  // Add new event listener
  newConfirmBtn.addEventListener("click", () => {
    hideModal("confirm-modal");
    if (typeof onConfirm === "function") {
      onConfirm();
    }
  });

  showModal("confirm-modal");
}

// Handle lab status form submission
document.addEventListener("DOMContentLoaded", function () {
  // Lab status change form handler
  const statusForm = document.getElementById("lab-status-form");
  if (statusForm) {
    statusForm.addEventListener("submit", async function (e) {
      e.preventDefault();

      const labId = document.getElementById("status-lab-id").value;
      const newStatus = document.getElementById("lab-status-select").value;

      const formData = new FormData();
      formData.append("action", "update_lab_status");
      formData.append("lab_id", labId);
      formData.append("status", newStatus);
      formData.append("csrf_token", getCSRFToken());

      try {
        showLoading("Updating lab status...");

        const response = await fetch("php/labs_api.php", {
          method: "POST",
          body: formData,
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
          showNotification(result.message, "success");
          hideModal("lab-status-modal");
          setTimeout(() => location.reload(), 1500);
        } else {
          showNotification(result.message, "error");
        }
      } catch (error) {
        hideLoading();
        showNotification(
          "An error occurred while updating lab status",
          "error"
        );
        console.error("Lab status update error:", error);
      }
    });
  }

  // Add any admin-specific initialization here
  console.log("Admin labs dashboard initialized");
});
