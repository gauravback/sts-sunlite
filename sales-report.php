<?php
// Session start karna zaroori hai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Indian Timezone ke liye
date_default_timezone_set('Asia/Kolkata');

require_once 'config/auth.php';
require_once 'config/database.php';

// ==========================================
// ROLE BASED ACCESS CHECK
// ==========================================
if (!isset($_SESSION['role'])) {
    header("Location: index.php?error=access_denied");
    exit(); 
}

$is_admin = ($_SESSION['role'] === 'admin');

// --- 🔴 SMART SESSION NAME DETECTION 🔴 ---
// Ye check karega ki kis variable me user ka naam hai
$session_name = '';
if (!empty($_SESSION['name'])) {
    $session_name = $_SESSION['name'];
} elseif (!empty($_SESSION['username'])) {
    $session_name = $_SESSION['username'];
} elseif (!empty($_SESSION['user_name'])) {
    $session_name = $_SESSION['user_name'];
} elseif (!empty($_SESSION['first_name'])) {
    $session_name = trim($_SESSION['first_name'] . ' ' . ($_SESSION['last_name'] ?? ''));
} else {
    // Agar inme se koi nahi hai, toh ye error pakadne ke liye hai.
    $session_name = 'UNKNOWN_USER'; 
}

// ==========================================
// ERROR HANDLING WRAPPER
// ==========================================
function runQuery($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        if(isset($_POST['action'])) return false; 
        echo "<div class='alert alert-danger m-4 shadow-sm border-0 border-start border-danger border-4 rounded-3'>
                <strong>SQL Error:</strong> " . mysqli_error($conn) . "<br><small class='text-muted'>Query: $sql</small>
              </div>";
        return false;
    }
    return $result;
}

include 'include/header.php';

// ==========================================
// CAPTURE DYNAMIC FILTERS & STRICT USER ACCESS
// ==========================================
$filter_status        = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$filter_priority      = isset($_GET['priority']) ? mysqli_real_escape_string($conn, $_GET['priority']) : '';
$filter_date_from     = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$filter_date_to       = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';
$filter_user          = isset($_GET['user']) ? mysqli_real_escape_string($conn, $_GET['user']) : '';

$where_clause = "WHERE 1=1";

// 🔴 STRICT SECURITY RULE 🔴
if (!$is_admin) {
    // Agar normal user hai, toh HAMESHA sirf apni leads dekhega
    $logged_in_user = mysqli_real_escape_string($conn, $session_name);
    $where_clause .= " AND lead_by = '$logged_in_user'";
    $filter_user = $logged_in_user; // Summary cards ke liye set kiya
} else {
    // Agar admin hai, aur usne dropdown se user select kiya hai
    if ($filter_user != '') {
        $where_clause .= " AND lead_by = '$filter_user'";
    }
}

if ($filter_status != '') {
    $where_clause .= " AND lead_status = '$filter_status'";
}

if ($filter_priority != '') {
    $where_clause .= " AND lead_priority = '$filter_priority'";
}

// Date Range Logic
if ($filter_date_from != '' && $filter_date_to != '') {
    $where_clause .= " AND DATE(created_at) BETWEEN '$filter_date_from' AND '$filter_date_to'";
} elseif ($filter_date_from != '') {
    $where_clause .= " AND DATE(created_at) >= '$filter_date_from'";
} elseif ($filter_date_to != '') {
    $where_clause .= " AND DATE(created_at) <= '$filter_date_to'";
}

// ==========================================
// PAGINATION LOGIC
// ==========================================
$allowed_limits = [40, 100, 200, 300, 500, 1000, 2000, 3000, 4000, 5000];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowed_limits) ? (int)$_GET['limit'] : 100;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$offset = ($page - 1) * $limit;

// Fixed Status Array
$all_statuses = [
    "New Lead", "Calling", "Meeting", "Follow-up", 
    "Closed", "Mature", "Sale", "Work in Progress", "Payment"
];

