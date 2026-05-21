<?php
// Session aur Database file sabse pehle call karenge
require_once 'config/auth.php';
require_once 'config/database.php';

// USER ROLE SAFE CHECK
$role = $_SESSION['role'] ?? 'user'; 

$current_user = $_SESSION['admin_name'] ?? $_SESSION['username'] ?? $_SESSION['name'] ?? ''; 

// ---------------- NEW: SERVER-SIDE SEARCH LOGIC ----------------
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$search_sql = "";

if (!empty($search_query)) {
    // Hum company name, contact person, ya contact number mein search kar rahe hain
    $search_sql = " AND (company_name LIKE '%$search_query%' OR contact_person LIKE '%$search_query%' OR contact_number LIKE '%$search_query%')";
}

// --- LOGIC: User Filter ---
if ($role === 'admin') {
    $where_sql = "WHERE 1=1" . $search_sql; // Admin sees all + search
} else {
    $where_sql = "WHERE lead_by LIKE '%$current_user%'" . $search_sql; // User sees own + search
}

// ---------------- EXPORT LOGIC (ADMIN ONLY) ----------------
if (isset($_GET['export']) && $role === 'admin') {
    $export_type = $_GET['export'];
    $filename = "leads_export_" . date('Y-m-d') . ($export_type === 'csv' ? '.csv' : '.xls');
    
    if ($export_type === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
    } else {
        header('Content-Type: application/vnd.ms-excel');
    }
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    $delimiter = ($export_type === 'csv') ? ',' : "\t";
    fputcsv($output, ['ID', 'Company Name', 'Department', 'Contact Person', 'Contact Number', 'Location', 'Category', 'Type', 'Date Added', 'Created By'], $delimiter);
    
    $export_query = $conn->query("SELECT * FROM leads $where_sql ORDER BY id DESC");
    while ($row = $export_query->fetch_assoc()) {
        fputcsv($output, [$row['id'], $row['company_name'], $row['department'], $row['contact_person'], $row['contact_number'], $row['location'], $row['lead_type'], $row['customer_type'], date("d M Y", strtotime($row['created_at'])), $row['lead_by'] ?? ''], $delimiter);
    }
    fclose($output);
    exit(); 
}

include 'include/header.php';

// ---------------- PAGINATION SETTINGS ----------------
$allowed_limits = [40, 100, 200, 300, 500, 2000, 5000];
$records_per_page = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowed_limits) ? (int)$_GET['limit'] : 40;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// URL parameter string for pagination links (taaki search na hate next page par)
$url_params = "&limit=$records_per_page";
if (!empty($search_query)) {
    $url_params .= "&search=" . urlencode($search_query);
}

// ---------------- TOTAL RECORD COUNT ----------------
$total_query = $conn->query("SELECT COUNT(*) AS total FROM leads $where_sql");
$total_row = $total_query->fetch_assoc();
$total_records = $total_row['total'] ?? 0;
$total_pages = ceil($total_records / $records_per_page);

// ---------------- FETCH LEADS ----------------
$sql = "SELECT * FROM leads $where_sql ORDER BY id DESC LIMIT $offset, $records_per_page";
$result = $conn->query($sql);

