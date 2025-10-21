// Authentication JavaScript Functions

// Helper functions (in case script.js is not loaded)
function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function validatePassword(password) {
  // At least 6 characters
  return password.length >= 6;
}

document.addEventListener("DOMContentLoaded", function () {
  initializeAuthPage();
});

function initializeAuthPage() {
  // Add form validation
  const loginForm = document.getElementById("loginForm");
  const registerForm = document.getElementById("registerForm");
  const forgotForm = document.getElementById("forgotForm");

  if (loginForm) {
    loginForm.addEventListener("submit", handleLoginSubmit);
  }

  if (registerForm) {
    registerForm.addEventListener("submit", handleRegisterSubmit);

    // Add password confirmation validation
    const password = document.getElementById("reg_password");
    const confirmPassword = document.getElementById("confirm_password");

    if (password && confirmPassword) {
      confirmPassword.addEventListener("input", validatePasswordMatch);
      password.addEventListener("input", checkPasswordStrength);
    }
  }

  if (forgotForm) {
    forgotForm.addEventListener("submit", handleForgotSubmit);
  }

  // Add keyboard navigation
  document.addEventListener("keydown", function (e) {
    if (e.key === "Enter" && e.ctrlKey) {
      const activeForm = document.querySelector(".auth-form.active form");
      if (activeForm) {
        activeForm.dispatchEvent(new Event("submit", { cancelable: true }));
      }
    }
  });
}

function showLoginForm() {
  hideAllForms();
  const loginForm = document.getElementById("login-form");
  loginForm.classList.add("active", "slide-in-left");
  loginForm.querySelector('input[type="text"]').focus();
}

function showRegisterForm() {
  hideAllForms();
  const registerForm = document.getElementById("register-form");
  registerForm.classList.add("active", "slide-in-right");
  registerForm.querySelector('input[type="text"]').focus();
}

function showForgotPassword() {
  hideAllForms();
  const forgotForm = document.getElementById("forgot-form");
  forgotForm.classList.add("active", "slide-in-right");
  forgotForm.querySelector('input[type="email"]').focus();
}

function hideAllForms() {
  const forms = document.querySelectorAll(".auth-form");
  forms.forEach((form) => {
    form.classList.remove("active", "slide-in-left", "slide-in-right");
  });
}

function handleLoginSubmit(e) {
  e.preventDefault();

  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  const formData = new FormData(form);

  // Validate form
  if (!validateLoginForm(form)) {
    showAlert("Please fill in all required fields correctly.", "danger");
    return;
  }

  // Set loading state
  setLoading(submitBtn, true);

  // Submit form
  fetch("php/login_process.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        showAlert("Login successful! Redirecting...", "success");
        setTimeout(() => {
          window.location.href = data.redirect || "dashboard.php";
        }, 1000);
      } else {
        showAlert(data.message || "Login failed. Please try again.", "danger");
      }
    })
    .catch((error) => {
      console.error("Login error:", error);
      showAlert("An error occurred. Please check your connection and try again.", "danger");
    })
    .finally(() => {
      setLoading(submitBtn, false);
    });
}

function handleRegisterSubmit(e) {
  e.preventDefault();

  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  const formData = new FormData(form);

  // Validate form
  if (!validateRegisterForm(form)) {
    return;
  }

  // Set loading state
  setLoading(submitBtn, true);

  // Submit form
  fetch("php/register_process.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        showAlert("Registration successful! Please log in.", "success");
        // Clear the form
        form.reset();
        setTimeout(() => {
          showLoginForm();
        }, 1500);
      } else {
        showAlert(
          data.message || "Registration failed. Please try again.",
          "danger"
        );
      }
    })
    .catch((error) => {
      console.error("Registration error:", error);
      showAlert("An error occurred. Please check your connection and try again.", "danger");
    })
    .finally(() => {
      setLoading(submitBtn, false);
    });
}

