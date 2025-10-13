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

  if (
    !confirm(
      `Are you sure you want to approve ${selectedCheckboxes.length} reservation(s)?`
    )
  ) {
    return;
  }

  const reservationIds = Array.from(selectedCheckboxes).map((cb) => cb.value);

  // Approve each reservation
  for (const reservationId of reservationIds) {
    await approveReservation(reservationId);
  }

  showNotification(
    `${reservationIds.length} reservations approved successfully`,
    "success"
  );
  setTimeout(() => location.reload(), 2000);
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
  const newStatus = prompt(
    "Enter new status (available, maintenance, in_use):"
  );

  if (
    !newStatus ||
    !["available", "maintenance", "in_use"].includes(newStatus)
  ) {
    showNotification(
      "Invalid status. Use: available, maintenance, or in_use",
      "error"
    );
    return;
  }

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
      setTimeout(() => location.reload(), 1500);
    } else {
      showNotification(result.message, "error");
    }
  } catch (error) {
    hideLoading();
    showNotification("An error occurred while updating lab status", "error");
    console.error("Lab status update error:", error);
  }
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
  const notes = prompt("Add approval notes (optional):");

  if (notes === null) return; // User cancelled

  const formData = new FormData();
  formData.append("action", "approve_reservation");
  formData.append("reservation_id", reservationId);
  formData.append("notes", notes || "");
  formData.append("csrf_token", getCSRFToken());

  try {
    showLoading("Approving reservation...");

    const response = await fetch("php/labs_api.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();
    hideLoading();

    if (result.success) {
      showNotification(result.message, "success");
      setTimeout(() => location.reload(), 1500);
    } else {
      showNotification(
        result.message || "Failed to approve reservation",
        "error"
      );
    }
  } catch (error) {
    hideLoading();
    showNotification(
      "An error occurred while approving the reservation",
      "error"
    );
    console.error("Approval error:", error);
  }
}

async function rejectReservation(reservationId) {
  const reason = prompt("Please provide a reason for rejection:");

  if (!reason) {
    showNotification("Rejection reason is required", "warning");
    return;
  }

  const formData = new FormData();
  formData.append("action", "reject_reservation");
  formData.append("reservation_id", reservationId);
  formData.append("reason", reason);
  formData.append("csrf_token", getCSRFToken());

  try {
    showLoading("Rejecting reservation...");

    const response = await fetch("php/labs_api.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();
    hideLoading();

    if (result.success) {
      showNotification(result.message, "success");
      setTimeout(() => location.reload(), 1500);
    } else {
      showNotification(
        result.message || "Failed to reject reservation",
        "error"
      );
    }
  } catch (error) {
    hideLoading();
    showNotification(
      "An error occurred while rejecting the reservation",
      "error"
    );
    console.error("Rejection error:", error);
  }
}

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
  // This would populate a modal with reservation details
  // For now, just show a notification with key details
  const details = `
    Reservation #${reservation.id}
    Lab: ${reservation.lab_name}
    Requester: ${reservation.requester_name}
    Date: ${reservation.reservation_date}
    Time: ${reservation.start_time} - ${reservation.end_time}
    Purpose: ${reservation.purpose}
  `;

  alert(details); // Temporary - should be replaced with a proper modal
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

// Initialize admin-specific functionality
document.addEventListener("DOMContentLoaded", function () {
  // Add any admin-specific initialization here
  console.log("Admin labs dashboard initialized");
});
