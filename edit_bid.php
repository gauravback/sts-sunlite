<?php
// Session & Database
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config/database.php'; 

// ==============================================================
// 1. SECURITY CHECK (Sirf Admin allowed hai)
// ==============================================================
$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
if ($user_role !== 'admin') {
    echo "<script>alert('Access Denied! Only Administrators can edit bids.'); window.location.href='bid_report.php';</script>";
    exit();
}

// Check karo ki URL mein ID aayi hai ya nahi
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Invalid Bid ID!'); window.location.href='bid_report.php';</script>";
    exit();
}
$bid_id = $_GET['id'];

// ==============================================================
// 2. UPDATE LOGIC (Jab form submit ho)
// ==============================================================
if (isset($_POST['update_bid'])) {
    
    $bid_no = mysqli_real_escape_string($conn, trim($_POST['bid_no']));
    $submitted_by = mysqli_real_escape_string($conn, trim($_POST['submitted_by']));
    $managed_by = mysqli_real_escape_string($conn, trim($_POST['managed_by']));
    $sale_employee = mysqli_real_escape_string($conn, trim($_POST['sale_employee']));
    $dept_contact = mysqli_real_escape_string($conn, trim($_POST['dept_contact']));
    $location = mysqli_real_escape_string($conn, trim($_POST['location']));
    
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : NULL;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
    
    $dept_name = mysqli_real_escape_string($conn, trim($_POST['dept_name']));
    $org_name = mysqli_real_escape_string($conn, trim($_POST['org_name']));
    $internal_name = mysqli_real_escape_string($conn, trim($_POST['internal_name']));
    $item_category = mysqli_real_escape_string($conn, trim($_POST['item_category']));
    $bid_type = mysqli_real_escape_string($conn, trim($_POST['bid_type']));
    $bid_status = mysqli_real_escape_string($conn, trim($_POST['bid_status']));
    
    $emd_amount = !empty($_POST['emd_amount']) ? (float)$_POST['emd_amount'] : 0.00;
    $estimated_value = !empty($_POST['estimated_value']) ? (float)$_POST['estimated_value'] : 0.00;
    
    $ra_status = mysqli_real_escape_string($conn, $_POST['ra_status']);
    $pre_bid_status = mysqli_real_escape_string($conn, $_POST['pre_bid_status']);
    
    // Purani files ko nikalna
    $existing_files = mysqli_real_escape_string($conn, $_POST['existing_files']);

    // Nayi files Upload Logic
    $uploaded_file_names = [];
    if (!empty($existing_files)) {
        $uploaded_file_names = explode(',', $existing_files); // Purani files array me daalo
    }

    $upload_dir = 'uploads/bids/'; 
    if (isset($_FILES['bid_document']) && !empty(array_filter($_FILES['bid_document']['name']))) {
        $total_files = count($_FILES['bid_document']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            $original_filename = $_FILES['bid_document']['name'][$i];
            $tmp_name = $_FILES['bid_document']['tmp_name'][$i];
            $clean_filename = preg_replace('/[^a-zA-Z0-9.\-_]/', '', $original_filename);
            $new_filename = time() . "_" . $i . "_" . $clean_filename; 
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($tmp_name, $target_file)) {
                $uploaded_file_names[] = $new_filename; // Nayi file array me jodo
            }
        }
    }
    
    $files_string = implode(',', $uploaded_file_names);

    // UPDATE Query
    $query = "UPDATE bids SET 
                bid_no=?, submitted_by=?, managed_by=?, sale_employee=?, dept_contact=?, location=?, 
                start_date=?, end_date=?, dept_name=?, org_name=?, internal_name=?, item_category=?, 
                bid_type=?, bid_status=?, emd_amount=?, estimated_value=?, ra_status=?, pre_bid_status=?, 
                uploaded_files=? 
              WHERE id=?";

    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, "ssssssssssssssddsssi", 
            $bid_no, $submitted_by, $managed_by, $sale_employee, $dept_contact, $location, 
            $start_date, $end_date, $dept_name, $org_name, $internal_name, $item_category, 
            $bid_type, $bid_status, $emd_amount, $estimated_value, $ra_status, $pre_bid_status, 
            $files_string, $bid_id
        );

        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Bid successfully updated!'); window.location.href = 'view_bid.php?id=$bid_id';</script>";
            exit(); 
        } else {
            echo "<script>alert('Failed to update bid: " . mysqli_error($conn) . "');</script>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>alert('System error: " . mysqli_error($conn) . "');</script>";
    }
}

