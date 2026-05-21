<?php
session_start();
require_once 'config/database.php';

if(isset($_POST['register'])){

    $name        = mysqli_real_escape_string($conn, $_POST['name']);
    $designation = mysqli_real_escape_string($conn, $_POST['designation']);
    $contact_no  = mysqli_real_escape_string($conn, $_POST['contact_no']);
    $email       = mysqli_real_escape_string($conn, $_POST['email']);
    $password    = $_POST['password'];
    $confirm     = $_POST['confirm_password'];

    // Password match check
    if($password !== $confirm){
        $error = "Passwords do not match!";
    } 
    else {

        // Check if email already exists
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
        
        if(mysqli_num_rows($check) > 0){
            $error = "Email already registered!";
        } 
        else {

            // Password Hash
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Default values
            $role = "user";
            $department = "sales";

            // Insert user
            $insert = mysqli_query($conn,
                "INSERT INTO users (name,designation,contact_no,email,password,role,department)
                 VALUES ('$name','$designation','$contact_no','$email','$hashedPassword','$role','$department')"
            );

            if($insert){
                header("Location: login.php");
                exit();
            } else {
                $error = "Registration Failed!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Register | STS Sales CRM</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="shortcut icon" href="assets/img/favicon.png">
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* =========================================
       ULTRA PREMIUM REGISTRATION UI STYLES
       ========================================= */
    :root {
        --primary-color: #4318ff;
        --primary-hover: #3311db;
        --bg-light: #f4f7fe;
        --text-dark: #1b2559;
        --text-muted: #a3aed0;
        --border-color: #e2e8f0;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: #ffffff;
        color: var(--text-dark);
        overflow-x: hidden;
    }

    /* Left Side: Form Container */
    .login-wrapper {
        min-height: 100vh;
        display: flex;
    }

    .login-form-section {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 3rem;
        background: #ffffff;
        position: relative;
        overflow-y: auto; /* Allows scrolling for longer forms */
    }

    .login-form-inner {
        max-width: 420px;
        width: 100%;
        margin: auto; /* Centers vertically when space is available */
    }

    .auth-logo img {
        height: 45px;
        object-fit: contain;
        margin-bottom: 1.5rem;
    }

    .auth-title {
        font-weight: 700;
        font-size: 1.8rem;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
        letter-spacing: -0.03em;
    }

    .auth-subtitle {
        color: var(--text-muted);
        font-size: 0.95rem;
        font-weight: 500;
        margin-bottom: 2rem;
    }

    /* Custom Inputs */
    .custom-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-dark);
        margin-bottom: 6px;
    }

    .input-group-custom {
        position: relative;
        margin-bottom: 1.2rem;
    }

    .input-group-custom .input-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        font-size: 1.2rem;
        z-index: 10;
        transition: color 0.3s ease;
    }

    .custom-input {
        width: 100%;
        padding: 12px 16px 12px 45px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        font-size: 0.95rem;
        background-color: #f8fafc;
        color: var(--text-dark);
        transition: all 0.3s ease;
    }

    .custom-input:focus {
        outline: none;
        background-color: #ffffff;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(67, 24, 255, 0.1);
    }

    .custom-input:focus ~ .input-icon {
        color: var(--primary-color);
    }

    /* Password Toggle */
    .toggle-password-btn {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        cursor: pointer;
        z-index: 10;
        background: none;
        border: none;
        padding: 0;
    }

    .toggle-password-btn:hover {
        color: var(--text-dark);
    }

    /* Primary Button */
    .btn-login {
        background-color: var(--primary-color);
        color: #ffffff;
        border: none;
        width: 100%;
        padding: 14px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 10px 20px rgba(67, 24, 255, 0.15);
        margin-top: 10px;
    }

    .btn-login:hover {
        background-color: var(--primary-hover);
        transform: translateY(-2px);
        box-shadow: 0 15px 25px rgba(67, 24, 255, 0.25);
    }

    /* Alert Box */
    .custom-alert {
        background-color: #fef2f2;
        border-left: 4px solid #ef4444;
        color: #991b1b;
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Right Side: Visual Banner */
    .login-banner-section {
        flex: 1.2;
        background: linear-gradient(135deg, #4318ff 0%, #8b5cf6 100%);
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 4rem;
    }

    .banner-content {
        position: relative;
        z-index: 2;
        text-align: center;
        color: #ffffff;
        max-width: 500px;
    }

    .banner-content h1 {
        font-weight: 800;
        font-size: 3rem;
        margin-bottom: 1rem;
        line-height: 1.2;
    }

    .banner-content p {
        font-size: 1.1rem;
        opacity: 0.9;
        line-height: 1.6;
    }

    /* Decorative Glass Circles in Banner */
    .glass-circle-1, .glass-circle-2 {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .glass-circle-1 {
        width: 300px;
        height: 300px;
        top: -50px;
        left: -50px;
    }

    .glass-circle-2 {
        width: 450px;
        height: 450px;
        bottom: -100px;
        right: -100px;
    }

    /* Scrollbar styling for form section */
    .login-form-section::-webkit-scrollbar { width: 6px; }
    .login-form-section::-webkit-scrollbar-track { background: transparent; }
    .login-form-section::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    /* Mobile Responsive */
    @media (max-width: 991.98px) {
        .login-banner-section {
            display: none;
        }
        .login-form-section {
            padding: 2rem 1.5rem;
        }
    }
</style>
</head>

<body>

<div class="login-wrapper">
    
    <div class="login-form-section">
        <div class="login-form-inner">
            
            <div class="auth-logo">
                <img src="assets/logo.jpeg" alt="STS CRM Logo">
            </div>

            <div>
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join our platform and start managing your workflow.</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="custom-alert">
                    <i class="ti ti-alert-circle text-danger fs-5"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                
                <div class="row">
                    <div class="col-md-12">
                        <label class="custom-label">Full Name</label>
                        <div class="input-group-custom">
                            <input type="text" name="name" class="custom-input" placeholder="e.g. John Doe" required>
                            <i class="ti ti-user input-icon"></i>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="custom-label">Designation</label>
                        <div class="input-group-custom">
                            <input type="text" name="designation" class="custom-input" placeholder="e.g. Sales Executive" required>
                            <i class="ti ti-briefcase input-icon"></i>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="custom-label">Contact Number</label>
                        <div class="input-group-custom">
                            <input type="text" name="contact_no" class="custom-input" placeholder="+91 98765 43210" required>
                            <i class="ti ti-phone input-icon"></i>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="custom-label">Email Address</label>
                        <div class="input-group-custom">
                            <input type="email" name="email" class="custom-input" placeholder="name@company.com" required>
                            <i class="ti ti-mail input-icon"></i>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="custom-label">Password</label>
                        <div class="input-group-custom">
                            <input type="password" name="password" class="custom-input" placeholder="Min 8 chars" required>
                            <i class="ti ti-lock input-icon"></i>
                            <button type="button" class="toggle-password-btn">
                                <i class="ti ti-eye-off"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="custom-label">Confirm Password</label>
                        <div class="input-group-custom">
                            <input type="password" name="confirm_password" class="custom-input" placeholder="Retype password" required>
                            <i class="ti ti-lock-check input-icon"></i>
                            <button type="button" class="toggle-password-btn">
                                <i class="ti ti-eye-off"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" name="register" class="btn-login">
                    Create My Account
                </button>

                <div class="text-center mt-4 mb-3">
                    <p class="text-muted mb-0" style="font-size: 0.95rem;">
                        Already have an account? 
                        <a href="login.php" class="fw-bold" style="color: var(--primary-color); text-decoration: none;">Sign In Instead</a>
                    </p>
                </div>
            </form>

            <div class="text-center mt-3">
                <p class="mb-0 text-muted" style="font-size: 0.8rem;">
                    Copyright &copy; <?= date("Y"); ?> - STS CRM. All rights reserved.
                </p>
            </div>

        </div>
    </div>

    <div class="login-banner-section">
        <div class="glass-circle-1"></div>
        <div class="glass-circle-2"></div>
        
        <div class="banner-content">
            <h1>Join Your Team.</h1>
            <p>Empower your business with real-time insights, automated workflows, and an intuitive pipeline management system.</p>
        </div>
    </div>
</div>

<script src="assets/js/jquery-3.7.1.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>

<script>
    // Smooth Password Visibility Toggle for MULTIPLE fields
    $(document).ready(function() {
        $('.toggle-password-btn').on('click', function() {
            // Find the input field relative to the clicked button
            let passInput = $(this).siblings('.custom-input');
            let icon = $(this).find('i');

            if (passInput.attr('type') === 'password') {
                passInput.attr('type', 'text');
                icon.removeClass('ti-eye-off').addClass('ti-eye');
            } else {
                passInput.attr('type', 'password');
                icon.removeClass('ti-eye').addClass('ti-eye-off');
            }
        });
    });
</script>

</body>
</html>