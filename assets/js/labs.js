// Labs Management JavaScript - Fixed Version

// Global variables
let labRequestForm = null;
let issueReportForm = null;

// Initialize when document is ready
document.addEventListener("DOMContentLoaded", function () {
  initializeForms();
  initializeFilters();
  setupEventListeners();
});

function initializeForms() {
  // Lab request form
  labRequestForm = document.getElementById("lab-request-form");
  if (labRequestForm) {
    labRequestForm.addEventListener("submit", handleLabRequest);

    // Lab selection change handler
    const labSelect = document.getElementById("lab_id");
    if (labSelect) {
      labSelect.addEventListener("change", handleLabSelection);
    }

    // Date and time validation
    const startTimeInput = document.getElementById("start_time");
    const endTimeInput = document.getElementById("end_time");
    if (startTimeInput && endTimeInput) {
      startTimeInput.addEventListener("change", validateTimeRange);
      endTimeInput.addEventListener("change", validateTimeRange);
    }
  }

  // Issue report form
  issueReportForm = document.getElementById("issue-report-form");
  if (issueReportForm) {
    issueReportForm.addEventListener("submit", handleIssueReport);
  }

  // Lab form (admin)
  const labForm = document.getElementById("lab-form");
  if (labForm) {
    labForm.addEventListener("submit", handleLabManagement);
  }

  // Timetable upload form (admin)
  const timetableForm = document.getElementById("timetable-upload-form");
  if (timetableForm) {
    timetableForm.addEventListener("submit", handleTimetableUpload);
  }
}

function initializeFilters() {
  // Status filter for reservations
  const statusFilter = document.getElementById("status-filter");
  if (statusFilter) {
    statusFilter.addEventListener("change", filterReservations);
  }

  // Lab filter for reservations
  const labFilter = document.getElementById("lab-filter");
  if (labFilter) {
    labFilter.addEventListener("change", filterReservations);
  }
}

function setupEventListeners() {
  // Refresh buttons
  const refreshButtons = document.querySelectorAll("[onclick*='refresh']");
  refreshButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      location.reload();
    });
  });
}

// Helper function for loading states
function setButtonLoading(button, isLoading, loadingText = "Loading...") {
  if (isLoading) {
    button.dataset.originalText = button.textContent;
    button.textContent = loadingText;
    button.disabled = true;
  } else {
    button.textContent = button.dataset.originalText || button.textContent;
    button.disabled = false;
  }
}

// Lab request handling
async function handleLabRequest(e) {
  e.preventDefault();

  if (!validateTimeRange()) {
    return;
  }

  const formData = new FormData(e.target);
  formData.append("action", "submit_reservation");

  const submitBtn = e.target.querySelector('button[type="submit"]');

  try {
    setButtonLoading(submitBtn, true, "Submitting...");

    const response = await fetch("php/labs_api.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success) {
      showAlert(result.message, "success");
      hideModal("lab-request-modal");
      // Refresh reservation list
      setTimeout(() => location.reload(), 1500);
    } else {
      showAlert(result.message, "danger");
    }
  } catch (error) {
    showAlert("An error occurred while submitting the request", "danger");
    console.error("Lab request error:", error);
  } finally {
    setButtonLoading(submitBtn, false);
  }
}

// Issue report handling
async function handleIssueReport(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  formData.append("action", "report_issue");

  const submitBtn = e.target.querySelector('button[type="submit"]');

  try {
    setButtonLoading(submitBtn, true, "Reporting...");

    const response = await fetch("php/labs_api.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success) {
      showAlert(result.message, "success");
      hideModal("issue-report-modal");
      // Reset form
      e.target.reset();
    } else {
      showAlert(result.message, "danger");
    }
  } catch (error) {
    showAlert("An error occurred while reporting the issue", "danger");
    console.error("Issue report error:", error);
  } finally {
    setButtonLoading(submitBtn, false);
  }
}

