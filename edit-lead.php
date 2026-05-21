<?php
require_once 'config/auth.php';
require_once 'config/database.php';
include 'include/header.php';

// Check if user is Admin (Optional security check, since button is already hidden for users)
if(isset($_SESSION['role']) && $_SESSION['role'] !== 'admin'){
    echo "<div class='container mt-4'><div class='alert alert-danger'>Access Denied. Only Admins can edit leads.</div></div>";
    include 'include/footer.php';
    exit;
}

if(!isset($_GET['id']) || empty($_GET['id'])){
    echo "<div class='container mt-4'><div class='alert alert-danger'>Lead not found or ID missing.</div></div>";
    include 'include/footer.php';
    exit;
}

$id = intval($_GET['id']);

// Fetch Existing Data
$stmt = $conn->prepare("SELECT * FROM leads WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$lead = $result->fetch_assoc();

if(!$lead){
    echo "<div class='container mt-4'><div class='alert alert-warning'>Lead not found.</div></div>";
    include 'include/footer.php';
    exit;
}

// UPDATE LOGIC
if(isset($_POST['edit_lead'])){
    $contact_person = trim($_POST['contact_person']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $company_name = trim($_POST['company_name']);
    $customer_type = trim($_POST['customer_type']);
    $location = trim($_POST['location']);
    
    // Naya Variable: Alternate Number
    $alternate_number = trim($_POST['alternate_number']);
    
    $assigned_by = trim($_POST['assigned_by']);
    $lead_type = trim($_POST['lead_type']); // Lead Source ke liye
    $reporting_manager = trim($_POST['reporting_manager']);
    $status = trim($_POST['status']);
    
    // Naya Variable: Lead Priority (Hot/Cold)
    $lead_priority = trim($_POST['lead_priority']);
    
    $followup_date = !empty($_POST['followup_date']) ? $_POST['followup_date'] : NULL;

    // UPDATE Query mein naye columns add kiye
    $update_query = "UPDATE leads SET 
        contact_person=?, contact_number=?, email=?, company_name=?, 
        customer_type=?, location=?, alternate_number=?, lead_by=?, lead_type=?, 
        manager=?, lead_status=?, lead_priority=?, followup_time=? WHERE id=?";
        
    $stmt_update = $conn->prepare($update_query);
    
    // Bind Params (ab 14 parameters hain)
    $stmt_update->bind_param("sssssssssssssi", 
        $contact_person, $phone, $email, $company_name, 
        $customer_type, $location, $alternate_number, $assigned_by, $lead_type, 
        $reporting_manager, $status, $lead_priority, $followup_date, $id
    );
    
    if($stmt_update->execute()){
        // SweetAlert2 Success Popup
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            window.onload = function() {
                Swal.fire({
                    title: 'Updated!',
                    text: 'Lead details have been updated successfully.',
                    icon: 'success',
                    confirmButtonColor: '#0d6efd',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    window.location = 'leads.php'; // Redirect back to leads list
                });
            };
        </script>";
    } else {
        echo "<script>alert('Error updating lead details!');</script>";
    }
}

// Format date for HTML datetime-local input
$followup_formatted = !empty($lead['followup_time']) ? date('Y-m-d\TH:i', strtotime($lead['followup_time'])) : '';
?>

<style>
    .form-container {
        background: #ffffff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid #eaeaea;
    }
    .form-control, .form-select {
        border-radius: 8px;
        padding: 10px 15px;
        border: 1px solid #ced4da;
        background-color: #f8fafc;
        transition: all 0.2s;
    }
    .form-control:focus, .form-select:focus {
        background-color: #fff;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }
    .section-title {
        font-size: 1.15rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 20px;
        border-bottom: 2px solid #f1f5f9;
        padding-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    label {
        font-weight: 500;
        color: #475569;
        margin-bottom: 6px;
        font-size: 0.9rem;
    }
    .btn-save {
        background: linear-gradient(135deg, #4f46e5, #4338ca);
        color: white;
        font-weight: 500;
        padding: 10px 24px;
        border-radius: 8px;
        border: none;
        transition: all 0.3s ease;
    }
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        color: white;
    }
</style>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1 text-dark">Edit Lead Master Data</h3>
            <p class="text-muted mb-0">Modify core information for Lead ID #<?= str_pad($id, 4, '0', STR_PAD_LEFT); ?></p>
        </div>
        <a href="leads.php" class="btn btn-outline-secondary btn-sm px-3 rounded-3">Cancel & Back</a>
    </div>

    <div class="form-container">
        <form method="POST">
            
            <div class="section-title">
                <i class="ti ti-building text-primary"></i> Customer Information
            </div>
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <label>Contact Person <span class="text-danger">*</span></label>
                    <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($lead['contact_person']) ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Phone Number <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($lead['contact_number']) ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($lead['email']) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label>Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($lead['company_name']) ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label>Location</label>
                    <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($lead['location']) ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label>Alternate No.</label>
                    <input type="text" name="alternate_number" class="form-control" value="<?= htmlspecialchars($lead['alternate_number'] ?? '') ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label>Customer Type</label>
                    <select name="customer_type" class="form-select">
                        <?php 
                        // Options Add Lead form ke hisaab se sync kiye hain
                        $types = ['New Customer', 'Existing Customer', 'Other'];
                        foreach($types as $type){
                            $selected = ($lead['customer_type'] == $type) ? 'selected' : '';
                            echo "<option value='$type' $selected>$type</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="section-title">
                <i class="ti ti-briefcase text-primary"></i> Internal Assignment & Status
            </div>
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <label>Assigned By</label>
                    <input type="text" name="assigned_by" class="form-control" value="<?= htmlspecialchars($lead['lead_by']) ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label>Reporting Manager</label>
                    <input type="text" name="reporting_manager" class="form-control" value="<?= htmlspecialchars($lead['manager']) ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label>Lead Source</label>
                    <select name="lead_type" class="form-select">
                        <?php 
                        // Options Add Lead form ke hisaab se sync kiye hain
                        $categories = ['Corporate', 'Government', 'Dealer', 'End User', 'Education', 'Retailor', 'Online', 'Other'];
                        foreach($categories as $cat){
                            $selected = ($lead['lead_type'] == $cat) ? 'selected' : '';
                            echo "<option value='$cat' $selected>$cat</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label>Current Status</label>
                    <select name="status" class="form-select">
                        <?php 
                        // Options Add Lead form ke hisaab se sync kiye hain
                        $statuses = ['New Lead', 'Follow-up', 'Meeting', 'Closed', 'Mature', 'Sale', 'Work in Progress', 'Payment'];
                        foreach($statuses as $st){
                            $selected = ($lead['lead_status'] == $st) ? 'selected' : '';
                            echo "<option value='$st' $selected>$st</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label>Lead Type</label>
                    <select name="lead_priority" class="form-select">
                        <?php 
                        $priorities = ['Hot', 'Cold', 'Normal'];
                        // Ensure key exists before checking to prevent warnings
                        $current_priority = $lead['lead_priority'] ?? ''; 
                        foreach($priorities as $prio){
                            $selected = ($current_priority == $prio) ? 'selected' : '';
                            echo "<option value='$prio' $selected>$prio</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="col-md-5 mb-3">
                    <label>Follow-up Date & Time</label>
                    <input type="datetime-local" name="followup_date" value="<?= $followup_formatted ?>" class="form-control">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label>Lead Creation Date</label>
                    <input type="text" class="form-control text-muted" value="<?= date('d M Y, h:i A', strtotime($lead['created_at'])) ?>" readonly disabled>
                    <small class="text-muted">Creation date cannot be modified.</small>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                <button type="submit" name="edit_lead" class="btn-save d-flex align-items-center gap-2">
                    <i class="ti ti-device-floppy"></i> Save Changes
                </button>
            </div>

        </form>
    </div>
</div>

<?php include 'include/footer.php'; ?>