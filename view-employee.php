<?php
// Session aur DB connection
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config/database.php'; 

// User Role nikalna taaki edit/delete button control kar sakein
$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'admin';

// Check karo ki URL mein ID aayi hai ya nahi
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Invalid Employee ID!'); window.location.href='employee_list.php';</script>";
    exit();
}
$emp_id = intval($_GET['id']);

// Fetch Employee Data
$sql = "SELECT * FROM employees WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $emp_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$emp = mysqli_fetch_assoc($result);

if (!$emp) {
    echo "<script>alert('Employee not found!'); window.location.href='employee_list.php';</script>";
    exit();
}

// Profile Picture ya Initials Logic
$photo_html = "";
if (!empty($emp['photograph']) && file_exists($emp['photograph'])) {
    $photo_html = "<img src='{$emp['photograph']}' alt='Profile' class='emp-avatar shadow-lg'>";
} else {
    $initial = strtoupper(substr(trim($emp['name']), 0, 1));
    $photo_html = "<div class='emp-initials shadow-lg'>{$initial}</div>";
}

// Status badge logic
$status = htmlspecialchars($emp['status'] ?: 'Active');
$badge_class = (strtolower($status) == 'active') ? 'status-active' : 'status-inactive';

?>

<?php include 'include/header.php'; ?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    :root {
        --primary: #4318ff;
        --primary-light: #ece8ff;
        --secondary: #2b3674;
        --text-muted: #a3aed0;
        --bg-body: #f4f7fe;
        --white: #ffffff;
        --glass-border: rgba(255, 255, 255, 0.4);
    }

    body { 
        background-color: var(--bg-body); 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        color: var(--secondary);
    }
    
    /* Animations */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-up { animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }

    /* Next-Gen Cover & Profile Header */
    .profile-cover {
        height: 180px;
        background: linear-gradient(135deg, #4318ff 0%, #8b5cf6 100%);
        border-radius: 24px;
        margin-bottom: -80px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(67, 24, 255, 0.15);
    }
    .profile-cover::after {
        content: ''; position: absolute; width: 100%; height: 100%;
        background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path opacity="0.1" d="M0 0h100v100H0z" fill="%23fff"/></svg>');
        background-size: cover; mix-blend-mode: overlay;
    }

    .profile-header-card { 
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-radius: 24px; 
        padding: 30px; 
        box-shadow: 0 10px 40px rgba(0,0,0,0.04); 
        border: 1px solid var(--glass-border); 
        position: relative; 
        z-index: 2;
        margin-left: 20px;
        margin-right: 20px;
        display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px;
    }
    
    .emp-avatar, .emp-initials { 
        width: 130px; height: 130px; 
        border-radius: 50%; 
        border: 6px solid var(--white); 
        margin-top: -65px;
        background-color: var(--white);
        object-fit: cover;
    }
    .emp-initials { 
        background: linear-gradient(135deg, #4318ff 0%, #2563eb 100%); 
        color: white; display: flex; align-items: center; justify-content: center; 
        font-weight: 800; font-size: 3rem; 
    }
    
    .emp-title { font-size: 2rem; font-weight: 800; color: var(--secondary); margin-bottom: 4px; letter-spacing: -0.5px;}
    .emp-subtitle { font-size: 1.05rem; color: var(--text-muted); font-weight: 500; display: flex; align-items: center; gap: 12px; }
    
    .status-badge { padding: 6px 16px; border-radius: 30px; font-weight: 700; font-size: 0.85rem; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px; }
    .status-active { background-color: #d1fae5; color: #047857; }
    .status-inactive { background-color: #fee2e2; color: #b91c1c; }

    /* Bento Box Detail Cards */
    .bento-card { 
        background: var(--white); 
        border-radius: 24px; 
        padding: 32px; 
        border: 1px solid rgba(226, 232, 240, 0.8); 
        box-shadow: 0 4px 20px rgba(0,0,0,0.02); 
        height: 100%; 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .bento-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.06);
        border-color: rgba(67, 24, 255, 0.1);
    }
    
    .card-title-modern { font-size: 1.25rem; font-weight: 800; color: var(--secondary); margin-bottom: 30px; display: flex; align-items: center; gap: 12px; }
    .card-icon { width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; border-radius: 14px; font-size: 1.2rem; }
    
    /* Clean Typography Data Groups */
    .data-group { margin-bottom: 1.5rem; transition: background 0.2s; border-radius: 12px; padding: 8px; margin-left: -8px;}
    .data-group:hover { background: #f8fafc; }
    .data-label { font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
    .data-value { font-size: 1.05rem; color: var(--secondary); font-weight: 600; word-wrap: break-word; }
    
    /* Document Vault Grid */
    .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
    .doc-item { 
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; 
        display: flex; align-items: center; justify-content: space-between; 
        transition: all 0.3s ease; position: relative; overflow: hidden;
    }
    .doc-item:hover { background: var(--white); border-color: var(--primary); box-shadow: 0 8px 20px rgba(67, 24, 255, 0.08); transform: scale(1.02); }
    
    .doc-info { display: flex; align-items: center; gap: 14px; z-index: 1; }
    .doc-icon-wrapper { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .doc-name { font-weight: 700; color: var(--secondary); font-size: 0.95rem; }
    
    .btn-view-file { 
        background: var(--primary-light); color: var(--primary); padding: 8px 16px; 
        border-radius: 10px; font-size: 0.85rem; font-weight: 700; text-decoration: none; 
        transition: all 0.2s; z-index: 1;
    }
    .btn-view-file:hover { background: var(--primary); color: white; }
    
    .btn-pending { 
        background: #fef2f2; color: #ef4444; padding: 8px 16px; border-radius: 10px; 
        font-size: 0.85rem; font-weight: 700; border: 1px dashed #fecaca; display: inline-block; cursor: not-allowed; 
    }

    /* Top Actions */
    .action-bar { position: relative; z-index: 10; margin-bottom: 20px; }
    .btn-glass { background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.5); font-weight: 700; color: var(--secondary); transition: all 0.3s;}
    .btn-glass:hover { background: var(--white); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.05);}
</style>

<div class="container-fluid mt-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center action-bar animate-fade-up">
        <a href="employee_list.php" class="btn btn-glass shadow-sm rounded-pill px-4 py-2">
            <i class="fas fa-arrow-left me-2"></i> Back to Directory
        </a>
        <div class="d-flex gap-2">
            <?php if($user_role == 'admin'): ?>
            <a href="edit-employee.php?id=<?= $emp_id ?>" class="btn btn-primary rounded-pill fw-bold px-4 py-2 shadow-sm" style="background: linear-gradient(135deg, #4318ff 0%, #2563eb 100%); border: none;">
                <i class="fas fa-pen me-2"></i> Edit Profile
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-cover animate-fade-up"></div>
    <div class="profile-header-card animate-fade-up">
        <div class="d-flex align-items-start gap-4 flex-wrap">
            <?= $photo_html ?>
            <div class="mt-2">
                <h1 class="emp-title"><?= htmlspecialchars($emp['name']) ?></h1>
                <p class="emp-subtitle">
                    <span class="d-flex align-items-center gap-2"><i class="fas fa-briefcase text-primary"></i> <?= htmlspecialchars($emp['designation'] ?: 'Designation N/A') ?></span>
                    <span class="text-muted">|</span> 
                    <span class="d-flex align-items-center gap-2"><i class="fas fa-building text-primary"></i> <?= htmlspecialchars($emp['department'] ?: 'Dept N/A') ?></span>
                </p>
            </div>
        </div>
        <div class="d-flex flex-column align-items-end gap-3">
            <span class="status-badge <?= $badge_class ?>"><i class="fas fa-circle" style="font-size: 8px;"></i> <?= $status ?></span>
            <span class="badge bg-light text-dark border px-4 py-2 rounded-pill shadow-sm" style="font-size: 0.9rem;">
                <i class="fas fa-fingerprint text-primary me-2"></i> EMP ID: <?= htmlspecialchars($emp['emp_id'] ?: 'N/A') ?>
            </span>
        </div>
    </div>

    <div class="row g-4 mt-2 mb-4">
        <div class="col-lg-6 animate-fade-up delay-1">
            <div class="bento-card">
                <h4 class="card-title-modern">
                    <div class="card-icon" style="color: #4318ff; background: #f4f7fe;"><i class="fas fa-user"></i></div> 
                    Personal Details
                </h4>
                <div class="row">
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-user-friends"></i> Guardian Name</div>
                        <div class="data-value"><?= htmlspecialchars($emp['guardian_name'] ?: '-') ?></div>
                    </div>
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-calendar-alt"></i> Date of Birth</div>
                        <div class="data-value"><?= !empty($emp['dob']) ? date('d M Y', strtotime($emp['dob'])) : '-' ?></div>
                    </div>
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-venus-mars"></i> Gender</div>
                        <div class="data-value"><?= htmlspecialchars($emp['gender'] ?: '-') ?></div>
                    </div>
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-ring"></i> Marital Status</div>
                        <div class="data-value"><?= htmlspecialchars($emp['marital_status'] ?: '-') ?></div>
                    </div>
                    <div class="col-sm-12 data-group mb-sm-0">
                        <div class="data-label"><i class="fas fa-flag"></i> Nationality</div>
                        <div class="data-value"><?= htmlspecialchars($emp['nationality'] ?: '-') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 animate-fade-up delay-1">
            <div class="bento-card">
                <h4 class="card-title-modern">
                    <div class="card-icon text-success bg-success bg-opacity-10"><i class="fas fa-briefcase"></i></div> 
                    Employment Details
                </h4>
                <div class="row">
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-id-card"></i> Employee ID</div>
                        <div class="data-value"><?= htmlspecialchars($emp['emp_id'] ?: '-') ?></div>
                    </div>
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-calendar-check"></i> Date of Joining</div>
                        <div class="data-value"><?= !empty($emp['joining_date']) ? date('d M Y', strtotime($emp['joining_date'])) : '-' ?></div>
                    </div>
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-map-pin"></i> Work Location</div>
                        <div class="data-value"><?= htmlspecialchars($emp['work_location'] ?: '-') ?></div>
                    </div>
                    <div class="col-sm-6 data-group mb-sm-0">
                        <div class="data-label"><i class="fas fa-user-tie"></i> Reporting Manager</div>
                        <div class="data-value"><?= htmlspecialchars($emp['reporting_manager'] ?: '-') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 animate-fade-up delay-2">
            <div class="bento-card">
                <h4 class="card-title-modern">
                    <div class="card-icon text-warning bg-warning bg-opacity-10"><i class="fas fa-address-book"></i></div> 
                    Contact Information
                </h4>
                <div class="row">
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-phone-alt"></i> Mobile Number</div>
                        <div class="data-value"><?= htmlspecialchars($emp['mobile_number'] ?: '-') ?></div>
                    </div>
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-phone"></i> Alternate Contact</div>
                        <div class="data-value"><?= htmlspecialchars($emp['alternate_contact'] ?: '-') ?></div>
                    </div>
                    <div class="col-sm-12 data-group">
                        <div class="data-label"><i class="fas fa-envelope"></i> Email Address</div>
                        <div class="data-value"><?= htmlspecialchars($emp['email'] ?: '-') ?></div>
                    </div>
                    <div class="col-sm-12 data-group">
                        <div class="data-label"><i class="fas fa-home"></i> Current Address</div>
                        <div class="data-value"><?= nl2br(htmlspecialchars($emp['current_address'] ?: '-')) ?></div>
                    </div>
                    <div class="col-sm-12 data-group mb-0">
                        <div class="data-label"><i class="fas fa-map-marked-alt"></i> Permanent Address</div>
                        <div class="data-value"><?= nl2br(htmlspecialchars($emp['permanent_address'] ?: '-')) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 animate-fade-up delay-2">
            <div class="bento-card">
                <h4 class="card-title-modern">
                    <div class="card-icon text-info bg-info bg-opacity-10"><i class="fas fa-shield-alt"></i></div> 
                    Bank & Compliance
                </h4>
                <div class="row">
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-university"></i> Bank Name</div>
                        <div class="data-value"><?= htmlspecialchars($emp['bank_name'] ?: '-') ?></div>
                    </div>
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-money-check"></i> Account Number</div>
                        <div class="data-value"><?= htmlspecialchars($emp['account_number'] ?: '-') ?></div>
                    </div>
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-code"></i> IFSC Code</div>
                        <div class="data-value"><?= htmlspecialchars($emp['ifsc_code'] ?: '-') ?></div>
                    </div>
                    <div class="col-sm-6 data-group">
                        <div class="data-label"><i class="fas fa-fingerprint"></i> Aadhaar Number</div>
                        <div class="data-value"><?= htmlspecialchars($emp['aadhaar_number'] ?: '-') ?></div>
                    </div>
                    <div class="col-sm-12 data-group mb-0">
                        <div class="data-label"><i class="fas fa-credit-card"></i> PAN Number</div>
                        <div class="data-value"><?= htmlspecialchars($emp['pan_number'] ?: '-') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bento-card mb-5 animate-fade-up delay-3">
        <h4 class="card-title-modern">
            <div class="card-icon text-danger bg-danger bg-opacity-10"><i class="fas fa-folder-open"></i></div> 
            Document Vault
        </h4>
        
        <div class="doc-grid">
            <?php
            $documents_list = [
                'doc_resume' => 'Resume / CV',
                'doc_offer_letter' => 'Offer Letter',
                'doc_appointment_letter' => 'Appointment Letter',
                'doc_pan_copy' => 'PAN Card Copy',
                'doc_aadhaar_copy' => 'Aadhaar Card Copy',
                'doc_10th_mark' => '10th Marksheet',
                'doc_12th_mark' => '12th Marksheet',
                'doc_ug_sem1' => 'UG 1st Sem Marksheet',
                'doc_ug_sem2' => 'UG 2nd Sem Marksheet',
                'doc_ug_sem3' => 'UG 3rd Sem Marksheet',
                'doc_ug_sem4' => 'UG 4th Sem Marksheet',
                'doc_ug_sem5' => 'UG 5th Sem Marksheet',
                'doc_ug_sem6' => 'UG 6th Sem Marksheet',
                'doc_ug_degree' => 'Graduation Degree',
                'doc_pg_sem1' => 'PG 1st Sem Marksheet',
                'doc_pg_sem2' => 'PG 2nd Sem Marksheet',
                'doc_pg_sem3' => 'PG 3rd Sem Marksheet',
                'doc_pg_sem4' => 'PG 4th Sem Marksheet',
                'doc_pg_degree' => 'Post-Graduation Degree',
                'doc_exp_cert' => 'Experience Certificates',
                'doc_relieving_letter' => 'Relieving Letter',
                'doc_salary_slip' => '3 Months Salary Slip',
                'doc_bank_stmt' => '3 Months Bank Statement',
                'doc_passport_photos' => 'Passport Photographs',
                'doc_cancelled_cheque' => 'Cancelled Cheque',
                'doc_bg_verification' => 'Background Verification',
                'doc_declaration' => 'Declaration'
            ];

            foreach ($documents_list as $db_column => $label) {
                $file_path = $emp[$db_column];
                
                // Smart Icon logic
                $icon_class = 'fa-file-pdf text-danger bg-danger'; 
                if (stripos($label, 'Photo') !== false) {
                    $icon_class = 'fa-image text-primary bg-primary';
                } elseif (stripos($label, 'Letter') !== false || stripos($label, 'Declaration') !== false) {
                    $icon_class = 'fa-file-alt text-success bg-success';
                }

                if (!empty($file_path) && file_exists($file_path)) {
                    // Uploaded State
                    echo "
                    <div class='doc-item'>
                        <div class='doc-info'>
                            <div class='doc-icon-wrapper bg-opacity-10 {$icon_class}'><i class='fas {$icon_class} bg-transparent'></i></div>
                            <div class='doc-name'>{$label}</div>
                        </div>
                        <a href='{$file_path}' target='_blank' class='btn-view-file'>View</a>
                    </div>";
                } else {
                    // Pending State
                    echo "
                    <div class='doc-item' style='background: #f8fafc; border: 1px dashed #cbd5e1;'>
                        <div class='doc-info' style='opacity: 0.5;'>
                            <div class='doc-icon-wrapper bg-secondary bg-opacity-10 text-secondary'><i class='fas fa-file-excel'></i></div>
                            <div class='doc-name text-muted'>{$label}</div>
                        </div>
                        <span class='btn-pending'>Missing</span>
                    </div>";
                }
            }
            ?>
        </div>
    </div>

</div>

<?php include 'include/footer.php'; ?>