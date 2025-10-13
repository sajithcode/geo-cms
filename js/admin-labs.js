// Admin Labs Management JavaScript

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

// Initialize admin-specific functionality
document.addEventListener("DOMContentLoaded", function () {
  // Add any admin-specific initialization here
  console.log("Admin labs dashboard initialized");
});
