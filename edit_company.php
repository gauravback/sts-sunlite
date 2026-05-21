<?php
// ERROR REPORTING ON (Error check karne ke liye)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config/database.php';

// Check if ID is passed in URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>alert('Error: Company ID missing!'); window.location.href='create_quotation.php';</script>";
    exit();
}

$company_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch existing company data
$query = mysqli_query($conn, "SELECT * FROM issuer_companies WHERE id = '$company_id'");
if (mysqli_num_rows($query) == 0) {
    echo "<script>alert('Error: Company not found!'); window.location.href='create_quotation.php';</script>";
    exit();
}
$company = mysqli_fetch_assoc($query);


// Handle Form Submission for Update
if (isset($_POST['update_issuer_company'])) {
    $iss_name = trim(mysqli_real_escape_string($conn, $_POST['iss_name']));
    
    if (empty($iss_name)) {
        echo "<script>alert('Error: Company Name is required.'); window.history.back();</script>";
        exit();
    }

    $iss_address = mysqli_real_escape_string($conn, $_POST['iss_address']);
    $iss_phone = mysqli_real_escape_string($conn, $_POST['iss_phone']);
    $iss_gstin = mysqli_real_escape_string($conn, $_POST['iss_gstin']);
    $iss_pan = mysqli_real_escape_string($conn, $_POST['iss_pan']);
    
    // Bank Details
    $acc_holder = mysqli_real_escape_string($conn, $_POST['iss_acc_holder']);
    $acc_num = mysqli_real_escape_string($conn, $_POST['iss_acc_num']);
    $bank_name = mysqli_real_escape_string($conn, $_POST['iss_bank_name']);
    $branch = mysqli_real_escape_string($conn, $_POST['iss_branch']);
    $ifsc = mysqli_real_escape_string($conn, $_POST['iss_ifsc']);
    $upi = mysqli_real_escape_string($conn, $_POST['iss_upi']);

    // Upload Directory
    $target_dir = "uploads/company_files/";
    if (!is_dir($target_dir)) { @mkdir($target_dir, 0777, true); }

    // Keep old filenames by default
    $signature_filename = $company['signature_image'];
    $header_filename = $company['header_image'];
    $footer_filename = $company['footer_image'];

    // 1. Update Signature Image (If new one is uploaded)
    if (isset($_FILES['signature_image']) && $_FILES['signature_image']['error'] == 0) {
        $file_extension = pathinfo($_FILES["signature_image"]["name"], PATHINFO_EXTENSION);
        $new_signature = "sign_" . time() . "_" . rand(1000, 9999) . "." . $file_extension;
        if (@move_uploaded_file($_FILES["signature_image"]["tmp_name"], $target_dir . $new_signature)) {
            $signature_filename = $new_signature; 
        }
    }

    // 2. Update Header Image (If new one is uploaded)
    if (isset($_FILES['header_image']) && $_FILES['header_image']['error'] == 0) {
        $file_extension = pathinfo($_FILES["header_image"]["name"], PATHINFO_EXTENSION);
        $new_header = "header_" . time() . "_" . rand(1000, 9999) . "." . $file_extension;
        if (@move_uploaded_file($_FILES["header_image"]["tmp_name"], $target_dir . $new_header)) {
            $header_filename = $new_header; 
        }
    }

    // 3. Update Footer Image (If new one is uploaded)
    if (isset($_FILES['footer_image']) && $_FILES['footer_image']['error'] == 0) {
        $file_extension = pathinfo($_FILES["footer_image"]["name"], PATHINFO_EXTENSION);
        $new_footer = "footer_" . time() . "_" . rand(1000, 9999) . "." . $file_extension;
        if (@move_uploaded_file($_FILES["footer_image"]["tmp_name"], $target_dir . $new_footer)) {
            $footer_filename = $new_footer; 
        }
    }

    // Update Query
    $update_iss = "UPDATE issuer_companies SET 
                    company_name = '$iss_name', 
                    address = '$iss_address', 
                    phone = '$iss_phone', 
                    gstin = '$iss_gstin', 
                    pan = '$iss_pan', 
                    account_holder = '$acc_holder', 
                    account_number = '$acc_num', 
                    bank_name = '$bank_name', 
                    branch = '$branch', 
                    ifsc_code = '$ifsc', 
                    upi_id = '$upi', 
                    signature_image = '$signature_filename', 
                    header_image = '$header_filename', 
                    footer_image = '$footer_filename'
                   WHERE id = '$company_id'";
    
    try {
        mysqli_query($conn, $update_iss);
        // Success ke baad waapis quotation page pe bhej do
        echo "<script>alert('Company Profile Updated Successfully!'); window.location.href='create_quotation.php';</script>";
        exit();
    } catch (Exception $e) {
        die("<div style='background: #fee2e2; color: #991b1b; padding: 20px; border-radius: 10px; margin: 20px;'>
                <h2>🚨 Database Error</h2>
                <p><b>Error:</b> " . $e->getMessage() . "</p>
             </div>");
    }
}
?>

<?php include 'include/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    body { background-color: #f4f7fe; font-family: 'Inter', sans-serif; }
    .modern-card { border-radius: 16px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: #fff; }
    .modern-label { font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px; display: block; }
    .form-control { border-radius: 10px; border: 1px solid #e2e8f0; padding: 12px 16px; font-size: 0.95rem; background-color: #f8fafc; }
    .form-control:focus { background-color: #fff; border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
    .btn-primary { background-color: #4f46e5; border-color: #4f46e5; border-radius: 8px; font-weight: 600;}
</style>

<div class="container mt-4 mb-5 max-w-4xl">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark mb-0"><i class="fas fa-edit me-2 text-primary"></i> Edit Company Profile</h3>
        <a href="create_quotation.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold shadow-sm">
            <i class="fas fa-arrow-left me-2"></i> Back to Quotation
        </a>
    </div>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="card modern-card p-4 mb-4">
            <h5 class="text-primary fw-bold border-bottom pb-3 mb-4">Basic Information</h5>
            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <label class="modern-label">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="iss_name" class="form-control" value="<?= htmlspecialchars($company['company_name']) ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="modern-label">Phone No. <span class="text-danger">*</span></label>
                    <input type="text" name="iss_phone" class="form-control" value="<?= htmlspecialchars($company['phone']) ?>" required maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
                <div class="col-12">
                    <label class="modern-label">Address <span class="text-danger">*</span></label>
                    <textarea name="iss_address" class="form-control" rows="2" required><?= htmlspecialchars($company['address']) ?></textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="modern-label">GSTIN <span class="text-danger">*</span></label>
                    <input type="text" name="iss_gstin" class="form-control" value="<?= htmlspecialchars($company['gstin']) ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="modern-label">PAN Number <span class="text-danger">*</span></label>
                    <input type="text" name="iss_pan" class="form-control" value="<?= htmlspecialchars($company['pan']) ?>" required>
                </div>
            </div>
        </div>

        <div class="card modern-card p-4 mb-4">
            <h5 class="text-success fw-bold border-bottom pb-3 mb-4">Bank Details</h5>
            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <label class="modern-label">Account Holder <span class="text-danger">*</span></label>
                    <input type="text" name="iss_acc_holder" class="form-control" value="<?= htmlspecialchars($company['account_holder']) ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="modern-label">Account Number <span class="text-danger">*</span></label>
                    <input type="text" name="iss_acc_num" class="form-control" value="<?= htmlspecialchars($company['account_number']) ?>" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
                <div class="col-12 col-md-6">
                    <label class="modern-label">Bank Name <span class="text-danger">*</span></label>
                    <input type="text" name="iss_bank_name" class="form-control" value="<?= htmlspecialchars($company['bank_name']) ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="modern-label">Branch <span class="text-danger">*</span></label>
                    <input type="text" name="iss_branch" class="form-control" value="<?= htmlspecialchars($company['branch']) ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="modern-label">IFSC Code <span class="text-danger">*</span></label>
                    <input type="text" name="iss_ifsc" class="form-control" value="<?= htmlspecialchars($company['ifsc_code']) ?>" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="modern-label">UPI ID <span class="text-danger">*</span></label>
                    <input type="text" name="iss_upi" class="form-control" value="<?= htmlspecialchars($company['upi_id']) ?>" required>
                </div>
            </div>
        </div>
        
        <div class="card modern-card p-4 mb-4 bg-light border">
            <h5 class="text-info fw-bold border-bottom pb-3 mb-4"><i class="fas fa-file-image me-2"></i> Update Letterhead & Images</h5>
            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <label class="modern-label">Header Image <small class="text-muted">(Leave empty to keep current)</small></label>
                    <input type="file" name="header_image" class="form-control bg-white" accept="image/png, image/jpeg, image/jpg">
                    <?php if(!empty($company['header_image'])): ?>
                        <small class="text-success mt-1 d-block"><i class="fas fa-check-circle"></i> File uploaded: <?= $company['header_image'] ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="modern-label">Footer Image <small class="text-muted">(Leave empty to keep current)</small></label>
                    <input type="file" name="footer_image" class="form-control bg-white" accept="image/png, image/jpeg, image/jpg">
                    <?php if(!empty($company['footer_image'])): ?>
                        <small class="text-success mt-1 d-block"><i class="fas fa-check-circle"></i> File uploaded: <?= $company['footer_image'] ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-12 mt-4">
                    <label class="modern-label">Authorized Signature / Stamp <small class="text-muted">(Leave empty to keep current)</small></label>
                    <input type="file" name="signature_image" class="form-control bg-white" accept="image/png, image/jpeg, image/jpg">
                    <?php if(!empty($company['signature_image'])): ?>
                        <small class="text-success mt-1 d-block"><i class="fas fa-check-circle"></i> File uploaded: <?= $company['signature_image'] ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" name="update_issuer_company" class="btn btn-primary btn-lg px-5 py-3 shadow-sm rounded-pill">
                <i class="fas fa-save me-2"></i> Update Profile
            </button>
        </div>
    </form>
</div>

<?php include 'include/footer.php'; ?>