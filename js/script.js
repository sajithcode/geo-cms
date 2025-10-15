// Geo CMS JavaScript Functions

// Utility Functions
function formatDate(date, format = "DD/MM/YYYY") {
  const d = new Date(date);
  const day = String(d.getDate()).padStart(2, "0");
  const month = String(d.getMonth() + 1).padStart(2, "0");
  const year = d.getFullYear();
  const hours = String(d.getHours()).padStart(2, "0");
  const minutes = String(d.getMinutes()).padStart(2, "0");

  switch (format) {
    case "DD/MM/YYYY":
      return `${day}/${month}/${year}`;
    case "YYYY-MM-DD":
      return `${year}-${month}-${day}`;
    case "DD/MM/YYYY HH:mm":
      return `${day}/${month}/${year} ${hours}:${minutes}`;
    default:
      return d.toLocaleDateString();
  }
}

function showAlert(message, type = "info", duration = 5000) {
  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${type} alert-dismissible`;
  alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()">×</button>
    `;

  // Insert at the top of the page
  const container = document.querySelector(".container") || document.body;
  container.insertBefore(alertDiv, container.firstChild);

  // Auto-hide after duration
  if (duration > 0) {
    setTimeout(() => {
      if (alertDiv.parentElement) {
        alertDiv.remove();
      }
    }, duration);
  }
}

function showToast(message, type = "info") {
  const toast = document.createElement("div");
  toast.className = `toast toast-${type}`;
  toast.innerHTML = message;

  // Create toast container if it doesn't exist
  let toastContainer = document.getElementById("toast-container");
  if (!toastContainer) {
    toastContainer = document.createElement("div");
    toastContainer.id = "toast-container";
    toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        `;
    document.body.appendChild(toastContainer);
  }

  toastContainer.appendChild(toast);

  // Auto-hide after 3 seconds
  setTimeout(() => {
    toast.remove();
  }, 3000);
}

// Notification function for issue reporting and other modules
function showNotification(message, type = "info", duration = 5000) {
  // Use existing notification container or create one
  let notificationContainer = document.getElementById("notification-container");

  if (!notificationContainer) {
    // Create container if it doesn't exist
    notificationContainer = document.createElement("div");
    notificationContainer.id = "notification-container";
    notificationContainer.className = "notification-container";
    document.body.appendChild(notificationContainer);
  }

  // Create notification element
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;

  // Map type to appropriate icon
  const icons = {
    success: "✅",
    error: "❌",
    warning: "⚠️",
    info: "ℹ️",
  };

  const icon = icons[type] || icons.info;

  notification.innerHTML = `
    <div class="notification-content">
      <span class="notification-icon">${icon}</span>
      <span class="notification-message">${message}</span>
      <button class="notification-close" onclick="this.parentElement.parentElement.remove()">×</button>
    </div>
  `;

  // Add to container
  notificationContainer.appendChild(notification);

  // Animate in
  setTimeout(() => {
    notification.classList.add("show");
  }, 10);

  // Auto-hide after duration
  if (duration > 0) {
    setTimeout(() => {
      notification.classList.remove("show");
      setTimeout(() => {
        if (notification.parentElement) {
          notification.remove();
        }
      }, 300);
    }, duration);
  }
}

// Form Validation
function validateForm(formId) {
  const form = document.getElementById(formId);
  const inputs = form.querySelectorAll(
    "input[required], select[required], textarea[required]"
  );
  let isValid = true;

  inputs.forEach((input) => {
    if (!input.value.trim()) {
      input.classList.add("is-invalid");
      isValid = false;
    } else {
      input.classList.remove("is-invalid");
      input.classList.add("is-valid");
    }
  });

  return isValid;
}

function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function validatePassword(password) {
  // At least 6 characters
  return password.length >= 6;
}

// AJAX Helper Functions
function makeRequest(url, method = "GET", data = null) {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url);
    xhr.setRequestHeader("Content-Type", "application/json");

    // Add CSRF token if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
      xhr.setRequestHeader("X-CSRF-Token", csrfToken.content);
    }

    xhr.onload = function () {
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          const response = JSON.parse(xhr.responseText);
          resolve(response);
        } catch (e) {
          resolve(xhr.responseText);
        }
      } else {
        reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
      }
    };

    xhr.onerror = function () {
      reject(new Error("Network error"));
    };

    xhr.send(data ? JSON.stringify(data) : null);
  });
}

// Sidebar Toggle
function toggleSidebar() {
  const sidebar = document.querySelector(".sidebar");
  const overlay = document.querySelector(".sidebar-overlay");

  if (sidebar) {
    sidebar.classList.toggle("active");

    // Create overlay for mobile
    if (window.innerWidth <= 768) {
      if (!overlay) {
        const newOverlay = document.createElement("div");
        newOverlay.className = "sidebar-overlay";
        newOverlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 999;
                    display: none;
                `;
        newOverlay.onclick = () => toggleSidebar();
        document.body.appendChild(newOverlay);
      }

      const currentOverlay = document.querySelector(".sidebar-overlay");
      if (sidebar.classList.contains("active")) {
        currentOverlay.style.display = "block";
      } else {
        currentOverlay.style.display = "none";
      }
    }
  }
}

// Modal Functions
function showModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";
  }
}

function hideModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.style.display = "none";
    document.body.style.overflow = "auto";
  }
}

// Confirmation Dialog
function confirmAction(message, callback) {
  if (confirm(message)) {
    callback();
  }
}

