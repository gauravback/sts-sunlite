<?php
require_once 'config/database.php';
session_start();

// Time Zone Set to India
date_default_timezone_set('Asia/Kolkata');

// Security Fix: Secure the ID
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: customer-list.php");
    exit();
}

/* ================= UPDATE CUSTOMER ================= */
if(isset($_POST['update_customer'])){
    $company = mysqli_real_escape_string($conn, trim($_POST['company_name']));
    $name = mysqli_real_escape_string($conn, trim($_POST['customer_name']));
    $designation = mysqli_real_escape_string($conn, trim($_POST['designation']));
    $contact = mysqli_real_escape_string($conn, trim($_POST['contact_no']));
    $alternate_no = mysqli_real_escape_string($conn, trim($_POST['alternate_no']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    
    $gst = mysqli_real_escape_string($conn, strtoupper(trim($_POST['gstin'])));
    $pan = mysqli_real_escape_string($conn, strtoupper(trim($_POST['pan'])));
    $cin = mysqli_real_escape_string($conn, strtoupper(trim($_POST['cin'])));
    $aadhar = mysqli_real_escape_string($conn, trim($_POST['aadhar_number']));
    
    $source = mysqli_real_escape_string($conn, trim($_POST['customer_source']));
    $type = mysqli_real_escape_string($conn, trim($_POST['customer_type']));
    $status = mysqli_real_escape_string($conn, trim($_POST['current_status']));
    $priority = mysqli_real_escape_string($conn, trim($_POST['customer_priority']));
    
    $followed_by = mysqli_real_escape_string($conn, trim($_POST['followed_by']));
    $manager = mysqli_real_escape_string($conn, trim($_POST['reporting_manager']));
    $support = mysqli_real_escape_string($conn, trim($_POST['support_team']));

    $followup_date = trim($_POST['followup_date']);
    $activity_note = trim($_POST['activity_note']);

    // Update Customer Table
    $updateQuery = "UPDATE customers SET
                    company_name='$company',
                    customer_name='$name',
                    designation='$designation',
                    contact_no='$contact',
                    alternate_no='$alternate_no',
                    email='$email',
                    address='$address',
                    gstin='$gst',
                    pan='$pan',
                    cin='$cin',
                    aadhar_number='$aadhar',
                    customer_source='$source',
                    customer_type='$type',
                    status='$status',
                    customer_priority='$priority',
                    followed_by='$followed_by',
                    reporting_manager='$manager',
                    support_team='$support'
                    WHERE id=$id";

    if(mysqli_query($conn, $updateQuery)) {
        // Insert History Note if provided
        if(!empty($activity_note) || !empty($followup_date)){
            $user_id = $_SESSION['user_id'] ?? 0;
            $updated_by_user = $_SESSION['user_name'] ?? "Admin";
            $formatted_followup = !empty($followup_date) ? $followup_date : date('Y-m-d H:i:s');
            
            $final_note = "Updated By: $updated_by_user | Manager: ".($manager ?: 'Unassigned')." | Status: $status | Note: " . $activity_note;
            
            $history_stmt = $conn->prepare("INSERT INTO customer_history (customer_id, history_note, followup_time, user_id) VALUES (?, ?, ?, ?)");
            $history_stmt->bind_param("issi", $id, $final_note, $formatted_followup, $user_id);
            $history_stmt->execute();
            $history_stmt->close();
        }

        header("Location: edit-customer.php?id=$id&msg=updated");
        exit();
    } else {
        $error_msg = "Database Error: " . mysqli_error($conn);
    }
}

/* ================= GET CUSTOMER DATA ================= */
$sql = "SELECT * FROM customers WHERE id=$id";
$result = mysqli_query($conn, $sql);
if(mysqli_num_rows($result) == 0){
    header("Location: customer-list.php");
    exit();
}
$row = mysqli_fetch_assoc($result);
?>

<?php include "include/header.php"; ?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
    :root {
        --primary: #4338ca;
        --primary-hover: #3730a3;
        --bg-body: #f1f5f9;
        --bg-card: #ffffff;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
        --input-bg: #f8fafc;
        --focus-ring: rgba(67, 56, 202, 0.15);
        --error-color: #ef4444;
    }

    body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; color: var(--text-main); }
    .form-container { max-width: 1400px; margin: 0 auto; width: 100%; padding-bottom: 3rem; }
    
    .card-modern { 
        border: none; 
        border-radius: 16px; 
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); 
        background: var(--bg-card); 
        padding: 40px; 
    }
    
    .section-title { 
        font-size: 0.9rem; 
        font-weight: 700; 
        color: var(--primary); 
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
        border-bottom: 2px solid var(--border-color); 
        padding-bottom: 10px; 
        margin: 32px 0 24px; 
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title:first-of-type { margin-top: 0; }
    
    .form-label { font-weight: 600; font-size: 0.8rem; color: #334155; margin-bottom: 8px; display: block; }
    .text-danger { color: var(--error-color) !important; }
    
    .input-wrapper { position: relative; display: flex; align-items: center; }
    .input-wrapper i { position: absolute; left: 14px; color: #94a3b8; font-size: 1.15rem; pointer-events: none; transition: 0.3s; }
    
    .form-control-modern, .form-select-modern { 
        background-color: var(--input-bg); 
        border: 1px solid var(--border-color); 
        border-radius: 10px; 
        padding: 12px 16px 12px 42px; 
        font-size: 0.9rem; 
        font-weight: 500;
        color: var(--text-main); 
        width: 100%; 
        transition: all 0.25s ease; 
    }
    .form-control-modern::placeholder { color: #94a3b8; font-weight: 400; }
    .form-control-modern:disabled, .form-control-modern[readonly] { background-color: #e2e8f0; cursor: not-allowed; color: #64748b; }
    .form-select-modern { padding-left: 16px; appearance: form-select; } 
    
    .form-control-modern:focus, .form-select-modern:focus { 
        background-color: #fff;
        border-color: var(--primary); 
        box-shadow: 0 0 0 4px var(--focus-ring); 
        outline: none; 
    }
    .form-control-modern:focus + i, .form-control-modern:focus ~ i { color: var(--primary); }
    
    .btn-save { 
        background: linear-gradient(135deg, var(--primary) 0%, #4f46e5 100%); 
        border: none; 
        padding: 14px 32px; 
        border-radius: 10px; 
        font-weight: 600; 
        font-size: 0.95rem; 
        color: #fff; 
        transition: all 0.3s ease; 
        box-shadow: 0 4px 12px rgba(67, 56, 202, 0.25);
    }
    .btn-save:hover:not(:disabled) { 
        transform: translateY(-2px); 
        box-shadow: 0 6px 16px rgba(67, 56, 202, 0.35); 
        color: #fff;
    }
</style>

<div class="main-wrapper">
    <div class="content container-fluid mt-4">
        <div class="form-container">
            
            <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                <div>
                    <h3 class="fw-bold mb-1 text-dark">Edit Customer Profile</h3>
                    <p class="text-muted mb-0 ">Update the credentials and records for this customer.</p>
                </div>
                <div>
                    <a href="customer-details.php?id=<?= $id ?>" class="btn btn-outline-secondary rounded-3 border-0 bg-light px-4 fw-medium">Cancel / Back</a>
                </div>
            </div>

            <?php if(isset($error_msg)): ?>
                <div class="alert alert-danger"><?= $error_msg ?></div>
            <?php endif; ?>

            <div class="card card-modern">
                <form method="POST">

                    <div class="row g-4 mb-4">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <div class="input-wrapper">
                                <i class="ti ti-user"></i>
                                <input type="text" name="customer_name" class="form-control-modern" value="<?= htmlspecialchars($row['customer_name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Designation</label>
                            <div class="input-wrapper">
                                <i class="ti ti-badge"></i>
                                <input type="text" name="designation" class="form-control-modern" value="<?= htmlspecialchars($row['designation'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <div class="input-wrapper">
                                <i class="ti ti-phone"></i>
                                <input type="text" name="contact_no" class="form-control-modern" value="<?= htmlspecialchars($row['contact_no']); ?>" required>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Email Address</label>
                            <div class="input-wrapper">
                                <i class="ti ti-mail"></i>
                                <input type="email" name="email" class="form-control-modern" value="<?= htmlspecialchars($row['email']); ?>">
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Organisation Name <span class="text-danger">*</span></label>
                            <div class="input-wrapper">
                                <i class="ti ti-building"></i>
                                <input type="text" name="company_name" class="form-control-modern" value="<?= htmlspecialchars($row['company_name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Alternate No.</label>
                            <div class="input-wrapper">
                                <i class="ti ti-phone-plus"></i>
                                <input type="text" name="alternate_no" class="form-control-modern" value="<?= htmlspecialchars($row['alternate_no'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12">
                            <label class="form-label">Full Address</label>
                            <div class="input-wrapper">
                                <i class="ti ti-map-pin"></i>
                                <input type="text" name="address" class="form-control-modern" value="<?= htmlspecialchars($row['address']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="section-title"><i class="ti ti-file-certificate"></i> Legal & Compliance</div>
                    <div class="row g-4 mb-4">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">GST Number</label>
                            <div class="input-wrapper">
                                <i class="ti ti-receipt-tax"></i>
                                <input type="text" name="gstin" class="form-control-modern text-uppercase" value="<?= htmlspecialchars($row['gstin']); ?>" maxlength="15">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">PAN Number</label>
                            <div class="input-wrapper">
                                <i class="ti ti-id-badge"></i>
                                <input type="text" name="pan" class="form-control-modern text-uppercase" value="<?= htmlspecialchars($row['pan']); ?>" maxlength="10">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">CIN ID</label>
                            <div class="input-wrapper">
                                <i class="ti ti-building-bank"></i>
                                <input type="text" name="cin" class="form-control-modern text-uppercase" value="<?= htmlspecialchars($row['cin']); ?>">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Aadhar Number</label>
                            <div class="input-wrapper">
                                <i class="ti ti-fingerprint"></i>
                                <input type="text" name="aadhar_number" class="form-control-modern" value="<?= htmlspecialchars($row['aadhar_number'] ?? ''); ?>" maxlength="12">
                            </div>
                        </div>
                    </div>

                    <div class="section-title"><i class="ti ti-chart-pie"></i> Customer Attributes</div>
                    <div class="row g-4 mb-4">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Customer Source</label>
                            <select name="customer_source" class="form-select-modern">
                                <option value="">Select Category</option>
                                <?php 
                                $sources = ["Corporate", "Government", "Dealer", "End User", "Education", "Retailor", "Online", "Other"];
                                foreach($sources as $s){
                                    $selected = ($row['customer_source'] == $s) ? "selected" : "";
                                    echo "<option value='$s' $selected>$s</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Customer Type</label>
                            <select name="customer_type" class="form-select-modern">
                                <?php 
                                $types = ["New Customer", "Existing Customer", "Partner"];
                                foreach($types as $t){
                                    $selected = ($row['customer_type'] == $t) ? "selected" : "";
                                    echo "<option value='$t' $selected>$t</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Current Status</label>
                            <select name="current_status" class="form-select-modern">
                                <?php 
                                $status_options = ['Calling', 'Follow-up', 'Meeting', 'Work in Progress', 'Payment', 'Sale', 'Mature', 'Closed'];
                                foreach($status_options as $opt){
                                    $selected = ($row['status'] == $opt) ? "selected" : "";
                                    echo "<option value='$opt' $selected>$opt</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Customer Priority</label>
                            <select name="customer_priority" class="form-select-modern">
                                <option value="Hot" <?= ($row['customer_priority'] == 'Hot') ? 'selected' : '' ?>>Hot</option>
                                <option value="Normal" <?= ($row['customer_priority'] == 'Normal') ? 'selected' : '' ?>>Normal</option>
                                <option value="Cold" <?= ($row['customer_priority'] == 'Cold') ? 'selected' : '' ?>>Cold</option>
                            </select>
                        </div>
                    </div>

                    <div class="section-title"><i class="ti ti-calendar-stats"></i> Management & Schedule</div>
                    <div class="row g-4 mb-4">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Followed By</label>
                            <select name="followed_by" class="form-select-modern">
                                <option value="">Select User</option>
                                <?php
                                $user_res = mysqli_query($conn, "SELECT * FROM users");
                                if($user_res && mysqli_num_rows($user_res) > 0) {
                                    while($u = mysqli_fetch_assoc($user_res)) {
                                        $uname = $u['username'] ?? $u['name'] ?? $u['email'] ?? 'User ID '.$u['id'];
                                        $selected = ($row['followed_by'] == $uname) ? 'selected' : '';
                                        echo "<option value='".htmlspecialchars($uname)."' $selected>".htmlspecialchars($uname)."</option>";
                                    }
                                } else {
                                    echo "<option value='Admin'>Admin</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Reporting Manager</label>
                            <div class="input-wrapper">
                                <i class="ti ti-tie"></i>
                                <input type="text" name="reporting_manager" class="form-control-modern" value="<?= htmlspecialchars($row['reporting_manager'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Created By <span class="text-danger">*</span></label>
                            <div class="input-wrapper">
                                <i class="ti ti-user-plus"></i>
                                <input type="text" class="form-control-modern" value="<?= htmlspecialchars($row['created_by'] ?? 'Admin') ?>" readonly style="background-color: #e2e8f0; color: #64748b;">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Support Team</label>
                            <select name="support_team" class="form-select-modern">
                                <option value="">Select Team</option>
                                <option value="Tech Support" <?= ($row['support_team'] == 'Tech Support') ? 'selected' : '' ?>>Tech Support</option>
                                <option value="Sales Support" <?= ($row['support_team'] == 'Sales Support') ? 'selected' : '' ?>>Sales Support</option>
                            </select>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Customer Created Date</label>
                            <div class="input-wrapper">
                                <i class="ti ti-calendar-event"></i>
                                <input type="text" class="form-control-modern" value="<?= date('d-m-Y H:i', strtotime($row['created_at'])) ?>" readonly style="background-color: #e2e8f0; color: #64748b;">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Next Followup Date</label>
                            <div class="input-wrapper">
                                <i class="ti ti-calendar-time"></i>
                                <input type="datetime-local" name="followup_date" class="form-control-modern" style="padding-left: 42px;">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-12">
                            <label class="form-label">Activity Note / Update Remarks</label>
                            <textarea name="activity_note" class="form-control-modern" rows="3" placeholder="Enter reason for edit, or latest conversation details..." style="padding-left:16px; border-radius: 12px;"></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end border-top pt-4">
                        <button type="submit" name="update_customer" class="btn btn-save">
                            <i class="ti ti-device-floppy me-2"></i> Save & Update Customer
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('msg') === 'updated') {
        Swal.fire({
            icon: 'success',
            title: 'Updated Successfully',
            text: 'Profile records have been synchronized.',
            timer: 2500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?id=<?= $id ?>";
        window.history.replaceState({path:newUrl}, '', newUrl);
    }
</script>

<?php include "include/footer.php"; ?>