// Helper Function for Badge Colors
function getBadgeClass($status) {
    $s = strtolower(trim($status));
    switch($s) {
        case 'new lead': return 'status-new';
        case 'calling': return 'status-calling';
        case 'meeting': return 'status-meeting';
        case 'follow-up': return 'status-followup';
        case 'won': return 'status-won';
        case 'lost': return 'status-lost';
        case 'hot': return 'status-hot';
        case 'cold': return 'status-cold';
        case 'closed': return 'status-closed';
        case 'mature': return 'status-mature';
        case 'sale': return 'status-sale';
        case 'work in progress': return 'status-wip';
        case 'payment': return 'status-payment';
        default: return 'status-default';
    }
}

// Helper Function for Client Details Box Style
function getPriorityStyleClass($priority) {
    $p = strtolower(trim($priority));
    switch($p) {
        case 'hot': return 'priority-box-hot';
        case 'cold': return 'priority-box-cold';
        case 'normal': return 'priority-box-normal';
        default: return 'priority-box-default'; 
    }
}

// Function to preserve URL parameters for pagination
function buildUrl($pageParam) {
    $params = $_GET;
    $params['page'] = $pageParam;
    return '?' . http_build_query($params);
}

// ==========================================
// DASHBOARD QUERIES
// ==========================================
if ($is_admin) {
    $users_dropdown_query = runQuery($conn, "SELECT DISTINCT lead_by FROM leads WHERE lead_by IS NOT NULL AND lead_by != ''");
}

$lead_query = runQuery($conn, "SELECT COUNT(*) as total FROM leads $where_clause");
$total_leads = $lead_query ? mysqli_fetch_assoc($lead_query)['total'] : 0;
$total_pages = ceil($total_leads / $limit);

// ==========================================
// PRIORITY SUMMARY LOGIC (HOT, COLD, NORMAL)
// ==========================================
$hot_count = 0; $cold_count = 0; $normal_count = 0;

if ($filter_user != '') {
    $priority_sql = "SELECT lead_priority, COUNT(*) as p_count FROM leads $where_clause GROUP BY lead_priority";
    $priority_query = runQuery($conn, $priority_sql);
    
    if ($priority_query) {
        while ($row = mysqli_fetch_assoc($priority_query)) {
            $p = strtolower(trim($row['lead_priority']));
            if ($p === 'hot') $hot_count = $row['p_count'];
            if ($p === 'cold') $cold_count = $row['p_count'];
            if ($p === 'normal') $normal_count = $row['p_count'];
        }
    }
}

// QUERY TO FETCH LEADS + ONLY LATEST HISTORY NOTE
$sql_leads = "
    SELECT leads.*, 
    (SELECT history_note FROM lead_history WHERE lead_history.lead_id = leads.id ORDER BY id DESC LIMIT 1) as last_history_note,
    (SELECT updated_by FROM lead_history WHERE lead_history.lead_id = leads.id ORDER BY id DESC LIMIT 1) as last_updated_by,
    (SELECT created_at FROM lead_history WHERE lead_history.lead_id = leads.id ORDER BY id DESC LIMIT 1) as last_history_date
    FROM leads 
    $where_clause 
    ORDER BY leads.id DESC 
    LIMIT $limit OFFSET $offset
";
$leads_list_query = runQuery($conn, $sql_leads);

