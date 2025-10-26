// Dashboard JavaScript Functions

document.addEventListener("DOMContentLoaded", function () {
  initializeDashboard();
});

function initializeDashboard() {
  // Initialize notifications
  loadNotifications();
  setInterval(loadNotifications, 60000); // Update every minute

  // Initialize real-time updates
  initializeRealTimeUpdates();

  // Add keyboard shortcuts
  addKeyboardShortcuts();

  // Initialize tooltips
  initializeTooltips();

  // Auto-refresh stats every 5 minutes
  setInterval(refreshStats, 300000);
}

function refreshStats() {
  fetch("php/get_dashboard_stats.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateStatsDisplay(data.stats);
      }
    })
    .catch((error) => {
      console.error("Error refreshing stats:", error);
    });
}

function updateStatsDisplay(stats) {
  Object.keys(stats).forEach((key) => {
    const element = document.querySelector(`[data-stat="${key}"] h3`);
    if (element) {
      // Animate number change
      animateNumber(element, parseInt(element.textContent), stats[key]);
    }
  });
}

function animateNumber(element, start, end) {
  const duration = 1000;
  const range = end - start;
  const increment = range / (duration / 16);
  let current = start;

  const timer = setInterval(() => {
    current += increment;
    if (
      (increment > 0 && current >= end) ||
      (increment < 0 && current <= end)
    ) {
      current = end;
      clearInterval(timer);
    }
    element.textContent = Math.floor(current);
  }, 16);
}

function initializeRealTimeUpdates() {
  // Check for new notifications
  setInterval(() => {
    checkForNewNotifications();
  }, 30000); // Every 30 seconds

  // Update notification badge
  const notificationIcon = document.querySelector(".notification-icon");
  if (notificationIcon) {
    notificationIcon.addEventListener("click", toggleNotificationDropdown);
  }
}

function checkForNewNotifications() {
  fetch("php/check_notifications.php")
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.new_notifications > 0) {
        updateNotificationBadge(data.total_unread);
        showToast(
          `You have ${data.new_notifications} new notification(s)`,
          "info"
        );
      }
    })
    .catch((error) => {
      console.error("Error checking notifications:", error);
    });
}

function toggleNotificationDropdown() {
  let dropdown = document.querySelector(".notification-dropdown");

  if (!dropdown) {
    dropdown = createNotificationDropdown();
    document.body.appendChild(dropdown);
  }

  dropdown.style.display = dropdown.style.display === "none" ? "block" : "none";

  if (dropdown.style.display === "block") {
    loadNotificationsDropdown();
  }
}

function createNotificationDropdown() {
  const dropdown = document.createElement("div");
  dropdown.className = "notification-dropdown";
  dropdown.style.cssText = `
        position: fixed;
        top: 60px;
        right: 20px;
        width: 350px;
        max-height: 400px;
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        z-index: 9999;
        overflow-y: auto;
        display: none;
    `;

  // Close dropdown when clicking outside
  document.addEventListener("click", (e) => {
    if (
      !dropdown.contains(e.target) &&
      !e.target.closest(".notification-icon")
    ) {
      dropdown.style.display = "none";
    }
  });

  return dropdown;
}

function loadNotificationsDropdown() {
  const dropdown = document.querySelector(".notification-dropdown");
  dropdown.innerHTML = '<div class="notification-loading">Loading...</div>';

  fetch("php/get_notifications.php?limit=10")
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        displayNotificationsDropdown(data.notifications);
      } else {
        dropdown.innerHTML =
          '<div class="notification-error">Failed to load notifications</div>';
      }
    })
    .catch((error) => {
      console.error("Error loading notifications:", error);
      dropdown.innerHTML =
        '<div class="notification-error">Error loading notifications</div>';
    });
}

function displayNotificationsDropdown(notifications) {
  const dropdown = document.querySelector(".notification-dropdown");

  if (notifications.length === 0) {
    dropdown.innerHTML = `
            <div class="notification-empty">
                <p>No notifications</p>
            </div>
        `;
    return;
  }

  const html = `
        <div class="notification-header">
            <h4>Notifications</h4>
            <button onclick="markAllNotificationsRead()" class="btn-link">Mark all read</button>
        </div>
        <div class="notification-list">
            ${notifications
      .map(
        (notification) => `
                <div class="notification-item ${notification.is_read ? "" : "unread"
          }" 
                     onclick="markNotificationAsRead(${notification.id})">
                    <div class="notification-content">
                        <div class="notification-title">${notification.title
          }</div>
                        <div class="notification-message">${notification.message
          }</div>
                        <div class="notification-time">${formatDate(
            notification.created_at,
            "DD/MM/YYYY HH:mm"
          )}</div>
                    </div>
                    ${!notification.is_read
            ? '<div class="notification-dot"></div>'
            : ""
          }
                </div>
            `
      )
      .join("")}
        </div>
        <div class="notification-footer">
            <a href="notifications.php" class="btn btn-sm btn-primary">View All</a>
        </div>
    `;

  dropdown.innerHTML = html;
}

function markAllNotificationsRead() {
  fetch("php/mark_all_notifications_read.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      csrf_token: document.querySelector('meta[name="csrf-token"]').content,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        updateNotificationBadge(0);
        loadNotificationsDropdown();
        showToast("All notifications marked as read", "success");
      }
    })
    .catch((error) => {
      console.error("Error marking notifications as read:", error);
    });
}

