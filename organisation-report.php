<?php
// Error display on 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. DATABASE & ADMIN AUTH CONNECTION
require_once 'config/database.php';
require_once 'config/auth.php'; 

// Time Ago Helper Function
function getTimeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hr' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}

// ============================================================================
// AJAX HANDLER: FETCH LEADS FOR EXPANDABLE ACCORDION ROW
// ============================================================================
if (isset($_GET['ajax_company'])) {
    $filter_company = mysqli_real_escape_string($conn, $_GET['ajax_company']);

    $detail_where = ["company_name = '$filter_company'"];
    if (!empty($_GET['filter_user'])) $detail_where[] = "lead_by = '" . mysqli_real_escape_string($conn, $_GET['filter_user']) . "'";
    if (!empty($_GET['filter_status'])) $detail_where[] = "lead_status = '" . mysqli_real_escape_string($conn, $_GET['filter_status']) . "'";
    if (!empty($_GET['filter_priority'])) $detail_where[] = "lead_priority = '" . mysqli_real_escape_string($conn, $_GET['filter_priority']) . "'";

    $detail_where_sql = implode(' AND ', $detail_where);

    $leads_sql = "SELECT id, company_name, contact_person, contact_number, lead_status, lead_priority, lead_by, created_at 
                  FROM leads 
                  WHERE $detail_where_sql 
                  ORDER BY id DESC";
    $leads_res = mysqli_query($conn, $leads_sql);
    $total_company_leads = mysqli_num_rows($leads_res);

    if ($total_company_leads == 0) {
        echo "<div style='padding:30px; text-align:center; color:#64748b; font-weight:600;'>No leads found for this company.</div>";
        exit;
    }
    ?>
    
    <div class="inner-leads-wrapper">
        <div class="master-header">
            <h2>Master Leads Directory <span class="master-badge">Total: <?= $total_company_leads ?></span></h2>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="master-table">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;">#</th>
                        <th width="15%">LEAD DATE</th>
                        <th width="30%">CLIENT DETAILS</th>
                        <th width="15%" style="text-align: center;">STATUS</th>
                        <th width="35%">LEAD HISTORY & TIMING</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sr = 1;
                    while ($l_row = mysqli_fetch_assoc($leads_res)) {
                        $lead_id = $l_row['id'];
                        $company = htmlspecialchars($l_row['company_name']);
                        $contact = htmlspecialchars($l_row['contact_person'] ?? 'N/A');
                        $phone = htmlspecialchars($l_row['contact_number'] ?? 'N/A');
                        $status = htmlspecialchars($l_row['lead_status'] ?? 'New');
                        $priority = strtoupper(htmlspecialchars($l_row['lead_priority'] ?? 'NORMAL'));
                        
                        // Fixed Date formatting logic
                        $date_val = strtotime($l_row['created_at']);
                        $f_date = date('d M, Y', $date_val); 
                        $f_time = date('h:i A', $date_val);
                        
                        // New Priority Dot Color Logic
                        $dot_class = 'normal';
                        if ($priority == 'HOT') $dot_class = 'hot';
                        else if ($priority == 'COLD') $dot_class = 'cold';
                        
                        $status_class = 'default';
                        $s_lower = strtolower($status);
                        if (strpos($s_lower, 'meeting') !== false) $status_class = 'meeting';
                        else if (strpos($s_lower, 'new') !== false) $status_class = 'new-lead';

                        // Fetch Lead History (ALL updates)
                        $history_html = "";
                        $history_sql = "SELECT * FROM lead_history WHERE lead_id = '$lead_id' ORDER BY id DESC";
                        $history_res = mysqli_query($conn, $history_sql);
                        
                        if ($history_res && mysqli_num_rows($history_res) > 0) {
                            $is_first = true; // Track latest update
                            while ($h_row = mysqli_fetch_assoc($history_res)) {
                                
                                // FIX: Removed htmlspecialchars() so that <b> and <br> tags render properly
                                $h_note = $h_row['history_note'] ?? 'Updated';
                                
                                $h_date = date('d M, Y - h:i A', strtotime($h_row['created_at']));
                                $h_by = !empty($h_row['updated_by']) ? htmlspecialchars($h_row['updated_by']) : htmlspecialchars($l_row['lead_by']);
                                
                                // Calculate Time Ago
                                $time_ago_str = getTimeAgo($h_row['created_at']);
                                
                                // Beautiful badge for the latest update
                                $time_badge = $is_first 
                                    ? "<span style='background:#f1f5f9; color:#0f172a; border: 1px solid #cbd5e1; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:600; margin-left:6px;'><i class='ti ti-clock'></i> Last update: $time_ago_str</span>" 
                                    : "<span style='color:#94a3b8; font-size:10px; margin-left:6px; font-weight:500;'>($time_ago_str)</span>";
                                
                                $history_html .= "
                                <div style='margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px dashed #cbd5e1;'>
                                    <div class='update-text' style='margin-bottom: 4px;'>" . nl2br($h_note) . "</div>
                                    <div class='update-meta'><i class='ti ti-pencil'></i> {$h_by} | {$h_date} {$time_badge}</div>
                                </div>";
                                
                                $is_first = false;
                            }
                        } else {
                            // If no history, show lead creation time ago
                            $created_ago_str = getTimeAgo($l_row['created_at']);
                            $history_html = "
                            <div class='update-text' style='color: #94a3b8; font-style: italic;'>No updates yet</div>
                            <div class='update-meta'><i class='ti ti-pencil'></i> " . htmlspecialchars($l_row['lead_by']) . " | $f_date - $f_time 
                                <span style='background:#f8fafc; color:#64748b; padding:2px 6px; border-radius:4px; font-size:10px; border: 1px solid #e2e8f0; margin-left:6px;'><i class='ti ti-clock'></i> Created: $created_ago_str</span>
                            </div>";
                        }
                        ?>
                        <tr>
                            <td style="text-align: center; font-weight: 600; color: #64748b;"><?= $sr ?></td>
                            <td>
                                <div class="date-col"><?= $f_date ?></div>
                                <div class="time-col"><i class="ti ti-clock"></i> <?= $f_time ?></div>
                            </td>
                            <td>
                                <div class="client-box">
                                    <a href="lead-update.php?id=<?= $lead_id ?>" style="text-decoration: none;" target="_blank" title="Click to edit lead">
                                        <h4><?= $company ?></h4>
                                    </a>
                                    <div class="client-info">
                                        <span><i class="ti ti-user"></i> <?= $contact ?></span>
                                        <span><i class="ti ti-phone"></i> <?= $phone ?></span>
                                    </div>
                                    <div class="priority-row">
                                        <span class="dot <?= $dot_class ?>"></span> PRIORITY: <?= $priority ?>
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="status-pill <?= $status_class ?>"><?= $status ?></span>
                            </td>
                            <td>
                                <div style="max-height: 140px; overflow-y: auto; padding-right: 5px;">
                                    <?= $history_html ?>
                                </div>
                            </td>
                        </tr>
                        <?php 
                        $sr++;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    exit; // Stop processing further for AJAX request
}

// ============================================================================
// MAIN ORGANISATION REPORT PAGE (WITH FILTERS)
// ============================================================================
$filter_company  = isset($_GET['company']) ? mysqli_real_escape_string($conn, $_GET['company']) : '';
$filter_user     = isset($_GET['filter_user']) ? mysqli_real_escape_string($conn, $_GET['filter_user']) : '';
$filter_status   = isset($_GET['filter_status']) ? mysqli_real_escape_string($conn, $_GET['filter_status']) : '';
$filter_priority = isset($_GET['filter_priority']) ? mysqli_real_escape_string($conn, $_GET['filter_priority']) : '';

// PAGINATION PARAMETERS
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// BUILD WHERE CLAUSE
$report_where = ["company_name IS NOT NULL", "company_name != ''"];
if (!empty($filter_company)) $report_where[] = "company_name LIKE '%$filter_company%'";
if (!empty($filter_user)) $report_where[] = "lead_by = '$filter_user'";
if (!empty($filter_status)) $report_where[] = "lead_status = '$filter_status'";
if (!empty($filter_priority)) $report_where[] = "lead_priority = '$filter_priority'";
$report_where_sql = implode(' AND ', $report_where);

// GET TOTAL UNIQUE ORGANISATIONS
$count_sql = "SELECT COUNT(DISTINCT company_name) as total FROM leads WHERE $report_where_sql";
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $limit);

