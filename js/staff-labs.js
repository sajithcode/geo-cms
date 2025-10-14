// Staff Labs Management JavaScript

// Get CSRF token
function getCSRFToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute("content") : "";
}

// Show loading indicator
function showLoading(message = "Loading...") {
  const existingLoading = document.querySelectorAll(".loading-overlay");
  existingLoading.forEach((loading) => loading.remove());

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
        display: flex;
        align-items: center;
        justify-content: center;
    `;

  const loadingDiv = document.createElement("div");
  loadingDiv.style.cssText = `
        background: white;
        padding: 20px 30px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        display: flex;
        align-items: center;
        gap: 15px;
    `;

  loadingDiv.innerHTML = `
        <div class="spinner-border text-primary" role="status"></div>
        <span>${message}</span>
    `;

  overlay.appendChild(loadingDiv);
  document.body.appendChild(overlay);
}

// Hide loading indicator
function hideLoading() {
  const loadingOverlay = document.querySelector(".loading-overlay");
  if (loadingOverlay) {
    loadingOverlay.remove();
  }
}

// Approve reservation
async function approveReservation(reservationId) {
  // Show approval modal
  showApprovalModal(reservationId, "approve");
}

// Reject reservation
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
      showAlert("Rejection reason is required", "danger");
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
        showAlert(result.message, "success");
        hideModal("approval-modal");
        setTimeout(() => location.reload(), 1500);
      } else {
        showAlert(result.message, "danger");
      }
    } catch (error) {
      hideLoading();
      showAlert("An error occurred while processing the request", "danger");
      console.error("Approval error:", error);
    }
  });

// Filter reservations
function filterReservations() {
  const statusFilter = document.getElementById("status-filter");
  const labFilter = document.getElementById("lab-filter");
  const searchInput = document.getElementById("reservation-search");
  const rows = document.querySelectorAll("#reservations-table tbody tr");

  rows.forEach((row) => {
    if (!row.dataset.status) return;

    let showRow = true;

    // Status filter
    if (statusFilter && statusFilter.value !== "") {
      showRow = showRow && row.dataset.status === statusFilter.value;
    }

    // Lab filter
    if (labFilter && labFilter.value !== "") {
      showRow = showRow && row.dataset.labId === labFilter.value;
    }

    // Search filter
    if (searchInput && searchInput.value.trim() !== "") {
      const searchTerm = searchInput.value.toLowerCase();
      const requesterInfo = row.querySelector(".requester-info");
      if (requesterInfo) {
        const text = requesterInfo.textContent.toLowerCase();
        showRow = showRow && text.includes(searchTerm);
      }
    }

    row.style.display = showRow ? "" : "none";
  });
}

// Setup search functionality
document
  .getElementById("reservation-search")
  ?.addEventListener("input", filterReservations);

// View timetable
async function viewTimetable(labId) {
  try {
    showLoading("Loading timetable...");

    const response = await fetch(
      `php/labs_api.php?action=get_timetable&lab_id=${labId}`
    );
    const result = await response.json();

    hideLoading();

    if (result.success) {
      displayTimetableInModal(result.timetable, labId);
      showModal("timetable-modal");
    } else {
      showAlert(result.message, "danger");
    }
  } catch (error) {
    hideLoading();
    showAlert("An error occurred while loading the timetable", "danger");
    console.error("Timetable error:", error);
  }
}

// Display timetable in modal
function displayTimetableInModal(timetable, labId) {
  const container = document.getElementById("timetable-content");
  const title = document.getElementById("timetable-modal-title");

  title.textContent = `Lab ${labId} Timetable`;

  if (timetable.length === 0) {
    container.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon">📅</div>
        <h3>No Timetable Available</h3>
        <p>There are no scheduled sessions for this lab yet.</p>
      </div>
    `;
    return;
  }

  // Group by days
  const dayOrder = ["monday", "tuesday", "wednesday", "thursday", "friday"];
  const dayNames = {
    monday: "Monday",
    tuesday: "Tuesday",
    wednesday: "Wednesday",
    thursday: "Thursday",
    friday: "Friday",
  };

  const groupedByDay = {};

  timetable.forEach((entry) => {
    if (!groupedByDay[entry.day_of_week]) {
      groupedByDay[entry.day_of_week] = [];
    }
    groupedByDay[entry.day_of_week].push(entry);
  });

  let html = '<div class="timetable-grid">';

  dayOrder.forEach((day) => {
    if (groupedByDay[day]) {
      html += `<div class="day-schedule">
                 <h4 class="day-title">${dayNames[day]}</h4>`;

      groupedByDay[day].forEach((entry) => {
        html += `<div class="schedule-entry">
                   <div class="time-slot">${formatTime(
                     entry.start_time
                   )} - ${formatTime(entry.end_time)}</div>
                   <div class="schedule-info">
                     <strong>${escapeHtml(entry.subject || "N/A")}</strong>
                     ${
                       entry.lecturer_name
                         ? `<p>Lecturer: ${escapeHtml(entry.lecturer_name)}</p>`
                         : ""
                     }
                     ${
                       entry.batch
                         ? `<small>Batch: ${escapeHtml(entry.batch)}</small>`
                         : ""
                     }
                   </div>
                 </div>`;
      });

      html += "</div>";
    }
  });

  html += "</div>";
  container.innerHTML = html;
}

