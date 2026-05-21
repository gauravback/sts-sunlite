<?php
// Error display on 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. DATABASE & ADMIN AUTH CONNECTION
require_once 'config/database.php';
require_once 'config/auth.php'; 

// --- ADMIN CHECK ---
// Yahan apne session ke hisaab se admin check kar lein. Default standard use kiya hai.
$is_admin = (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin');

// --- DELETE HISTORY LOGIC ---
if (isset($_GET['action']) && $_GET['action'] == 'delete_history' && $is_admin) {
    $h_id = intval($_GET['h_id']);
    $type = $_GET['type'];
    
    if ($h_id > 0) {
        if ($type === 'Lead') {
            mysqli_query($conn, "DELETE FROM lead_history WHERE id = $h_id");
        } elseif ($type === 'Customer') {
            mysqli_query($conn, "DELETE FROM customer_history WHERE id = $h_id");
        }
        // Redirect to remove URL parameters and refresh cleanly
        header("Location: meeting-report.php?msg=deleted");
        exit();
    }
}

// 2. FILTER & PAGINATION PARAMETERS
$selected_user = isset($_GET['user_id']) ? $_GET['user_id'] : 'all';
$search_company = isset($_GET['search_company']) ? trim($_GET['search_company']) : '';

// CALENDAR VIEW KE LIYE DEFAULT DATE DENA ZAROORI HAI
$from_date = (isset($_GET['from_date']) && $_GET['from_date'] != '') ? $_GET['from_date'] : date('Y-m-01'); // First day of current month
$to_date = (isset($_GET['to_date']) && $_GET['to_date'] != '') ? $_GET['to_date'] : date('Y-m-t'); // Last day of current month
$selected_priority = isset($_GET['priority']) ? strtolower($_GET['priority']) : 'all';

// Pagination variables
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;


// 3. MAIN SQL QUERY (SMART FIX: Included history_id so Admin can delete specific records)
// 3. MAIN SQL QUERY (SMART FIX: Ignore 'Created' logs to show actual meeting Update date)
// 3. MAIN SQL QUERY (SMART FIX: Show cards on every meeting date, but hide 'Created' auto-logs)
$sql = "
SELECT * FROM (
    -- 1. Get Meetings from LEADS grouped by ID AND Date (Shows every real meeting date)
    SELECT 
        'Lead' AS source_type,
        l.id AS record_id, 
        MAX(lh.id) AS history_id,
        MAX(l.lead_by) AS user_name, 
        MAX(l.lead_priority) AS priority,
        MAX(lh.created_at) AS meeting_date, 
        MAX(l.company_name) AS company_name, 
        MAX(l.contact_person) AS contact_person, 
        MAX(l.contact_number) AS contact_number,
        MAX(l.lead_status) AS current_status
    FROM leads l
    JOIN lead_history lh ON l.id = lh.lead_id
    WHERE l.lead_status IN ('Meeting', 'Follow-up')
      AND lh.history_note NOT LIKE 'Created By%' -- UPDATE 'history_note' TO YOUR ACTUAL COLUMN NAME
    GROUP BY l.id, DATE(lh.created_at)
    
    UNION ALL
    
    -- 2. Get Meetings from CUSTOMERS grouped by ID AND Date (Shows every real meeting date)
    SELECT 
        'Customer' AS source_type,
        c.id AS record_id, 
        MAX(ch.id) AS history_id,
        MAX(c.followed_by) AS user_name, 
        MAX(c.customer_priority) AS priority,
        MAX(ch.created_at) AS meeting_date, 
        MAX(c.company_name) AS company_name,                      
        MAX(c.customer_name) AS contact_person,   
        MAX(c.contact_no) AS contact_number,
        MAX(c.status) AS current_status
    FROM customers c
    JOIN customer_history ch ON c.id = ch.customer_id
    WHERE c.status IN ('Meeting', 'Follow-up')
      AND ch.history_note NOT LIKE 'Created By%' -- UPDATE 'history_note' TO YOUR ACTUAL COLUMN NAME
    GROUP BY c.id, DATE(ch.created_at)

    UNION ALL
    
    -- 3. Fallback for LEADS with NO history yet
    SELECT 
        'Lead' AS source_type,
        l.id AS record_id, 
        0 AS history_id,
        l.lead_by AS user_name, 
        l.lead_priority AS priority,
        l.created_at AS meeting_date, 
        l.company_name, 
        l.contact_person, 
        l.contact_number,
        l.lead_status AS current_status
    FROM leads l
    LEFT JOIN lead_history lh ON l.id = lh.lead_id AND lh.history_note NOT LIKE 'Created By%'
    WHERE l.lead_status IN ('Meeting', 'Follow-up') AND lh.id IS NULL
    
    UNION ALL
    
    -- 4. Fallback for CUSTOMERS with NO history yet
    SELECT 
        'Customer' AS source_type,
        c.id AS record_id, 
        0 AS history_id,
        c.followed_by AS user_name, 
        c.customer_priority AS priority,
        c.created_at AS meeting_date, 
        c.company_name,                      
        c.customer_name AS contact_person,   
        c.contact_no AS contact_number,
        c.status AS current_status
    FROM customers c
    LEFT JOIN customer_history ch ON c.id = ch.customer_id AND ch.history_note NOT LIKE 'Created By%'
    WHERE c.status IN ('Meeting', 'Follow-up') AND ch.id IS NULL
) AS combined_meetings
WHERE meeting_date BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'
";

// Apply User Filter
if ($selected_user != 'all' && $selected_user != '') {
    $sql .= " AND user_name = '" . mysqli_real_escape_string($conn, $selected_user) . "'"; 
}

// Apply Company Name Search Filter
if ($search_company != '') {
    $sql .= " AND company_name LIKE '%" . mysqli_real_escape_string($conn, $search_company) . "%'"; 
}

// ORDER BY Latest First
$sql .= " ORDER BY user_name ASC, meeting_date DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("<div style='padding:20px; background:red; color:white; font-family:Arial; text-align:center;'>
            <h3>Database Query Fail!</h3><p>Error: " . mysqli_error($conn) . "</p>
         </div>");
}

