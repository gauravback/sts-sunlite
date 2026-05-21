<?php
// 1. BACKEND LOGIC
// Notice aur warnings ko hide kar do taki HTML break na ho
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

if (session_status() === PHP_SESSION_NONE) {
    @session_start(); // @ lagane se "already active" wala error print nahi hoga
}

include 'config/database.php'; 
$logged_in_user = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';
$logged_in_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// =========================================================================
// FETCH ALL USERS FOR DROPDOWNS (For Quick Add Modal)
// =========================================================================
$users_query = "SELECT id, name FROM users ORDER BY name ASC";
$users_result = mysqli_query($conn, $users_query);
$all_users = [];
if ($users_result) {
    while ($row = mysqli_fetch_assoc($users_result)) {
        $all_users[] = $row;
    }
}

// =========================================================================
// AJAX LIVE SEARCH LOGIC (Fetching address, mapping company_name)
// =========================================================================
if (isset($_GET['ajax_search'])) {
    $search = mysqli_real_escape_string($conn, trim($_GET['ajax_search']));
    
    $query = "SELECT id, company_name, reporting_manager, followed_by, created_by, customer_name, contact_no, address 
              FROM customers 
              WHERE company_name LIKE '%$search%' OR customer_name LIKE '%$search%' 
              LIMIT 10";
    
    $result = mysqli_query($conn, $query);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    echo json_encode($data);
    exit(); 
}