function handleForgotSubmit(e) {
  e.preventDefault();

  const form = e.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  const formData = new FormData(form);

  // Validate email
  const email = formData.get("email");
  if (!validateEmail(email)) {
    showAlert("Please enter a valid email address.", "danger");
    return;
  }

  // Set loading state
  setLoading(submitBtn, true);

  // Submit form
  fetch("php/forgot_password_process.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        showAlert("Password reset link sent to your email.", "success");
        setTimeout(() => {
          showLoginForm();
        }, 2000);
      } else {
        showAlert(
          data.message || "Failed to send reset link. Please try again.",
          "danger"
        );
      }
    })
    .catch((error) => {
      console.error("Forgot password error:", error);
      showAlert("An error occurred. Please try again.", "danger");
    })
    .finally(() => {
      setLoading(submitBtn, false);
    });
}

function validateLoginForm(form) {
  const loginId = form.querySelector('[name="login_id"]');
  const password = form.querySelector('[name="password"]');
  const role = form.querySelector('[name="role"]');

  let isValid = true;

  // Reset previous validation
  form.querySelectorAll(".form-control").forEach((input) => {
    input.classList.remove("is-invalid", "is-valid");
  });

  // Validate login ID
  if (!loginId.value.trim()) {
    loginId.classList.add("is-invalid");
    isValid = false;
  } else {
    loginId.classList.add("is-valid");
  }

  // Validate password
  if (!password.value || password.value.length < 6) {
    password.classList.add("is-invalid");
    isValid = false;
  } else {
    password.classList.add("is-valid");
  }

  // Validate role
  if (!role.value) {
    role.classList.add("is-invalid");
    isValid = false;
  } else {
    role.classList.add("is-valid");
  }

  return isValid;
}

function validateRegisterForm(form) {
  const name = form.querySelector('[name="name"]');
  const email = form.querySelector('[name="email"]');
  const userId = form.querySelector('[name="user_id"]');
  const role = form.querySelector('[name="role"]');
  const password = form.querySelector('[name="password"]');
  const confirmPassword = form.querySelector('[name="confirm_password"]');
  const terms = form.querySelector('[name="terms"]');

  let isValid = true;

  // Reset previous validation
  form.querySelectorAll(".form-control").forEach((input) => {
    input.classList.remove("is-invalid", "is-valid");
  });

  // Validate name
  if (!name.value.trim() || name.value.trim().length < 2) {
    name.classList.add("is-invalid");
    isValid = false;
  } else {
    name.classList.add("is-valid");
  }

  // Validate email
  if (!validateEmail(email.value)) {
    email.classList.add("is-invalid");
    isValid = false;
  } else {
    email.classList.add("is-valid");
  }

  // Validate user ID format
  if (!validateUserIdFormat(userId.value, role.value)) {
    userId.classList.add("is-invalid");
    isValid = false;
  } else {
    userId.classList.add("is-valid");
  }

  // Validate role
  if (!role.value) {
    role.classList.add("is-invalid");
    isValid = false;
  } else {
    role.classList.add("is-valid");
  }

  // Validate password
  if (!validatePassword(password.value)) {
    password.classList.add("is-invalid");
    isValid = false;
  } else {
    password.classList.add("is-valid");
  }

  // Validate password confirmation
  if (password.value !== confirmPassword.value) {
    confirmPassword.classList.add("is-invalid");
    isValid = false;
  } else {
    confirmPassword.classList.add("is-valid");
  }

  // Validate terms acceptance
  if (!terms.checked) {
    showAlert(
      "Please accept the Terms of Service and Privacy Policy.",
      "danger"
    );
    isValid = false;
  }

  return isValid;
}

function validateUserIdFormat(userId, role) {
  if (!userId || !role) return false;

  const patterns = {
    student: /^STU\d{3,}$/,
    lecturer: /^LEC\d{3,}$/,
    staff: /^STAFF\d{3,}$/,
    admin: /^ADMIN\d{3,}$/,
  };

  return patterns[role] && patterns[role].test(userId);
}