function getAvatarColor($char) {
    $colors = ['#ef4444', '#f97316', '#f59e0b', '#84cc16', '#10b981', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#d946ef', '#f43f5e'];
    $index = ord(strtoupper($char)) % count($colors);
    return $colors[$index];
}
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
/* =====================================
   ULTRA-PREMIUM SAAS UI CSS
   ===================================== */
:root {
    --primary: #4f46e5;
    --primary-hover: #4338ca;
    --primary-light: #eef2ff;
    --danger: #ef4444;
    --bg-body: #f8fafc;
    --bg-surface: #ffffff;
    --text-main: #0f172a;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --radius-xl: 16px;
    --radius-lg: 12px;
    --shadow-soft: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
}

body { 
    background-color: var(--bg-body); 
    font-family: 'Plus Jakarta Sans', sans-serif; 
    color: var(--text-main); 
}

.page-wrapper { padding: 2rem 1.5rem; min-height: 100vh; }

/* Main Card */
.admin-card { 
    border: 1px solid rgba(226, 232, 240, 0.8); 
    border-radius: var(--radius-xl); 
    background: var(--bg-surface); 
    box-shadow: var(--shadow-soft); 
    overflow: hidden; 
    transition: all 0.3s ease;
}

/* Header & Typography */
.page-title { font-weight: 700; font-size: 1.5rem; letter-spacing: -0.02em; color: var(--text-main); }
.page-subtitle { color: var(--text-muted); font-size: 0.9rem; margin-top: 4px; }

/* Buttons */
.btn-modern { 
    padding: 0.6rem 1.2rem; border-radius: 10px; font-weight: 600; font-size: 0.875rem; 
    display: inline-flex; align-items: center; gap: 8px; text-decoration: none; 
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid transparent; 
}
.btn-primary-modern { 
    background: var(--primary); color: white; 
    box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25); 
}
.btn-primary-modern:hover { 
    background: var(--primary-hover); transform: translateY(-1px); color: white; 
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); 
}