// =========================================================================
// AJAX ADD NEW CUSTOMER LOGIC
// =========================================================================
if (isset($_POST['ajax_add_customer'])) {
    $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name']));
    $customer_name = mysqli_real_escape_string($conn, trim($_POST['customer_name']));
    $contact_no = mysqli_real_escape_string($conn, trim($_POST['contact_no']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $reporting_manager = mysqli_real_escape_string($conn, trim($_POST['reporting_manager']));
    $followed_by = mysqli_real_escape_string($conn, trim($_POST['followed_by']));

    // BACKEND VALIDATION FOR CUSTOMER
    if(strlen($company_name) > 150 || strlen($customer_name) > 100) {
        echo json_encode(["status" => "error", "message" => "Name is too long!"]); exit();
    }
    if(!preg_match('/^[0-9]{10,15}$/', $contact_no)) {
        echo json_encode(["status" => "error", "message" => "Invalid phone number format!"]); exit();
    }

    $query = "INSERT INTO customers (company_name, customer_name, contact_no, email, reporting_manager, followed_by, created_by) 
              VALUES ('$company_name', '$customer_name', '$contact_no', '$email', '$reporting_manager', '$followed_by', '$logged_in_user')";
    
    if (mysqli_query($conn, $query)) {
        $new_cust_id = mysqli_insert_id($conn);
        echo json_encode([
            "status" => "success", 
            "customer_id" => $new_cust_id,
            "company_name" => $company_name, 
            "customer_name" => $customer_name,
            "contact_no" => $contact_no,
            "reporting_manager" => $reporting_manager, 
            "followed_by" => $followed_by,
            "created_by" => $logged_in_user
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
    }
    exit();
}

// =========================================================================
// FORM SUBMISSION LOGIC (ADD BID)
// =========================================================================
if (isset($_POST['submit_bid'])) {
    $customer_id = !empty($_POST['customer_id']) ? intval($_POST['customer_id']) : 0; 
    
    // BACKEND CHECK: Prevent saving if customer ID is not set
    if ($customer_id === 0) {
        die("<script>alert('Error: Please select a valid Organisation from the search list or add a new one first!'); history.back();</script>");
    }
    
    $bid_no = mysqli_real_escape_string($conn, trim($_POST['bid_no']));
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : NULL;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
    $org_name = mysqli_real_escape_string($conn, trim($_POST['org_name']));
    
    $contact_person = trim($_POST['contact_person'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    if ($contact_person && $contact_phone) {
        $dept_contact_combined = $contact_person . ' (' . $contact_phone . ')';
    } elseif ($contact_person) {
        $dept_contact_combined = $contact_person;
    } elseif ($contact_phone) {
        $dept_contact_combined = $contact_phone;
    } else {
        $dept_contact_combined = '';
    }

    $dept_contact = mysqli_real_escape_string($conn, $dept_contact_combined);
    $dept_name = mysqli_real_escape_string($conn, trim($_POST['dept_name'] ?? ''));
    $location = mysqli_real_escape_string($conn, trim($_POST['location'] ?? '')); 
    
    $managed_by = mysqli_real_escape_string($conn, trim($_POST['managed_by'] ?? ''));
    $sale_employee = mysqli_real_escape_string($conn, trim($_POST['sale_employee'] ?? ''));
    $submitted_by = mysqli_real_escape_string($conn, trim($_POST['submitted_by'] ?? ''));
    $internal_name = mysqli_real_escape_string($conn, trim($_POST['internal_name'] ?? ''));
    
    $item_category = mysqli_real_escape_string($conn, trim($_POST['item_category']));
    $bid_type = mysqli_real_escape_string($conn, trim($_POST['bid_type']));
    $bid_status = mysqli_real_escape_string($conn, trim($_POST['bid_status']));
    
    // EMD LOGIC
    $emd_status = $_POST['emd_status'] ?? 'no';
    $emd_amount = ($emd_status === 'yes' && !empty($_POST['emd_amount'])) ? (float)$_POST['emd_amount'] : 0.00;
    
    // Convert estimated value to float robustly
    $estimated_value = !empty($_POST['estimated_value']) ? (float)str_replace(',', '', $_POST['estimated_value']) : 0.00;
    $ra_status = mysqli_real_escape_string($conn, $_POST['ra_status'] ?? 'no');
    
    $pre_bid_status = mysqli_real_escape_string($conn, $_POST['pre_bid_status'] ?? 'no');
    $pre_bid_date = ($pre_bid_status === 'yes' && !empty($_POST['pre_bid_date'])) ? $_POST['pre_bid_date'] : NULL;
    $pre_bid_address = ($pre_bid_status === 'yes') ? mysqli_real_escape_string($conn, trim($_POST['pre_bid_address'] ?? '')) : '';
    
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks'] ?? ''));

    // ==========================================
    // BACKEND VALIDATIONS
    // ==========================================
    
    // 1. Restrict special characters in Bid No.
    if (!preg_match('/^[a-zA-Z0-9\-\/]+$/', $bid_no)) {
        die("<script>alert('Error: Bid No. contains invalid special characters! Only alphanumeric, hyphens, and slashes are allowed.'); history.back();</script>");
    }

    if(strlen($bid_no) > 50 || strlen($submitted_by) > 100 || strlen($dept_name) > 150) {
        die("<script>alert('Error: One or more text fields exceeded maximum allowed length!'); history.back();</script>");
    }
    
    if(!empty($contact_phone) && !preg_match('/^[0-9]{10,15}$/', $contact_phone)) {
        die("<script>alert('Error: Contact Phone must contain only digits (10-15 chars)!'); history.back();</script>");
    }

    if($emd_amount < 0 || $estimated_value < 0) {
        die("<script>alert('Error: Amount/Value cannot be negative!'); history.back();</script>");
    }
    
    $uploaded_file_names = [];
    $upload_dir = 'uploads/bids/'; 
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

    if (isset($_FILES['bid_document']) && !empty(array_filter($_FILES['bid_document']['name']))) {
        $total_files = count($_FILES['bid_document']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            $original_filename = $_FILES['bid_document']['name'][$i];
            $tmp_name = $_FILES['bid_document']['tmp_name'][$i];
            
            $clean_filename = preg_replace('/[^a-zA-Z0-9.\-_]/', '', $original_filename);
            if(strtolower($clean_filename) == 'image.png') {
                $clean_filename = 'Pasted_Image_' . rand(1000, 9999) . '.png';
            }

            $new_filename = time() . "_" . $i . "_" . $clean_filename; 
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($tmp_name, $target_file)) { 
                $uploaded_file_names[] = $new_filename; 
            }
        }
    }
    $files_string = implode(',', $uploaded_file_names);

    $query = "INSERT INTO bids (bid_no, submitted_by, managed_by, sale_employee, dept_contact, location, start_date, end_date, dept_name, org_name, internal_name, item_category, bid_type, bid_status, emd_amount, estimated_value, ra_status, pre_bid_status, pre_bid_date, pre_bid_address, uploaded_files) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, "ssssssssssssssddsssss", $bid_no, $submitted_by, $managed_by, $sale_employee, $dept_contact, $location, $start_date, $end_date, $dept_name, $org_name, $internal_name, $item_category, $bid_type, $bid_status, $emd_amount, $estimated_value, $ra_status, $pre_bid_status, $pre_bid_date, $pre_bid_address, $files_string);
        
        if (mysqli_stmt_execute($stmt)) {
            $last_bid_id = mysqli_insert_id($conn);
            
            // 1. Remark
            if (!empty($remarks)) {
                $remark_query = "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES (?, ?, ?)";
                $remark_stmt = mysqli_prepare($conn, $remark_query);
                mysqli_stmt_bind_param($remark_stmt, "iss", $last_bid_id, $remarks, $logged_in_user);
                mysqli_stmt_execute($remark_stmt);
            }

            // 2. Customer History
            if ($customer_id > 0) {
                $history_note = "📌 <b>New Bid Created:</b> " . htmlspecialchars($bid_no) . " | <b>Type:</b> " . htmlspecialchars($bid_type) . " | <b>Status:</b> " . htmlspecialchars($bid_status);
                $history_note .= "<br><b>Initial Note:</b> " . ($remarks ? htmlspecialchars($remarks) : "No remarks provided.");
                
                if (!empty($uploaded_file_names)) {
                    $history_note .= "<br><b>Attachments:</b> ";
                    $doc_links = [];
                    foreach ($uploaded_file_names as $file) {
                        $file_url = 'uploads/bids/' . $file;
                        $display_name = preg_replace('/^[0-9]+_\d+_/', '', $file);
                        $doc_links[] = "<a href='$file_url' target='_blank' style='color: #10b981; text-decoration: none;'><i class='fas fa-paperclip'></i> $display_name</a>";
                    }
                    $history_note .= implode(' | ', $doc_links);
                }
                
                $history_note .= "<br>🔗 <a href='view_bid.php?id=$last_bid_id' target='_blank' style='color: #3b82f6; font-weight: 600; text-decoration: none;'>View Complete Bid Details</a>";

                $null_followup = NULL; 
                
                $hist_query = "INSERT INTO customer_history (customer_id, history_note, followup_time, user_id) VALUES (?, ?, ?, ?)";
                if ($h_stmt = mysqli_prepare($conn, $hist_query)) {
                    mysqli_stmt_bind_param($h_stmt, "issi", $customer_id, $history_note, $null_followup, $logged_in_user_id);
                    mysqli_stmt_execute($h_stmt);
                    mysqli_stmt_close($h_stmt);
                }
            }

            echo "<script>alert('Bid successfully saved!'); window.location.href = 'bid_report.php';</script>";
            exit(); 
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
        }
    }
}
?>

<?php include 'include/header.php'; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    body { background-color: #f4f7fa; }
    .modern-card { border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04); background: #ffffff; }
    .section-title { font-size: 1.15rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
    .section-icon { background: #eff6ff; color: #3b82f6; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .modern-label { font-size: 0.8rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
    .modern-input { border-radius: 10px; border: 1px solid #e2e8f0; padding: 12px 16px; background-color: #f8fafc; font-size: 0.95rem; color: #334155; transition: all 0.3s ease; }
    .modern-input:focus { background-color: #ffffff; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); outline: none; }
    .file-upload-box { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 24px; background: #f8fafc; transition: all 0.3s; text-align: center; }
    .file-upload-box.dragover { border-color: #3b82f6; background: #eff6ff; }
    .btn-modern { border-radius: 10px; padding: 10px 24px; font-weight: 600; transition: all 0.3s; }
    .btn-primary-modern { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none; color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
    .pre-bid-extra { display: none; } 
    
    /* Cross icon styling */
    .remove-file-btn { cursor: pointer; color: #ef4444; margin-left: auto; transition: 0.2s; }
    .remove-file-btn:hover { color: #b91c1c; transform: scale(1.1); }

    /* Readonly style enforcement */
    .readonly-field { pointer-events: none; background-color: #e9ecef !important; color: #495057; border-color: #ced4da; }
</style>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">Add New Bid</h3>
            <p class="text-muted small mb-0">Fill in the details below to create a new bid entry.</p>
        </div>
        <a href="bid_report.php" class="btn btn-light btn-modern border"><i class="fas fa-arrow-left me-2"></i> Back to Report</a>
    </div>

    <div class="card modern-card">
        <div class="card-body p-4 p-md-5">
            <form id="bidForm" action="" method="POST" enctype="multipart/form-data">
                
                <input type="hidden" name="customer_id" id="form_customer_id" value="">

                <div class="section-title"><div class="section-icon"><i class="fas fa-info-circle"></i></div> 1. Basic Details</div>

                <div class="row g-4 mb-5">
                    
                    <div class="col-md-3 position-relative">
                        <label class="modern-label text-primary"> Organisation Name <span class="text-danger">*</span></label>
                        <div class="input-group shadow-sm" style="border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0;">
                            <input type="text" name="org_name" id="form_org_name" class="form-control border-0 py-2 bg-white" placeholder="Search Company..." maxlength="150" autocomplete="off" required>
                            <button class="btn btn-light border-0 text-success px-3" type="button" data-bs-toggle="modal" data-bs-target="#addCustomerModal" title="Add Customer if not found" style="background-color: #f8fafc;">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div id="search_results" class="position-absolute w-100 bg-white border rounded shadow-lg mt-1" style="display:none; z-index: 1050; max-height: 250px; overflow-y: auto;"></div>
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> BID No. <span class="text-danger">*</span></label>
                        <input type="text" name="bid_no" class="form-control modern-input" placeholder="e.g. BID-001" maxlength="50" pattern="[a-zA-Z0-9\-\/]+" title="Only alphanumeric, hyphens, and slashes allowed" oninput="this.value = this.value.replace(/[^a-zA-Z0-9\-\/]/g, '');" required>
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> Start Date <span class="text-danger">*</span></label>
                        <input type="text" id="start_date" name="start_date" class="form-control modern-input bg-white" placeholder="DD/MM/YYYY HH:MM" required>
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> End Date <span class="text-danger">*</span></label>
                        <input type="text" id="end_date" name="end_date" class="form-control modern-input bg-white" placeholder="DD/MM/YYYY HH:MM" required>
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> Department Name </label>
                        <input type="text" name="dept_name" id="form_dept_name" class="form-control modern-input" placeholder="Enter Dept" maxlength="150">
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> Contact Person Name </label>
                        <input type="text" name="contact_person" id="form_contact_person" class="form-control modern-input" placeholder="e.g. Amit Kumar" maxlength="50">
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> Contact Phone No. </label>
                        <input type="text" name="contact_phone" id="form_contact_phone" class="form-control modern-input" placeholder="e.g. 9876543210" maxlength="15" pattern="[0-9]{10,15}" title="Only digits, min 10 max 15 characters." oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> Location (State) <span class="text-danger">*</span></label>
                        <select name="location" id="form_location" class="form-select modern-input" required>
                            <option value="">-- Select State --</option>
                            <option value="Andhra Pradesh">Andhra Pradesh</option>
                            <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                            <option value="Assam">Assam</option>
                            <option value="Bihar">Bihar</option>
                            <option value="Chhattisgarh">Chhattisgarh</option>
                            <option value="Goa">Goa</option>
                            <option value="Gujarat">Gujarat</option>
                            <option value="Haryana">Haryana</option>
                            <option value="Himachal Pradesh">Himachal Pradesh</option>
                            <option value="Jharkhand">Jharkhand</option>
                            <option value="Karnataka">Karnataka</option>
                            <option value="Kerala">Kerala</option>
                            <option value="Madhya Pradesh">Madhya Pradesh</option>
                            <option value="Maharashtra">Maharashtra</option>
                            <option value="Manipur">Manipur</option>
                            <option value="Meghalaya">Meghalaya</option>
                            <option value="Mizoram">Mizoram</option>
                            <option value="Nagaland">Nagaland</option>
                            <option value="Odisha">Odisha</option>
                            <option value="Punjab">Punjab</option>
                            <option value="Rajasthan">Rajasthan</option>
                            <option value="Sikkim">Sikkim</option>
                            <option value="Tamil Nadu">Tamil Nadu</option>
                            <option value="Telangana">Telangana</option>
                            <option value="Tripura">Tripura</option>
                            <option value="Uttar Pradesh">Uttar Pradesh</option>
                            <option value="Uttarakhand">Uttarakhand</option>
                            <option value="West Bengal">West Bengal</option>
                            <option value="Andaman and Nicobar">Andaman and Nicobar</option>
                            <option value="Chandigarh">Chandigarh</option>
                            <option value="Dadra and Nagar Haveli">Dadra and Nagar Haveli</option>
                            <option value="Delhi">Delhi</option>
                            <option value="Jammu and Kashmir">Jammu and Kashmir</option>
                            <option value="Ladakh">Ladakh</option>
                            <option value="Lakshadweep">Lakshadweep</option>
                            <option value="Puducherry">Puducherry</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label text-primary"> Managed By (Manager) </label>
                        <input type="text" name="managed_by" id="form_managed_by" class="form-control modern-input readonly-field" placeholder="Auto-filled" readonly tabindex="-1">
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label text-primary"> Sale Employee Name </label>
                        <input type="text" name="sale_employee" id="form_sale_employee" class="form-control modern-input readonly-field" placeholder="Auto-filled" readonly tabindex="-1">
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> Submitted By </label>
                        <input type="text" name="submitted_by" id="form_submitted_by" class="form-control modern-input readonly-field" placeholder="Auto-filled" readonly tabindex="-1">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="modern-label"> Internal Name </label>
                        <input type="text" name="internal_name" class="form-control modern-input" placeholder="Office code/name" maxlength="100">
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> Item Category <span class="text-danger">*</span></label>
                        <input type="text" name="item_category" class="form-control modern-input" placeholder="Category" maxlength="100" required>
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> Bid Type <span class="text-danger">*</span></label>
                        <select name="bid_type" class="form-select modern-input" required>
                            <option value="">-- Select Type --</option>
                            <option value="Product">Product</option>
                            <option value="Bunch Product">Bunch Product</option>
                            <option value="Service">Service</option>
                            <option value="Bunch Service">Bunch Service</option>
                            <option value="Custom Bid">Custom Bid</option>
                            <option value="Bunch Custom Bid">Bunch Custom Bid</option>
                            <option value="BOQ Bid">BOQ Bid</option>
                            <option value="Bunch BOQ Bid">Bunch BOQ Bid</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> Bid Status <span class="text-danger">*</span></label>
                        <select name="bid_status" class="form-select modern-input" required>
                            <option value="In Progress" selected>In Progress</option>
                        </select>
                    </div>

                  <div class="col-md-3">
    <label class="modern-label"> Estimated Value </label>
    <input type="text" name="estimated_value" class="form-control modern-input" placeholder="₹ Value" oninput="this.value = this.value.replace(/[^0-9.]/g, '');">
</div>

                    <div class="col-md-3">
                        <label class="modern-label"> EMD Status <span class="text-danger">*</span></label>
                        <div class="btn-group btn-group-toggle d-flex" role="group">
                            <input type="radio" class="btn-check" name="emd_status" id="emd_yes" value="yes">
                            <label class="btn btn-outline-primary" for="emd_yes">Yes</label>
                            <input type="radio" class="btn-check" name="emd_status" id="emd_no" value="no" checked>
                            <label class="btn-outline-primary btn" for="emd_no">No</label>
                        </div>
                    </div>

                    <div class="col-md-3" id="emd_amount_container" style="display: none;">
                        <label class="modern-label text-primary"> EMD Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="emd_amount" id="emd_amount_input" class="form-control modern-input" placeholder="₹ Amount" onkeypress="return (event.charCode !=8 && event.charCode ==0 || (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46)">
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> RA Status </label>
                        <div class="btn-group btn-group-toggle d-flex" role="group">
                            <input type="radio" class="btn-check" name="ra_status" id="ra_yes" value="yes">
                            <label class="btn btn-outline-primary" for="ra_yes">Yes</label>
                            <input type="radio" class="btn-check" name="ra_status" id="ra_no" value="no" checked>
                            <label class="btn btn-outline-primary" for="ra_no">No</label>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="modern-label"> Pre-Bid Meeting </label>
                        <div class="btn-group btn-group-toggle d-flex" role="group">
                            <input type="radio" class="btn-check" name="pre_bid_status" id="pre_bid_yes" value="yes">
                            <label class="btn btn-outline-primary" for="pre_bid_yes">Yes</label>
                            <input type="radio" class="btn-check" name="pre_bid_status" id="pre_bid_no" value="no" checked>
                            <label class="btn btn-outline-primary" for="pre_bid_no">No</label>
                        </div>
                    </div>
                    
                    <div class="col-md-3 pre-bid-extra">
                        <label class="modern-label text-primary"> Meeting Date </label>
                        <input type="text" id="pre_bid_date" name="pre_bid_date" class="form-control modern-input bg-white" placeholder="DD/MM/YYYY HH:MM">
                    </div>
                    
                    <div class="col-md-3 pre-bid-extra">
                        <label class="modern-label text-primary"> Meeting Address </label>
                        <input type="text" name="pre_bid_address" class="form-control modern-input" placeholder="Enter Location/Link">
                    </div>

                </div>

                <div class="section-title"><div class="section-icon text-success bg-success bg-opacity-10"><i class="fas fa-file-upload"></i></div> 2. Documents & Remarks</div>
                
                <div class="row g-4 mb-4">
                    <div class="col-md-12">
                        <div class="file-upload-box d-flex flex-column justify-content-center py-5" id="drop-zone">
                            <label class="modern-label text-primary mb-2"><i class="fas fa-cloud-upload-alt fs-3 mb-2 d-block"></i> Upload Documents </label>
                            <input type="file" name="bid_document[]" id="file_input" class="form-control modern-input mx-auto w-50 p-2" multiple>
                            <small class="text-muted mt-3">Drag & Drop files or press Ctrl+V to paste images/files</small>
                            <div id="file-list" class="mt-3 mx-auto text-start w-50" style="max-height: 150px; overflow-y: auto;"></div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="modern-label">Initial Remark / Note </label>
                        <textarea name="remarks" class="form-control modern-input" rows="4" placeholder="Enter notes regarding this bid..." maxlength="1000"></textarea>
                    </div>
                </div>

                <div class="mt-5 text-end bg-light p-3 rounded-3 border">
                    <button type="button" id="resetFormBtn" class="btn btn-light btn-modern border me-2">Reset Form</button>
                    <button type="submit" name="submit_bid" class="btn btn-primary-modern btn-modern px-5">Save New Bid</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header bg-light border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold text-dark"><i class="fas fa-building text-primary me-2"></i> Quick Add Customer</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
          <form id="quickAddCustomerForm">
              <div class="row g-3">
                  <div class="col-md-6">
                      <label class="modern-label">Company/Org Name <span class="text-danger">*</span></label>
                      <input type="text" id="new_company" class="form-control modern-input" maxlength="150" required>
                  </div>
                  <div class="col-md-6">
                      <label class="modern-label">Contact Person</label>
                      <input type="text" id="new_contact_name" class="form-control modern-input" maxlength="100">
                  </div>
                  <div class="col-md-6">
                      <label class="modern-label">Phone Number <span class="text-danger">*</span></label>
                      <input type="text" id="new_phone" class="form-control modern-input" maxlength="15" title="Enter valid phone numbers" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
                  </div>
                  <div class="col-md-6">
                      <label class="modern-label">Email Address</label>
                      <input type="email" id="new_email" class="form-control modern-input" maxlength="100">
                  </div>
                  
                  <div class="col-md-6">
                      <label class="modern-label">Reporting Manager</label>
                      <select id="new_manager" class="form-select modern-input">
                          <option value="">Select Manager</option>
                          <?php 
                          $allowed_managers = ['jatin goyal', 'rohit bansal'];
                          foreach($all_users as $user): 
                              $u_name = trim($user['name']);
                              if (in_array(strtolower($u_name), $allowed_managers)):
                          ?>
                              <option value="<?= htmlspecialchars($u_name) ?>"><?= htmlspecialchars($u_name) ?></option>
                          <?php endif; endforeach; ?>
                      </select>
                  </div>
                  <div class="col-md-6">
                      <label class="modern-label">Followed By (Executive)</label>
                      <select id="new_executive" class="form-select modern-input">
                          <option value="">Select Executive</option>
                          <?php 
                          $excluded_executives = ['jatin goyal', 'rohit bansal', 'amina das', 'himani', 'nandlal', 'kitty jaiswal'];
                          foreach($all_users as $user): 
                              $u_name = trim($user['name']);
                              if (!in_array(strtolower($u_name), $excluded_executives)):
                          ?>
                              <option value="<?= htmlspecialchars($u_name) ?>"><?= htmlspecialchars($u_name) ?></option>
                          <?php endif; endforeach; ?>
                      </select>
                  </div>
              </div>
          </form>
          <div id="quickAddMessage" class="mt-3 fw-bold small"></div>
      </div>
      <div class="modal-footer border-top-0 pt-0">
        <button type="button" class="btn btn-light btn-modern border" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary-modern btn-modern" id="saveCustomerBtn">Save & Select</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // FLATPICKR INITIALIZATION FOR DD/MM/YYYY FORMAT
    document.addEventListener("DOMContentLoaded", function () {
        // Initializing Start & End date with current date as default
        flatpickr("#start_date, #end_date", {
            enableTime: true,
            dateFormat: "Y-m-d H:i", // This safely submits to PHP/MySQL as standard format
            altInput: true,
            altFormat: "d/m/Y H:i", // This forces the frontend to show DD/MM/YYYY HH:MM
            defaultDate: new Date()
        });

        // Initialize Pre Bid Date (No default date)
        flatpickr("#pre_bid_date", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            altInput: true,
            altFormat: "d/m/Y H:i"
        });
    });

    // PRE-BID TOGGLE SCRIPT
    const preBidYes = document.getElementById('pre_bid_yes');
    const preBidNo = document.getElementById('pre_bid_no');
    const preBidExtras = document.querySelectorAll('.pre-bid-extra');

    function togglePreBidFields() {
        if (preBidYes.checked) {
            preBidExtras.forEach(el => {
                el.style.display = 'block';
            });
        } else {
            preBidExtras.forEach(el => {
                el.style.display = 'none';
                const input = el.querySelector('input');
                // Don't clear flatpickr values natively, reset it via flatpickr instance if needed
                if(input && input._flatpickr) {
                    input._flatpickr.clear();
                } else if(input) {
                    input.value = '';
                }
            });
        }
    }

    preBidYes.addEventListener('change', togglePreBidFields);
    preBidNo.addEventListener('change', togglePreBidFields);
    togglePreBidFields();

    // EMD AMOUNT TOGGLE SCRIPT
    const emdYes = document.getElementById('emd_yes');
    const emdNo = document.getElementById('emd_no');
    const emdAmountContainer = document.getElementById('emd_amount_container');
    const emdAmountInput = document.getElementById('emd_amount_input');

    function toggleEmdField() {
        if (emdYes.checked) {
            emdAmountContainer.style.display = 'block';
            emdAmountInput.setAttribute('required', 'required');
        } else {
            emdAmountContainer.style.display = 'none';
            emdAmountInput.removeAttribute('required');
            emdAmountInput.value = ''; 
        }
    }

    emdYes.addEventListener('change', toggleEmdField);
    emdNo.addEventListener('change', toggleEmdField);
    toggleEmdField();

    // 1. AJAX SEARCH SCRIPT (UPDATED FOR SMART LOCATION MATCHING)
    const searchInput = document.getElementById('form_org_name');
    const searchResults = document.getElementById('search_results');

    searchInput.addEventListener('input', function() {
        // ID clear kar do jab bhi user manually type kare
        document.getElementById('form_customer_id').value = ''; 
        
        let query = this.value.trim();
        if (query.length < 2) { searchResults.style.display = 'none'; return; }

        fetch('add-bid.php?ajax_search=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                searchResults.innerHTML = '';
                if (data.length > 0) {
                    searchResults.style.display = 'block';
                    data.forEach(item => {
                        let div = document.createElement('div');
                        div.className = 'p-3 border-bottom';
                        div.style.cursor = 'pointer';
                        div.innerHTML = `<div class="fw-bold text-dark">${item.company_name}</div><div class="small text-muted">Manager: ${item.reporting_manager} | Executive: ${item.followed_by}</div>`;
                        
                        div.onclick = function() {
                            document.getElementById('form_customer_id').value = item.id || '';
                            searchInput.value = item.company_name || '';
                            
                            document.getElementById('form_contact_person').value = item.customer_name || '';
                            document.getElementById('form_contact_phone').value = item.contact_no || '';
                            
                            document.getElementById('form_dept_name').value = item.company_name || '';
                            
                            let locSelect = document.getElementById('form_location');
                            let options = Array.from(locSelect.options);
                            let addressVal = (item.address || '').trim().toLowerCase(); 
                            
                            let foundState = null;
                            if(addressVal !== '') {
                                foundState = options.find(opt => {
                                    if(opt.value === '') return false;
                                    return addressVal.includes(opt.value.toLowerCase());
                                });
                            }
                            
                            if(foundState) {
                                locSelect.value = foundState.value;
                                locSelect.classList.add('readonly-field');
                                locSelect.tabIndex = -1;
                            } else {
                                locSelect.value = '';
                                locSelect.classList.remove('readonly-field');
                                locSelect.removeAttribute('tabIndex');
                            }
                            
                            document.getElementById('form_managed_by').value = item.reporting_manager || '';
                            document.getElementById('form_sale_employee').value = item.followed_by || '';
                            
                            let submitter = item.created_by ? item.created_by : item.followed_by;
                            document.getElementById('form_submitted_by').value = submitter || '';
                            
                            searchResults.style.display = 'none';
                        };
                        searchResults.appendChild(div);
                    });
                }
            });
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });

    // 2. QUICK ADD CUSTOMER SCRIPT (AJAX)
    document.getElementById('saveCustomerBtn').addEventListener('click', function() {
        const form = document.getElementById('quickAddCustomerForm');
        
        if(!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const company = document.getElementById('new_company').value.trim();
        const phone = document.getElementById('new_phone').value.trim();
        const msgBox = document.getElementById('quickAddMessage');

        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        this.disabled = true;

        let formData = new FormData();
        formData.append('ajax_add_customer', '1');
        formData.append('company_name', company);
        formData.append('customer_name', document.getElementById('new_contact_name').value);
        formData.append('contact_no', phone);
        formData.append('email', document.getElementById('new_email').value);
        formData.append('reporting_manager', document.getElementById('new_manager').value);
        formData.append('followed_by', document.getElementById('new_executive').value);

        fetch('add-bid.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                var myModal = bootstrap.Modal.getInstance(document.getElementById('addCustomerModal'));
                myModal.hide();
                
                form.reset();
                msgBox.innerHTML = '';
                document.getElementById('saveCustomerBtn').innerHTML = 'Save & Select';
                document.getElementById('saveCustomerBtn').disabled = false;

                document.getElementById('form_customer_id').value = data.customer_id;
                searchInput.value = data.company_name;
                
                document.getElementById('form_contact_person').value = data.customer_name || '';
                document.getElementById('form_contact_phone').value = data.contact_no || '';
                document.getElementById('form_dept_name').value = data.company_name || '';
                
                let locSelect = document.getElementById('form_location');
                locSelect.value = ''; 
                locSelect.classList.remove('readonly-field');
                locSelect.removeAttribute('tabIndex');
                
                document.getElementById('form_managed_by').value = data.reporting_manager || '';
                document.getElementById('form_sale_employee').value = data.followed_by || '';
                document.getElementById('form_submitted_by').value = data.created_by || '';
                
                alert('Customer successfully added and selected!');
            } else {
                msgBox.innerHTML = '<span class="text-danger">' + data.message + '</span>';
                document.getElementById('saveCustomerBtn').innerHTML = 'Save & Select';
                document.getElementById('saveCustomerBtn').disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('saveCustomerBtn').innerHTML = 'Save & Select';
            document.getElementById('saveCustomerBtn').disabled = false;
        });
    });

    // 3. FILE HANDLING SCRIPT
    document.addEventListener("DOMContentLoaded", function () {
        const fileInput = document.getElementById('file_input');
        const fileListDisplay = document.getElementById('file-list');
        const dropZone = document.getElementById('drop-zone');
        const resetBtn = document.getElementById('resetFormBtn');
        let dataTransfer = new DataTransfer();

        function updateFileList() {
            fileListDisplay.innerHTML = '';
            for (let i = 0; i < dataTransfer.files.length; i++) {
                let file = dataTransfer.files[i];
                fileListDisplay.innerHTML += `
                    <div class="d-flex justify-content-between align-items-center p-2 border rounded mb-2 bg-white small text-secondary shadow-sm">
                        <span class="text-truncate" style="max-width: 85%;"><i class="fas fa-file-alt me-2 text-primary"></i>${file.name}</span>
                        <i class="fas fa-times remove-file-btn" data-index="${i}" title="Remove File"></i>
                    </div>`;
            }
            fileInput.files = dataTransfer.files; 
        }

        fileInput.addEventListener('change', function () {
            for (let i = 0; i < fileInput.files.length; i++) { 
                dataTransfer.items.add(fileInput.files[i]); 
            }
            updateFileList();
        });

        document.addEventListener('paste', function (e) {
            let pastedFiles = e.clipboardData.files;
            if (pastedFiles.length > 0) {
                for (let i = 0; i < pastedFiles.length; i++) { 
                    let file = pastedFiles[i];
                    if(file.name === 'image.png') {
                        let newName = 'Pasted_Image_' + Date.now() + '.png';
                        file = new File([file], newName, { type: file.type });
                    }
                    dataTransfer.items.add(file); 
                }
                updateFileList();
            }
        });

        dropZone.addEventListener('dragover', function(e) { e.preventDefault(); dropZone.classList.add('dragover'); });
        dropZone.addEventListener('dragleave', function(e) { e.preventDefault(); dropZone.classList.remove('dragover'); });
        dropZone.addEventListener('drop', function(e) {
            e.preventDefault(); dropZone.classList.remove('dragover');
            let droppedFiles = e.dataTransfer.files;
            if (droppedFiles.length > 0) {
                for (let i = 0; i < droppedFiles.length; i++) { dataTransfer.items.add(droppedFiles[i]); }
                updateFileList();
            }
        });

        fileListDisplay.addEventListener('click', function(e) {
            if(e.target.classList.contains('remove-file-btn')) {
                let indexToRemove = parseInt(e.target.getAttribute('data-index'));
                let newDt = new DataTransfer();
                for(let i = 0; i < dataTransfer.files.length; i++) {
                    if(i !== indexToRemove) {
                        newDt.items.add(dataTransfer.files[i]);
                    }
                }
                dataTransfer = newDt; 
                updateFileList(); 
            }
        });

        resetBtn.addEventListener('click', function() {
            document.getElementById('bidForm').reset();
            dataTransfer = new DataTransfer(); 
            updateFileList();
            document.getElementById('search_results').style.display = 'none';
            
            let locSelect = document.getElementById('form_location');
            locSelect.classList.remove('readonly-field');
            locSelect.removeAttribute('tabIndex');
        });
    });

   // 4. FORM SUBMIT VALIDATION: Check if customer is selected
    document.getElementById('bidForm').addEventListener('submit', function(e) {
        let customerId = document.getElementById('form_customer_id').value;
        if (!customerId || customerId.trim() === '' || customerId === '0') {
            e.preventDefault(); // Form ko submit hone se rok dega
            alert('Please select a valid Organisation from the search list or add a new one using the "+" button.');
            document.getElementById('form_org_name').focus();
        }
    });
</script>

<?php include 'include/footer.php'; ?>