function addKeyboardShortcuts() {
  document.addEventListener("keydown", (e) => {
    // Ctrl/Cmd + combinations
    if (e.ctrlKey || e.metaKey) {
      switch (e.key) {
        case "h":
          e.preventDefault();
          showModal("help-modal");
          break;
        case "n":
          e.preventDefault();
          toggleNotificationDropdown();
          break;
        case "k":
          e.preventDefault();
          focusSearch();
          break;
      }
    }

    // Escape key
    if (e.key === "Escape") {
      // Close any open modals or dropdowns
      const modal = document.querySelector('.modal[style*="flex"]');
      if (modal) {
        modal.style.display = "none";
      }

      const dropdown = document.querySelector(
        '.notification-dropdown[style*="block"]'
      );
      if (dropdown) {
        dropdown.style.display = "none";
      }
    }
  });
}

function focusSearch() {
  const searchInput = document.querySelector('input[type="search"]');
  if (searchInput) {
    searchInput.focus();
  }
}

function initializeTooltips() {
  // Add tooltips to action cards
  document.querySelectorAll(".action-card").forEach((card) => {
    const title = card.querySelector("h4").textContent;
    const description = card.querySelector("p").textContent;
    card.setAttribute("data-tooltip", `${title}: ${description}`);
  });

  // Add tooltips to stat cards
  document.querySelectorAll(".stat-card").forEach((card) => {
    const number = card.querySelector("h3").textContent;
    const label = card.querySelector("p").textContent;
    card.setAttribute(
      "data-tooltip",
      `Current ${label.toLowerCase()}: ${number}`
    );
  });
}

// Quick action functions
function quickBorrowRequest() {
  window.location.href = "store/";
}

function quickLabRequest() {
  window.location.href = "labs/request-reservation.php";
}

function quickReportIssue() {
  window.location.href = "issues/report-issue.php";
}

function viewLabsOverview() {
  window.location.href = "labs/labs-overview.php";
}

// Dashboard refresh function
function refreshDashboard() {
  showToast("Refreshing dashboard...", "info");

  // Refresh stats
  refreshStats();

  // Refresh notifications
  loadNotifications();

  // Add loading state to dashboard
  document.querySelector(".dashboard-content").classList.add("loading");

  setTimeout(() => {
    document.querySelector(".dashboard-content").classList.remove("loading");
    showToast("Dashboard refreshed", "success");
  }, 1500);
}

// Add refresh button to header
function addRefreshButton() {
  const headerRight = document.querySelector(".header-right");
  const refreshBtn = document.createElement("button");
  refreshBtn.className = "btn btn-sm btn-outline-primary";
  refreshBtn.innerHTML = "🔄 Refresh";
  refreshBtn.onclick = refreshDashboard;
  refreshBtn.style.marginRight = "var(--space-3)";

  headerRight.insertBefore(refreshBtn, headerRight.firstChild);
}

// Initialize on load
document.addEventListener("DOMContentLoaded", () => {
  addRefreshButton();
});

// Handle tile hover effects
document.querySelectorAll(".tile, .action-card, .stat-card").forEach((card) => {
  card.addEventListener("mouseenter", function () {
    this.style.transform = "translateY(-4px)";
  });

  card.addEventListener("mouseleave", function () {
    this.style.transform = "translateY(0)";
  });
});

// Add click effects to interactive elements
document.querySelectorAll(".btn, .action-card, .tile").forEach((element) => {
  element.addEventListener("click", function () {
    this.style.transform = "scale(0.98)";
    setTimeout(() => {
      this.style.transform = "";
    }, 100);
  });
});

// Dark mode toggle (future enhancement)
function toggleDarkMode() {
  document.body.classList.toggle("dark-mode");
  localStorage.setItem(
    "dark-mode",
    document.body.classList.contains("dark-mode")
  );
}

// Load dark mode preference
if (localStorage.getItem("dark-mode") === "true") {
  document.body.classList.add("dark-mode");
}

// Enhanced search functionality (if search is added later)
function initializeSearch() {
  const searchInput = document.querySelector("#dashboard-search");
  if (searchInput) {
    let searchTimeout;

    searchInput.addEventListener("input", function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        performDashboardSearch(this.value);
      }, 300);
    });
  }
}

function performDashboardSearch(query) {
  if (!query.trim()) {
    showAllDashboardItems();
    return;
  }

  // Hide/show dashboard items based on search
  document.querySelectorAll(".action-card, .tile").forEach((item) => {
    const text = item.textContent.toLowerCase();
    if (text.includes(query.toLowerCase())) {
      item.style.display = "";
    } else {
      item.style.display = "none";
    }
  });
}

function showAllDashboardItems() {
  document.querySelectorAll(".action-card, .tile").forEach((item) => {
    item.style.display = "";
  });
}

// Performance monitoring
function logPagePerformance() {
  if (window.performance && window.performance.timing) {
    const timing = window.performance.timing;
    const loadTime = timing.loadEventEnd - timing.navigationStart;

    // Log to analytics or send to server
    console.log(`Dashboard load time: ${loadTime}ms`);
  }
}

// Initialize performance monitoring
window.addEventListener("load", logPagePerformance);