// 4. DATA GROUPING & STATS CALCULATION
$all_grouped_data = [];
$total_unique_leads = 0; 

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $user = !empty($row['user_name']) ? $row['user_name'] : 'Unassigned'; 
        $priority_val = !empty($row['priority']) ? strtolower(trim($row['priority'])) : 'normal';
        
        $raw_date = date("Y-m-d", strtotime($row['meeting_date']));
        $m_time = date("h:i A", strtotime($row['meeting_date']));
        
        if ($priority_val != 'hot' && $priority_val != 'cold') {
            $priority_val = 'normal';
        }

        if (!isset($all_grouped_data[$user])) {
            $all_grouped_data[$user] = [
                'meetings_by_date' => [], 
                'stats' => ['hot' => 0, 'cold' => 0, 'normal' => 0]
            ];
        }
        
        // Grouping by Date
        if (!isset($all_grouped_data[$user]['meetings_by_date'][$raw_date])) {
            $all_grouped_data[$user]['meetings_by_date'][$raw_date] = [];
        }

        // Avoid incrementing stats multiple times for the same lead across different dates
        $lead_unique_key = $row['source_type'].'_'.$row['record_id'];
        if (!isset($all_grouped_data[$user]['unique_leads_counted'][$lead_unique_key])) {
            $all_grouped_data[$user]['stats'][$priority_val]++;
            $all_grouped_data[$user]['unique_leads_counted'][$lead_unique_key] = true;
            $total_unique_leads++;
        }

        // Set Default display text if fields are blank
        $display_company = !empty($row['company_name']) ? $row['company_name'] : 'N/A';
        $display_person = !empty($row['contact_person']) ? $row['contact_person'] : 'N/A';
        $display_phone = !empty($row['contact_number']) ? $row['contact_number'] : 'N/A';
        $current_status = !empty($row['current_status']) ? $row['current_status'] : 'Meeting';

        $all_grouped_data[$user]['meetings_by_date'][$raw_date][] = [
            'record_id' => $row['record_id'],
            'history_id' => $row['history_id'], // Captured history ID for deletion
            'source_type' => $row['source_type'], 
            'priority' => $priority_val,
            'meeting_time' => $m_time,
            'company_name' => $display_company,
            'contact_person' => $display_person,
            'contact_number' => $display_phone,
            'current_status' => $current_status
        ];
    }
}

