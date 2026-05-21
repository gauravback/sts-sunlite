<?php
// ERROR REPORTING ON (Testing ke liye)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Auth & Database include (Session auth.php handle kar lega ab)
require_once 'config/auth.php';
require_once 'config/database.php';

// Timezone
date_default_timezone_set('Asia/Kolkata');

// User Details from Session
$logged_in_role = $_SESSION['role'] ?? 'user'; 
$logged_in_user = $_SESSION['name'] ?? $_SESSION['username'] ?? $_SESSION['user_name'] ?? '';
$is_admin = (strtolower($logged_in_role) === 'admin');

// =================== FETCH USERS FOR ADMIN DROPDOWN ===================
$users_list = [];
if ($is_admin) {
    $u_query = mysqli_query($conn, "SELECT name FROM users ORDER BY name ASC");
    if ($u_query && mysqli_num_rows($u_query) > 0) {
        while ($u = mysqli_fetch_assoc($u_query)) {
            if(!empty($u['name'])) {
                $users_list[] = $u['name'];
            }
        }
    }
}

// =================== FILTER & SECURITY LOGIC ===================
$where_clauses = ["1=1"]; 

// --- 1. ROLE BASED ACCESS CONTROL (RBAC) ---
if (!$is_admin) {
    $safe_logged_user = mysqli_real_escape_string($conn, $logged_in_user);
    $where_clauses[] = "(l.followed_by = '$safe_logged_user' OR c.followed_by = '$safe_logged_user')";
} 
elseif ($is_admin && !empty($_GET['user_filter'])) {
    $selected_user = mysqli_real_escape_string($conn, trim($_GET['user_filter']));
    $where_clauses[] = "(l.followed_by = '$selected_user' OR c.followed_by = '$selected_user')";
}

// --- 2. Search by Company Name, Contact Person, or Department ---
if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, trim($_GET['search']));
    $where_clauses[] = "(
        l.company_name LIKE '%$search%' OR 
        c.company_name LIKE '%$search%' OR 
        l.department LIKE '%$search%' OR 
        l.contact_person LIKE '%$search%' OR 
        c.customer_name LIKE '%$search%'
    )";
}

// --- 3. Start Date Filter ---
if (!empty($_GET['start_date'])) {
    $start_date = mysqli_real_escape_string($conn, $_GET['start_date']);
    $where_clauses[] = "DATE(s.sale_date) >= '$start_date'";
}

// --- 4. End Date Filter ---
if (!empty($_GET['end_date'])) {
    $end_date = mysqli_real_escape_string($conn, $_GET['end_date']);
    $where_clauses[] = "DATE(s.sale_date) <= '$end_date'";
}

$where_sql = implode(" AND ", $where_clauses);

// =================== SMART FETCH DATA QUERY (UNION ALL WITH CAST) ===================
// FIX: Added CAST() to prevent "Illegal mix of collations" error
$query = "
    SELECT 
        s.sale_id,
        s.invoice_no,
        s.sale_date,
        s.amount,
        s.entity_type,
        s.entity_id,
        s.sale_category,
        COALESCE(l.company_name, c.company_name) AS company_name,
        COALESCE(l.contact_person, c.customer_name) AS contact_person,
        COALESCE(l.department, '') AS department,
        COALESCE(l.followed_by, c.followed_by) AS sales_person
    FROM (
        -- NORMAL SALES
        SELECT 
            id AS sale_id, 
            CAST(invoice_no AS CHAR) AS invoice_no, 
            CAST(sale_date AS CHAR) AS sale_date, 
            amount, 
            CAST(entity_type AS CHAR) AS entity_type, 
            entity_id, 
            CAST('Normal' AS CHAR) AS sale_category 
        FROM sales_entries
        
        UNION ALL
        
        -- GOV SALES
        SELECT 
            id AS sale_id, 
            CAST(gem_contact_no AS CHAR) AS invoice_no, 
            CAST(contract_date AS CHAR) AS sale_date, 
            amount, 
            CAST(entity_type AS CHAR) AS entity_type, 
            entity_id, 
            CAST('Government' AS CHAR) AS sale_category 
        FROM gov_sales_entries
    ) AS s
    LEFT JOIN leads l ON s.entity_type = 'lead' AND s.entity_id = l.id
    LEFT JOIN customers c ON s.entity_type = 'customer' AND s.entity_id = c.id
    WHERE $where_sql
    ORDER BY s.sale_date DESC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("<div style='padding:20px; background:#ffebee; color:#e53935; border:1px solid #ef5350; margin:20px; border-radius:8px;'>
            <strong>SQL Error:</strong> " . mysqli_error($conn) . "
         </div>");
}