// ==============================================================
// 3. FETCH EXISTING DATA
// ==============================================================
$bid_query = "SELECT * FROM bids WHERE id = ?";
$stmt = mysqli_prepare($conn, $bid_query);
mysqli_stmt_bind_param($stmt, "i", $bid_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$bid = mysqli_fetch_assoc($result);

if (!$bid) {
    echo "<script>alert('Bid not found!'); window.location.href='bid_report.php';</script>";
    exit();
}
?>

<?php include 'include/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    body { background-color: #f8fafc; }
    .modern-card { border-radius: 16px; border: none; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); background: #ffffff; margin-bottom: 24px; }
    .card-header-modern { background-color: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 20px 24px; border-radius: 16px 16px 0 0; display: flex; align-items: center; justify-content: space-between; }
    .card-title-modern { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 10px; }
    
    .modern-label { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .modern-input { 
        border-radius: 10px; border: 1px solid #cbd5e1; padding: 12px 16px; background-color: #ffffff; font-size: 0.95rem; color: #1e293b; font-weight: 500; transition: all 0.3s;
    }
    .modern-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); outline: none; }
    
    .doc-pill { display: inline-flex; align-items: center; padding: 8px 14px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 8px; color: #475569; font-size: 0.85rem; margin: 5px 5px 5px 0; }
    
    .btn-modern { border-radius: 10px; padding: 10px 24px; font-weight: 600; transition: all 0.3s; }
    .btn-primary-modern { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none; color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
    .btn-primary-modern:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3); color: white; }
</style>