// GENERATE DATE RANGE FOR CALENDAR VIEW
$period = new DatePeriod(
     new DateTime($from_date),
     new DateInterval('P1D'),
     (new DateTime($to_date))->modify('+1 day')
);
$date_range_array = [];
// Reverse order to show latest dates on top
foreach (array_reverse(iterator_to_array($period)) as $dt) {
    $date_range_array[] = $dt->format("Y-m-d");
}

// 5. APPLYING PAGINATION LOGIC
$filtered_grouped_data = [];
foreach ($all_grouped_data as $u_name => $data) {
    $has_valid_meeting = false;
    foreach ($data['meetings_by_date'] as $date_key => $meets) {
        foreach ($meets as $meet) {
            if ($selected_priority == 'all' || $selected_priority == $meet['priority']) {
                $has_valid_meeting = true;
                break 2;
            }
        }
    }
    if ($has_valid_meeting) {
        $filtered_grouped_data[$u_name] = $data;
    }
}

$total_records = count($filtered_grouped_data);
$total_pages = ceil($total_records / $limit);
$offset = ($page - 1) * $limit;
$grouped_data = array_slice($filtered_grouped_data, $offset, $limit, true);

$get_params = $_GET;
$get_params['page'] = $page - 1;
$prev_url = '?' . http_build_query($get_params);
$get_params['page'] = $page + 1;
$next_url = '?' . http_build_query($get_params);