function validatePasswordMatch() {
  const password = document.getElementById("reg_password");
  const confirmPassword = document.getElementById("confirm_password");

  if (password.value !== confirmPassword.value) {
    confirmPassword.classList.add("is-invalid");
    confirmPassword.classList.remove("is-valid");
  } else {
    confirmPassword.classList.remove("is-invalid");
    confirmPassword.classList.add("is-valid");
  }
}

function checkPasswordStrength() {
  const password = document.getElementById("reg_password");
  const strengthIndicator = document.getElementById("password-strength");
  // If password field is empty, remove the indicator
  if (!password.value) {
    if (strengthIndicator) {
      strengthIndicator.remove();
    }
    return;
  }

  // Ensure the strength indicator exists
  let indicator = document.getElementById("password-strength");
  if (!indicator) {
    indicator = document.createElement("div");
    indicator.id = "password-strength";
    indicator.className = "password-strength";
    indicator.innerHTML = `
            <div class="strength-text">Password Strength: <span class="strength-level">Weak</span></div>
            <div class="strength-bar">
                <div class="strength-fill"></div>
            </div>
        `;
    password.parentNode.appendChild(indicator);
  }

  const strength = calculatePasswordStrength(password.value);
  const levelText = indicator.querySelector(".strength-level");
  const strengthBar = indicator.querySelector(".strength-bar");

  // Remove previous strength classes from the indicator
  indicator.classList.remove("strength-weak", "strength-medium", "strength-strong");

  if (strength < 3) {
    levelText.textContent = "Weak";
    indicator.classList.add("strength-weak");
  } else if (strength < 5) {
    levelText.textContent = "Medium";
    indicator.classList.add("strength-medium");
  } else {
    levelText.textContent = "Strong";
    indicator.classList.add("strength-strong");
  }
}

function calculatePasswordStrength(password) {
  let strength = 0;

  // Length check
  if (password.length >= 8) strength++;
  if (password.length >= 12) strength++;

  // Character variety checks
  if (/[a-z]/.test(password)) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[^A-Za-z0-9]/.test(password)) strength++;

  return strength;
}

// Auto-suggest User ID based on role
document.addEventListener("change", function (e) {
  if (
    e.target.name === "role" &&
    e.target.form &&
    e.target.form.id === "registerForm"
  ) {
    const role = e.target.value;
    const userIdInput = e.target.form.querySelector('[name="user_id"]');

    if (role && userIdInput && !userIdInput.value) {
      const suggestions = {
        student: "STU001",
        lecturer: "LEC001",
        staff: "STAFF001",
      };

      if (suggestions[role]) {
        userIdInput.placeholder = `e.g., ${suggestions[role]}`;
      }
    }
  }
});

// Enhanced error display
function showAlert(message, type = "info", duration = 5000) {
  // Remove existing alerts
  const existingAlerts = document.querySelectorAll(".alert");
  existingAlerts.forEach((alert) => alert.remove());

  const alertDiv = document.createElement("div");
  alertDiv.className = `alert alert-${type}`;
  alertDiv.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <span>${message}</span>
            <button type="button" onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer;">&times;</button>
        </div>
    `;

  // Insert at the top of the active form
  const activeForm = document.querySelector(".auth-form.active");
  if (activeForm) {
    activeForm.insertBefore(alertDiv, activeForm.firstChild);
  }

  // Auto-hide after duration
  if (duration > 0) {
    setTimeout(() => {
      if (alertDiv.parentElement) {
        alertDiv.style.opacity = "0";
        setTimeout(() => alertDiv.remove(), 300);
      }
    }, duration);
  }
}

// Override the global setLoading function for auth forms
function setLoading(element, isLoading = true) {
  if (!element.getAttribute("data-original-text")) {
    element.setAttribute("data-original-text", element.innerHTML);
  }

  if (isLoading) {
    element.disabled = true;
    element.innerHTML = '<span class="spinner"></span> Processing...';
  } else {
    element.disabled = false;
    element.innerHTML = element.getAttribute("data-original-text");
  }
}
