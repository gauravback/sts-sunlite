<?php
require_once 'config/auth.php';
require_once 'config/database.php';
include 'include/header.php';

// Logged-in user ka naam session se nikal rahe hain
// Note: Agar aapke session mein naam kisi aur key se save hota hai (e.g. 'username'), toh yahan change kar lena.
$logged_in_user = isset($_SESSION['name']) ? $_SESSION['name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : '');

/* Database se Users Fetch karne ki query */
$user_query = "SELECT id, name FROM users ORDER BY name ASC";
$user_result = mysqli_query($conn, $user_query);

// Users ko ek array mein store kar rahe hain taaki isko multiple dropdowns mein use kar sakein
$users_list = [];
if($user_result && mysqli_num_rows($user_result) > 0) {
    while($user = mysqli_fetch_assoc($user_result)) {
        $users_list[] = $user;
    }
}

/* Default Timezone */
date_default_timezone_set('Asia/Kolkata');
$current_datetime = date('Y-m-d\TH:i');
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
:root {
    --primary:#4318ff;
    --bg-main:#f4f7fe;
    --card-bg:#ffffff;
    --text-dark:#1b2559;
    --border-color:#e0e5f2;
    --danger: #ff5b5c;
}

body{
    background:var(--bg-main);
    font-family:'Plus Jakarta Sans',sans-serif;
    color:var(--text-dark);
}

.form-container{
    /*max-width:1100px;*/
    margin:auto;
}

.glass-card{
    background:var(--card-bg);
    border-radius:20px;
    box-shadow:0px 20px 50px rgba(0,0,0,0.05);
    padding:35px;
    margin-bottom:30px;
}

.custom-label{
    font-size:0.85rem;
    font-weight:700;
    margin-bottom:8px;
    display:block;
}

.req { color: var(--danger); } /* Red asterisk style */

.input-group-custom{
    position:relative;
}

.input-group-custom i{
    position:absolute;
    left:15px;
    top:50%;
    transform:translateY(-50%);
    color:#a3aed0;
    z-index: 10;
}

.custom-input{
    border:1px solid var(--border-color);
    border-radius:12px;
    padding:12px 15px 12px 45px;
    background:#f8fafc;
    width:100%;
    font-size:0.95rem;
}




/* Fix for standard select without icons */
select.custom-input-no-icon {
    padding-left: 15px;
}

.custom-input:focus{
    outline:none;
    border-color:var(--primary);
    background:#fff;
    box-shadow:0 0 0 4px rgba(67,24,255,0.1);
}

.readonly-field{
    background:#e2e8f0;
    cursor:not-allowed;
}

.section-title{
    font-size:1.1rem;
    font-weight:700;
    color:var(--primary);
    margin:20px 0;
    display:flex;
    align-items:center;
}

.section-title::after{
    content:"";
    flex:1;
    height:1px;
    background:var(--border-color);
    margin-left:15px;
}

.btn-primary-custom{
    background:var(--primary);
    color:#fff;
    border:none;
    border-radius:12px;
    padding:14px 30px;
    font-weight:600;
    box-shadow:0 10px 20px rgba(67,24,255,0.2);
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary-custom:hover{
    opacity:.9;
    transform: translateY(-2px);
}

/* --- Pop-up Loader CSS --- */
.popup-loader-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5); /* Semi-transparent black background */
    backdrop-filter: blur(4px); /* Background ko blur karne ke liye */
    z-index: 9999; /* Sabse upar dikhane ke liye */
    display: none; /* By default hide rakhega */
    align-items: center;
    justify-content: center;
}

.loader-content {
    background: #ffffff;
    padding: 30px 50px;
    border-radius: 16px;
    text-align: center;
    box-shadow: 0px 20px 50px rgba(0,0,0,0.15);
    transform: scale(0.9);
    animation: popIn 0.3s ease forwards;
}

.loader-content i {
    font-size: 3.5rem;
    color: var(--primary); /* Aapka blue color lega */
    display: inline-block;
}

.loader-content p {
    margin: 15px 0 0 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--text-dark);
}

.spin-icon {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    100% { transform: rotate(360deg); }
}

@keyframes popIn {
    to { transform: scale(1); }
}
</style>