// View reservation details
async function viewReservationDetails(reservationId) {
  try {
    showLoading("Loading details...");

    const response = await fetch(
      `php/labs_api.php?action=get_reservation_details&reservation_id=${reservationId}`
    );
    const result = await response.json();

    hideLoading();

    if (result.success) {
      displayReservationDetailsInModal(result.reservation);
      showModal("reservation-details-modal");
    } else {
      showAlert(result.message, "danger");
    }
  } catch (error) {
    hideLoading();
    showAlert("An error occurred while loading reservation details", "danger");
    console.error("Details error:", error);
  }
}

// Display reservation details in modal
function displayReservationDetailsInModal(reservation) {
  const container = document.getElementById("reservation-details-content");

  const statusBadgeClass = getReservationBadgeClass(reservation.status);

  container.innerHTML = `
    <div class="reservation-details">
      <div class="detail-row">
        <strong>Requester:</strong> ${escapeHtml(
          reservation.requester_name
        )} (${escapeHtml(reservation.requester_id)})
      </div>
      <div class="detail-row">
        <strong>Role:</strong> ${escapeHtml(
          reservation.requester_role.charAt(0).toUpperCase() +
            reservation.requester_role.slice(1)
        )}
      </div>
      <div class="detail-row">
        <strong>Lab:</strong> ${escapeHtml(reservation.lab_name)}
      </div>
      <div class="detail-row">
        <strong>Date:</strong> ${formatDate(
          reservation.reservation_date,
          "DD/MM/YYYY"
        )}
      </div>
      <div class="detail-row">
        <strong>Time:</strong> ${formatTime(
          reservation.start_time
        )} - ${formatTime(reservation.end_time)}
      </div>
      <div class="detail-row">
        <strong>Purpose:</strong><br>
        <p style="margin-top: 5px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
          ${escapeHtml(reservation.purpose)}
        </p>
      </div>
      <div class="detail-row">
        <strong>Status:</strong> 
        <span class="badge badge-${statusBadgeClass}">
          ${
            reservation.status.charAt(0).toUpperCase() +
            reservation.status.slice(1)
          }
        </span>
      </div>
      <div class="detail-row">
        <strong>Requested On:</strong> ${formatDate(
          reservation.request_date,
          "DD/MM/YYYY HH:mm"
        )}
      </div>
      ${
        reservation.approved_by_name
          ? `
        <div class="detail-row">
          <strong>Processed By:</strong> ${escapeHtml(
            reservation.approved_by_name
          )}
        </div>
      `
          : ""
      }
      ${
        reservation.approved_date
          ? `
        <div class="detail-row">
          <strong>Processed On:</strong> ${formatDate(
            reservation.approved_date,
            "DD/MM/YYYY HH:mm"
          )}
        </div>
      `
          : ""
      }
      ${
        reservation.notes
          ? `
        <div class="detail-row">
          <strong>Notes:</strong><br>
          <p style="margin-top: 5px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
            ${escapeHtml(reservation.notes)}
          </p>
        </div>
      `
          : ""
      }
    </div>
  `;
}