// Lab management (admin)
async function handleLabManagement(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  formData.append("action", "manage_lab");

  const submitBtn = e.target.querySelector('button[type="submit"]');

  try {
    setButtonLoading(submitBtn, true, "Saving...");

    const response = await fetch("php/labs_api.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success) {
      showAlert(result.message, "success");
      hideModal("add-lab-modal");
      // Refresh the page to show updated data
      setTimeout(() => location.reload(), 1500);
    } else {
      showAlert(result.message, "danger");
    }
  } catch (error) {
    showAlert("An error occurred while saving the lab", "danger");
    console.error("Lab management error:", error);
  } finally {
    setButtonLoading(submitBtn, false);
  }
}

// Timetable upload (admin)
async function handleTimetableUpload(e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  formData.append("action", "upload_timetable");

  const submitBtn = e.target.querySelector('button[type="submit"]');

  try {
    setButtonLoading(submitBtn, true, "Uploading...");

    const response = await fetch("php/labs_api.php", {
      method: "POST",
      body: formData,
    });

    const result = await response.json();

    if (result.success) {
      showAlert(result.message, "success");
      hideModal("upload-timetable-modal");
      // Reset form
      e.target.reset();
    } else {
      showAlert(result.message, "danger");
    }
  } catch (error) {
    showAlert("An error occurred while uploading the timetable", "danger");
    console.error("Timetable upload error:", error);
  } finally {
    setButtonLoading(submitBtn, false);
  }
}

// Get timetable for a lab
async function getTimetable(labId) {
  try {
    const response = await fetch(
      `php/labs_api.php?action=get_timetable&lab_id=${labId}`
    );
    const result = await response.json();

    if (result.success) {
      displayTimetable(result.timetable);
    } else {
      showAlert(result.message, "danger");
    }
  } catch (error) {
    showAlert("An error occurred while loading the timetable", "danger");
    console.error("Timetable error:", error);
  }
}