.btn-export-csv { background: var(--bg-surface); color: var(--text-main); border-color: var(--border-color); }
.btn-export-csv:hover { background: #f1f5f9; border-color: #cbd5e1; }
.btn-export-excel { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
.btn-export-excel:hover { background: #dcfce7; }

/* Search Bar */
.search-wrapper { position: relative; margin: 0; }
.search-wrapper .ti-search { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1.1rem; }
.search-input { 
    padding: 0.6rem 1rem 0.6rem 2.8rem; border: 1px solid var(--border-color); 
    border-radius: 10px; font-size: 0.9rem; width: 260px; 
    transition: all 0.2s ease; background-color: var(--bg-body); color: var(--text-main);
}
.search-input:focus { background-color: #fff; outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
.search-input::placeholder { color: #94a3b8; font-weight: 500; }

/* Table Design */
.table-responsive::-webkit-scrollbar { width: 6px; height: 6px; }
.table-responsive::-webkit-scrollbar-track { background: #f8fafc; }
.table-responsive::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.table-modern { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 0; }
.table-modern thead th { 
    background: #f8fafc; color: var(--text-muted); font-size: 0.75rem; 
    font-weight: 700; text-transform: uppercase; padding: 1.2rem 1.5rem; 
    border-bottom: 1px solid var(--border-color); letter-spacing: 0.05em; white-space: nowrap;
}
.table-modern tbody tr { transition: all 0.2s ease; }
.table-modern tbody tr:hover td { background-color: #f8fafc; }
.table-modern tbody td { padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; vertical-align: middle; }
.table-modern tbody tr:last-child td { border-bottom: none; }

/* Company Info & Avatar */
.company-link { text-decoration: none; color: inherit; display: block; }
.company-name { font-weight: 600; color: var(--text-main); transition: color 0.2s; font-size: 0.95rem; }
.company-link:hover .company-name { color: var(--primary); }
.company-cell { display: flex; align-items: center; gap: 14px; }
.company-avatar { 
    width: 44px; height: 44px; border-radius: 12px; color: white; 
    display: flex; align-items: center; justify-content: center; 
    font-weight: 700; font-size: 1.1rem; flex-shrink: 0; 
    box-shadow: inset 0 0 0 1px rgba(0,0,0,0.1);
}

/* Contact Details & Copy */
.contact-person { font-weight: 600; color: var(--text-main); display: block; margin-bottom: 2px; }
.contact-phone { display: flex; align-items: center; gap: 6px; color: var(--text-muted); font-size: 0.85rem; font-weight: 500; }
.copy-btn { cursor: pointer; color: #cbd5e1; transition: color 0.2s; padding: 2px; border-radius: 4px; }
.copy-btn:hover { color: var(--primary); background: var(--primary-light); }
.copy-success { color: #10b981 !important; }

/* Badges */
.badge-soft { 
    padding: 6px 12px; font-size: 0.75rem; font-weight: 700; border-radius: 8px; 
    display: inline-flex; align-items: center; gap: 6px; 
    background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; letter-spacing: 0.3px;
}
.badge-new { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
.badge-existing { background: var(--primary-light); color: var(--primary-hover); border-color: #c7d2fe; }
.badge-dealer { background: #fef9c3; color: #a16207; border-color: #fef08a; }
.badge-distributor { background: #ffedd5; color: #c2410c; border-color: #fed7aa; }

/* Action Buttons */
.action-group { display: flex; gap: 8px; justify-content: flex-end; }
.action-btn { 
    width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; 
    border-radius: 10px; color: #64748b; text-decoration: none; transition: all 0.2s ease; 
    background: #f8fafc; border: 1px solid #e2e8f0; font-size: 1.1rem;
}
.action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.view-btn:hover { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
.edit-btn:hover { background: var(--primary-light); color: var(--primary); border-color: #c7d2fe; }
.delete-btn:hover { background: #fef2f2; color: var(--danger); border-color: #fecaca; }

/* Pagination & Limits */
.pagination-container { 
    padding: 1.2rem 1.5rem; display: flex; justify-content: space-between; align-items: center; 
    background-color: var(--bg-surface); border-top: 1px solid var(--border-color); flex-wrap: wrap; gap: 1rem; 
}
.pagination-info { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }
.pagination-modern { display: flex; list-style: none; padding: 0; margin: 0; gap: 6px; flex-wrap: wrap; }
.pagination-modern a, .pagination-modern span { 
    padding: 6px 14px; border: 1px solid var(--border-color); border-radius: 8px; 
    color: var(--text-muted); text-decoration: none; font-size: 0.875rem; font-weight: 600; transition: all 0.2s; 
}
.pagination-modern a:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
.pagination-modern .active a { background: var(--text-main); color: white; border-color: var(--text-main); pointer-events: none; }
.pagination-modern .disabled a { opacity: 0.4; pointer-events: none; background: #f8fafc; }
.limit-select { 
    padding: 0.4rem 2rem 0.4rem 1rem; font-size: 0.875rem; font-weight: 500; color: var(--text-main); 
    border: 1px solid var(--border-color); border-radius: 8px; background-color: #fff; cursor: pointer; outline: none; transition: all 0.2s;
}
.limit-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
</style>

<div class=" container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="page-title mb-0">Leads Directory</h1>
            <p class="page-subtitle mb-0">Manage, track, and interact with your customer leads</p>
        </div>
        
        <div class="d-flex gap-3 align-items-center flex-wrap">
            <form method="GET" class="search-wrapper d-none d-md-block">
                <i class="ti ti-search"></i>
                <input type="text" name="search" class="search-input" placeholder="Search overall & hit Enter..." value="<?= htmlspecialchars($search_query) ?>">
                <?php if(isset($_GET['limit'])): ?>
                    <input type="hidden" name="limit" value="<?= htmlspecialchars($_GET['limit']) ?>">
                <?php endif; ?>
            </form>
            
            <?php if($role === 'admin'): ?>
                <div class="d-flex gap-2">
                    <a href="?export=csv<?= $url_params ?>" class="btn-modern btn-export-csv" title="Download CSV">
                        <i class="ti ti-file-text"></i> CSV
                    </a>
                    <a href="?export=excel<?= $url_params ?>" class="btn-modern btn-export-excel" title="Download Excel">
                        <i class="ti ti-file-spreadsheet"></i> Excel
                    </a>
                </div>
            <?php endif; ?>
            
            <a href="leads.php" class="btn-modern btn-primary-modern shadow-sm">
                <i class="ti ti-plus"></i> New Lead
            </a>
        </div>
    </div>

    <div class="admin-card">
        <div class="table-responsive">
            <table class="table-modern" id="leadsTable">
                <thead>
                    <tr>
                        <?php if($role !== 'admin'): ?>
                        <th style="width: 80px;">ID</th>
                        <?php endif; ?>

                        <th>Client Details</th>
                        <th>Contact Info</th>
                        <th>Location</th>
                        <th>Category</th>

                        <?php if($role !== 'admin'): ?>
                        <th>Type</th>
                        <th>Added On</th>
                        <?php endif; ?>

                        <?php if($role === 'admin'): ?>
                        <th>Followed By</th>
                        <?php endif; ?>

                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $typeClass = 'badge-soft';
                            switch(strtolower($row['customer_type'] ?? '')){
                                case 'new customer': $typeClass = 'badge-new'; break;
                                case 'existing customer': $typeClass = 'badge-existing'; break;
                                case 'dealer': $typeClass = 'badge-dealer'; break;
                                case 'distributor': $typeClass = 'badge-distributor'; break;
                            }
                            $initial = strtoupper(substr(trim($row['company_name']), 0, 1));
                            if(empty($initial)) $initial = "#";
                            
                            // Convert solid color to slightly transparent for a softer look
                            $rawColor = getAvatarColor($initial);
                            $avatarColor = $rawColor . "25"; // Adding 25 for 15% opacity hex
                            $textColor = $rawColor;
                        ?>

                        <tr class="lead-row">
                            <?php if($role !== 'admin'): ?>
                            <td>
                                <span class="text-muted fw-bold" style="font-size: 0.85rem;">
                                    #<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?>
                                </span>
                            </td>
                            <?php endif; ?>

                            <td>
                                <a href="lead-update.php?id=<?= $row['id']; ?>" class="company-link" target="blank">
                                    <div class="company-cell">
                                        <div class="company-avatar" style="background-color: <?= $avatarColor ?>; color: <?= $textColor ?>;">
                                            <?= $initial; ?>
                                        </div>
                                        <div>
                                            <span class="company-name">
                                                <?= htmlspecialchars($row['company_name'] ?? ''); ?>
                                            </span>
                                            <div class="text-muted" style="font-size: 0.8rem; font-weight: 500; margin-top: 2px;">
                                                <?= htmlspecialchars($row['department'] ?? 'No Department'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </td>

                            <td>
                                <span class="contact-person">
                                    <?= htmlspecialchars($row['contact_person'] ?? ''); ?>
                                </span>
                                <div class="contact-phone">
                                    <i class="ti ti-phone"></i> 
                                    <span class="copy-text"><?= htmlspecialchars($row['contact_number'] ?? ''); ?></span>
                                    <i class="ti ti-copy copy-btn" onclick="copyToClipboard(this)" title="Copy Number"></i>
                                </div>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-1 text-dark" style="font-weight: 500; font-size: 0.85rem;">
                                    <i class="ti ti-map-pin text-muted"></i> 
                                    <?= htmlspecialchars($row['location'] ?? 'N/A'); ?>
                                </div>
                            </td>

                            <td>
                                <span class="badge-soft">
                                    <i class="ti ti-tag text-muted"></i>
                                    <?= htmlspecialchars(!empty($row['lead_type']) ? $row['lead_type'] : '-'); ?>
                                </span>
                            </td>

                            <?php if($role !== 'admin'): ?>
                            <td>
                                <span class="badge-soft <?= $typeClass ?>">
                                    <?= htmlspecialchars(!empty($row['customer_type']) ? $row['customer_type'] : '-'); ?>
                                </span>
                            </td>

                            <td>
                                <div class="text-dark fw-medium" style="font-size: 0.85rem;">
                                    <?= date("d M Y", strtotime($row['created_at'])) ?>
                                </div>
                            </td>
                            <?php endif; ?>

                            <?php if($role === 'admin'): ?>
                            <td>
                                <span class="badge-soft" style="background: #f8fafc;">
                                    <i class="ti ti-user-circle text-muted"></i>
                                    <?= htmlspecialchars($row['lead_by'] ?? 'Unassigned'); ?>
                                </span>
                            </td>
                            <?php endif; ?>

                            <td>
                                <div class="action-group">
                                    <a href="view-lead.php?id=<?= $row['id']; ?>" class="action-btn view-btn" title="View Details">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <?php if($role === 'admin'): ?>
                                    <a href="edit-lead.php?id=<?= $row['id']; ?>" class="action-btn edit-btn" title="Edit Lead">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <a href="delete-lead.php?id=<?= $row['id']; ?>" class="action-btn delete-btn" title="Delete Lead" onclick="return confirm('Are you sure you want to delete this lead? This action cannot be undone.')">
                                        <i class="ti ti-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= ($role === 'admin') ? '6' : '8' ?>">
                                <div class="text-center py-5">
                                    <div class="mb-3 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%;">
                                        <i class="ti ti-inbox text-muted" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mt-2">No Leads Found</h5>
                                    <p class="text-muted" style="font-size: 0.95rem;">You haven't added any leads yet or they don't match your criteria.</p>
                                    <?php if(!empty($search_query)): ?>
                                        <a href="?" class="btn-modern btn-export-csv mt-2">
                                            <i class="ti ti-x"></i> Clear Search
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 0 || $total_records > 0): ?>
        <div class="pagination-container">
            <div class="d-flex align-items-center gap-3">
                <select id="limitSelect" class="limit-select">
                    <option value="40" <?= $records_per_page == 40 ? 'selected' : '' ?>>40 Rows</option>
                    <option value="100" <?= $records_per_page == 100 ? 'selected' : '' ?>>100 Rows</option>
                    <option value="200" <?= $records_per_page == 200 ? 'selected' : '' ?>>200 Rows</option>
                    <option value="300" <?= $records_per_page == 300 ? 'selected' : '' ?>>300 Rows</option>
                    <option value="500" <?= $records_per_page == 500 ? 'selected' : '' ?>>500 Rows</option>
                    <option value="2000" <?= $records_per_page == 2000 ? 'selected' : '' ?>>2000 Rows</option>
                    <option value="5000" <?= $records_per_page == 5000 ? 'selected' : '' ?>>5000 Rows</option>
                </select>

                <div class="pagination-info d-none d-sm-block">
                    <?php 
                    $start = $total_records > 0 ? $offset + 1 : 0;
                    $end = min($offset + $records_per_page, $total_records);
                    ?>
                    Showing <strong><?= $start ?></strong> to <strong><?= $end ?></strong> of <strong><?= $total_records ?></strong> entries
                </div>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <ul class="pagination-modern">
                <li class="<?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a href="?page=<?= max(1, $page - 1) ?><?= $url_params ?>">
                        <i class="ti ti-chevron-left"></i>
                    </a>
                </li>
                
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    echo '<li><a href="?page=1'.$url_params.'">1</a></li>';
                    if ($start_page > 2) echo '<li><span style="border:none; pointer-events:none;">...</span></li>';
                }
                
                for($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="<?= ($page == $i) ? 'active' : '' ?>">
                        <a href="?page=<?= $i ?><?= $url_params ?>"><?= $i ?></a>
                    </li>
                <?php endfor; 
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) echo '<li><span style="border:none; pointer-events:none;">...</span></li>';
                    echo '<li><a href="?page='.$total_pages.$url_params.'">'.$total_pages.'</a></li>';
                }
                ?>
                
                <li class="<?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                    <a href="?page=<?= min($total_pages, $page + 1) ?><?= $url_params ?>">
                        <i class="ti ti-chevron-right"></i>
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
// Advanced copy to clipboard with icon feedback
function copyToClipboard(element) {
    let textToCopy = element.previousElementSibling.innerText;
    navigator.clipboard.writeText(textToCopy).then(() => {
        element.classList.remove('ti-copy');
        element.classList.add('ti-check', 'copy-success');
        
        // Add tiny pop animation
        element.style.transform = "scale(1.2)";
        setTimeout(() => element.style.transform = "scale(1)", 150);

        setTimeout(() => {
            element.classList.remove('ti-check', 'copy-success');
            element.classList.add('ti-copy');
        }, 2000);
    }).catch(err => { console.error('Failed to copy: ', err); });
}

// Reload page with new limit and retain search keyword
document.getElementById('limitSelect').addEventListener('change', function() {
    let urlParams = new URLSearchParams(window.location.search);
    let searchVal = urlParams.get('search') || '';
    let searchString = searchVal ? "&search=" + encodeURIComponent(searchVal) : "";
    
    window.location.href = "?page=1&limit=" + this.value + searchString;
});
</script>

<?php include 'include/footer.php'; ?>