// View issue details
async function viewIssueDetails(issueId) {
  try {
    showLoading("Loading issue details...");

    const response = await fetch(
      `php/labs_api.php?action=get_issue_details&issue_id=${issueId}`
    );
    const result = await response.json();

    hideLoading();

    if (result.success) {
      displayIssueDetailsInModal(result.issue);
      showModal("issue-details-modal");
    } else {
      showAlert(result.message, "danger");
    }
  } catch (error) {
    hideLoading();
    showAlert("An error occurred while loading issue details", "danger");
    console.error("Issue details error:", error);
  }
}

// Display issue details in modal
function displayIssueDetailsInModal(issue) {
  const container = document.getElementById("issue-details-content");

  const statusBadgeClass = getIssueBadgeClass(issue.status);

  container.innerHTML = `
    <div class="issue-details">
      <div class="detail-row">
        <strong>Reporter:</strong> ${escapeHtml(issue.reporter_name)}
      </div>
      <div class="detail-row">
        <strong>Lab:</strong> ${escapeHtml(issue.lab_name || "General")}
      </div>
      ${
        issue.computer_number
          ? `
        <div class="detail-row">
          <strong>Computer:</strong> ${escapeHtml(issue.computer_number)}
        </div>
      `
          : ""
      }
      <div class="detail-row">
        <strong>Description:</strong><br>
        <p style="margin-top: 5px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
          ${escapeHtml(issue.description)}
        </p>
      </div>
      <div class="detail-row">
        <strong>Status:</strong> 
        <span class="badge badge-${statusBadgeClass}">
          ${
            issue.status.charAt(0).toUpperCase() +
            issue.status.slice(1).replace("_", " ")
          }
        </span>
      </div>
      <div class="detail-row">
        <strong>Reported On:</strong> ${formatDate(
          issue.created_at,
          "DD/MM/YYYY HH:mm"
        )}
      </div>
      ${
        issue.assigned_to_name
          ? `
        <div class="detail-row">
          <strong>Assigned To:</strong> ${escapeHtml(issue.assigned_to_name)}
        </div>
      `
          : ""
      }
      ${
        issue.resolved_at
          ? `
        <div class="detail-row">
          <strong>Resolved On:</strong> ${formatDate(
            issue.resolved_at,
            "DD/MM/YYYY HH:mm"
          )}
        </div>
      `
          : ""
      }
    </div>
  `;
}

// Export reservations
function exportReservations() {
  showAlert("Export functionality will be implemented", "info");
}

// Refresh data
function refreshData() {
  location.reload();
}

// Helper functions
function formatTime(timeString) {
  if (!timeString) return "N/A";
  const parts = timeString.split(":");
  return `${parts[0]}:${parts[1]}`;
}

function formatDate(dateString, format = "DD/MM/YYYY") {
  if (!dateString) return "N/A";

  const date = new Date(dateString);
  const day = String(date.getDate()).padStart(2, "0");
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const year = date.getFullYear();
  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");

  switch (format) {
    case "DD/MM/YYYY":
      return `${day}/${month}/${year}`;
    case "YYYY-MM-DD":
      return `${year}-${month}-${day}`;
    case "DD/MM/YYYY HH:mm":
      return `${day}/${month}/${year} ${hours}:${minutes}`;
    default:
      return date.toLocaleDateString();
  }
}

function escapeHtml(text) {
  if (!text) return "";
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

function getReservationBadgeClass(status) {
  switch (status) {
    case "pending":
      return "warning";
    case "approved":
      return "success";
    case "rejected":
      return "danger";
    case "completed":
      return "secondary";
    default:
      return "secondary";
  }
}

function getIssueBadgeClass(status) {
  switch (status) {
    case "pending":
      return "danger";
    case "in_progress":
      return "warning";
    case "fixed":
      return "success";
    default:
      return "secondary";
  }
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", function () {
  console.log("Staff labs dashboard initialized");
});