<div class="container-fluid mt-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">Edit Bid Details</h3>
            <p class="text-danger small mb-0"><i class="fas fa-exclamation-triangle"></i> You are modifying an existing record.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="view_bid.php?id=<?= $bid['id'] ?>" class="btn btn-light border fw-bold text-secondary px-4 py-2" style="border-radius: 10px;">Cancel</a>
        </div>
    </div>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="card modern-card">
            <div class="card-header-modern">
                <h4 class="card-title-modern"><div class="section-icon"><i class="fas fa-edit"></i></div> Update Bid Information</h4>
            </div>
            <div class="card-body p-4 p-md-5">
                <div class="row g-4">
                    
                    <div class="col-md-3">
                        <label class="modern-label">BID No. <span class="text-danger">*</span></label>
                        <input type="text" name="bid_no" class="form-control modern-input" value="<?= htmlspecialchars($bid['bid_no']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">Submitted By</label>
                        <input type="text" name="submitted_by" class="form-control modern-input" value="<?= htmlspecialchars($bid['submitted_by']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">Managed By (Manager)</label>
                        <input type="text" name="managed_by" class="form-control modern-input" value="<?= htmlspecialchars($bid['managed_by']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">Sale Employee</label>
                        <input type="text" name="sale_employee" class="form-control modern-input" value="<?= htmlspecialchars($bid['sale_employee']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label">Organisation Name</label>
                        <input type="text" name="org_name" class="form-control modern-input" value="<?= htmlspecialchars($bid['org_name']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">Department Name</label>
                        <input type="text" name="dept_name" class="form-control modern-input" value="<?= htmlspecialchars($bid['dept_name']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">Internal Name</label>
                        <input type="text" name="internal_name" class="form-control modern-input" value="<?= htmlspecialchars($bid['internal_name']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">Location</label>
                        <input type="text" name="location" class="form-control modern-input" value="<?= htmlspecialchars($bid['location']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label">Start Date & Time</label>
                        <input type="datetime-local" name="start_date" class="form-control modern-input" value="<?= !empty($bid['start_date']) ? date('Y-m-d\TH:i:s', strtotime($bid['start_date'])) : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">End Date & Time</label>
                        <input type="datetime-local" name="end_date" class="form-control modern-input" value="<?= !empty($bid['end_date']) ? date('Y-m-d\TH:i:s', strtotime($bid['end_date'])) : '' ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">Dept. Contact</label>
                        <input type="text" name="dept_contact" class="form-control modern-input" value="<?= htmlspecialchars($bid['dept_contact']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">Item Category</label>
                        <input type="text" name="item_category" class="form-control modern-input" value="<?= htmlspecialchars($bid['item_category']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label">Estimated Value (₹)</label>
                        <input type="number" step="0.01" name="estimated_value" class="form-control modern-input" value="<?= htmlspecialchars($bid['estimated_value']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">EMD Amount (₹)</label>
                        <input type="number" step="0.01" name="emd_amount" class="form-control modern-input" value="<?= htmlspecialchars($bid['emd_amount']) ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label">Bid Type</label>
                        <select name="bid_type" class="form-select modern-input">
                            <?php 
                            $types = ['Product', 'Bunch Product', 'Service', 'bunch Service', 'Custom bid', 'Bunch Custom Bid', 'BOQ Bid', 'Bunch BoQ bid'];
                            foreach($types as $t) {
                                $sel = (strtolower($bid['bid_type']) == strtolower($t)) ? 'selected' : '';
                                echo "<option value='{$t}' {$sel}>{$t}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="modern-label">Bid Status</label>
                        <select name="bid_status" class="form-select modern-input fw-bold text-primary">
                            <?php 
                            $statuses = ['Doc. Awaited', 'In Progress', 'Pre-Bid Meeting', 'Submitted', 'Technical Evaluation', 'Representation', 'Query Window', 'Reply Submitted', 'Technical Qualified', 'Technical Dis-Qualified', 'Financial Qualified', 'Financial Dis-Qualified', 'Order Received', 'lost', 'Bid-Cancel'];
                            foreach($statuses as $s) {
                                $sel = (strtolower($bid['bid_status']) == strtolower($s)) ? 'selected' : '';
                                echo "<option value='{$s}' {$sel}>{$s}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label">RA (Reverse Auction)</label>
                        <div class="btn-group btn-group-toggle d-flex" role="group">
                            <input type="radio" class="btn-check" name="ra_status" id="ra_yes" value="yes" <?= (strtolower($bid['ra_status']) == 'yes') ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="ra_yes">Yes</label>
                            <input type="radio" class="btn-check" name="ra_status" id="ra_no" value="no" <?= (strtolower($bid['ra_status']) == 'no' || empty($bid['ra_status'])) ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="ra_no">No</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="modern-label">Pre-Bid Meeting</label>
                        <div class="btn-group btn-group-toggle d-flex" role="group">
                            <input type="radio" class="btn-check" name="pre_bid_status" id="pre_bid_yes" value="yes" <?= (strtolower($bid['pre_bid_status']) == 'yes') ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="pre_bid_yes">Yes</label>
                            <input type="radio" class="btn-check" name="pre_bid_status" id="pre_bid_no" value="no" <?= (strtolower($bid['pre_bid_status']) == 'no' || empty($bid['pre_bid_status'])) ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="pre_bid_no">No</label>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="card modern-card mb-5">
            <div class="card-header-modern pb-3 border-bottom-0">
                <h4 class="card-title-modern text-primary"><i class="fas fa-paperclip"></i> Documents</h4>
            </div>
            <div class="card-body pt-0 px-4 pb-4">
                
                <label class="modern-label">Previously Attached Files:</label><br>
                <div class="mb-3 border p-3 rounded bg-light">
                    <input type="hidden" name="existing_files" value="<?= htmlspecialchars($bid['uploaded_files']) ?>">
                    
                    <?php
                    if (!empty($bid['uploaded_files'])) {
                        $files = explode(',', $bid['uploaded_files']);
                        foreach ($files as $file) {
                            $display_name = preg_replace('/^[0-9]+_\d+_/', '', trim($file));
                            echo "<span class='doc-pill'><i class='fas fa-file-alt me-2'></i> {$display_name}</span>";
                        }
                    } else {
                        echo "<span class='text-muted small'>No files attached previously.</span>";
                    }
                    ?>
                </div>

                <label class="modern-label mt-3">Attach New Documents (Optional)</label>
                <input type="file" name="bid_document[]" class="form-control modern-input" multiple>
                <small class="text-muted mt-1 d-block">Uploading new files will add them to the existing list.</small>

                <div class="mt-5 text-end pt-4 border-top">
                    <button type="submit" name="update_bid" class="btn btn-primary-modern btn-modern px-5">
                        <i class="fas fa-save me-2"></i> Update Bid Data
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>

<?php include 'include/footer.php'; ?>