// Display timetable
function displayTimetable(timetable) {
  const container = document.getElementById("timetable-display");
  if (!container) return;

  if (timetable.length === 0) {
    container.innerHTML =
      '<p class="text-muted">No timetable data available.</p>';
    return;
  }

  let html = '<div class="timetable-grid">';

  // Group by days
  const dayOrder = [
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday",
  ];
  const groupedByDay = {};

  timetable.forEach((entry) => {
    if (!groupedByDay[entry.day]) {
      groupedByDay[entry.day] = [];
    }
    groupedByDay[entry.day].push(entry);
  });

  dayOrder.forEach((day) => {
    if (groupedByDay[day]) {
      html += `<div class="day-schedule">
                 <h4 class="day-title">${day}</h4>`;

      groupedByDay[day].forEach((entry) => {
        html += `<div class="schedule-entry">
                   <div class="time-slot">${entry.start_time} - ${
          entry.end_time
        }</div>
                   <div class="schedule-info">
                     <strong>${entry.title}</strong>
                     ${entry.description ? `<p>${entry.description}</p>` : ""}
                     ${
                       entry.instructor
                         ? `<small>Instructor: ${entry.instructor}</small>`
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

// Cancel reservation
async function cancelReservation(reservationId) {
  showConfirmModal(
    "Cancel Reservation",
    "Are you sure you want to cancel this reservation?",
    async () => {
      try {
        const formData = new FormData();
        formData.append("action", "cancel_reservation");
        formData.append("reservation_id", reservationId);
        formData.append(
          "csrf_token",
          document.querySelector('meta[name="csrf-token"]').content
        );

        const response = await fetch("php/labs_api.php", {
          method: "POST",
          body: formData,
        });

        const result = await response.json();

        if (result.success) {
          showAlert(result.message, "success");
          setTimeout(() => location.reload(), 1500);
        } else {
          showAlert(result.message, "danger");
        }
      } catch (error) {
        showAlert(
          "An error occurred while cancelling the reservation",
          "danger"
        );
        console.error("Cancel reservation error:", error);
      }
    }
  );
}

// View reservation details
async function viewReservationDetails(reservationId) {
  try {
    console.log("Fetching reservation details for ID:", reservationId);
    const response = await fetch(
      `php/labs_api.php?action=get_reservation_details&reservation_id=${reservationId}`
    );

    console.log("Response status:", response.status);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    console.log("API Result:", result);

    if (result.success) {
      displayReservationDetails(result.reservation);
      showModal("reservation-details-modal");
    } else {
      showAlert(
        result.message || "Failed to load reservation details",
        "danger"
      );
      console.error("API Error:", result.message);
    }
  } catch (error) {
    showAlert("An error occurred while loading reservation details", "danger");
    console.error("Reservation details error:", error);
  }
}

// Display reservation details in modal
function displayReservationDetails(reservation) {
  const container = document.getElementById("reservation-details-content");
  if (!container) {
    console.error("Reservation details container not found");
    return;
  }

  console.log("Displaying reservation:", reservation);

  try {
    // Safely escape HTML - simpler version
    const safe = (str) => {
      if (str === null || str === undefined) return "N/A";
      return String(str).replace(/[&<>"']/g, (char) => {
        const escapeChars = {
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        };
        return escapeChars[char];
      });
    };

    // Safe date formatter
    const safeFormatDate = (dateStr, format = "DD/MM/YYYY") => {
      if (!dateStr) return "N/A";
      try {
        return formatDate(dateStr, format);
      } catch (e) {
        console.error("Date format error:", e);
        return dateStr; // Return original if format fails
      }
    };

    // Safe status badge
    const statusText = reservation.status
      ? reservation.status.charAt(0).toUpperCase() + reservation.status.slice(1)
      : "Pending";

    container.innerHTML = `
      <div class="reservation-details">
        <div class="detail-section">
          <h4>Reservation Information</h4>
          <div class="detail-grid">
            <div class="detail-item">
              <label>Reservation ID:</label>
              <span>#${safe(reservation.id)}</span>
            </div>
            <div class="detail-item">
              <label>Status:</label>
              <span class="badge badge-${getStatusBadgeClass(
                reservation.status || "pending"
              )}">
                ${statusText.toUpperCase()}
              </span>
            </div>
            <div class="detail-item">
              <label>Lab:</label>
              <span>${safe(reservation.lab_name)}</span>
            </div>
            ${
              reservation.capacity
                ? `
            <div class="detail-item">
              <label>Lab Capacity:</label>
              <span>${safe(reservation.capacity)} seats</span>
            </div>
            `
                : ""
            }
            <div class="detail-item">
              <label>Date:</label>
              <span>${safeFormatDate(reservation.reservation_date)}</span>
            </div>
            <div class="detail-item">
              <label>Time:</label>
              <span>${safe(reservation.start_time)} - ${safe(
      reservation.end_time
    )}</span>
            </div>
            <div class="detail-item">
              <label>Request Date:</label>
              <span>${safeFormatDate(reservation.request_date, "DD/MM/YYYY HH:mm")}</span>
            </div>
            ${
              reservation.approved_at
                ? `
            <div class="detail-item">
              <label>Approved Date:</label>
              <span>${safeFormatDate(
                reservation.approved_at,
                "DD/MM/YYYY HH:mm"
              )}</span>
            </div>
            `
                : ""
            }
          </div>
        </div>

        <div class="detail-section">
          <h4>Purpose</h4>
          <div class="detail-grid">
            <div class="detail-item full-width">
              <p>${safe(reservation.purpose)}</p>
            </div>
          </div>
        </div>

        ${
          reservation.approved_by_name || reservation.notes
            ? `
        <div class="detail-section">
          <h4>Additional Information</h4>
          <div class="detail-grid">
            ${
              reservation.approved_by_name
                ? `
            <div class="detail-item">
              <label>Approved By:</label>
              <span>${safe(reservation.approved_by_name)}</span>
            </div>
            `
                : ""
            }
            ${
              reservation.notes
                ? `
            <div class="detail-item full-width">
              <label>Notes:</label>
              <span>${safe(reservation.notes)}</span>
            </div>
            `
                : ""
            }
          </div>
        </div>
        `
            : ""
        }

        ${
          reservation.rejection_reason
            ? `
        <div class="detail-section alert-danger">
          <h4>Rejection Reason</h4>
          <div class="detail-grid">
            <div class="detail-item full-width">
              <p class="text-danger"><strong>${safe(
                reservation.rejection_reason
              )}</strong></p>
            </div>
          </div>
        </div>
        `
            : ""
        }
      </div>
    `;

    console.log("Reservation details displayed successfully");
  } catch (error) {
    console.error("Error displaying reservation details:", error);
    console.error("Error stack:", error.stack);
    container.innerHTML = `
      <div class="alert alert-danger">
        <strong>Error:</strong> Unable to display reservation details.
        <br><small>Error: ${error.message}</small>
      </div>
    `;
  }
}

// Validation functions
function validateTimeRange() {
  const startTime = document.getElementById("start_time");
  const endTime = document.getElementById("end_time");

  if (startTime && endTime && startTime.value && endTime.value) {
    if (startTime.value >= endTime.value) {
      showAlert("End time must be after start time", "danger");
      return false;
    }
  }
  return true;
}

function handleLabSelection() {
  // This can be extended to show lab-specific information
  const labSelect = document.getElementById("lab_id");
  if (labSelect && labSelect.value) {
    // Could load lab capacity, availability, etc.
    console.log("Lab selected:", labSelect.value);
  }
}

// Filter functions
function filterReservations() {
  const statusFilter = document.getElementById("status-filter");
  const labFilter = document.getElementById("lab-filter");
  const rows = document.querySelectorAll("#reservations-table tbody tr");

  rows.forEach((row) => {
    let showRow = true;

    // Status filter
    if (statusFilter && statusFilter.value !== "") {
      const statusCell = row.querySelector(".status");
      if (
        statusCell &&
        !statusCell.textContent
          .toLowerCase()
          .includes(statusFilter.value.toLowerCase())
      ) {
        showRow = false;
      }
    }

    // Lab filter
    if (labFilter && labFilter.value !== "") {
      const labCell = row.querySelector(".lab-name");
      if (
        labCell &&
        !labCell.textContent
          .toLowerCase()
          .includes(labFilter.value.toLowerCase())
      ) {
        showRow = false;
      }
    }

    row.style.display = showRow ? "" : "none";
  });
}

// Utility functions
function formatDate(dateString, format = "DD/MM/YYYY") {
  if (typeof window.formatDate === "function") {
    return window.formatDate(dateString, format);
  }

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

function getStatusBadgeClass(status) {
  switch (status) {
    case "pending":
      return "warning";
    case "approved":
      return "success";
    case "rejected":
      return "danger";
    case "cancelled":
      return "secondary";
    case "completed":
      return "success";
    default:
      return "secondary";
  }
}

// Refresh lab status
async function refreshLabStatus() {
  location.reload();
}

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

// Export functions for global access
window.handleLabRequest = handleLabRequest;
window.handleIssueReport = handleIssueReport;
window.handleLabManagement = handleLabManagement;
window.handleTimetableUpload = handleTimetableUpload;
window.cancelReservation = cancelReservation;
window.viewReservationDetails = viewReservationDetails;
window.getTimetable = getTimetable;
window.refreshLabStatus = refreshLabStatus;
window.showConfirmModal = showConfirmModal;
window.validateTimeRange = validateTimeRange;
window.handleLabSelection = handleLabSelection;
window.filterReservations = filterReservations;
