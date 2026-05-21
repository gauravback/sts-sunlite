<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config/database.php';

$message = "";
$vendor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Agar form submit hua hai toh update karo
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $vendor_name = mysqli_real_escape_string($conn, $_POST['vendor_name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $gstin = strtoupper(mysqli_real_escape_string($conn, $_POST['gstin']));
    $pan = strtoupper(mysqli_real_escape_string($conn, $_POST['pan']));
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $update_query = "UPDATE vendors SET vendor_name=?, address=?, gstin=?, pan=?, email=?, phone=?, status=? WHERE id=?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssssssi", $vendor_name, $address, $gstin, $pan, $email, $phone, $status, $vendor_id);

    if ($stmt->execute()) {
        $message = '<div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4" role="alert" style="background-color: #ecfdf5; color: #065f46; border-left: 4px solid #10b981 !important;">
                        <i class="fas fa-check-circle me-2 fs-5 align-middle"></i> 
                        <strong>Success!</strong> Vendor updated successfully.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4" role="alert" style="background-color: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444 !important;">
                        <i class="fas fa-exclamation-circle me-2 fs-5 align-middle"></i> 
                        <strong>Error!</strong> ' . $stmt->error . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    }
    $stmt->close();
}

// Vendor ka purana data fetch karo
$fetch_query = "SELECT * FROM vendors WHERE id = $vendor_id";
$result = mysqli_query($conn, $fetch_query);

if(mysqli_num_rows($result) == 0) {
    die("<div class='container mt-5'><div class='alert alert-danger'>Vendor not found! <a href='view_vendors.php'>Go Back</a></div></div>");
}

$vendor = mysqli_fetch_assoc($result);
?>

<?php include 'include/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    .vendor-page-wrapper { background-color: #f4f7f9; min-height: calc(100vh - 70px); padding: 2rem 1rem; }
    .vendor-form-card { background: #ffffff; border-radius: 12px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04); border: 1px solid #e9ecef; max-width: 950px; margin: 0 auto; }
    .section-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 1.5rem; display: flex; align-items: center; text-transform: uppercase; letter-spacing: 0.5px; }
    .section-title i { background: #eff6ff; color: #3b82f6; padding: 8px 10px; border-radius: 8px; margin-right: 12px; font-size: 1.1rem; }
    .form-label { font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px; display: block; }
    
    .input-group-custom { position: relative; display: flex; align-items: center; }
    .input-group-custom i { position: absolute; left: 16px; color: #94a3b8; font-size: 1.05rem; transition: color 0.3s; pointer-events: none; }
    .form-control-custom, .form-select-custom { width: 100%; padding: 12px 16px 12px 45px; font-size: 0.95rem; color: #1e293b; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; transition: all 0.2s ease; box-shadow: none; outline: none; }
    .form-select-custom { padding-left: 45px; appearance: none; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right .75rem center; background-size: 16px 12px; }
    .form-control-custom:focus, .form-select-custom:focus { background-color: #ffffff; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
    .input-group-custom:focus-within i { color: #3b82f6; }
    
    .text-uppercase-input { text-transform: uppercase; }
    
    .was-validated .form-control-custom:invalid { border-color: #ef4444; background-color: #fef2f2; }
    .was-validated .form-control-custom:valid { border-color: #10b981; }
    .invalid-feedback-custom { display: none; color: #ef4444; font-size: 0.8rem; margin-top: 6px; font-weight: 500; }
    .was-validated .form-control-custom:invalid ~ .invalid-feedback-custom { display: block; }

    .btn-submit { background-color: #2563eb; color: white; border-radius: 8px; padding: 10px 28px; font-weight: 600; transition: all 0.2s; border: none; }
    .btn-submit:hover { background-color: #1d4ed8; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); color: white; transform: translateY(-1px); }
</style>

<div class="vendor-page-wrapper">
    <div class="container-fluid">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3" style="max-width: 950px; margin: 0 auto;">
            <div>
                <h3 class="fw-bold text-dark mb-1" style="letter-spacing: -0.5px;">Edit Vendor</h3>
                <p class="text-muted small mb-0">Update supplier information</p>
            </div>
            <div>
                <a href="view_vendors.php" class="btn btn-white border bg-white rounded-pill px-4 py-2 fw-semibold text-dark shadow-sm" style="font-size: 0.9rem;">
                    <i class="fas fa-arrow-left me-2"></i> Back to List
                </a>
            </div>
        </div>

        <div style="max-width: 950px; margin: 0 auto;">
            <?php echo $message; ?>
        </div>

        <div class="vendor-form-card">
            <div class="card-body p-4 p-md-5">
                <form method="POST" action="" novalidate id="vendorRegistrationForm">
                    
                    <h5 class="section-title"><i class="fas fa-building"></i> Company Details</h5>
                    <div class="row g-4 mb-5">
                        <div class="col-md-12">
                            <label class="form-label">Vendor / Company Name <span class="text-danger">*</span></label>
                            <div class="input-group-custom">
                                <i class="fas fa-briefcase"></i>
                                <input type="text" name="vendor_name" class="form-control-custom" value="<?= htmlspecialchars($vendor['vendor_name']) ?>" required minlength="3">
                                <div class="invalid-feedback-custom">Company name is required.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">GSTIN <span class="text-danger">*</span></label>
                            <div class="input-group-custom">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <input type="text" name="gstin" class="form-control-custom text-uppercase-input" value="<?= htmlspecialchars($vendor['gstin']) ?>" required pattern="^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}[Z]{1}[0-9A-Z]{1}$" maxlength="15">
                                <div class="invalid-feedback-custom">Enter a valid 15-character GSTIN.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">PAN Number <span class="text-danger">*</span></label>
                            <div class="input-group-custom">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="pan" class="form-control-custom text-uppercase-input" value="<?= htmlspecialchars($vendor['pan']) ?>" required pattern="^[A-Z]{5}[0-9]{4}[A-Z]{1}$" maxlength="10">
                                <div class="invalid-feedback-custom">Enter a valid 10-character PAN.</div>
                            </div>
                        </div>
                    </div>

                    <hr class="text-muted opacity-25 mb-5">

                    <h5 class="section-title"><i class="fas fa-address-book"></i> Contact Information & Status</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Contact Email</label>
                            <div class="input-group-custom">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" class="form-control-custom" value="<?= htmlspecialchars($vendor['email']) ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <div class="input-group-custom">
                                <i class="fas fa-phone"></i>
                                <input type="text" name="phone" class="form-control-custom" value="<?= htmlspecialchars($vendor['phone']) ?>" pattern="^\+?[0-9\s\-]{10,15}$">
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Full Registered Address <span class="text-danger">*</span></label>
                            <div class="input-group-custom" style="align-items: flex-start;">
                                <i class="fas fa-map-marker-alt" style="margin-top: 14px;"></i>
                                <textarea name="address" class="form-control-custom" rows="3" required><?= htmlspecialchars($vendor['address']) ?></textarea>
                            </div>
                        </div>

                        <div class="col-md-6 mt-4">
                            <label class="form-label">Vendor Status</label>
                            <div class="input-group-custom">
                                <i class="fas fa-toggle-on"></i>
                                <select name="status" class="form-select-custom">
                                    <option value="Active" <?= $vendor['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $vendor['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-top text-end">
                        <a href="view_vendors.php" class="btn btn-light border px-4 py-2 fw-semibold me-2">Cancel</a>
                        <button type="submit" class="btn btn-submit" id="submitBtn">
                            <i class="fas fa-save me-2"></i> Update Vendor
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const uppercaseFields = document.querySelectorAll('.text-uppercase-input');
    uppercaseFields.forEach(function(field) {
        field.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    });

    const form = document.getElementById('vendorRegistrationForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Updating...';
            submitBtn.classList.add('disabled');
        }
        form.classList.add('was-validated');
    }, false);
});
</script>

<?php include 'include/footer.php'; ?>