// Grid Column sizing logic based on Role
$user_col_class     = $is_admin ? "col-lg-2 col-md-6" : "d-none";
$date_col_class     = $is_admin ? "col-lg-3 col-md-6" : "col-lg-4 col-md-6";
$status_col_class   = $is_admin ? "col-lg-3 col-md-6" : "col-lg-3 col-md-6";
$priority_col_class = $is_admin ? "col-lg-2 col-md-6" : "col-lg-3 col-md-6";
$btn_col_class      = "col-lg-2 col-md-12";
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --brand-primary: #4F46E5; 
        --bg-body: #F8FAFC;
        --card-bg: #FFFFFF;
        --text-main: #0F172A;
        --text-muted: #64748B;
        --border-light: #CBD5E1; 
        --radius-lg: 12px;
        --radius-md: 8px;
        --shadow-sm: 0 2px 4px rgba(15, 23, 42, 0.04);
    }

    body, .crm-wrapper { 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        background-color: var(--bg-body); 
        color: var(--text-main); 
    }
    
    .crm-wrapper { padding: 24px; min-height: 100vh; }
    
    .page-title { 
        font-weight: 800; 
        font-size: 1.6rem; 
        color: var(--text-main); 
    }
    
    /* Modern Card */
    .new-gen-card { 
        background: var(--card-bg); 
        border: 1px solid rgba(226, 232, 240, 0.8); 
        border-radius: var(--radius-lg); 
        box-shadow: var(--shadow-sm); 
    }

    /* Modern Inputs */
    .clean-input { 
        border-radius: 8px; border: 1px solid #E2E8F0; padding: 10px 14px; 
        font-size: 0.95rem; color: var(--text-main); background-color: #F8FAFC;
    }

    /* ENHANCED BORDERED TABLE STYLING */
    .table-container { 
        border-radius: var(--radius-md); 
        overflow: hidden; 
        border: 1px solid var(--border-light); 
        overflow-x: auto;
    }
    .clean-table { 
        width: 100%; 
        border-collapse: collapse; 
    }
    .clean-table th { 
        background: #F1F5F9; 
        color: #334155; 
        font-weight: 800; 
        font-size: 0.85rem; 
        text-transform: uppercase; 
        padding: 14px 16px; 
        text-align: left; 
        border: 1px solid var(--border-light); 
    }
    .clean-table td { 
        padding: 14px 16px; 
        border: 1px solid var(--border-light); 
        vertical-align: middle; 
        font-size: 0.9rem; 
        font-weight: 500;
    }
    .clean-table tbody tr:nth-child(even) {
        background-color: #F8FAFC; 
    }
    
    .clickable-row { cursor: pointer; transition: background-color 0.15s ease-in-out; }
    .clickable-row:hover td { background-color: #E2E8F0 !important; }

    /* Priority Styling for Client Details Box */
    .priority-box {
        padding: 10px 14px;
        border-radius: 6px;
        transition: all 0.2s ease;
    }
    .priority-box-hot {
        background-color: #fbc2c2; 
        border-left: 4px solid #EF4444; 
    }
    .priority-box-hot .company-name { color: #991B1B !important; } 
    
    .priority-box-cold {
        background-color: #FFFFFF;
        border: 1px solid #E2E8F0;
        box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05); 
    }
    
    .priority-box-normal {
        background-color: #a8dcff; 
        border-left: 4px solid #0EA5E9; 
    }
    .priority-box-normal .company-name { color: #075985 !important; } 

    .priority-box-default { background-color: transparent; }

    /* Modern Badges (Statuses) */
    .status-badge { 
        padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; 
        display: inline-flex; align-items: center; border: 1px solid transparent; 
    }
    .status-new { background: #EFF6FF; color: #2563EB; border-color: #DBEAFE; }
    .status-followup { background: #FFF7ED; color: #EA580C; border-color: #FFEDD5; }
    .status-won { background: #ECFDF5; color: #059669; border-color: #D1FAE5; }
    .status-lost { background: #FEF2F2; color: #DC2626; border-color: #FEE2E2; }
    .status-default { background: #F8FAFC; color: #64748B; border-color: #E2E8F0; }
    .status-calling { background: #F3E8FF; color: #7E22CE; border-color: #E9D5FF; } 
    .status-meeting { background: #FEF3C7; color: #B45309; border-color: #FDE68A; } 

    /* Buttons */
    .btn-brand { 
        background: var(--brand-primary); color: #fff; border: none; border-radius: 8px; 
        padding: 8px 18px; font-weight: 600; font-size: 0.9rem;
    }

    .pagination .page-link { color: var(--text-main); border: 1px solid #E2E8F0; margin: 0 3px; border-radius: 6px; padding: 6px 12px; font-size: 0.9rem;}
    .pagination .page-item.active .page-link { background-color: var(--brand-primary); border-color: var(--brand-primary); color: #fff; }
</style>

<div class="crm-wrapper">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title m-0">Performance Overview</h1>
        <button class="btn-brand"><i class="fa-solid fa-cloud-arrow-down me-2"></i>Export Data</button>
    </div>

    <?php if(!isset($_GET['hide_filter']) || $_GET['hide_filter'] != '1'): ?>
    <div class="new-gen-card p-4 mb-4">
        <form method="GET" action="" id="filterForm" class="m-0">
            <input type="hidden" name="limit" value="<?= $limit ?>">
            <input type="hidden" name="page" value="1"> 
            
            <div class="row g-3 align-items-end">
                
                <?php if($is_admin): ?>
                <div class="<?= $user_col_class ?>">
                    <label class="form-label text-muted small fw-bold mb-2">Executive / User</label>
                    <select name="user" id="userDropdown" class="form-select clean-input">
                        <option value="">All</option>
                        <?php if($users_dropdown_query) { while($u = mysqli_fetch_assoc($users_dropdown_query)): ?>
                            <option value="<?= htmlspecialchars($u['lead_by']) ?>" <?= isset($_GET['user']) && $_GET['user'] == $u['lead_by'] ? 'selected' : '' ?>><?= htmlspecialchars($u['lead_by']) ?></option>
                        <?php endwhile; } ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="<?= $date_col_class ?>">
                    <label class="form-label text-muted small fw-bold mb-2">Date Range</label>
                    <div class="input-group">
                        <input type="date" name="date_from" class="form-control clean-input" value="<?= htmlspecialchars($filter_date_from) ?>" style="border-radius: 8px 0 0 8px; border-right: none;">
                        <span class="input-group-text bg-light text-muted border-top border-bottom" style="border-left: none; border-right: none; font-size: 0.9rem;">to</span>
                        <input type="date" name="date_to" class="form-control clean-input" value="<?= htmlspecialchars($filter_date_to) ?>" style="border-radius: 0 8px 8px 0; border-left: none;">
                    </div>
                </div>

                <div class="<?= $status_col_class ?>">
                    <label class="form-label text-muted small fw-bold mb-2">Lead Status</label>
                    <select name="status" class="form-select clean-input">
                        <option value="">All Statuses</option>
                        <?php foreach($all_statuses as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>" <?= $filter_status == $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="<?= $priority_col_class ?>">
                    <label class="form-label text-muted small fw-bold mb-2">Lead Priority</label>
                    <select name="priority" class="form-select clean-input">
                        <option value="">All Priorities</option>
                        <option value="Hot" <?= $filter_priority == 'Hot' ? 'selected' : '' ?>>Hot</option>
                        <option value="Cold" <?= $filter_priority == 'Cold' ? 'selected' : '' ?>>Cold</option>
                        <option value="Normal" <?= $filter_priority == 'Normal' ? 'selected' : '' ?>>Normal</option>
                    </select>
                </div>

                <div class="<?= $btn_col_class ?> d-flex gap-2">
                    <button type="submit" class="btn btn-dark w-100 fw-bold" style="border-radius: 8px; padding: 10px; background: var(--text-main);">Apply</button>
                    <?php if(($is_admin && isset($_GET['user']) && $_GET['user'] != '') || $filter_status || $filter_priority || $filter_date_from || $filter_date_to): ?>
                        <a href="?" class="btn btn-light border d-flex justify-content-center align-items-center text-danger" style="border-radius: 8px; width: 45px;" title="Clear Filters"><i class="fa-solid fa-eraser"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if($filter_user != ''): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="new-gen-card p-3 d-flex align-items-center" style="border-left: 5px solid #EF4444;">
                <div class="rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 48px; height: 48px; background: #FEF2F2; color: #EF4444; font-size: 1.2rem;">
                    <i class="fa-solid fa-fire"></i>
                </div>
                <div>
                    <h6 class="m-0 text-muted small fw-bold text-uppercase">Hot Leads</h6>
                    <h3 class="m-0 fw-bold" style="color: #991B1B;"><?= $hot_count ?></h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="new-gen-card p-3 d-flex align-items-center" style="border-left: 5px solid #0EA5E9;">
                <div class="rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 48px; height: 48px; background: #F0F9FF; color: #0EA5E9; font-size: 1.2rem;">
                    <i class="fa-solid fa-snowflake"></i>
                </div>
                <div>
                    <h6 class="m-0 text-muted small fw-bold text-uppercase">Cold Leads</h6>
                    <h3 class="m-0 fw-bold" style="color: #075985;"><?= $cold_count ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="new-gen-card p-3 d-flex align-items-center" style="border-left: 5px solid #10B981;">
                <div class="rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 48px; height: 48px; background: #ECFDF5; color: #10B981; font-size: 1.2rem;">
                    <i class="fa-solid fa-check-circle"></i>
                </div>
                <div>
                    <h6 class="m-0 text-muted small fw-bold text-uppercase">Normal Leads</h6>
                    <h3 class="m-0 fw-bold" style="color: #065F46;"><?= $normal_count ?></h3>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-0 mb-4">
        <div class="col-lg-12" id="leadsTable">
            <div class="new-gen-card overflow-hidden h-100">
                <div class="p-3 border-bottom bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center ms-2">
                        <h6 class="m-0 fw-bold text-dark" style="font-size: 1.05rem;">
                            <?php 
                                if(!$is_admin) {
                                    echo "My Leads";
                                } else {
                                    echo ($filter_user != '') ? "Leads for " . htmlspecialchars($filter_user) : "Master Leads Directory";
                                }
                            ?>
                        </h6>
                        <span class="badge bg-light text-secondary border fw-bold px-3 py-1 ms-3" style="border-radius: 6px; font-size: 0.8rem;">
                            Total: <?= number_format($total_leads) ?>
                        </span>
                    </div>

                    <div class="d-flex align-items-center gap-2 me-2">
                        <label class="text-muted fw-bold mb-0" style="font-size: 0.85rem;">Show</label>
                        <select class="form-select form-select-sm border-light bg-light fw-bold" style="border-radius: 6px; width: 80px; padding: 6px 10px; font-size: 0.85rem;" onchange="changeLimit(this.value)">
                            <?php foreach($allowed_limits as $lim): ?>
                                <option value="<?= $lim ?>" <?= $limit == $lim ? 'selected' : '' ?>><?= $lim ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="table-container bg-white">
                    <table class="clean-table mb-0">
                        <thead style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th style="width: 50px; text-align: center;">#</th>
                                <th style="width: 140px;">Lead Date</th>
                                <th style="width: 320px;">Client Details</th> 
                                <th style="width: 130px; text-align: center;">Status</th>
                                <th>Last Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($leads_list_query && mysqli_num_rows($leads_list_query) > 0): 
                                $sr_no = $offset + 1; 
                                while($lead = mysqli_fetch_assoc($leads_list_query)): 
                                    $l_badgeClass = getBadgeClass($lead['lead_status'] ?? '');
                                    
                                    $company = $lead['company_name'] ?? '-';
                                    $contact = $lead['contact_person'] ?? '-';
                                    $phone = $lead['contact_number'] ?? '-';
                                    $priority = $lead['lead_priority'] ?? '';  
                            ?>
                                <tr class="clickable-row" onclick="window.open('lead-update.php?id=<?= $lead['id'] ?>', '_blank')" title="Click to view/update lead">
                                    <td class="text-center text-muted fw-bold" style="font-size: 0.85rem;">
                                        <?= $sr_no++ ?>
                                    </td>
                                    
                                    <td>
                                        <?php 
                                        if(!empty($lead['created_at'])) {
                                            $dt = new DateTime($lead['created_at']); 
                                            $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                        ?>
                                            <div class="fw-bold text-dark m-0" style="font-size: 0.9rem;"><?= $dt->format("d M, Y") ?></div>
                                            <div class="text-muted mt-1" style="font-size: 0.8rem;"><i class="fa-regular fa-clock me-1"></i> <?= $dt->format("h:i A") ?></div>
                                        <?php } else { echo '-'; } ?>
                                    </td>

                                    <td>
                                        <div class="priority-box <?= getPriorityStyleClass($priority) ?>">
                                            <div class="fw-bold text-dark m-0 company-name" style="font-size: 0.95rem; line-height: 1.3;">
                                                <?= htmlspecialchars($company) ?>
                                            </div>
                                            <div class="text-muted mt-2" style="font-size: 0.85rem;">
                                                <span class="d-inline-block me-3"><i class="fa-regular fa-user me-1"></i> <?= htmlspecialchars($contact) ?></span>
                                                <span class="d-inline-block mt-1 mt-md-0"><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars($phone) ?></span>
                                            </div>
                                            <?php if(!empty($priority)): ?>
                                                <div class="mt-2" style="font-size: 0.65rem; text-transform: uppercase; font-weight: 800; opacity: 0.7; letter-spacing: 0.5px;">
                                                    <i class="fa-solid fa-circle me-1" style="font-size: 0.5rem;"></i>PRIORITY: <?= htmlspecialchars($priority) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td class="text-center">
                                        <span class="status-badge <?= $l_badgeClass ?>"><?= htmlspecialchars($lead['lead_status'] ?? 'N/A') ?></span>
                                    </td>

                                    <td>
                                        <?php if(!empty($lead['last_history_note'])): ?>
                                            <div class="text-dark fw-medium m-0" style="font-size: 0.9rem; white-space: normal; word-break: break-word; line-height: 1.5;">
                                                <?= nl2br(htmlspecialchars($lead['last_history_note'])) ?>
                                            </div>
                                            <div class="text-muted mt-2" style="font-size: 0.8rem;">
                                                <i class="fa-solid fa-pen-clip me-1"></i> <?= htmlspecialchars($lead['last_updated_by'] ?? 'System') ?> 
                                                <?php if(!empty($lead['last_history_date'])): ?>
                                                    | <?= date('d M, Y - h:i A', strtotime($lead['last_history_date'])) ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.85rem;">No updates yet</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <?php if(isset($session_name) && $session_name == 'UNKNOWN_USER'): ?>
                                            <h6 class="fw-bold text-danger m-0" style="font-size: 0.95rem;">
                                                <i class="fa-solid fa-triangle-exclamation me-2"></i> Session Error: Could not find user name in session. Please check your login script.
                                            </h6>
                                        <?php else: ?>
                                            <h6 class="fw-bold text-muted m-0" style="font-size: 0.95rem;">
                                                <i class="fa-solid fa-folder-open me-2"></i>No records found
                                            </h6>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if($total_pages > 1): ?>
                <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <span class="text-muted fw-medium ms-2" style="font-size: 0.9rem;">
                        Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_leads) ?> of <?= $total_leads ?>
                    </span>
                    <nav aria-label="Page navigation" class="me-2">
                        <ul class="pagination pagination-sm m-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= ($page > 1) ? buildUrl($page - 1) : '#' ?>"><i class="fa-solid fa-chevron-left"></i></a>
                            </li>

                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if($start_page > 1) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                            
                            for($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= buildUrl($i) ?>"><?= $i ?></a>
                                </li>
                            <?php 
                            endfor; 
                            
                            if($end_page < $total_pages) {
                                echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                            }
                            ?>

                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= ($page < $total_pages) ? buildUrl($page + 1) : '#' ?>"><i class="fa-solid fa-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
function changeLimit(newLimit) {
    let url = new URL(window.location.href);
    url.searchParams.set('limit', newLimit);
    url.searchParams.set('page', 1); 
    window.location.href = url.href;
}

// Form submit hone par new tab open karne ke liye (SIRF ADMIN KE LIYE)
document.getElementById('filterForm').addEventListener('submit', function(e) {
    let userElem = document.getElementById('userDropdown');
    let userValue = userElem ? userElem.value : "";
    let existingHidden = document.getElementById('hideFilterInput');
    
    let isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;

    // Naya Tab Sirf tabhi khulega jab ADMIN ne dropdown se koi user select kiya ho
    if (isAdmin && userElem && userValue !== "") {
        this.target = "_blank"; 
        
        if (!existingHidden) {
            let hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'hide_filter';
            hiddenInput.value = '1';
            hiddenInput.id = 'hideFilterInput';
            this.appendChild(hiddenInput);
        }
        
        setTimeout(() => {
            let hInput = document.getElementById('hideFilterInput');
            if(hInput) hInput.remove();
        }, 100);

    } else {
        // Normal user ke liye ya admin ke "All" ke liye same tab rahega
        this.target = "_self";  
        if (existingHidden) {
            existingHidden.remove();
        }
    }
});
</script>

<?php include 'include/footer.php'; ?>