include 'include/header.php'; 
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; color: #333; margin: 0; }
    .main-wrapper { padding: 25px; max-width: 100%; box-sizing: border-box; }
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .page-title { margin: 0; font-size: 20px; font-weight: 700; color: #111827; }
    .filter-card { background: #ffffff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; display: flex; align-items: flex-end; gap: 20px; flex-wrap: wrap; }
    .filter-group { display: flex; flex-direction: column; gap: 8px; flex: 1; min-width: 150px; }
    .filter-group label { font-size: 12px; font-weight: 500; color: #6b7280; }
    .filter-input { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; color: #374151; background: #fff; outline: none; box-sizing: border-box; }
    .btn-apply { background: #0056b3; color: white; border: none; padding: 10px 24px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; height: 38px; white-space: nowrap; }
    .btn-apply:hover { background: #004494; }
    .btn-reset { color: #6b7280; text-decoration: none; font-size: 13px; font-weight: 500; padding: 10px; }
    .table-card { background: #ffffff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden; }
    .table-header-area { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
    .table-header-title { display: flex; align-items: center; gap: 12px; font-size: 15px; font-weight: 600; color: #111827; margin: 0; }
    .badge-total { background: #eef2ff; color: #0056b3; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
    .show-records { font-size: 12px; color: #6b7280; display: flex; align-items: center; gap: 8px; }
    .show-records select { border: 1px solid #d1d5db; border-radius: 4px; padding: 4px 8px; font-size: 12px; }
    
    .user-section-block { background: #fff; border-bottom: 2px solid #e5e7eb; margin-bottom: 0; }
    .user-section-block:last-child { border-bottom: none; }
    .user-section-header { background: #f8fafc; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; position: sticky; top: 0; z-index: 10; }
    .user-header-left { display: flex; align-items: center; gap: 15px; }
    .user-sr-badge { background: #64748b; color: white; width: 28px; height: 28px; display: flex; justify-content: center; align-items: center; border-radius: 50%; font-size: 13px; font-weight: bold; }
    .user-info-name { font-size: 16px; font-weight: 700; color: #111827; margin: 0; text-transform: uppercase; }
    
    .stat-pills-row { display: flex; flex-direction: row; gap: 10px; align-items: center; }
    .stat-pill { display: inline-flex; justify-content: space-between; font-size: 12px; padding: 6px 12px; border-radius: 20px; text-decoration: none; font-weight: bold; min-width: 80px; text-align: center; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .user-section-body { padding: 20px; }
    .active-filter { border: 2px solid #000 !important; transform: scale(1.05); }
    .stat-pill.hot { background-color: #ff4d4d; color: white; }
    .stat-pill.cold { background-color: #87cefa; color: #000; }
    .stat-pill.normal { background-color: #eee; color: #333; }
    
    .calendar-day-block { margin-bottom: 30px; border-bottom: 1px solid #e5e7eb; padding-bottom: 20px; }
    .calendar-day-block:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .calendar-date-header { display: flex; align-items: center; background: #f9fafb; color: #1f2937; font-size: 14px; font-weight: 700; padding: 8px 15px; border-radius: 6px; margin-bottom: 15px; border-left: 5px solid #0056b3; width: max-content;}
    .meeting-count-badge { background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-left: 15px; font-weight: 800; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);}
    
    .leads-wrap-container { display: flex; flex-wrap: wrap; gap: 15px; }
    .client-detail-block { display: flex; flex-direction: column; flex: 0 0 auto; width: 280px; border-radius: 6px; padding: 12px 16px; text-decoration: none !important; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border: 1px solid #eee; position: relative; transition: 0.2s; background: #fff; }
    .client-detail-block:hover { transform: translateY(-3px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
    .block-hot { border-left: 5px solid #ff4d4d; }
    .block-cold { border-left: 5px solid #0088ff; }
    .block-normal { border-left: 5px solid #888; }
    .cd-title { font-size: 13px; font-weight: 800; color: #111; text-transform: uppercase; margin-bottom: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center;}
    .cd-info { font-size: 12px; color: #555; line-height: 1.6; margin-bottom: 10px; }
    .cd-footer { font-size: 10px; font-weight: bold; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 8px; display: flex; justify-content: space-between; align-items: center; color: #555; }
    
    /* Badges */
    .badge-lead { background-color: #f59e0b; color: white; font-size: 9px; padding: 2px 6px; border-radius: 3px; margin-right: 6px; }
    .badge-customer { background-color: #10b981; color: white; font-size: 9px; padding: 2px 6px; border-radius: 3px; margin-right: 6px; }
    .badge-status { background-color: #e0e7ff; color: #4338ca; font-size: 9px; padding: 2px 6px; border-radius: 3px; margin-left: auto; font-weight: bold; }
    
    /* Admin Delete Badge */
    .badge-delete { background-color: #ef4444; color: white; font-size: 12px; padding: 4px 6px; border-radius: 4px; margin-left: 6px; display: inline-flex; align-items: center; transition: 0.2s; border: none; cursor: pointer; }
    .badge-delete:hover { background-color: #dc2626; transform: scale(1.05); }
    
    .pagination-bar { padding: 15px 20px; display: flex; justify-content: center; align-items: center; background: #fff; gap: 10px; border-top: 1px solid #e5e7eb; }
    .page-info { font-size: 13px; color: #333; font-weight: bold; margin: 0 10px; }
    .page-controls a { padding: 7px 15px; background: #0056b3; color: white; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; }
    .page-controls a:hover { background: #004494; }
</style>

<div class="main-wrapper">
    
    <div class="page-header">
        <h2 class="page-title">Meeting Report & Stats</h2>
    </div>

    <div class="filter-card">
        <form method="GET" style="display:flex; width:100%; gap:20px; flex-wrap:wrap; align-items:flex-end;">
            <input type="hidden" name="priority" value="<?= htmlspecialchars($selected_priority) ?>">
            
            <div class="filter-group">
                <label>Select User:</label>
                <select name="user_id" class="filter-input">
                    <option value="all">All Users</option>
                    <?php
                    $u_sql = "SELECT id, name FROM users ORDER BY name ASC";
                    $u_res = mysqli_query($conn, $u_sql);
                    if($u_res) {
                        while($ur = mysqli_fetch_assoc($u_res)) {
                            $uname = trim($ur['name']);
                            if(!empty($uname)){
                                $s = ($selected_user == $uname) ? 'selected' : '';
                                echo "<option value='".htmlspecialchars($uname)."' $s>".htmlspecialchars($uname)."</option>";
                            }
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Search Company:</label>
                <input type="text" name="search_company" value="<?= htmlspecialchars($search_company) ?>" placeholder="Enter Company Name..." class="filter-input">
            </div>
            
            <div class="filter-group" style="flex: 2;">
                <label>Date Range (Calendar View):</label> 
                <div style="display:flex; gap:10px; align-items:center;">
                    <input type="date" name="from_date" value="<?= $from_date ?>" class="filter-input">
                    <span style="font-size:12px; color:#6b7280;">to</span>
                    <input type="date" name="to_date" value="<?= $to_date ?>" class="filter-input">
                </div>
            </div>
            
            <div class="filter-group" style="align-items: flex-start; min-width:180px;">
                <label>&nbsp;</label>
                <div style="display:flex; gap:10px; align-items:center;">
                    <button type="submit" class="btn-apply">Search</button>
                    <a href="meeting-report.php" class="btn-reset">Reset All</a>
                </div>
            </div>
        </form>
    </div>

    <div class="table-card">
        
        <div class="table-header-area">
            <h3 class="table-header-title">
                Meeting Leads Directory 
                <span class="badge-total">Total: <?= $total_unique_leads ?></span>
            </h3>
            
            <div class="show-records">
                Show:
                <select onchange="window.location.href='?limit='+this.value">
                    <?php foreach([100, 200, 300, 400, 500, 1000] as $l) {
                        $s = ($limit == $l) ? 'selected' : '';
                        echo "<option value='$l' $s>$l</option>";
                    } ?>
                </select>
            </div>
        </div>

        <div>
            <?php if (!empty($grouped_data)) {
                $sr = $offset + 1;
                foreach ($grouped_data as $u_name => $data) {
                    $stats = $data['stats'];
                    $bu = "?user_id=".urlencode($u_name)."&from_date=$from_date&to_date=$to_date&limit=$limit&search_company=".urlencode($search_company);
                    ?>
                    
                    <div class="user-section-block">
                        <div class="user-section-header">
                            <div class="user-header-left">
                                <div class="user-sr-badge"><?= $sr ?></div>
                                <h4 class="user-info-name"><?= htmlspecialchars($u_name) ?></h4>
                            </div>
                            
                            <div class="stat-pills-row">
                                <a href="<?= $bu ?>&priority=hot" target="_blank" class="stat-pill hot <?= ($selected_priority=='hot'?'active-filter':'') ?>">Hot: <?= $stats['hot'] ?></a>
                                <a href="<?= $bu ?>&priority=cold" target="_blank" class="stat-pill cold <?= ($selected_priority=='cold'?'active-filter':'') ?>">Cold: <?= $stats['cold'] ?></a>
                                <a href="<?= $bu ?>&priority=normal" target="_blank" class="stat-pill normal <?= ($selected_priority=='normal'?'active-filter':'') ?>">Normal: <?= $stats['normal'] ?></a>
                            </div>
                        </div>

                        <div class="user-section-body">
                            <?php 
                            foreach ($date_range_array as $current_date) {
                                
                                $meetings_today = isset($data['meetings_by_date'][$current_date]) ? $data['meetings_by_date'][$current_date] : [];
                                
                                $filtered_meetings_today = [];
                                foreach ($meetings_today as $m) {
                                    if ($selected_priority == 'all' || $selected_priority == $m['priority']) {
                                        $filtered_meetings_today[] = $m;
                                    }
                                }

                                $count_today = count($filtered_meetings_today);

                                if ($count_today == 0) {
                                    continue; 
                                }

                                $display_date = date("d M, Y", strtotime($current_date));
                                
                                echo "<div class='calendar-day-block'>";
                                echo "<div class='calendar-date-header'>📅 $display_date";
                                echo "<span class='meeting-count-badge'>🚀 $count_today Meetings Scheduled</span>";
                                echo "</div>";

                                echo "<div class='leads-wrap-container'>";
                                foreach ($filtered_meetings_today as $m) {
                                    $block_class = "block-normal"; 
                                    if ($m['priority'] == 'hot') $block_class = "block-hot"; 
                                    if ($m['priority'] == 'cold') $block_class = "block-cold"; 
                                    
                                    $link = ($m['source_type'] == 'Customer') ? "customer-details.php?id=".$m['record_id'] : "lead-update.php?id=".$m['record_id'];
                                    
                                    echo "<div class='client-detail-block $block_class'>";
                                    
                                    $badge_class = ($m['source_type'] == 'Lead') ? 'badge-lead' : 'badge-customer';
                                    $status_badge = "<span class='badge-status'>".htmlspecialchars($m['current_status'])."</span>";

                                    echo "<div class='cd-title'>
                                            <span class='{$badge_class}'>".strtoupper($m['source_type'])."</span>
                                            <a href='$link' target='_blank' style='color:#111; text-decoration:none; flex:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;' title='".htmlspecialchars($m['company_name'])."'>".htmlspecialchars($m['company_name'])."</a>
                                            {$status_badge}";
                                            
                                            // Admin Delete Button Integration
                                            if ($is_admin && $m['history_id'] > 0) {
                                                $del_link = "?action=delete_history&h_id=".$m['history_id']."&type=".$m['source_type'];
                                                echo "<button class='badge-delete' title='Delete History Entry' onclick='confirmDelete(\"$del_link\")'><i class='ti ti-trash'></i></button>";
                                            }

                                    echo "</div>";
                                    
                                    // Make body clickable to view details
                                    echo "<a href='$link' target='_blank' style='text-decoration:none; display:block;'>";
                                    echo "<div class='cd-info'>👤 ".htmlspecialchars($m['contact_person'])."<br>📞 ".htmlspecialchars($m['contact_number'])."</div>";
                                    
                                    echo "<div class='cd-footer'>";
                                    echo "<span>PRIORITY: ".strtoupper($m['priority'])."</span>";
                                    echo "<span>⏰ ".$m['meeting_time']."</span>";
                                    echo "</div>";
                                    echo "</a>";

                                    echo "</div>";
                                }
                                echo "</div>"; 
                                echo "</div>"; 
                            }
                            ?>
                        </div>
                    </div>

                    <?php
                    $sr++;
                }
            } else {
                echo "<div style='text-align:center; padding:50px; color:#6b7280; font-size:14px;'>No records found.</div>";
            } ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination-bar">
                <div class="page-controls">
                    <?php if($page > 1) echo "<a href='$prev_url'>« Previous</a>"; ?>
                </div>
                <div class="page-info">
                    Page <?= $page ?> of <?= $total_pages ?> (Total Users: <?= $total_records ?>)
                </div>
                <div class="page-controls">
                    <?php if($page < $total_pages) echo "<a href='$next_url'>Next »</a>"; ?>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Show SweetAlert on successful deletion
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('msg') === 'deleted') {
        Swal.fire({ 
            icon: 'success', 
            title: 'Entry Deleted', 
            text: 'The meeting history record has been removed.', 
            timer: 2000, 
            showConfirmButton: false, 
            toast: true, 
            position: 'top-end' 
        });
        // Remove the parameter from URL without reloading
        window.history.replaceState({}, '', 'meeting-report.php');
    }

    // Function to confirm deletion before redirecting
    function confirmDelete(deleteUrl) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will delete the interaction history for this specific date. This cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = deleteUrl;
            }
        });
    }
</script>

<?php include 'include/footer.php'; ?>