// Loading State
function setLoading(element, isLoading = true) {
  if (isLoading) {
    element.disabled = true;
    element.innerHTML = '<span class="spinner"></span> Loading...';
  } else {
    element.disabled = false;
    element.innerHTML = element.getAttribute("data-original-text") || "Submit";
  }
}

// File Upload Preview
function previewFile(input, previewId) {
  const file = input.files[0];
  const preview = document.getElementById(previewId);

  if (file && preview) {
    const reader = new FileReader();
    reader.onload = function (e) {
      if (file.type.startsWith("image/")) {
        preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">`;
      } else {
        preview.innerHTML = `<p>File: ${file.name}</p>`;
      }
    };
    reader.readAsDataURL(file);
  }
}

// Table Search
function searchTable(searchInput, tableId) {
  const filter = searchInput.value.toLowerCase();
  const table = document.getElementById(tableId);
  const rows = table
    .getElementsByTagName("tbody")[0]
    .getElementsByTagName("tr");

  for (let i = 0; i < rows.length; i++) {
    const cells = rows[i].getElementsByTagName("td");
    let found = false;

    for (let j = 0; j < cells.length; j++) {
      if (cells[j].textContent.toLowerCase().includes(filter)) {
        found = true;
        break;
      }
    }

    rows[i].style.display = found ? "" : "none";
  }
}

// Notifications
function loadNotifications() {
  makeRequest("php/get_notifications.php")
    .then((response) => {
      if (response.success) {
        updateNotificationBadge(response.unread_count);
        updateNotificationDropdown(response.notifications);
      }
    })
    .catch((error) => {
      console.error("Error loading notifications:", error);
    });
}

function updateNotificationBadge(count) {
  const badge = document.querySelector(".notification-badge");
  if (badge) {
    badge.textContent = count;
    badge.style.display = count > 0 ? "inline" : "none";
  }
}

function updateNotificationDropdown(notifications) {
  const dropdown = document.querySelector(".notification-dropdown");
  if (dropdown) {
    dropdown.innerHTML = notifications
      .map(
        (notification) => `
            <div class="notification-item ${
              notification.is_read ? "" : "unread"
            }">
                <div class="notification-title">${notification.title}</div>
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${formatDate(
                  notification.created_at,
                  "DD/MM/YYYY HH:mm"
                )}</div>
            </div>
        `
      )
      .join("");
  }
}

function markNotificationAsRead(notificationId) {
  makeRequest("php/mark_notification_read.php", "POST", { id: notificationId })
    .then((response) => {
      if (response.success) {
        loadNotifications();
      }
    })
    .catch((error) => {
      console.error("Error marking notification as read:", error);
    });
}

// Real-time Clock
function updateClock() {
  const clockElement = document.getElementById("current-time");
  if (clockElement) {
    const now = new Date();
    const time = now.toLocaleTimeString();
    const date = now.toLocaleDateString();
    clockElement.textContent = `${date} ${time}`;
  }
}

// Auto-save Form Data
function autoSaveForm(formId, interval = 30000) {
  const form = document.getElementById(formId);
  if (!form) return;

  setInterval(() => {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    localStorage.setItem(`autosave_${formId}`, JSON.stringify(data));
  }, interval);
}

function restoreFormData(formId) {
  const savedData = localStorage.getItem(`autosave_${formId}`);
  if (savedData) {
    const data = JSON.parse(savedData);
    const form = document.getElementById(formId);

    Object.keys(data).forEach((key) => {
      const input = form.querySelector(`[name="${key}"]`);
      if (input) {
        input.value = data[key];
      }
    });
  }
}

// Initialize page
document.addEventListener("DOMContentLoaded", function () {
  // Update clock every second
  updateClock();
  setInterval(updateClock, 1000);

  // Load notifications if user is logged in
  if (document.querySelector(".notification-badge")) {
    loadNotifications();
    setInterval(loadNotifications, 60000); // Update every minute
  }

  // Auto-hide alerts
  document.querySelectorAll(".alert").forEach((alert) => {
    setTimeout(() => {
      if (alert.parentElement) {
        alert.style.opacity = "0";
        setTimeout(() => alert.remove(), 300);
      }
    }, 5000);
  });

  // Initialize tooltips
  document.querySelectorAll("[data-tooltip]").forEach((element) => {
    element.addEventListener("mouseenter", function () {
      const tooltip = document.createElement("div");
      tooltip.className = "tooltip";
      tooltip.textContent = this.getAttribute("data-tooltip");
      document.body.appendChild(tooltip);

      const rect = this.getBoundingClientRect();
      tooltip.style.left =
        rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + "px";
      tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + "px";
    });

    element.addEventListener("mouseleave", function () {
      const tooltip = document.querySelector(".tooltip");
      if (tooltip) tooltip.remove();
    });
  });

  // Form validation on submit
  document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", function (e) {
      if (!validateForm(this.id)) {
        e.preventDefault();
        showAlert("Please fill in all required fields.", "danger");
      }
    });
  });
});

// Export for use in other scripts
window.GeoCMS = {
  formatDate,
  showAlert,
  showToast,
  showNotification,
  validateForm,
  validateEmail,
  validatePassword,
  makeRequest,
  toggleSidebar,
  showModal,
  hideModal,
  confirmAction,
  setLoading,
  previewFile,
  searchTable,
  loadNotifications,
  markNotificationAsRead,
  autoSaveForm,
  restoreFormData,
};