<div class="container-fluid py-5">
    <div class="form-container">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold m-0 text-dark">Lead Management</h2>
                <p class="text-muted">Fields marked with <span class="req">*</span> are mandatory.</p>
            </div>
            <a href="leads-list.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                View All Leads
            </a>
        </div>

        <div class="glass-card">
            <form action="save-lead.php" method="POST">

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="custom-label">Customer Name <span class="req">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-user"></i>
                            <input type="text" name="contact_person" class="custom-input capitalize-input" placeholder="e.g. John Doe" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Phone Number <span class="req">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-phone"></i>
                            <input type="text" name="contact_number" class="custom-input" placeholder="+91 98765 43210" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Email Address</label>
                        <div class="input-group-custom">
                            <i class="ti ti-mail"></i>
                            <input type="email" name="email" class="custom-input" placeholder="john@example.com">
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="custom-label">Company Name / Organisation Name <span class="req">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-building"></i>
                            <input type="text" name="company_name" class="custom-input capitalize-input" placeholder="Company Ltd." required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Address</label>
                        <div class="input-group-custom">
                            <i class="ti ti-map-pin"></i>
                            <input type="text" name="location" class="custom-input" placeholder="City, State">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Alternate No.</label>
                        <div class="input-group-custom">
                            <i class="ti ti-phone-plus"></i>
                            <input type="text" name="alternate_number" class="custom-input" placeholder="Alternate Number">
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                     <div class="col-md-3">
                        <label class="custom-label">Lead Source</label>
                        <select name="lead_type" class="custom-input custom-input-no-icon">
                            <option value="">Select Category</option>
                            <option>Corporate</option>
                            <option>Government</option>
                            <option>Dealer</option>
                            <option>End User</option>
                            <option>Education</option>
                            <option>Retailor</option>
                            <option>Online</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="custom-label">Customer Type</label>
                        <div class="input-group-custom">
                            <i class="ti ti-users"></i>
                            <select name="customer_type" class="custom-input">
                                <option>New Customer</option>
                                <option>Existing Customer</option>
                                <option>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="custom-label">Current Status</label>
                        <div class="input-group-custom">
                            <i class="ti ti-chart-bar"></i>
                            <select name="lead_status" class="custom-input">
                                <option>New Lead</option>
                                <option>Follow-up</option>
                                 <option>Meeting</option>
                                <option>Closed</option>
                                <option>Mature</option>
                                <option>Sale</option>
                                 <option>Calling</option>
                                <option>Work in Progress</option>
                                <option>Payment</option>
                            </select>
                        </div>
                    </div>
                    
                      <div class="col-md-3">
                        <label class="custom-label">Lead Type</label>
                        <div class="input-group-custom">
                            <i class="ti ti-chart-bar"></i>
                            <select name="lead_priority" class="custom-input">
                                <option>Hot</option>
                                <option>Cold</option>
                                <option>Normal</option>
                               
                            </select>
                        </div>
                    </div>
                    
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="custom-label">Followed By</label>
                        <div class="input-group-custom">
                            <i class="ti ti-user-share"></i>
                            <select name="followed_by" class="custom-input">
                                <option value="">Select User</option>
                                <?php 
                                foreach($users_list as $user) {
                                    echo '<option value="'.$user['name'].'">'.$user['name'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                  <div class="col-md-6">
    <label class="custom-label">Reporting Manager <span class="req">*</span></label>
    <div class="input-group-custom">
        <i class="ti ti-user-circle"></i>
        <input type="text" name="manager" class="custom-input" value="Jatin Goyal" readonly required>
    </div>
</div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="custom-label">Lead Created By <span class="req">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-user-check"></i>
                            <input type="text" name="lead_by" class="custom-input readonly-field" value="<?php echo htmlspecialchars($logged_in_user); ?>" placeholder="Creator Name" required readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="custom-label">Support Team</label>
                        <div class="input-group-custom">
                            <i class="ti ti-headset"></i>
                            <select name="support_team" class="custom-input">
                                <option value="">Select Team Member</option>
                                <?php 
                                foreach($users_list as $user) {
                                    echo '<option value="'.$user['name'].'">'.$user['name'].'</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="custom-label">Lead Update Date</label>
                        <div class="input-group-custom">
                            <i class="ti ti-calendar-event"></i>
                            <input type="datetime-local" name="current_time" class="custom-input readonly-field" value="<?php echo $current_datetime; ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="custom-label">Lead Followup date</label>
                        <div class="input-group-custom">
                            <i class="ti ti-bell-ringing"></i>
                            <input type="datetime-local" name="followup_time" class="custom-input">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="custom-label">Activity Note / History Update</label>
                    <textarea name="history_note" class="custom-input custom-input-no-icon" rows="4" placeholder="Enter latest conversation notes..."></textarea>
                </div>

                <div class="text-end border-top pt-4">
                    <button type="submit" class="btn-primary-custom">
                        <i class="ti ti-device-floppy me-2"></i>
                        <span>Save & Create Lead</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<div id="popup-loader" class="popup-loader-overlay">
    <div class="loader-content">
        <i class="ti ti-loader spin-icon"></i>
        <p>Saving Lead, Please Wait...</p>
    </div>
</div>
<script>
document.querySelectorAll('.capitalize-input').forEach(function(input) {
    input.addEventListener('input', function() {
        // Sirf pehle letter ko Capital karne ke liye
        if (this.value.length > 0) {
            this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
        }
    });
});
document.querySelector('form').addEventListener('submit', function() {
    // 1. Pop-up loader ko show karna
    document.getElementById('popup-loader').style.display = 'flex';
    
    // 2. Submit button ko disable karna taaki double submit na ho
    const submitBtn = document.querySelector('.btn-primary-custom[type="submit"]');
    if(submitBtn) {
        submitBtn.disabled = true;
    }
});
</script>
<?php include 'include/footer.php'; ?>