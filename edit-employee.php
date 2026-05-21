<?php
// Session aur DB connection
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config/database.php'; 

// Current Logged-in User
$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin');

// Get Employee ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) {
    echo "<script>alert('Invalid Employee ID!'); window.location.href='employee_list.php';</script>";
    exit();
}

// Fetch Existing Employee Data
$fetch_sql = "SELECT * FROM employees WHERE id = $id";
$fetch_result = mysqli_query($conn, $fetch_sql);
$emp = mysqli_fetch_assoc($fetch_result);

if (!$emp) {
    echo "<script>alert('Employee not found!'); window.location.href='employee_list.php';</script>";
    exit();
}

if (isset($_POST['update_employee'])) {
    
    // --- BACKEND VALIDATIONS ---
    $errors = [];

    // 1. PERSONAL DETAILS
    $name = trim($_POST['name']);
    if (empty($name) || !preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $errors[] = "Valid Full Name is required (letters and spaces only).";
    }
    $name = mysqli_real_escape_string($conn, $name);
    
    $guardian_name = mysqli_real_escape_string($conn, trim($_POST['guardian_name']));
    
    $dob = !empty($_POST['dob']) ? $_POST['dob'] : NULL;
    if ($dob && strtotime($dob) > time()) {
        $errors[] = "Date of Birth cannot be in the future.";
    }

    $nationality = mysqli_real_escape_string($conn, trim($_POST['nationality']));
    $marital_status = mysqli_real_escape_string($conn, trim($_POST['marital_status']));
    $gender = mysqli_real_escape_string($conn, trim($_POST['gender']));

    // 2. CONTACT DETAILS
    $current_address = mysqli_real_escape_string($conn, trim($_POST['current_address']));
    $permanent_address = mysqli_real_escape_string($conn, trim($_POST['permanent_address']));
    
    $mobile_number = trim($_POST['mobile_number']);
    if (empty($mobile_number) || !preg_match('/^[0-9]{10}$/', $mobile_number)) {
        $errors[] = "Valid 10-digit Mobile Number is required.";
    }
    $mobile_number = mysqli_real_escape_string($conn, $mobile_number);

    $alternate_contact = trim($_POST['alternate_contact']);
    if (!empty($alternate_contact) && !preg_match('/^[0-9]{10}$/', $alternate_contact)) {
         $errors[] = "Alternate Contact must be exactly 10 digits.";
    }
    $alternate_contact = mysqli_real_escape_string($conn, $alternate_contact);

    $email = trim($_POST['email']);
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid Email Address format.";
    }
    $email = mysqli_real_escape_string($conn, $email);

    // 3. JOB DETAILS
    $emp_id_val = trim($_POST['emp_id']);
    if (empty($emp_id_val)) {
        $errors[] = "Employee ID is required.";
    }
    $emp_id_val = mysqli_real_escape_string($conn, $emp_id_val);
    
    $designation = mysqli_real_escape_string($conn, trim($_POST['designation']));
    $department = mysqli_real_escape_string($conn, trim($_POST['department']));
    $joining_date = !empty($_POST['joining_date']) ? $_POST['joining_date'] : NULL;
    $work_location = mysqli_real_escape_string($conn, trim($_POST['work_location']));
    $reporting_manager = mysqli_real_escape_string($conn, trim($_POST['reporting_manager']));

    // 4. BANK DETAILS
    $bank_name = mysqli_real_escape_string($conn, trim($_POST['bank_name']));
    $account_number = mysqli_real_escape_string($conn, trim($_POST['account_number']));
    
    $aadhaar_number = trim($_POST['aadhaar_number']);
    if (!empty($aadhaar_number) && !preg_match('/^\d{12}$/', $aadhaar_number)) {
        $errors[] = "Aadhaar Number must be exactly 12 digits.";
    }
    $aadhaar_number = mysqli_real_escape_string($conn, $aadhaar_number);

    $pan_number = strtoupper(trim($_POST['pan_number']));
    if (!empty($pan_number) && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $pan_number)) {
        $errors[] = "Invalid PAN Number format (e.g., ABCDE1234F).";
    }
    $pan_number = mysqli_real_escape_string($conn, $pan_number);

    $ifsc_code = strtoupper(trim($_POST['ifsc_code']));
    if (!empty($ifsc_code) && !preg_match('/^[A-Z]{4}0[A-Z0-9]{6}$/', $ifsc_code)) {
         $errors[] = "Invalid IFSC Code format.";
    }
    $ifsc_code = mysqli_real_escape_string($conn, $ifsc_code);

    // STOP EXECUTION IF ERRORS EXIST
    if (!empty($errors)) {
        $error_string = implode("\\n", $errors);
        echo "<script>alert('Please fix the following errors:\\n$error_string'); history.back();</script>";
        exit();
    }

    // ==========================================
    // 5. FILE UPLOAD LOGIC (Photo + 27 Docs)
    // ==========================================
    $upload_dir = 'uploads/employees/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

    $file_fields = [
        'photograph', 'doc_resume', 'doc_offer_letter', 'doc_appointment_letter', 
        'doc_pan_copy', 'doc_aadhaar_copy', 'doc_10th_mark', 'doc_12th_mark', 
        'doc_ug_sem1', 'doc_ug_sem2', 'doc_ug_sem3', 'doc_ug_sem4', 'doc_ug_sem5', 
        'doc_ug_sem6', 'doc_ug_degree', 'doc_pg_sem1', 'doc_pg_sem2', 'doc_pg_sem3', 
        'doc_pg_sem4', 'doc_pg_degree', 'doc_exp_cert', 'doc_relieving_letter', 
        'doc_salary_slip', 'doc_bank_stmt', 'doc_passport_photos', 'doc_cancelled_cheque', 
        'doc_bg_verification', 'doc_declaration'
    ];

    $uploaded_paths = [];
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $max_file_size = 5 * 1024 * 1024; // 5 MB

    foreach ($file_fields as $field) {
        // By default, keep the existing file path
        $uploaded_paths[$field] = mysqli_real_escape_string($conn, $emp[$field]); 

        if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
            $file_ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            $file_size = $_FILES[$field]['size'];

            if (in_array($file_ext, $allowed_extensions) && $file_size <= $max_file_size) {
                 $new_filename = time() . '_' . rand(1000, 9999) . '_' . $field . '.' . $file_ext;
                 $target_file = $upload_dir . $new_filename;
                 if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_file)) {
                     // Update path to new file
                     $uploaded_paths[$field] = mysqli_real_escape_string($conn, $target_file);
                     
                     // Optional: Delete old file to save space
                     // if(!empty($emp[$field]) && file_exists($emp[$field])){ unlink($emp[$field]); }
                 }
            }
        }
    }

    // ==========================================
    // UPDATE DATABASE
    // ==========================================
    $sql = "UPDATE employees SET 
        name = '$name', 
        guardian_name = '$guardian_name', 
        dob = " . ($dob ? "'$dob'" : "NULL") . ", 
        nationality = '$nationality', 
        marital_status = '$marital_status', 
        gender = '$gender', 
        photograph = '{$uploaded_paths['photograph']}',
        current_address = '$current_address', 
        permanent_address = '$permanent_address', 
        mobile_number = '$mobile_number', 
        alternate_contact = '$alternate_contact', 
        email = '$email',
        emp_id = '$emp_id_val', 
        designation = '$designation', 
        department = '$department', 
        joining_date = " . ($joining_date ? "'$joining_date'" : "NULL") . ", 
        work_location = '$work_location', 
        reporting_manager = '$reporting_manager',
        bank_name = '$bank_name', 
        account_number = '$account_number', 
        aadhaar_number = '$aadhaar_number', 
        pan_number = '$pan_number', 
        ifsc_code = '$ifsc_code',
        doc_resume = '{$uploaded_paths['doc_resume']}', 
        doc_offer_letter = '{$uploaded_paths['doc_offer_letter']}', 
        doc_appointment_letter = '{$uploaded_paths['doc_appointment_letter']}', 
        doc_pan_copy = '{$uploaded_paths['doc_pan_copy']}', 
        doc_aadhaar_copy = '{$uploaded_paths['doc_aadhaar_copy']}',
        doc_10th_mark = '{$uploaded_paths['doc_10th_mark']}', 
        doc_12th_mark = '{$uploaded_paths['doc_12th_mark']}', 
        doc_ug_sem1 = '{$uploaded_paths['doc_ug_sem1']}', 
        doc_ug_sem2 = '{$uploaded_paths['doc_ug_sem2']}', 
        doc_ug_sem3 = '{$uploaded_paths['doc_ug_sem3']}', 
        doc_ug_sem4 = '{$uploaded_paths['doc_ug_sem4']}',
        doc_ug_sem5 = '{$uploaded_paths['doc_ug_sem5']}', 
        doc_ug_sem6 = '{$uploaded_paths['doc_ug_sem6']}', 
        doc_ug_degree = '{$uploaded_paths['doc_ug_degree']}', 
        doc_pg_sem1 = '{$uploaded_paths['doc_pg_sem1']}', 
        doc_pg_sem2 = '{$uploaded_paths['doc_pg_sem2']}', 
        doc_pg_sem3 = '{$uploaded_paths['doc_pg_sem3']}',
        doc_pg_sem4 = '{$uploaded_paths['doc_pg_sem4']}', 
        doc_pg_degree = '{$uploaded_paths['doc_pg_degree']}', 
        doc_exp_cert = '{$uploaded_paths['doc_exp_cert']}', 
        doc_relieving_letter = '{$uploaded_paths['doc_relieving_letter']}', 
        doc_salary_slip = '{$uploaded_paths['doc_salary_slip']}',
        doc_bank_stmt = '{$uploaded_paths['doc_bank_stmt']}', 
        doc_passport_photos = '{$uploaded_paths['doc_passport_photos']}', 
        doc_cancelled_cheque = '{$uploaded_paths['doc_cancelled_cheque']}', 
        doc_bg_verification = '{$uploaded_paths['doc_bg_verification']}', 
        doc_declaration = '{$uploaded_paths['doc_declaration']}'
    WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Employee details updated successfully!'); window.location.href='employee_list.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<?php include 'include/header.php'; ?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    .modern-card { border-radius: 16px; border: none; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04); background: #ffffff; margin-bottom: 24px; }
    .card-header-modern { background-color: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 20px 24px; border-radius: 16px 16px 0 0; }
    
    .section-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9; }
    .section-icon { background: #eff6ff; color: #3b82f6; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
    
    .modern-label { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .modern-input { border-radius: 10px; border: 1px solid #cbd5e1; padding: 10px 14px; background-color: #f8fafc; font-size: 0.9rem; color: #334155; transition: all 0.3s ease; width: 100%; }
    .modern-input:focus { background-color: #ffffff; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
    
    .input-error { border-color: #ef4444 !important; background-color: #fef2f2 !important; box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important; }
    .input-success { border-color: #10b981 !important; background-color: #f0fdf4 !important; }
    .error-msg { font-size: 0.75rem; color: #ef4444; font-weight: 500; margin-top: 4px; display: none; }

    .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
    .doc-item { background: #f8fafc; border: 1px dashed #cbd5e1; padding: 12px; border-radius: 10px; text-align: left; transition: all 0.2s; position: relative;}
    .doc-item:hover { border-color: #3b82f6; background: #eff6ff; }
    .doc-item label { font-size: 0.8rem; font-weight: 600; color: #1e293b; display: block; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;}
    .doc-item input[type="file"] { font-size: 0.75rem; width: 100%; }
    .doc-item input[type=file]::file-selector-button { border: none; background: #e2e8f0; padding: 4px 8px; border-radius: 6px; color: #334155; cursor: pointer; transition: background .2s; margin-right: 10px; font-size: 0.75rem;}
    .doc-item input[type=file]::file-selector-button:hover { background: #cbd5e1; }
    .doc-status { font-size: 0.75rem; margin-top: 5px; display: flex; justify-content: space-between;}
</style>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">Edit Employee Profile</h3>
            <p class="text-muted small mb-0">Update details and documents for <?php echo htmlspecialchars($emp['name']); ?>.</p>
        </div>
        <a href="employee_list.php" class="btn btn-light border shadow-sm rounded-3 fw-bold text-secondary px-4"><i class="fas fa-arrow-left me-2"></i> Back</a>
    </div>

    <form action="" method="POST" id="employeeForm" enctype="multipart/form-data">
        <div class="card modern-card">
            <div class="card-body p-4 p-md-5">

                <h4 class="section-title"><div class="section-icon"><i class="fas fa-user"></i></div> 1. Personal Details</h4>
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <label class="modern-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="emp_name" class="form-control modern-input" value="<?php echo htmlspecialchars($emp['name']); ?>" required>
                        <div class="error-msg" id="err_name">Please enter a valid name (letters only).</div>
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">Father’s/Husband’s Name</label>
                        <input type="text" name="guardian_name" class="form-control modern-input" value="<?php echo htmlspecialchars($emp['guardian_name']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="dob" id="dob" class="form-control modern-input" value="<?php echo $emp['dob']; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">Nationality</label>
                        <input type="text" name="nationality" class="form-control modern-input" value="<?php echo htmlspecialchars($emp['nationality']); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">Marital Status</label>
                        <select name="marital_status" class="form-select modern-input">
                            <option value="">Select Status</option>
                            <option value="Single" <?php echo ($emp['marital_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                            <option value="Married" <?php echo ($emp['marital_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                            <option value="Other" <?php echo ($emp['marital_status'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">Gender</label>
                        <select name="gender" class="form-select modern-input">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo ($emp['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($emp['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($emp['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label text-primary">Passport Photograph</label>
                        <input type="file" name="photograph" class="form-control modern-input bg-white" accept="image/*">
                        <?php if(!empty($emp['photograph'])): ?>
                            <div class="mt-1"><a href="<?php echo $emp['photograph']; ?>" target="_blank" class="text-decoration-none small"><i class="fas fa-image"></i> View Current Photo</a></div>
                        <?php endif; ?>
                    </div>
                </div>

                <h4 class="section-title"><div class="section-icon text-warning bg-warning bg-opacity-10"><i class="fas fa-map-marker-alt"></i></div> 2. Contact Details</h4>
                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <label class="modern-label">Current Address</label>
                        <textarea name="current_address" id="current_address" class="form-control modern-input" rows="2"><?php echo htmlspecialchars($emp['current_address']); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="modern-label d-flex justify-content-between">
                            Permanent Address 
                            <span><input type="checkbox" id="same_address" class="form-check-input ms-2 me-1"> <small class="text-lowercase" style="font-weight: 500;">Same as Current</small></span>
                        </label>
                        <textarea name="permanent_address" id="permanent_address" class="form-control modern-input" rows="2"><?php echo htmlspecialchars($emp['permanent_address']); ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">Mobile Number <span class="text-danger">*</span></label>
                        <input type="tel" name="mobile_number" id="mobile_number" class="form-control modern-input" maxlength="10" value="<?php echo htmlspecialchars($emp['mobile_number']); ?>" required>
                        <div class="error-msg" id="err_mobile">Must be exactly 10 digits.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">Alternate Contact</label>
                        <input type="tel" name="alternate_contact" id="alternate_contact" class="form-control modern-input" maxlength="10" value="<?php echo htmlspecialchars($emp['alternate_contact']); ?>">
                        <div class="error-msg" id="err_alt_mobile">Must be exactly 10 digits.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">Email Address</label>
                        <input type="email" name="email" id="emp_email" class="form-control modern-input" value="<?php echo htmlspecialchars($emp['email']); ?>">
                        <div class="error-msg" id="err_email">Enter a valid email address.</div>
                    </div>
                </div>

                <h4 class="section-title"><div class="section-icon text-success bg-success bg-opacity-10"><i class="fas fa-briefcase"></i></div> 3. Job Details</h4>
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <label class="modern-label">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" name="emp_id" class="form-control modern-input" value="<?php echo htmlspecialchars($emp['emp_id']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">Designation</label>
                        <input type="text" name="designation" class="form-control modern-input" value="<?php echo htmlspecialchars($emp['designation']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">Department</label>
                        <input type="text" name="department" class="form-control modern-input" value="<?php echo htmlspecialchars($emp['department']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">Date of Joining</label>
                        <input type="date" name="joining_date" class="form-control modern-input" value="<?php echo $emp['joining_date']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">Work Location</label>
                        <input type="text" name="work_location" class="form-control modern-input" value="<?php echo htmlspecialchars($emp['work_location']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">Reporting Manager</label>
                        <input type="text" name="reporting_manager" class="form-control modern-input" value="<?php echo htmlspecialchars($emp['reporting_manager']); ?>">
                    </div>
                </div>

                <h4 class="section-title"><div class="section-icon text-info bg-info bg-opacity-10"><i class="fas fa-university"></i></div> 4. Bank Details</h4>
                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <label class="modern-label">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control modern-input" value="<?php echo htmlspecialchars($emp['bank_name']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">Account Number</label>
                        <input type="text" name="account_number" class="form-control modern-input" value="<?php echo htmlspecialchars($emp['account_number']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">IFSC Code</label>
                        <input type="text" name="ifsc_code" id="ifsc_code" class="form-control modern-input text-uppercase" maxlength="11" value="<?php echo htmlspecialchars($emp['ifsc_code']); ?>">
                        <div class="error-msg" id="err_ifsc">Invalid IFSC format (e.g. SBIN0001234).</div>
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">Aadhaar Number</label>
                        <input type="text" name="aadhaar_number" id="aadhaar_number" class="form-control modern-input" maxlength="12" value="<?php echo htmlspecialchars($emp['aadhaar_number']); ?>">
                        <div class="error-msg" id="err_aadhaar">Must be exactly 12 digits.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="modern-label">PAN Number</label>
                        <input type="text" name="pan_number" id="pan_number" class="form-control modern-input text-uppercase" maxlength="10" value="<?php echo htmlspecialchars($emp['pan_number']); ?>">
                        <div class="error-msg" id="err_pan">Invalid PAN format (e.g. ABCDE1234F).</div>
                    </div>
                </div>

                <h4 class="section-title"><div class="section-icon text-danger bg-danger bg-opacity-10"><i class="fas fa-file-upload"></i></div> 5. Documents Required (Upload)</h4>
                <p class="text-muted small mb-4"><i class="fas fa-info-circle"></i> Uploading a new file will replace the existing one. Leave empty to keep the current file.</p>
                
                <div class="doc-grid">
                    <?php
                    $documents_list = [
                        'doc_resume' => '1. Resume',
                        'doc_offer_letter' => '2. Offer Letter',
                        'doc_appointment_letter' => '3. Appointment Letter',
                        'doc_pan_copy' => '4. PAN Card Copy',
                        'doc_aadhaar_copy' => '5. Aadhaar Card Copy',
                        'doc_10th_mark' => '6. 10th Marksheet',
                        'doc_12th_mark' => '7. 12th Marksheet',
                        'doc_ug_sem1' => '8. UG 1st Sem Marksheet',
                        'doc_ug_sem2' => '9. UG 2nd Sem Marksheet',
                        'doc_ug_sem3' => '10. UG 3rd Sem Marksheet',
                        'doc_ug_sem4' => '11. UG 4th Sem Marksheet',
                        'doc_ug_sem5' => '12. UG 5th Sem Marksheet',
                        'doc_ug_sem6' => '13. UG 6th Sem Marksheet',
                        'doc_ug_degree' => '14. Graduation Degree',
                        'doc_pg_sem1' => '15. PG 1st Sem Marksheet',
                        'doc_pg_sem2' => '16. PG 2nd Sem Marksheet',
                        'doc_pg_sem3' => '17. PG 3rd Sem Marksheet',
                        'doc_pg_sem4' => '18. PG 4th Sem Marksheet',
                        'doc_pg_degree' => '19. Post-Graduation Degree',
                        'doc_exp_cert' => '20. Experience Certificates',
                        'doc_relieving_letter' => '21. Relieving Letter',
                        'doc_salary_slip' => '22. 3 Months Salary Slip',
                        'doc_bank_stmt' => '23. 3 Months Bank Statement',
                        'doc_passport_photos' => '24. Passport Photographs',
                        'doc_cancelled_cheque' => '25. Cancelled Cheque / Passbook',
                        'doc_bg_verification' => '26. Background Verification',
                        'doc_declaration' => '27. Declaration (If required)'
                    ];

                    foreach ($documents_list as $input_name => $label) {
                        $existing_file = $emp[$input_name];
                        $status_html = !empty($existing_file) ? "<a href='{$existing_file}' target='_blank' class='text-primary text-decoration-none'><i class='fas fa-eye'></i> View File</a>" : "<span class='text-muted'>(No file)</span>";

                        echo "
                        <div class='doc-item'>
                            <label title='{$label}'>{$label}</label>
                            <input type='file' name='{$input_name}' class='form-control' accept='.pdf,.jpg,.jpeg,.png'>
                            <div class='doc-status'>{$status_html}</div>
                        </div>";
                    }
                    ?>
                </div>

                <div class="mt-5 text-end border-top pt-4">
                    <button type="submit" name="update_employee" class="btn btn-primary rounded-3 px-5 fw-bold shadow-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none;">
                        <i class="fas fa-save me-2"></i> Update Employee Profile
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
    // Max Date for DOB (Cannot be in future)
    var today = new Date().toISOString().split('T')[0];
    document.getElementById("dob").setAttribute('max', today);

    // ==========================================
    // REAL-TIME VALIDATION LOGIC
    // ==========================================
    function checkValidation(elementId, regex, errorId, allowEmpty = false) {
        const input = document.getElementById(elementId);
        const errNode = document.getElementById(errorId);
        
        input.addEventListener('input', function(e) {
            let val = e.target.value.trim();
            
            // Auto uppercase for PAN and IFSC
            if(elementId === 'pan_number' || elementId === 'ifsc_code') {
                val = val.toUpperCase();
                e.target.value = val;
            }

            if (val === '') {
                if(allowEmpty) {
                    input.classList.remove('input-error', 'input-success');
                    errNode.style.display = 'none';
                } else {
                    input.classList.add('input-error');
                    input.classList.remove('input-success');
                    errNode.style.display = 'block';
                }
                return;
            }

            if (regex.test(val)) {
                input.classList.remove('input-error');
                input.classList.add('input-success');
                errNode.style.display = 'none';
            } else {
                input.classList.add('input-error');
                input.classList.remove('input-success');
                errNode.style.display = 'block';
            }
        });
    }

    // Attach validations
    checkValidation('emp_name', /^[a-zA-Z\s]+$/, 'err_name', false);
    checkValidation('mobile_number', /^\d{10}$/, 'err_mobile', false);
    checkValidation('alternate_contact', /^\d{10}$/, 'err_alt_mobile', true);
    checkValidation('emp_email', /^[^\s@]+@[^\s@]+\.[^\s@]+$/, 'err_email', true);
    checkValidation('aadhaar_number', /^\d{12}$/, 'err_aadhaar', true);
    checkValidation('pan_number', /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/, 'err_pan', true);
    checkValidation('ifsc_code', /^[A-Z]{4}0[A-Z0-9]{6}$/, 'err_ifsc', true);

    // Copy Current Address to Permanent Address
    document.getElementById('same_address').addEventListener('change', function() {
        var currentAddress = document.getElementById('current_address').value;
        var permanentAddress = document.getElementById('permanent_address');
        
        if (this.checked) {
            permanentAddress.value = currentAddress;
            permanentAddress.readOnly = true;
            permanentAddress.classList.add('bg-light');
        } else {
            permanentAddress.value = '';
            permanentAddress.readOnly = false;
            permanentAddress.classList.remove('bg-light');
        }
    });
    
    document.getElementById('current_address').addEventListener('input', function() {
        if (document.getElementById('same_address').checked) {
            document.getElementById('permanent_address').value = this.value;
        }
    });
</script>

<?php include 'include/footer.php'; ?>