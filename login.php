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
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
</head>
<body class="auth-body">
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
                <h2 class="form-title">Sign In</h2>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <form id="loginForm" action="php/login_process.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="login_id" class="form-label">User ID or Email</label>
                        <input type="text" id="login_id" name="login_id" class="form-control" required 
                               placeholder="Enter your User ID or Email">
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required 
                               placeholder="Enter your password">
                    </div>

                    <div class="form-group">
                        <label for="role" class="form-label">Login as</label>
                        <select id="role" name="role" class="form-control form-select" required>
                            <option value="">Select your role</option>
                            <option value="admin">Admin</option>
                            <option value="lecturer">Lecturer</option>
                            <option value="staff">Staff</option>
                            <option value="student">Student</option>
                        </select>
                    </div>

                    <div class="form-group d-flex justify-content-between align-items-center">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember_me">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="#" class="forgot-password" onclick="showForgotPassword()">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block auth-btn">
                        Sign In
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="#" onclick="showRegisterForm()">Sign up here</a></p>
                </div>
            </div>

            <!-- Register Form -->
            <div id="register-form" class="auth-form">
                <h2 class="form-title">Create Account</h2>
                
                <form id="registerForm" action="php/register_process.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="reg_name" class="form-label">Full Name</label>
                        <input type="text" id="reg_name" name="name" class="form-control" required 
                               placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label for="reg_email" class="form-label">Email Address</label>
                        <input type="email" id="reg_email" name="email" class="form-control" required 
                               placeholder="Enter your email address">
                    </div>

                    <div class="form-group">
                        <label for="reg_user_id" class="form-label">User ID</label>
                        <input type="text" id="reg_user_id" name="user_id" class="form-control" required 
                               placeholder="e.g., STU001, LEC001, ADMIN001">
                        <small class="form-text">Use format: STU001 (Student), LEC001 (Lecturer), STAFF001 (Staff), ADMIN001 (Admin)</small>
                    </div>

                    <div class="form-group">
                        <label for="reg_role" class="form-label">Role</label>
                        <select id="reg_role" name="role" class="form-control form-select" required>
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
                                <input type="password" id="reg_password" name="password" class="form-control" required 
                                       placeholder="Choose a strong password" minlength="6">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                                       placeholder="Confirm your password">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-container">
                            <input type="checkbox" name="terms" required>
                            <span class="checkmark"></span>
                            I agree to the <a href="#" target="_blank">Terms of Service</a> and <a href="#" target="_blank">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block auth-btn">
                        Create Account
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="#" onclick="showLoginForm()">Sign in here</a></p>
                </div>
            </div>

            <!-- Forgot Password Form -->
            <div id="forgot-form" class="auth-form">
                <h2 class="form-title">Reset Password</h2>
                <p class="form-description">Enter your email address and we'll send you a link to reset your password.</p>
                
                <form id="forgotForm" action="php/forgot_password_process.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="forgot_email" class="form-label">Email Address</label>
                        <input type="email" id="forgot_email" name="email" class="form-control" required 
                               placeholder="Enter your email address">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block auth-btn">
                        Send Reset Link
                    </button>
                </form>

                <div class="auth-footer">
                    <p><a href="#" onclick="showLoginForm()">Back to Sign In</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Background Labs Image -->
    <div class="auth-background"></div>

    <script src="js/script.js"></script>
    <script src="js/auth.js"></script>
</body>
</html>