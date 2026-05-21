<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config/database.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $gstin = strtoupper(mysqli_real_escape_string($conn, $_POST['gstin']));
    $pan = strtoupper(mysqli_real_escape_string($conn, $_POST['pan']));
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    // Check agar pehle se company added hai toh usko update karenge, nahi toh nayi add karenge
    $check = mysqli_query($conn, "SELECT id FROM po_issuer_companies LIMIT 1");
    
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $id = $row['id'];
        $query = "UPDATE po_issuer_companies SET company_name=?, address=?, gstin=?, pan=?, email=?, phone=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssi", $company_name, $address, $gstin, $pan, $email, $phone, $id);
    } else {
        $query = "INSERT INTO po_issuer_companies (company_name, address, gstin, pan, email, phone) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssss", $company_name, $address, $gstin, $pan, $email, $phone);
    }

    if ($stmt->execute()) {
        $message = '<div class="alert alert-success shadow-sm border-0"><i class="fas fa-check-circle me-2"></i> Company Details Saved Successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger shadow-sm border-0"><i class="fas fa-exclamation-circle me-2"></i> Error: ' . $stmt->error . '</div>';
    }
}

// Fetch existing details form me dikhane ke liye (agar DB me already hain toh)
$comp_data = mysqli_query($conn, "SELECT * FROM po_issuer_companies LIMIT 1");
$comp = mysqli_fetch_assoc($comp_data);
?>

<?php include 'include/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    .wrapper-bg { background-color: #f4f7f9; min-height: calc(100vh - 70px); padding: 2rem 1rem; }
    .card-custom { border-radius: 12px; border: none; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04); background: #fff; max-width: 800px; margin: 0 auto; }
    .form-label { font-weight: 600; color: #475569; margin-bottom: 8px; font-size: 0.9rem; }
    .form-control { border-radius: 8px; padding: 10px 15px; border: 1px solid #cbd5e1; background-color: #f8fafc; }
    .form-control:focus { background-color: #ffffff; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
    .text-uppercase-input { text-transform: uppercase; }
    .text-uppercase-input::placeholder { text-transform: none; }
    
    /* Validation CSS */
    .invalid-feedback { font-size: 0.8rem; font-weight: 500; margin-top: 6px; }
    .was-validated .form-control:invalid { border-color: #ef4444; background-color: #fef2f2; }
    .was-validated .form-control:valid { border-color: #10b981; }
</style>

<div class="wrapper-bg">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4" style="max-width: 800px; margin: 0 auto;">
            <h3 class="fw-bold text-dark mb-0">Our Company Details (For PO)</h3>
        </div>
        
        <div style="max-width: 800px; margin: 0 auto;">
            <?= $message ?>
        </div>

        <div class="card card-custom">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="" class="needs-validation" novalidate id="companyForm">
                    <div class="row g-4">
                        
                        <div class="col-md-12">
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($comp['company_name'] ?? '') ?>" placeholder="Enter Full Company Name" required minlength="3">
                            <div class="invalid-feedback">Please enter a valid company name (minimum 3 characters).</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">GSTIN <span class="text-danger">*</span></label>
                            <input type="text" name="gstin" class="form-control text-uppercase-input" value="<?= htmlspecialchars($comp['gstin'] ?? '') ?>" placeholder="e.g. 09AAXCS1234J1ZU" required pattern="^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[Z]{1}[0-9A-Z]{1}$" maxlength="15">
                            <div class="invalid-feedback">Please enter a valid 15-character GSTIN.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">PAN Number <span class="text-danger">*</span></label>
                            <input type="text" name="pan" class="form-control text-uppercase-input" value="<?= htmlspecialchars($comp['pan'] ?? '') ?>" placeholder="e.g. AAXCS1234J" required pattern="^[A-Z]{5}[0-9]{4}[A-Z]{1}$" maxlength="10">
                            <div class="invalid-feedback">Please enter a valid 10-character PAN number.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($comp['email'] ?? '') ?>" placeholder="sales@example.com">
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($comp['phone'] ?? '') ?>" placeholder="+91 XXXXXXXXXX" pattern="^\+?[0-9\s\-]{10,15}$">
                            <div class="invalid-feedback">Please enter a valid phone number.</div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Full Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Enter complete registered address" required minlength="10"><?= htmlspecialchars($comp['address'] ?? '') ?></textarea>
                            <div class="invalid-feedback">Address is required (minimum 10 characters).</div>
                        </div>

                        <div class="col-md-12 text-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold" id="submitBtn" style="background-color: #2563eb;">
                                <i class="fas fa-save me-2"></i> Save Company Details
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // Auto convert PAN and GSTIN to uppercase
    const uppercaseFields = document.querySelectorAll('.text-uppercase-input');
    uppercaseFields.forEach(function(field) {
        field.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    });

    // Bootstrap Custom Validation
    const form = document.getElementById('companyForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Saving...';
            submitBtn.classList.add('disabled');
        }
        form.classList.add('was-validated');
    }, false);
});
</script>

<?php include 'include/footer.php'; ?>