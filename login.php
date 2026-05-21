<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config/database.php';

if(isset($_POST['login'])){
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' LIMIT 1");
    $user  = mysqli_fetch_assoc($query);

    // ✅ Secure Password Verify
    if($user && password_verify($password, $user['password'])){

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['name']    = $user['name'];

        header("Location: index.php");
        exit();

    } else {
        $error = "Invalid Email or Password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Login | STS Sales CRM</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="shortcut icon" href="assets/logo.jpg">
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/plugins/tabler-icons/tabler-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* =========================================
       ULTRA PREMIUM LOGIN UI STYLES
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
        justify-content: center;
        padding: 3rem;
        background: #ffffff;
        position: relative;
    }

    .login-form-inner {
        max-width: 420px;
        width: 100%;
        margin: 0 auto;
    }

    .auth-logo img {
        height: 50px;
        object-fit: contain;
        margin-bottom: 2rem;
    }

    .auth-title {
        font-weight: 700;
        font-size: 2rem;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
        letter-spacing: -0.03em;
    }

    .auth-subtitle {
        color: var(--text-muted);
        font-size: 1rem;
        font-weight: 500;
        margin-bottom: 2.5rem;
    }

    /* Custom Inputs */
    .custom-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-dark);
        margin-bottom: 8px;
    }

    .input-group-custom {
        position: relative;
        margin-bottom: 1.5rem;
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
        padding: 14px 16px 14px 45px;
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
.banner-image {
    width: 100%;          /* Image ko section ki puri width lene ke liye */
    height: 100vh;        /* Pure screen ki height lene ke liye (aap isko apne hisaab se 500px vagaira bhi kar sakte ho) */
    object-fit: cover;    /* Isse image perfect fit hogi aur stretch nahi hogi */
    display: block;
}
    /* Right Side: Visual Banner */
    /*.login-banner-section {*/
    /*    flex: 1.2;*/
    /*    background: linear-gradient(135deg, #4318ff 0%, #8b5cf6 100%);*/
    /*    position: relative;*/
    /*    overflow: hidden;*/
    /*    display: flex;*/
    /*    align-items: center;*/
    /*    justify-content: center;*/
    /*    padding: 4rem;*/
    /*}*/

.login-banner-section {
    /* Niche URL mein apni image ka sahi naam daal dena (jaise banner.jpg ya img/banner.png) */
    background-image: url('/assets/img/log.png'); 
    background-size: cover;       /* Ye image ko pure right section mein fit kar dega */
    background-position: center;  /* Image ko center mein rakhega */
    background-repeat: no-repeat; /* Image ko baar-baar repeat hone se rokega */
    height: 100vh;                /* Screen ki puri lambai (height) lene ke liye */
    width: 44%;                   /* Right side ka adha hissa lene ke liye (agar pehle se set hai toh isko chhod sakte ho) */
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

    .footer-text {
        position: absolute;
        bottom: 2rem;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 0.85rem;
        color: var(--text-muted);
        font-weight: 500;
    }

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
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Enter your email and password to access your dashboard.</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="custom-alert">
                    <i class="ti ti-alert-circle text-danger fs-5"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="custom-label">Email Address</label>
                    <div class="input-group-custom">
                        <input type="email" name="email" class="custom-input" placeholder="name@company.com" required autofocus>
                        <i class="ti ti-mail input-icon"></i>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="custom-label">Password</label>
                    <div class="input-group-custom">
                        <input type="password" name="password" id="passwordInput" class="custom-input" placeholder="Min. 8 characters" required>
                        <i class="ti ti-lock input-icon"></i>
                        <button type="button" class="toggle-password-btn" id="togglePassword">
                            <i class="ti ti-eye-off" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" name="login" class="btn-login">
                    Sign In to Dashboard
                </button>
            </form>

        </div>

        <div class="footer-text">
            Copyright &copy; <?= date("Y"); ?> - STS CRM System. All rights reserved.
        </div>
    </div>

    <div class="login-banner-section">
    
</div>
</div>

<script src="assets/js/jquery-3.7.1.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>

<script>
    // Simple & Smooth Password Visibility Toggle
    $(document).ready(function() {
        $('#togglePassword').on('click', function() {
            let passInput = $('#passwordInput');
            let icon = $('#toggleIcon');

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