// MAIN SQL QUERY
$sql = "SELECT company_name, 
               COUNT(id) as total_leads,
               SUM(CASE WHEN UPPER(lead_priority) = 'HOT' THEN 1 ELSE 0 END) as hot_count,
               SUM(CASE WHEN UPPER(lead_priority) = 'COLD' THEN 1 ELSE 0 END) as cold_count,
               SUM(CASE WHEN UPPER(lead_priority) = 'NORMAL' OR lead_priority IS NULL OR lead_priority = '' THEN 1 ELSE 0 END) as normal_count
        FROM leads 
        WHERE $report_where_sql 
        GROUP BY company_name 
        ORDER BY company_name ASC 
        LIMIT $offset, $limit";
$result = mysqli_query($conn, $sql);

// ============================================================================
// FETCH USER OVERALL STATS
// ============================================================================
$user_stats = null;
if (!empty($filter_user)) {
    $stat_where = ["lead_by = '$filter_user'"];
    if (!empty($filter_status)) $stat_where[] = "lead_status = '$filter_status'";
    if (!empty($filter_priority)) $stat_where[] = "lead_priority = '$filter_priority'";
    if (!empty($filter_company)) $stat_where[] = "company_name LIKE '%$filter_company%'";
    
    $stat_where_sql = implode(' AND ', $stat_where);
    
    $stat_sql = "SELECT 
                    COUNT(id) as total_user_leads,
                    SUM(CASE WHEN UPPER(lead_priority) = 'HOT' THEN 1 ELSE 0 END) as total_hot,
                    SUM(CASE WHEN UPPER(lead_priority) = 'COLD' THEN 1 ELSE 0 END) as total_cold,
                    SUM(CASE WHEN UPPER(lead_priority) = 'NORMAL' OR lead_priority IS NULL OR lead_priority = '' THEN 1 ELSE 0 END) as total_normal
                 FROM leads 
                 WHERE $stat_where_sql";
                 
    $stat_res = mysqli_query($conn, $stat_sql);
    if ($stat_res && mysqli_num_rows($stat_res) > 0) {
        $user_stats = mysqli_fetch_assoc($stat_res);
    }
}

