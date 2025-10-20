<?php
require_once 'php/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Faculty of Geomatics Content Management System - Login Portal">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <style>
        /* Additional inline enhancements */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
    </style>
</head>
<body class="auth-body">
    <!-- Background Pattern -->
    <div class="auth-background"></div>
    <div class="auth-container">
        <div class="auth-card">
            <!-- Faculty Logo -->
            <div class="auth-header">
                <img src="images/faculty-logo.png" alt="Faculty of Geomatics" class="faculty-logo" onerror="this.style.display='none'">
                <h1 class="auth-title">Faculty of Geomatics</h1>
                <p class="auth-subtitle">Sabaragamuwa University of Sri Lanka</p>
            </div>

            <!-- Login Form -->
            <div id="login-form" class="auth-form active">
                <h2 class="form-title">Welcome Back</h2>
                <p class="form-description">Sign in to access your account</p>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <form id="loginForm" action="php/login_process.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="login_id" class="form-label">User ID or Email</label>
                        <input 
                            type="text" 
                            id="login_id" 
                            name="login_id" 
                            class="form-control" 
                            required 
                            autocomplete="username"
                            placeholder="john@example.com or STU001"
                            aria-label="User ID or Email">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            required 
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            aria-label="Password">
                    </div>

                    <div class="form-group">
                        <label for="role" class="form-label">Login as</label>
                        <select 
                            id="role" 
                            name="role" 
                            class="form-control form-select" 
                            required
                            aria-label="Select your role">
                            <option value="">Select your role</option>
                            <option value="admin">Admin</option>
                            <option value="lecturer">Lecturer</option>
                            <option value="staff">Staff</option>
                            <option value="student">Student</option>
                        </select>
                    </div>

                    <div class="form-group d-flex justify-content-between align-items-center">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember_me" id="remember_me">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="#" class="forgot-password" onclick="showForgotPassword(); return false;">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block auth-btn">
                        Sign In
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="#" onclick="showRegisterForm(); return false;">Sign up here</a></p>
                </div>
            </div>

            <!-- Register Form -->
            <div id="register-form" class="auth-form">
                <h2 class="form-title">Create Your Account</h2>
                <p class="form-description">Join the Faculty of Geomatics community</p>
                
                <form id="registerForm" action="php/register_process.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="reg_name" class="form-label">Full Name</label>
                        <input 
                            type="text" 
                            id="reg_name" 
                            name="name" 
                            class="form-control" 
                            required 
                            autocomplete="name"
                            placeholder="John Doe"
                            aria-label="Full Name">
                    </div>

                    <div class="form-group">
                        <label for="reg_email" class="form-label">Email Address</label>
                        <input 
                            type="email" 
                            id="reg_email" 
                            name="email" 
                            class="form-control" 
                            required 
                            autocomplete="email"
                            placeholder="john.doe@example.com"
                            aria-label="Email Address">
                    </div>

                    <div class="form-group">
                        <label for="reg_user_id" class="form-label">User ID</label>
                        <input 
                            type="text" 
                            id="reg_user_id" 
                            name="user_id" 
                            class="form-control" 
                            required 
                            placeholder="e.g., STU001, LEC001, STAFF001"
                            aria-label="User ID">
                        <small class="form-text">Format: STU001 (Student), LEC001 (Lecturer), STAFF001 (Staff)</small>
                    </div>

                    <div class="form-group">
                        <label for="reg_role" class="form-label">Role</label>
                        <select 
                            id="reg_role" 
                            name="role" 
                            class="form-control form-select" 
                            required
                            aria-label="Select your role">
                            <option value="">Select your role</option>
                            <option value="student">Student</option>
                            <option value="lecturer">Lecturer</option>
                            <option value="staff">Staff</option>
                        </select>
                        <small class="form-text">Admin accounts must be created by existing admins</small>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label for="reg_password" class="form-label">Password</label>
                                <input 
                                    type="password" 
                                    id="reg_password" 
                                    name="password" 
                                    class="form-control" 
                                    required 
                                    autocomplete="new-password"
                                    placeholder="Min. 6 characters" 
                                    minlength="6"
                                    aria-label="Password">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="form-control" 
                                    required 
                                    autocomplete="new-password"
                                    placeholder="Repeat password"
                                    aria-label="Confirm Password">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-container">
                            <input type="checkbox" name="terms" id="terms" required>
                            <span class="checkmark"></span>
                            I agree to the <a href="#" onclick="return false;">Terms of Service</a> and <a href="#" onclick="return false;">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block auth-btn">
                        Create Account
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="#" onclick="showLoginForm(); return false;">Sign in here</a></p>
                </div>
            </div>

            <!-- Forgot Password Form -->
            <div id="forgot-form" class="auth-form">
                <h2 class="form-title">Reset Your Password</h2>
                <p class="form-description">Enter your email address and we'll send you a link to reset your password.</p>
                
                <form id="forgotForm" action="php/forgot_password_process.php" method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="forgot_email" class="form-label">Email Address</label>
                        <input 
                            type="email" 
                            id="forgot_email" 
                            name="email" 
                            class="form-control" 
                            required 
                            autocomplete="email"
                            placeholder="john.doe@example.com"
                            aria-label="Email Address">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block auth-btn">
                        Send Reset Link
                    </button>
                </form>

                <div class="auth-footer">
                    <p><a href="#" onclick="showLoginForm(); return false;">← Back to Sign In</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>