$total_sales_count = 0;
$total_revenue = 0;
$sales_data = [];

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $sales_data[] = $row;
        $total_sales_count++;
        $total_revenue += floatval($row['amount']);
    }
}

include 'include/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    body { background-color: #f4f7fe; font-family: 'Plus Jakarta Sans', sans-serif; color: #1b2559; }
    .page-container { max-width: 1400px; margin: 0 auto; padding: 2rem 1rem; }
    
    .stat-card {
        background: #fff; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        display: flex; align-items: center; border: 1px solid #e0e5f2; transition: transform 0.3s;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-icon { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-right: 20px; }
    .icon-blue { background: rgba(67, 24, 255, 0.1); color: #4318ff; }
    .icon-green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .stat-title { color: #a3aed0; font-size: 0.9rem; font-weight: 600; margin-bottom: 5px; }
    .stat-value { color: #1b2559; font-size: 1.8rem; font-weight: 700; margin: 0; }

    .glass-card { 
        background: #ffffff; border-radius: 20px; box-shadow: 0px 20px 50px rgba(0,0,0,0.04); 
        padding: 25px; margin-bottom: 25px; border: 1px solid #e0e5f2;
    }
    
    .custom-label { font-size: 0.85rem; font-weight: 700; margin-bottom: 8px; display: block; color: #1b2559;}
    .input-group-custom { position: relative; }
    .input-group-custom i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #a3aed0; z-index: 10; pointer-events: none;}
    .custom-input { border: 1px solid #e0e5f2; border-radius: 12px; padding: 12px 15px 12px 45px; background: #f8fafc; width: 100%; font-size: 0.95rem; outline: none; transition: 0.3s; }
    .custom-input:focus { border-color: #4318ff; background: #fff; box-shadow: 0 0 0 4px rgba(67,24,255,0.1); }
    select.custom-input { appearance: auto; }
    
    .table-custom { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-custom th { color: #a3aed0; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; padding: 15px 20px; border-bottom: 1px solid #e0e5f2; text-align: left; }
    .table-custom td { padding: 18px 20px; border-bottom: 1px solid #f4f7fe; font-size: 0.95rem; color: #1b2559; font-weight: 500; vertical-align: middle; }
    .table-custom tbody tr { transition: background 0.2s; }
    .table-custom tbody tr:hover { background: #f8fafc; }
    
    .company-title { font-weight: 700; color: #1b2559; margin-bottom: 2px; }
    .contact-sub { font-size: 0.8rem; color: #64748b; }
    
    .badge-lead { background: rgba(67, 24, 255, 0.1); color: #4318ff; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
    .badge-customer { background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
    
    .amount-text { font-size: 1.1rem; font-weight: 700; color: #10b981; }

    .btn-action { background: #f4f7fe; color: #4318ff; padding: 8px 16px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; border: 1px solid transparent; display: inline-block; }
    .btn-action:hover { background: #4318ff; color: #fff; box-shadow: 0 4px 10px rgba(67, 24, 255, 0.2); }
</style>

<div class="page-container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0 text-dark">Sales Directory</h2>
            <p class="text-muted m-0">View and manage all closed deals and invoices (Including GeM).</p>
        </div>
        <a href="search-sale.php" class="btn" style="background:#1b2559; color:#fff; border-radius:12px; padding:12px 25px; font-weight:600; box-shadow:0 10px 20px rgba(27, 37, 89, 0.2);">
            <i class="ti ti-plus me-2"></i> Log New Sale
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="ti ti-receipt-2"></i>
                </div>
                <div>
                    <div class="stat-title">
                        <?php 
                            if ($is_admin && !empty($_GET['user_filter'])) {
                                echo htmlspecialchars($_GET['user_filter']) . "'s Deals";
                            } else {
                                echo $is_admin ? 'Total Deals Closed' : 'My Deals Closed';
                            }
                        ?>
                    </div>
                    <h3 class="stat-value"><?= number_format($total_sales_count) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="ti ti-currency-rupee"></i>
                </div>
                <div>
                    <div class="stat-title">
                        <?php 
                            if ($is_admin && !empty($_GET['user_filter'])) {
                                echo htmlspecialchars($_GET['user_filter']) . "'s Revenue";
                            } else {
                                echo $is_admin ? 'Total Sales Revenue' : 'My Sales Revenue';
                            }
                        ?>
                    </div>
                    <h3 class="stat-value">₹<?= number_format($total_revenue, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <form method="GET" action="">
            <div class="row g-3 align-items-end">
                
                <div class="<?= $is_admin ? 'col-md-3' : 'col-md-4' ?>">
                    <label class="custom-label">Search Company / Dept</label>
                    <div class="input-group-custom">
                        <i class="ti ti-search"></i>
                        <input type="text" name="search" class="custom-input" placeholder="Keyword..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                </div>

                <?php if ($is_admin): ?>
                <div class="col-md-3">
                    <label class="custom-label">Filter by Executive</label>
                    <div class="input-group-custom">
                        <i class="ti ti-user"></i>
                        <select name="user_filter" class="custom-input">
                            <option value="">All Executives</option>
                            <?php foreach($users_list as $u_name): ?>
                                <?php $selected = (isset($_GET['user_filter']) && $_GET['user_filter'] == $u_name) ? 'selected' : ''; ?>
                                <option value="<?= htmlspecialchars($u_name) ?>" <?= $selected ?>><?= htmlspecialchars($u_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>

                <div class="<?= $is_admin ? 'col-md-2' : 'col-md-3' ?>">
                    <label class="custom-label">Start Date</label>
                    <input type="date" name="start_date" class="custom-input" style="padding-left:15px;" value="<?= htmlspecialchars($_GET['start_date'] ?? '') ?>">
                </div>
                
                <div class="<?= $is_admin ? 'col-md-2' : 'col-md-3' ?>">
                    <label class="custom-label">End Date</label>
                    <input type="date" name="end_date" class="custom-input" style="padding-left:15px;" value="<?= htmlspecialchars($_GET['end_date'] ?? '') ?>">
                </div>
                
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn" style="background:#4318ff; color:#fff; border-radius:12px; padding:12px; width:100%; font-weight:600; border:none;">
                        <i class="ti ti-filter"></i> Filter
                    </button>
                    <a href="sales-list.php" class="btn btn-light d-flex align-items-center justify-content-center" style="border-radius:12px; padding:12px; border: 1px solid #e0e5f2;" title="Clear Filters">
                        <i class="ti ti-refresh" style="font-size:1.2rem; color:#64748b;"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="glass-card" style="padding: 0; overflow: hidden;">
        <div class="table-responsive">
            <table class="table-custom">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th>Invoice / GeM No</th>
                        <th>Sale Date</th>
                        <th>Company Details</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Closed By (Salesperson)</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($sales_data) > 0): ?>
                        <?php foreach ($sales_data as $sale): 
                            $link = ($sale['entity_type'] == 'lead') ? "lead-update.php?id=" . $sale['entity_id'] : "customer-details.php?id=" . $sale['entity_id'];
                            $badge = ($sale['entity_type'] == 'lead') ? 'badge-lead' : 'badge-customer';
                            
                            $comp_name = !empty($sale['company_name']) ? htmlspecialchars($sale['company_name']) : 'Unknown';
                            $cont_person = !empty($sale['contact_person']) ? htmlspecialchars($sale['contact_person']) : 'N/A';
                            $department = !empty($sale['department']) ? htmlspecialchars($sale['department']) : '';
                            
                            $sp_name = !empty($sale['sales_person']) ? htmlspecialchars($sale['sales_person']) : 'Unassigned';
                            $sp_initial = strtoupper(substr($sp_name, 0, 1));
                            $is_gov = ($sale['sale_category'] == 'Government');
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: #1b2559;">
                                        <?php if($is_gov): ?>
                                            <span class="badge bg-warning text-dark me-1" style="font-size:0.65rem; border-radius: 4px;">GeM</span>
                                        <?php else: ?>
                                            <i class="ti ti-file-invoice text-muted me-1"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($sale['invoice_no'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: #334155;">
                                        <?= !empty($sale['sale_date']) ? date('d M Y', strtotime($sale['sale_date'])) : '-' ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="company-title"><?= $comp_name ?></div>
                                    <div class="contact-sub">
                                        <i class="ti ti-user me-1"></i> <?= $cont_person ?> 
                                        <?= !empty($department) ? " | <i class='ti ti-briefcase ms-1 me-1'></i>" . $department : "" ?>
                                    </div>
                                </td>
                                <td><span class="<?= $badge ?>"><?= ucfirst($sale['entity_type']) ?></span></td>
                                <td class="amount-text">₹<?= number_format($sale['amount'], 2) ?></td>
                                
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="width:32px; height:32px; border-radius:50%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; font-size:0.85rem; font-weight:800; color:#475569; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                                            <?= $sp_initial ?>
                                        </div>
                                        <span style="font-size:0.9rem; font-weight: 600; color: #1b2559;"><?= $sp_name ?></span>
                                    </div>
                                </td>
                                
                                <td class="text-end">
                                    <a href="<?= $link ?>" class="btn-action"><i class="ti ti-external-link"></i> View Deal</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto;">
                                    <i class="ti ti-receipt-off text-muted" style="font-size: 2.5rem;"></i>
                                </div>
                                <h5 class="m-0 text-dark fw-bold">No sales records found</h5>
                                <p class="text-muted mt-2">Try adjusting your filters or search terms.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include 'include/footer.php'; ?>