// FETCH DISTINCT VALUES FOR DROPDOWNS
$users_result = mysqli_query($conn, "SELECT DISTINCT lead_by FROM leads WHERE lead_by IS NOT NULL AND lead_by != '' ORDER BY lead_by");
$status_result = mysqli_query($conn, "SELECT DISTINCT lead_status FROM leads WHERE lead_status IS NOT NULL AND lead_status != ''");
$priority_result = mysqli_query($conn, "SELECT DISTINCT lead_priority FROM leads WHERE lead_priority IS NOT NULL AND lead_priority != ''");

include 'include/header.php'; 
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
    :root {
        --bg-body: #f8fafc; --surface: #ffffff; --text-main: #0f172a; --text-muted: #64748b;
        --primary: #4f46e5; --primary-light: #e0e7ff; --border: #e2e8f0;
    }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: var(--text-main); margin: 0; }
    .main-wrapper {  box-sizing: border-box; min-height: 80vh; padding: 20px;}
    .page-header { margin-bottom: 20px; }
    .page-title { margin: 0 0 20px 0; font-size: 28px; font-weight: 700; color: var(--text-main); }

    .filter-card { background: var(--surface); padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03); border: 1px solid var(--border); margin-bottom: 24px; }
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 13px; font-weight: 600; color: var(--text-muted); }
    .form-control { padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; font-family: inherit; outline: none; }
    .form-control:focus { border-color: var(--primary); }
    .btn-submit { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; height: 40px;}
    .btn-clear { background: #f1f5f9; color: var(--text-main); text-decoration: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; text-align: center; height: 40px; display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--border);}

    .user-stats-box { background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); color: #fff; padding: 20px 30px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; }
    .stat-user-title { font-size: 18px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px; }
    .stat-user-title i { font-size: 24px; background: rgba(255,255,255,0.2); padding: 8px; border-radius: 50%; }
    .stat-numbers-grid { display: flex; gap: 30px; flex-wrap: wrap; }
    .stat-item { display: flex; flex-direction: column; align-items: center; background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 10px; min-width: 90px; }
    .stat-item .stat-val { font-size: 22px; font-weight: 700; }
    .stat-item .stat-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9; margin-top: 4px; display: flex; align-items: center; gap: 5px; }
    
    .stat-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .stat-dot.s-hot { background-color: #fca5a5; box-shadow: 0 0 5px #ef4444; }
    .stat-dot.s-cold { background-color: #bae6fd; box-shadow: 0 0 5px #0ea5e9; }
    .stat-dot.s-normal { background-color: #d1fae5; box-shadow: 0 0 5px #10b981; }

    .table-card { background: var(--surface); border-radius: 16px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05); border: 1px solid var(--border); overflow: hidden; }
    .table-header-area { padding: 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
    .table-header-title { font-size: 18px; font-weight: 700; margin: 0; display: flex; gap:12px; align-items:center; }
    .badge-total { background: var(--primary-light); color: var(--primary); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
    
    .data-table { width: 100%; border-collapse: collapse; text-align: left; }
    .data-table th { background-color: #f8fafc; padding: 16px 20px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border); border-right: 1px solid var(--border); }
    .data-table th:last-child { border-right: none; }
    .data-table td { padding: 18px 20px; border-bottom: 1px solid var(--border); border-right: 1px solid var(--border); transition: background-color 0.2s; vertical-align: middle; }
    .data-table td:last-child { border-right: none; }
    
    .data-table tbody > tr.main-row:hover { background-color: #f8fafc; cursor: pointer; }
    
    .company-name { font-weight: 700; font-size: 15px; color: var(--text-main); }
    .lead-count-badge { background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 8px; font-size: 13px; font-weight: 700; border: 1px solid #bfdbfe; }
    
    .p-stat { display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; color: #475569; justify-content: center; }
    
    .p-stat i.hot { color: #ef4444; } 
    .p-stat i.cold { color: #0ea5e9; } 
    .p-stat i.normal { color: #ffffff; filter: drop-shadow(0px 0px 3px rgba(0,0,0,0.3)); border-radius: 50%; }

    .toggle-icon { transition: transform 0.3s ease; color: var(--text-muted); font-size: 18px; }

    .details-row { display: none; background: #fbfdff; }
    .inner-leads-wrapper { padding: 20px 40px; border-left: 4px solid var(--primary); background: #ffffff; box-shadow: inset 0 3px 5px -3px rgba(0,0,0,0.05); }
    
    .master-header { margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
    .master-header h2 { margin: 0; font-size: 16px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 10px; }
    .master-badge { background: #eff6ff; color: #3b82f6; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
    
    .master-table { width: 100%; border-collapse: collapse; text-align: left; background: #fff; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
    .master-table th { background-color: #f8fafc; padding: 12px 16px; font-size: 11px; font-weight: 700; color: #475569; border-bottom: 1px solid var(--border); border-right: 1px solid var(--border); }
    .master-table td { padding: 16px; border-bottom: 1px solid var(--border); border-right: 1px solid var(--border); vertical-align: top; }
    
    .date-col { font-size: 13px; font-weight: 600; color: #1e293b; white-space: nowrap; }
    .time-col { font-size: 11px; font-weight: 500; color: #64748b; margin-top: 4px; display: flex; align-items: center; gap: 4px; }
    
    .client-box { background: #bce3ff; padding: 12px 16px; border-radius: 6px; display: inline-block; min-width: 200px; }
    .client-box h4 { margin: 0 0 8px 0; color: #0056b3; font-size: 14px; font-weight: 700; }
    .client-info { display: flex; align-items: center; gap: 12px; font-size: 12px; color: #334155; margin-bottom: 8px; }
    
    .priority-row { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: #334155; }
    .dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .dot.hot { background-color: #ef4444; } 
    .dot.cold { background-color: #0ea5e9; } 
    .dot.normal { background-color: #ffffff; box-shadow: 0 0 4px rgba(0,0,0,0.3); border: 1px solid #cbd5e1; }
    
    .status-pill { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; text-align: center; white-space: nowrap; border: 1px solid transparent; }
    .status-pill.meeting { background: #fffbeb; color: #d97706; border-color: #fde68a; }
    .status-pill.new-lead { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
    .status-pill.default { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    
    .update-text { font-size: 13px; color: #1e293b; margin-bottom: 8px; font-weight: 500; }
    .update-meta { font-size: 11px; color: #64748b; display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
    
    .loading-spinner { padding: 40px; text-align: center; color: var(--primary); font-weight: 600; }
    .pagination-bar { padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-top: 1px solid var(--border); }
    .page-controls a { padding: 8px 16px; background-color: var(--surface); border: 1px solid var(--border); border-radius: 8px; text-decoration: none; color: var(--text-main); font-weight: 600; margin-left: 8px; }
</style>

<div class="main-wrapper">
    <div class="page-header">
        <h2 class="page-title">Organisation Report</h2>
    </div>

    <div class="filter-card">
        <form method="GET" action="" id="filter-form">
            <div class="filter-grid">
                <div class="form-group">
                    <label>Company / Organisation</label>
                    <input type="text" name="company" class="form-control" placeholder="Search company name..." value="<?= htmlspecialchars($filter_company) ?>">
                </div>
                <div class="form-group">
                    <label>Assigned User</label>
                    <select name="filter_user" class="form-control">
                        <option value="">All Users</option>
                        <?php while($u = mysqli_fetch_assoc($users_result)): ?>
                            <option value="<?= htmlspecialchars($u['lead_by']) ?>" <?= $filter_user == $u['lead_by'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['lead_by']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lead Status</label>
                    <select name="filter_status" class="form-control">
                        <option value="">All Statuses</option>
                        <?php while($s = mysqli_fetch_assoc($status_result)): ?>
                            <option value="<?= htmlspecialchars($s['lead_status']) ?>" <?= $filter_status == $s['lead_status'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['lead_status']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lead Type (Priority)</label>
                    <select name="filter_priority" class="form-control">
                        <option value="">All Priorities</option>
                        <?php while($p = mysqli_fetch_assoc($priority_result)): ?>
                            <option value="<?= htmlspecialchars($p['lead_priority']) ?>" <?= $filter_priority == $p['lead_priority'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['lead_priority']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group" style="flex-direction: row; gap: 10px;">
                    <button type="submit" class="btn-submit" style="flex: 1;">Apply Filters</button>
                    <a href="?" class="btn-clear">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <?php if ($user_stats): ?>
    <div class="user-stats-box">
        <div class="stat-user-title">
            <i class="ti ti-user-check"></i>
            <div>
                <div style="font-size: 13px; font-weight: 500; opacity: 0.9;">Performance Overview</div>
                <?= htmlspecialchars(strtoupper($filter_user)) ?>
            </div>
        </div>
        <div class="stat-numbers-grid">
            <div class="stat-item">
                <div class="stat-val"><?= $user_stats['total_user_leads'] ?></div>
                <div class="stat-label">Total Leads</div>
            </div>
            <div class="stat-item">
                <div class="stat-val"><?= $user_stats['total_hot'] ?></div>
                <div class="stat-label"><span class="stat-dot s-hot"></span> Hot</div>
            </div>
            <div class="stat-item">
                <div class="stat-val"><?= $user_stats['total_cold'] ?></div>
                <div class="stat-label"><span class="stat-dot s-cold"></span> Cold</div>
            </div>
            <div class="stat-item">
                <div class="stat-val"><?= $user_stats['total_normal'] ?></div>
                <div class="stat-label"><span class="stat-dot s-normal"></span> Normal</div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-header-area">
            <h3 class="table-header-title">
                Master Organisations List
                <span class="badge-total"><?= number_format($total_records) ?> Unique</span>
            </h3>
        </div>

        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;">#</th>
                        <th width="35%">Organisation Name</th>
                        <th width="12%" style="text-align: center;">Total Leads</th>
                        <th width="12%" style="text-align: center;">Hot Leads</th>
                        <th width="12%" style="text-align: center;">Cold Leads</th>
                        <th width="12%" style="text-align: center;">Normal Leads</th>
                        <th width="5%" style="text-align: center;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($result) > 0) {
                        $sr = $offset + 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                            $company_name = htmlspecialchars($row['company_name']);
                            $count = $row['total_leads'];
                            $hot = $row['hot_count'];
                            $cold = $row['cold_count'];
                            $normal = $row['normal_count'];
                            
                            echo "<tr class='main-row toggle-details' data-company='{$company_name}'>";
                            echo "<td style='text-align: center; color:#64748b; font-weight:600;'>{$sr}</td>";
                            echo "<td><div class='company-name'>" . strtoupper($company_name) . "</div></td>";
                            
                            echo "<td style='text-align: center;'><span class='lead-count-badge'>{$count}</span></td>";
                            
                            echo "<td style='text-align: center;'>
                                    <div class='p-stat'><i class='ti ti-circle-filled hot'></i> {$hot}</div>
                                  </td>";
                                  
                            echo "<td style='text-align: center;'>
                                    <div class='p-stat'><i class='ti ti-circle-filled cold'></i> {$cold}</div>
                                  </td>";
                                  
                            echo "<td style='text-align: center;'>
                                    <div class='p-stat'><i class='ti ti-circle-filled normal'></i> {$normal}</div>
                                  </td>";
                                  
                            echo "<td style='text-align: center;'><i class='ti ti-chevron-down toggle-icon'></i></td>";
                            echo "</tr>";
                            $sr++;
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; padding:60px;'>No organisations found matching your criteria.</td></tr>";
                    } 
                    ?>
                </tbody>
            </table>
        </div>
        
        <?php 
        $current_params = $_GET;
        $current_params['page'] = $page - 1;
        $prev_url = '?' . http_build_query($current_params);
        $current_params['page'] = $page + 1;
        $next_url = '?' . http_build_query($current_params);
        ?>
        <?php if ($total_pages > 1): ?>
            <div class="pagination-bar">
                <div>Showing page <strong><?= $page ?></strong> of <strong><?= $total_pages ?></strong></div>
                <div class="page-controls">
                    <?php if($page > 1) echo "<a href='$prev_url'>&larr; Prev</a>"; ?>
                    <?php if($page < $total_pages) echo "<a href='$next_url'>Next &rarr;</a>"; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.toggle-details').click(function(e) {
        e.preventDefault();
        
        var $row = $(this);
        var company = $row.data('company');
        var $icon = $row.find('.toggle-icon');
        
        if ($row.next().hasClass('details-row')) {
            $row.next().slideToggle(300);
            
            if ($icon.hasClass('ti-chevron-down')) {
                $icon.removeClass('ti-chevron-down').addClass('ti-chevron-up');
            } else {
                $icon.removeClass('ti-chevron-up').addClass('ti-chevron-down');
            }
        } 
        else {
            var colspan = $row.find('td').length;
            var $detailsRow = $('<tr class="details-row"><td colspan="' + colspan + '" style="padding: 0;"><div class="details-content loading-spinner"><i class="ti ti-loader ti-spin"></i> Fetching details...</div></td></tr>');
            
            $row.after($detailsRow);
            $detailsRow.slideDown(300);
            $icon.removeClass('ti-chevron-down').addClass('ti-chevron-up');
            
            var filterUser = $('select[name="filter_user"]').val() || '';
            var filterStatus = $('select[name="filter_status"]').val() || '';
            var filterPriority = $('select[name="filter_priority"]').val() || '';

            $.ajax({
                url: window.location.pathname,
                type: 'GET',
                data: {
                    ajax_company: company,
                    filter_user: filterUser,
                    filter_status: filterStatus,
                    filter_priority: filterPriority
                },
                success: function(response) {
                    $detailsRow.find('.details-content').removeClass('loading-spinner').html(response).hide().fadeIn(400);
                },
                error: function() {
                    $detailsRow.find('.details-content').html('<div class="text-danger p-4 text-center">Failed to load data. Please try again.</div>');
                }
            });
        }
    });
});
</script>

<?php include 'include/footer.php'; ?>