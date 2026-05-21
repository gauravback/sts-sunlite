<?php
// Session & Database
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
include 'config/database.php'; 

// SESSION CHECK
if (isset($_SESSION['name']) && !empty($_SESSION['name'])) {
    $logged_in_user = $_SESSION['name'];
} elseif (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
    $logged_in_user = $_SESSION['user_name'];
} elseif (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    $logged_in_user = $_SESSION['username'];
} else {
    $logged_in_user = 'Admin'; 
}

// Check URL ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('Invalid Bid ID!'); window.location.href='bid_report.php';</script>";
    exit();
}
$bid_id = $_GET['id'];

// Terminal State & RA Sequence Checks
$is_canceled = false;
$is_awarded = false;
$has_ra_create = false;
$has_ra_inprog = false;

$term_check_q = mysqli_query($conn, "SELECT remark_text FROM bid_remarks WHERE bid_id = $bid_id");
if ($term_check_q) {
    while($r = mysqli_fetch_assoc($term_check_q)){
        if(strpos($r['remark_text'], '[CANCEL_CARD]') !== false) $is_canceled = true;
        if(strpos($r['remark_text'], '[AWARD_CARD]') !== false) $is_awarded = true;
        if(strpos($r['remark_text'], '[RA_CREATE_CARD]') !== false) $has_ra_create = true;
        if(strpos($r['remark_text'], '[RA_INPROGRESS_CARD]') !== false) $has_ra_inprog = true;
    }
}

// ADMIN DELETE CARD & FILES LOGIC
if (isset($_POST['delete_remark_id']) && isset($_POST['action_delete'])) {
    if (strtolower($logged_in_user) === 'admin') {
        $del_id = intval($_POST['delete_remark_id']);
        $fetch_q = mysqli_query($conn, "SELECT remark_text FROM bid_remarks WHERE id = $del_id AND bid_id = $bid_id");
        if ($fetch_q && mysqli_num_rows($fetch_q) > 0) {
            $row = mysqli_fetch_assoc($fetch_q);
            $remark_content = $row['remark_text'];
            $files_to_delete = [];
            preg_match_all("/href='uploads\/bids\/([^']+)'/", $remark_content, $matches);
            if (!empty($matches[1])) { $files_to_delete = $matches[1]; }

            foreach ($files_to_delete as $file) {
                $file_path = "uploads/bids/" . $file;
                if (file_exists($file_path)) { unlink($file_path); }
            }

            if (!empty($files_to_delete)) {
                $bids_q = mysqli_query($conn, "SELECT uploaded_files FROM bids WHERE id = $bid_id");
                if ($bids_q && mysqli_num_rows($bids_q) > 0) {
                    $bids_row = mysqli_fetch_assoc($bids_q);
                    $existing_files_array = array_filter(explode(',', trim($bids_row['uploaded_files'])));
                    $updated_files_array = array_diff($existing_files_array, $files_to_delete);
                    $updated_files_str = implode(',', $updated_files_array);
                    mysqli_query($conn, "UPDATE bids SET uploaded_files = '$updated_files_str' WHERE id = $bid_id");
                }
            }
            mysqli_query($conn, "DELETE FROM bid_remarks WHERE id = $del_id AND bid_id = $bid_id");
            echo "<script>alert('Record and associated files deleted successfully!'); window.location.href='view_bid.php?id=$bid_id';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Access Denied: Only Admin can delete records.'); window.history.back();</script>";
        exit();
    }
}

// Company List for Dropdown
$company_list = ["Sunlite", "Rudraksh", "BDC", "TCI", "Aggarwal Traders", "VINAYAK SPORTS INDUSTRIES", "M/s JBS Technology Private Limited", "AV Media", "Kartik", "AVC", "shri ridhi sidhi enterprises", "SHREE SHYAM AND COMPANY", "Syniso", "NETSURE IT SOLUTION PRIVATE LIMITED", "A G SOLUTIONS"];

$current_date_val = date('Y-m-d');
$current_datetime_val = date('Y-m-d\TH:i');
$max_date_val = date('Y-m-d', strtotime('+4 months'));
$max_datetime_val = date('Y-m-d\TH:i', strtotime('+4 months'));

$doc_awaited_exists = false;
$doc_aw_q = mysqli_query($conn, "SELECT id FROM bid_remarks WHERE bid_id = $bid_id AND remark_text LIKE '%Documents Awaited:%'");
if ($doc_aw_q && mysqli_num_rows($doc_aw_q) > 0) {
    $doc_awaited_exists = true;
}

// Fetch Submitted Companies
$company_levels = [];
$company_emds = [];
$sub_q = mysqli_query($conn, "SELECT remark_text FROM bid_remarks WHERE bid_id = $bid_id AND remark_text LIKE '%[SUBMISSION_CARD]%' ORDER BY id ASC");
while($row = mysqli_fetch_assoc($sub_q)) {
    preg_match('/<b>Company:<\/b>\s*(.*?)\s*<\/div>/', $row['remark_text'], $c_match);
    preg_match('/<b>EMD Submitted:<\/b>\s*<span[^>]*>(Yes|No|No \(Exempted\))<\/span>/', $row['remark_text'], $e_match);
    if(isset($c_match[1])) {
        $comp = htmlspecialchars_decode(trim($c_match[1]), ENT_QUOTES);
        $company_levels[$comp] = true;
        if(isset($e_match[1])) $company_emds[$comp] = $e_match[1];
    }
}
$submitted_companies = array_keys($company_levels);

// Tech Eval Funnel
$tech_status_map = [];
$te_check_res = mysqli_query($conn, "SELECT remark_text FROM bid_remarks WHERE bid_id = $bid_id AND remark_text LIKE '%[TECHEVAL_CARD]%' ORDER BY id ASC");
if ($te_check_res) {
    while ($row = mysqli_fetch_assoc($te_check_res)) {
        preg_match_all('/<tr><td.*?>(.*?)<\/td><td><span class=\'badge[^\']+\'>(.*?)<\/span><\/td>.*?<\/tr>/', $row['remark_text'], $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $c_name = htmlspecialchars_decode(trim($m[1]), ENT_QUOTES);
            $c_status = trim($m[2]);
            $tech_status_map[$c_name] = $c_status; 
        }
    }
}

$qualified_companies_tech = [];
foreach($tech_status_map as $c => $s) {
    if($s === 'Qualified') { $qualified_companies_tech[] = $c; }
}

$comp_options_sub_html = "<option value=''>Select Company...</option>";
foreach($company_list as $c) { $comp_options_sub_html .= "<option value='" . htmlspecialchars($c, ENT_QUOTES) . "'>" . htmlspecialchars($c, ENT_QUOTES) . "</option>"; }

$comp_options_tech_html = "<option value=''>Select Company...</option>";
foreach($submitted_companies as $c) { $comp_options_tech_html .= "<option value='" . htmlspecialchars($c, ENT_QUOTES) . "'>" . htmlspecialchars($c, ENT_QUOTES) . "</option>"; }

// STRICT TECHNICAL VALIDATION FOR FIN EVAL POOL
$comp_options_qualified_html = "<option value=''>Select Company...</option>";
$pool_for_next_stages = $qualified_companies_tech; 
foreach($pool_for_next_stages as $c) { $comp_options_qualified_html .= "<option value='" . htmlspecialchars($c, ENT_QUOTES) . "'>" . htmlspecialchars($c, ENT_QUOTES) . "</option>"; }

// AWARD POOL (FINANCIAL QUALIFIED)
$financially_evaluated_companies = [];
$fe_companies_q = mysqli_query($conn, "SELECT remark_text FROM bid_remarks WHERE bid_id = $bid_id AND remark_text LIKE '%[FINEVAL_CARD]%'");
while($r = mysqli_fetch_assoc($fe_companies_q)) {
    preg_match_all('/<tr><td.*?>(.*?)<\/td>.*?<td class=\'fw-bold fs-5\'>₹([\d,\.]+)<\/td><\/tr>/s', $r['remark_text'], $matches, PREG_SET_ORDER);
    foreach($matches as $m) { 
        $c_name = htmlspecialchars_decode(trim($m[1]), ENT_QUOTES);
        $financially_evaluated_companies[$c_name] = true;
    }
}
$award_pool = array_keys($financially_evaluated_companies);

if(empty($award_pool)) { $award_pool = $qualified_companies_tech; }

// Fetch already awarded companies so they are removed from the dropdown
$awarded_companies = [];
$aw_check_q = mysqli_query($conn, "SELECT remark_text FROM bid_remarks WHERE bid_id = $bid_id AND remark_text LIKE '%[AWARD_CARD]%'");
if ($aw_check_q) {
    while($r = mysqli_fetch_assoc($aw_check_q)){
        preg_match('/<b>Company:<\/b>\s*(.*?)<\/div>/', $r['remark_text'], $c_match);
        if(isset($c_match[1])) {
            $awarded_companies[] = htmlspecialchars_decode(trim($c_match[1]), ENT_QUOTES);
        }
    }
}

$comp_options_award_html = "<option value=''>Select Company...</option>";
foreach($award_pool as $c) { 
    if (!in_array($c, $awarded_companies)) {
        $comp_options_award_html .= "<option value='" . htmlspecialchars($c, ENT_QUOTES) . "'>" . htmlspecialchars($c, ENT_QUOTES) . "</option>"; 
    }
}

// Fetch Prices for Auto-fill in forms
$company_prices = [];
$sub_prices_q = mysqli_query($conn, "SELECT remark_text FROM bid_remarks WHERE bid_id = $bid_id AND remark_text LIKE '%[SUBMISSION_CARD]%'");
while($r = mysqli_fetch_assoc($sub_prices_q)) {
    preg_match('/<b>Company:<\/b>\s*(.*?)\s*<\/div>/', $r['remark_text'], $c_m);
    preg_match('/Grand Total.*?₹([\d,\.]+)/s', $r['remark_text'], $p_m);
    if(isset($c_m[1]) && isset($p_m[1])) { $company_prices[htmlspecialchars_decode(trim($c_m[1]), ENT_QUOTES)] = str_replace(',', '', $p_m[1]); }
}
$fe_prices_q = mysqli_query($conn, "SELECT remark_text FROM bid_remarks WHERE bid_id = $bid_id AND remark_text LIKE '%[FINEVAL_CARD]%'");
while($r = mysqli_fetch_assoc($fe_prices_q)) {
    preg_match_all('/<tr><td.*?>(.*?)<\/td>.*?<td class=\'fw-bold fs-5\'>₹([\d,\.]+)<\/td><\/tr>/s', $r['remark_text'], $matches, PREG_SET_ORDER);
    foreach($matches as $m) { $company_prices[htmlspecialchars_decode(trim($m[1]), ENT_QUOTES)] = str_replace(',', '', $m[2]); }
}
$ra_prices_q = mysqli_query($conn, "SELECT remark_text FROM bid_remarks WHERE bid_id = $bid_id AND remark_text LIKE '%[RA_INPROGRESS_CARD]%'");
while($r = mysqli_fetch_assoc($ra_prices_q)) {
    preg_match('/<b>Company:<\/b>\s*(.*?)\s*<\/div>/', $r['remark_text'], $c_m);
    preg_match('/<b>RA Total Price:<\/b>\s*<span[^>]*>₹([\d,\.]+)<\/span>/', $r['remark_text'], $p_m);
    if(isset($c_m[1]) && isset($p_m[1])) { $company_prices[htmlspecialchars_decode(trim($c_m[1]), ENT_QUOTES)] = str_replace(',', '', $p_m[1]); }
}

$pn_total = 0;
$pn_prices_q = mysqli_query($conn, "SELECT remark_text FROM bid_remarks WHERE bid_id = $bid_id AND remark_text LIKE '%[PN_CARD]%' ORDER BY id DESC LIMIT 1");
if($pn_prices_q && mysqli_num_rows($pn_prices_q) > 0) {
    $r = mysqli_fetch_assoc($pn_prices_q);
    preg_match('/New Total.*?₹([\d,\.]+)/s', $r['remark_text'], $p_m);
    if(isset($p_m[1])) { $pn_total = str_replace(',', '', $p_m[1]); }
}

$base_price_val = '';
$bp_query = mysqli_query($conn, "SELECT remark_text FROM bid_remarks WHERE bid_id = '$bid_id' AND remark_text LIKE '%[SUBMISSION_CARD]%' ORDER BY id DESC LIMIT 1");
if($bp_query && mysqli_num_rows($bp_query) > 0) {
    $bp_row = mysqli_fetch_assoc($bp_query);
    if (preg_match('/>₹([\d,\.]+)<\/span>/', $bp_row['remark_text'], $matches)) { $base_price_val = str_replace(',', '', $matches[1]); }
}

// SINGLE BUTTON SUBMIT LOGIC
if (isset($_POST['save_all'])) {
    $old_data_query = mysqli_query($conn, "SELECT bid_status, end_date, uploaded_files FROM bids WHERE id = '$bid_id'");
    $old_data = mysqli_fetch_assoc($old_data_query);
    $existing_status = $old_data['bid_status'];
    $existing_end_date = $old_data['end_date'];
    $existing_files = trim($old_data['uploaded_files'] ?? '');

    $u_bid_no = mysqli_real_escape_string($conn, trim($_POST['bid_no']));
    $u_org_name = mysqli_real_escape_string($conn, trim($_POST['org_name']));
    $u_item_category = mysqli_real_escape_string($conn, trim($_POST['item_category']));
    $u_bid_status = mysqli_real_escape_string($conn, trim($_POST['bid_status']));
    $u_end_date = $existing_end_date; 

    $new_remark = trim($_POST['remark_text'] ?? '');
    $user_note_html = "";
    if (!empty($new_remark)) {
        $user_note_html = "<div class='mt-3 p-3 rounded-3 shadow-sm' style='background-color: #f8fafc; border-left: 4px solid #3b82f6; font-size: 0.9rem;'><b class='text-dark'><i class='fas fa-comment-dots text-primary me-1'></i> User Note:</b><div class='mt-1 text-muted'>" . nl2br(htmlspecialchars($new_remark, ENT_QUOTES, 'UTF-8')) . "</div></div>";
    }

    if ($is_canceled && $u_bid_status !== 'Bid Cancel') die("<script>alert('Error: This Bid is Cancelled and locked. Status cannot be changed.'); history.back();</script>");
    

    $upload_dir = 'uploads/bids/'; 
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
    $new_uploaded_files_array = [];
    $current_datetime = date('d/m/Y h:i A');

    function process_strict_files($file_input_name, $prefix, $upload_dir) {
        global $new_uploaded_files_array;
        $saved_files = [];
        if (!isset($_FILES[$file_input_name]) || empty(array_filter($_FILES[$file_input_name]['name']))) {
            die("<script>alert('Strict Error: Uploading files/screenshots is mandatory for this action.'); history.back();</script>");
        }
        $total_files = count($_FILES[$file_input_name]['name']);
        for ($i = 0; $i < $total_files; $i++) {
            $original_filename = $_FILES[$file_input_name]['name'][$i];
            $tmp_name = $_FILES[$file_input_name]['tmp_name'][$i];
            $clean_filename = preg_replace('/[^a-zA-Z0-9.\-_]/', '', $original_filename);
            if($clean_filename == 'image.png') $clean_filename = "screenshot_".$i.".png";
            $new_filename = time() . $prefix . $i . "_" . $clean_filename; 
            if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) { 
                $saved_files[] = $new_filename; 
                $new_uploaded_files_array[] = $new_filename;
            }
        }
        return $saved_files;
    }

    // ACTION 1: CORRIGENDUM & EXTENSION
    if ($u_bid_status === 'Corrigendum' || $u_bid_status === 'Extension date') {
        $is_corrigendum = ($u_bid_status === 'Corrigendum');
        $file_prefix = $is_corrigendum ? '_C_' : '_E_';
        $action_file_names = [];
        $a_date = $_POST['action_date'] ?? '';
        $a_end_date = $_POST['action_end_date'] ?? '';
        
        if (empty($a_end_date)) die("<script>alert('Error: New End Date is required for this action.'); history.back();</script>");
        
        if (strtotime($a_end_date) <= strtotime($existing_end_date)) {
            die("<script>alert('Error: The New End Date must strictly be greater than the existing End Date.'); history.back();</script>");
        }
        $u_end_date = $a_end_date;

        if (isset($_FILES['action_files']) && !empty(array_filter($_FILES['action_files']['name']))) {
            $total_files = count($_FILES['action_files']['name']);
            for ($i = 0; $i < $total_files; $i++) {
                $original_filename = $_FILES['action_files']['name'][$i];
                $tmp_name = $_FILES['action_files']['tmp_name'][$i];
                $clean_filename = preg_replace('/[^a-zA-Z0-9.\-_]/', '', $original_filename);
                $new_filename = time() . $file_prefix . $i . "_" . $clean_filename; 
                if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) { 
                    $action_file_names[] = $new_filename; $new_uploaded_files_array[] = $new_filename;
                }
            }
        }
        $card_theme = 'primary'; $card_icon = $is_corrigendum ? 'fa-exclamation-triangle' : 'fa-calendar-plus';
        $card_title = $is_corrigendum ? 'Corrigendum Update' : 'Extension Update';
        $tag_prefix = $is_corrigendum ? '[CORRIGENDUM_CARD]' : '[EXTENSION_CARD]';

        $c_remark = "<div class='border border-{$card_theme} rounded-3 shadow-sm mb-4 overflow-hidden'>
            <div class='bg-{$card_theme} bg-opacity-10 text-{$card_theme} px-3 py-3 fw-bold border-bottom border-{$card_theme} border-opacity-25 d-flex justify-content-between align-items-center'>
                <span><i class='fas {$card_icon} me-1'></i> {$card_title}</span>
                <span class='text-muted small fw-normal'><i class='far fa-clock'></i> {$current_datetime} | By: {$logged_in_user}</span>
            </div><div class='p-3 bg-white text-dark'><div class='row mb-2 small'>";
        if (!empty($a_date)) { $c_remark .= "<div class='col-md-6 mb-2'><b>Current Date:</b> " . date('d/m/Y', strtotime($a_date)) . "</div>"; }
        $c_remark .= "<div class='col-md-6 mb-2'><b>New End Date:</b> <span class='text-primary fw-bold'>" . date('d/m/Y h:i A', strtotime($a_end_date)) . "</span></div></div>";
        if (!empty($action_file_names)) {
            $c_remark .= "<div class='pt-3 border-top border-light'><b><i class='fas fa-paperclip'></i> Attached Reference Files:</b><br><div class='d-flex flex-wrap gap-2 mt-2'>";
            foreach ($action_file_names as $file) {
                $file_url = 'uploads/bids/' . $file;
                $display_name = preg_replace('/^[0-9]+_[CE]_\d+_/', '', $file);
                $c_remark .= "<a href='{$file_url}' target='_blank' class='badge bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm'><i class='fas fa-file me-1 text-primary'></i> {$display_name}</a>";
            }
            $c_remark .= "</div></div>";
        }
        $c_remark .= "</div></div>";
        $tagged_remark = $tag_prefix . $c_remark;
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $tagged_remark) . "', '$logged_in_user')");

        $chat_msg = $is_corrigendum ? "🚨 <b>Corrigendum Update:</b><br>" : "📅 <b>Extension Date Update:</b><br>";
        if (!empty($a_date)) $chat_msg .= "<b>Current Date:</b> " . date('d/m/Y', strtotime($a_date)) . "<br>";
        $chat_msg .= "<b>New End Date:</b> " . date('d/m/Y h:i A', strtotime($a_end_date));
        
        if (!empty($action_file_names)) {
            $chat_msg .= "<br><br><b><i class='fas fa-paperclip text-muted'></i> Attached Files:</b> ";
            $chat_file_links = [];
            foreach ($action_file_names as $file) {
                $file_url = 'uploads/bids/' . $file;
                $display_name = preg_replace('/^[0-9]+_[CE]_\d+_/', '', $file);
                $chat_file_links[] = "<a href='{$file_url}' target='_blank' style='color: #4f46e5; text-decoration: none; font-weight: 500;'><i class='fas fa-file-download'></i> {$display_name}</a>";
            }
            $chat_msg .= implode(' &nbsp;|&nbsp; ', $chat_file_links);
        }
        
        $chat_msg .= $user_note_html;
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $chat_msg) . "', '$logged_in_user')");
        $new_remark = '';
    }

    // ACTION 2: SUBMITTED / RE-SUBMITTED
    elseif ($u_bid_status === 'Submitted' || $u_bid_status === 'Re-submitted') {
        $sub_company = mysqli_real_escape_string($conn, trim($_POST['sub_company'] ?? ''));
        $sub_ip = mysqli_real_escape_string($conn, trim($_POST['sub_ip'] ?? ''));
        $sub_datetime = $_POST['sub_datetime'] ?? '';
        
        $bid_query_temp = mysqli_query($conn, "SELECT emd_amount FROM bids WHERE id = $bid_id");
        $bid_temp = mysqli_fetch_assoc($bid_query_temp);
        
        $emd_amount_val = floatval($bid_temp['emd_amount'] ?? 0);
        $requires_fixed_emd = ($emd_amount_val > 0);
        
        $sub_emd = isset($_POST['sub_emd']) ? mysqli_real_escape_string($conn, trim($_POST['sub_emd'])) : 'N/A';

        if (empty($sub_company) || empty($sub_ip) || empty($sub_datetime)) {
            die("<script>alert('Error: All required fields are missing for Submission.'); history.back();</script>");
        }

        if ($u_bid_status === 'Submitted' && in_array($sub_company, $submitted_companies)) {
            die("<script>alert('Error: You have already submitted for \'" . $sub_company . "\'. Multiple submissions not allowed unless Re-submitting!'); history.back();</script>");
        }
        if ($u_bid_status === 'Re-submitted' && !in_array($sub_company, $submitted_companies)) {
            die("<script>alert('Error: You cannot Re-submit for \'" . $sub_company . "\' because they have no initial submission on record! Please select \"Submitted\" instead.'); history.back();</script>");
        }

        $sub_emd_docs = [];
        if (in_array($sub_emd, ['Yes', 'No', 'No (Exempted)'])) {
            $sub_emd_docs = process_strict_files('sub_emd_docs', '_SEMD_', $upload_dir);
        }
        
        $sub_docs = process_strict_files('sub_docs', '_SD_', $upload_dir);
        $sub_ip_screens = process_strict_files('sub_ip_screens', '_SIP_', $upload_dir);
        $sub_price_screens = process_strict_files('sub_price_screens', '_SPR_', $upload_dir);
        
        $sub_file_names = array_merge($sub_emd_docs, $sub_docs, $sub_ip_screens, $sub_price_screens);

        $formatted_datetime = date('d/m/Y h:i A', strtotime($sub_datetime));
        $submission_title = ($u_bid_status === 'Re-submitted') ? 'Re-Submission Record' : 'Final Submission Record';

        $emd_display = "";
        if ($sub_emd !== 'N/A' && $sub_emd !== '') {
            $badge_emd_clr = ($sub_emd === 'Yes') ? 'success' : 'secondary';
            $emd_display = "<div class='col-md-4 mb-2'><b>EMD Submitted:</b> <span class='badge bg-{$badge_emd_clr} px-2'>{$sub_emd}</span></div>";
        }

        $sub_remark = "<div class='border border-info rounded-3 shadow-sm mb-4 overflow-hidden'>
            <div class='bg-info bg-opacity-10 text-info px-3 py-3 fw-bold border-bottom border-info border-opacity-25 d-flex justify-content-between align-items-center'>
                <span><i class='fas fa-check-circle me-1'></i> {$submission_title}</span>
                <span class='text-muted small fw-normal'><i class='far fa-clock'></i> {$formatted_datetime} | By: {$logged_in_user}</span>
            </div><div class='p-3 bg-white text-dark'><div class='row mb-4 small'>
            <div class='col-md-4 mb-2'><b>Company:</b> {$sub_company}</div><div class='col-md-4 mb-2'><b>IP Address:</b> {$sub_ip}</div>
            <div class='col-md-4 mb-2'><b>Level:</b> <span class='badge bg-secondary px-3 py-2 fs-5 shadow-sm border'>Calculated Dynamically</span></div>{$emd_display}</div>";

        $prod_names = $_POST['prod_name'] ?? []; $prod_qtys = $_POST['prod_qty'] ?? []; $prod_prices = $_POST['prod_price'] ?? []; $grand_total = 0;
        if(!empty($prod_names) && !empty($prod_names[0])) {
            $sub_remark .= "<div class='modern-table-wrapper mb-3'><table class='modern-table'>
                <thead><tr><th class='ps-3'>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>";
            for($i=0; $i < count($prod_names); $i++) {
                if(empty(trim($prod_names[$i]))) continue;
                $n = htmlspecialchars($prod_names[$i]); $q = floatval($prod_qtys[$i] ?? 0); $p = floatval($prod_prices[$i] ?? 0); $t = $q * $p; $grand_total += $t;
                $sub_remark .= "<tr><td class='ps-3'>{$n}</td><td>{$q}</td><td>₹" . number_format($p, 2) . "</td><td class='fw-bold text-dark'>₹" . number_format($t, 2) . "</td></tr>";
            }
            $sub_remark .= "</tbody></table></div><div class='d-flex justify-content-end mb-3 mt-2'><div class='border border-info px-4 py-2 rounded-3 d-flex align-items-center shadow-sm' style='background-color: #eff6ff;'><span class='fw-bold me-3 text-uppercase text-muted' style='letter-spacing: 0.5px; font-size: 14px;'>Grand Total</span><span class='fw-bolder text-primary' style='font-size: 26px !important; line-height: 1;'>₹" . number_format($grand_total, 2) . "</span></div></div>";
        }

        if (!empty($sub_file_names)) {
            $sub_remark .= "<div class='pt-3 border-top border-light'><b><i class='fas fa-paperclip'></i> Uploaded Assets:</b><br><div class='d-flex flex-wrap gap-3 mt-3'>";
            foreach ($sub_file_names as $file) {
                $file_url = 'uploads/bids/' . $file;
                $display_name = preg_replace('/^[0-9]+_S[D]?[S]?[IP]?[PR]?[EMD]?_\d+_/', '', $file);
                if (strpos($file, '_SIP_') !== false) { $icon = 'fa-network-wired text-info'; $doc_label = 'IP Screen'; }
                elseif (strpos($file, '_SPR_') !== false) { $icon = 'fa-rupee-sign text-primary'; $doc_label = 'Price Screen'; }
                elseif (strpos($file, '_SS_') !== false) { $icon = 'fa-image text-primary'; $doc_label = 'Screenshot'; }
                elseif (strpos($file, '_SEMD_') !== false) { $icon = 'fa-file-invoice-dollar text-primary'; $doc_label = 'EMD Doc'; }
                else { $icon = 'fa-file-pdf text-primary'; $doc_label = 'Sub Doc'; }
                $sub_remark .= "<div class='text-center'><div class='small fw-bold text-muted mb-1' style='font-size:0.7rem;'>{$doc_label}</div><a href='{$file_url}' target='_blank' class='badge bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm' title='{$display_name}'><i class='fas {$icon} me-1'></i> {$display_name}</a></div>";
            }
            $sub_remark .= "</div></div>";
        }
        $sub_remark .= "</div></div>";
        $tagged_sub_remark = "[SUBMISSION_CARD]" . $sub_remark;
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $tagged_sub_remark) . "', '$logged_in_user')");

        $status_word = ($u_bid_status === 'Re-submitted') ? 'Re-Submitted' : 'Submitted';
        $chat_msg = "✅ <b>Bid {$status_word}:</b> Submission recorded for <b>{$sub_company}</b>.<br><span class='small text-muted'><b>Initial Level:</b> Calculated Dynamically";
        $chat_msg .= "</span>";
        
        if (!empty($sub_file_names)) {
            $chat_msg .= "<br><br><b><i class='fas fa-paperclip text-muted'></i> Attached Files:</b> ";
            $chat_file_links = [];
            foreach ($sub_file_names as $file) {
                $file_url = 'uploads/bids/' . $file;
                $display_name = preg_replace('/^[0-9]+_S[D]?[S]?[IP]?[PR]?[EMD]?_\d+_/', '', $file);
                $chat_file_links[] = "<a href='{$file_url}' target='_blank' style='color: #3b82f6; text-decoration: none; font-weight: 500;'><i class='fas fa-file-download'></i> {$display_name}</a>";
            }
            $chat_msg .= implode(' &nbsp;|&nbsp; ', $chat_file_links);
        }
        
        $chat_msg .= $user_note_html;
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $chat_msg) . "', '$logged_in_user')");
        $new_remark = '';
    }

    // ACTION 3: QUERY WINDOW
    elseif ($u_bid_status === 'Query window') {
        $q_start_date = $_POST['query_start_date'] ?? ''; $q_end_date = $_POST['query_end_date'] ?? ''; $q_ip = $_POST['query_ip'] ?? '';
        if(empty($q_start_date) || empty($q_end_date)) die("<script>alert('Error: Dates are required for Query Window.'); history.back();</script>");
        
        $query_screens = process_strict_files('query_screens', '_QS_', $upload_dir);
        $query_docs = process_strict_files('query_docs', '_QD_', $upload_dir);
        $query_sub_screens = process_strict_files('query_sub_screens', '_QSS_', $upload_dir);
        $query_file_names = array_merge($query_screens, $query_docs, $query_sub_screens);

        $q_remark = "<div class='border border-primary rounded-3 shadow-sm mb-4 overflow-hidden'><div class='bg-primary bg-opacity-10 text-primary px-3 py-3 fw-bold border-bottom border-primary border-opacity-25 d-flex justify-content-between align-items-center'><span><i class='fas fa-question-circle me-1'></i> Query Window Record</span><span class='text-muted small fw-normal'><i class='far fa-clock'></i> {$current_datetime} | By: {$logged_in_user}</span></div><div class='p-3 bg-white text-dark'><div class='row mb-3 small'><div class='col-md-4'><b>Query Start Date:</b> " . date('d/m/Y h:i A', strtotime($q_start_date)) . "</div><div class='col-md-4'><b>Query End Date:</b> <span class='text-primary fw-bold'>" . date('d/m/Y h:i A', strtotime($q_end_date)) . "</span></div><div class='col-md-4'><b>IP Address:</b> {$q_ip}</div></div>";
        if (!empty($query_file_names)) {
            $q_remark .= "<div class='pt-3 border-top border-light'><b><i class='fas fa-paperclip'></i> Attached Assets:</b><br><div class='d-flex flex-wrap gap-2 mt-2'>";
            foreach ($query_file_names as $file) {
                $file_url = 'uploads/bids/' . $file; $display_name = preg_replace('/^[0-9]+_(QS|QD|QSS)_\d+_/', '', $file);
                $icon = 'fa-file'; if(strpos($file, '_QS_') !== false) { $icon = 'fa-camera'; } if(strpos($file, '_QD_') !== false) { $icon = 'fa-file-pdf'; } if(strpos($file, '_QSS_') !== false) { $icon = 'fa-check-circle'; }
                $q_remark .= "<a href='{$file_url}' target='_blank' class='badge bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm'><i class='fas {$icon} me-1 text-primary'></i> <span class='text-primary'>{$display_name}</span></a>";
            }
            $q_remark .= "</div></div>";
        }
        $q_remark .= "</div></div>";
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, "[QUERY_CARD]" . $q_remark) . "', '$logged_in_user')");

        $chat_msg = "💬 <b>Query Window:</b> A new query record has been added.<br><span class='small text-muted'><b>Start:</b> " . date('d/m/Y h:i A', strtotime($q_start_date)) . " &nbsp;|&nbsp; <b>End:</b> " . date('d/m/Y h:i A', strtotime($q_end_date)) . "</span>";
        if (!empty($query_file_names)) {
            $chat_msg .= "<br><br><b><i class='fas fa-paperclip text-muted'></i> Attached Files:</b> ";
            $chat_file_links = [];
            foreach ($query_file_names as $file) {
                $file_url = 'uploads/bids/' . $file;
                $display_name = preg_replace('/^[0-9]+_(QS|QD|QSS)_\d+_/', '', $file);
                $chat_file_links[] = "<a href='{$file_url}' target='_blank' style='color: #3b82f6; text-decoration: none; font-weight: 500;'><i class='fas fa-file-download'></i> {$display_name}</a>";
            }
            $chat_msg .= implode(' &nbsp;|&nbsp; ', $chat_file_links);
        }
        
        $chat_msg .= $user_note_html;
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $chat_msg) . "', '$logged_in_user')");
        $new_remark = '';
    }

    // ACTION 10: REPRESENTATION WINDOW
    elseif ($u_bid_status === 'Representation') {
        $rep_ip = $_POST['rep_ip'] ?? '';
        
        $rep_screens = process_strict_files('rep_screens', '_REPS_', $upload_dir);
        $rep_docs = process_strict_files('rep_docs', '_REPD_', $upload_dir);
        $rep_file_names = array_merge($rep_screens, $rep_docs);

        $r_remark = "<div class='border border-info rounded-3 shadow-sm mb-4 overflow-hidden'><div class='bg-info bg-opacity-10 text-info px-3 py-3 fw-bold border-bottom border-info border-opacity-25 d-flex justify-content-between align-items-center'><span><i class='fas fa-gavel me-1'></i> Representation Record</span><span class='text-muted small fw-normal'><i class='far fa-clock'></i> {$current_datetime} | By: {$logged_in_user}</span></div><div class='p-3 bg-white text-dark'><div class='mb-3 small'><b>IP Address:</b> {$rep_ip}</div>";
        if (!empty($rep_file_names)) {
            $r_remark .= "<div class='pt-3 border-top border-light'><b><i class='fas fa-paperclip'></i> Attached Assets:</b><br><div class='d-flex flex-wrap gap-2 mt-2'>";
            foreach ($rep_file_names as $file) {
                $file_url = 'uploads/bids/' . $file; $display_name = preg_replace('/^[0-9]+_(REPS|REPD)_\d+_/', '', $file);
                $icon = (strpos($file, '_REPS_') !== false) ? 'fa-camera' : 'fa-file-pdf';
                $r_remark .= "<a href='{$file_url}' target='_blank' class='badge bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm'><i class='fas {$icon} me-1 text-primary'></i> <span class='text-primary'>{$display_name}</span></a>";
            }
            $r_remark .= "</div></div>";
        }
        $r_remark .= "</div></div>";
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, "[REP_CARD]" . $r_remark) . "', '$logged_in_user')");

        $chat_msg = "⚖️ <b>Representation Window:</b> A new representation record has been added.";
        if (!empty($rep_file_names)) {
            $chat_msg .= "<br><br><b><i class='fas fa-paperclip text-muted'></i> Attached Files:</b> ";
            $chat_file_links = [];
            foreach ($rep_file_names as $file) {
                $file_url = 'uploads/bids/' . $file;
                $display_name = preg_replace('/^[0-9]+_(REPS|REPD)_\d+_/', '', $file);
                $chat_file_links[] = "<a href='{$file_url}' target='_blank' style='color: #3b82f6; text-decoration: none; font-weight: 500;'><i class='fas fa-file-download'></i> {$display_name}</a>";
            }
            $chat_msg .= implode(' &nbsp;|&nbsp; ', $chat_file_links);
        }
        
        $chat_msg .= $user_note_html;
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $chat_msg) . "', '$logged_in_user')");
        $new_remark = '';
    }

    // ACTION 4: TECHNICAL EVALUATION
    elseif ($u_bid_status === 'Technical Evaluation' || $u_bid_status === 'Re-Technical Evaluation') {
        $te_date = $_POST['techeval_date'] ?? ''; $te_comps = $_POST['techeval_company'] ?? []; $te_statuses = $_POST['techeval_status'] ?? [];
        if (empty($te_date)) die("<script>alert('Error: Date & Time is required.'); history.back();</script>");
        
        $te_file_names = process_strict_files('techeval_screens', '_TE_', $upload_dir);
        $te_remark = "<div class='border border-info rounded-3 shadow-sm mb-4 overflow-hidden'><div class='bg-info bg-opacity-10 text-info px-3 py-3 fw-bold border-bottom border-info border-opacity-25 d-flex justify-content-between align-items-center'><span><i class='fas fa-tasks me-1'></i> Technical Evaluation Record</span><span class='text-muted small fw-normal'><i class='far fa-clock'></i> {$current_datetime} | By: {$logged_in_user}</span></div><div class='p-3 bg-white text-dark'><div class='mb-3 small'><b>Evaluation Date:</b> " . date('d/m/Y h:i A', strtotime($te_date)) . "</div>";
        $has_valid_company = false; $html_rows = "";
        
        $chat_detail_list = "<ul class='mb-0 mt-2' style='padding-left: 1.2rem; font-size: 0.85rem;'>";
        if(!empty($te_comps) && !empty($te_comps[0])) {
            for($i=0; $i < count($te_comps); $i++) {
                $c_raw = trim($te_comps[$i]); if(empty($c_raw)) continue;
                $has_valid_company = true; $c = htmlspecialchars($c_raw, ENT_QUOTES); $s = htmlspecialchars($te_statuses[$i] ?? '', ENT_QUOTES);
                $badge_clr = ($s == 'Qualified') ? 'primary' : 'secondary';
                $html_rows .= "<tr><td class='ps-3'>{$c}</td><td><span class='badge bg-{$badge_clr}'>{$s}</span></td></tr>";
                $s_color = ($s == 'Qualified') ? 'text-success' : 'text-danger';
                $chat_detail_list .= "<li><b>{$c}</b>: <span class='fw-bold {$s_color}'>{$s}</span></li>";
            }
            if($has_valid_company) { $te_remark .= "<div class='modern-table-wrapper mb-3'><table class='modern-table'><thead><tr><th class='ps-3'>Company Name</th><th style='width: 30%;'>Status</th></tr></thead><tbody>" . $html_rows . "</tbody></table></div>"; }
        }
        $chat_detail_list .= "</ul>";

        if (!empty($te_file_names)) {
            $te_remark .= "<div class='pt-2 border-top border-light'><b><i class='fas fa-paperclip'></i> Attached Screenshots:</b><br><div class='d-flex flex-wrap gap-2 mt-2'>";
            foreach ($te_file_names as $file) { $te_remark .= "<a href='uploads/bids/{$file}' target='_blank' class='badge bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm'><i class='fas fa-image me-1 text-primary'></i> " . preg_replace('/^[0-9]+_TE_\d+_/', '', $file) . "</a>"; }
            $te_remark .= "</div></div>";
        }
        $te_remark .= "</div></div>";
        
        if ($has_valid_company || !empty($te_file_names)) { 
            mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, "[TECHEVAL_CARD]" . $te_remark) . "', '$logged_in_user')"); 
            $chat_msg = "📝 <b>Technical Evaluation Results:</b>" . $chat_detail_list;
            if (!empty($te_file_names)) {
                $chat_msg .= "<br><b><i class='fas fa-paperclip text-muted'></i> Attached Files:</b> ";
                $chat_file_links = [];
                foreach ($te_file_names as $file) {
                    $file_url = 'uploads/bids/' . $file;
                    $display_name = preg_replace('/^[0-9]+_TE_\d+_/', '', $file);
                    $chat_file_links[] = "<a href='{$file_url}' target='_blank' style='color: #4f46e5; text-decoration: none; font-weight: 500;'><i class='fas fa-file-download'></i> {$display_name}</a>";
                }
                $chat_msg .= implode(' &nbsp;|&nbsp; ', $chat_file_links);
            }
            
            $chat_msg .= $user_note_html;
            mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $chat_msg) . "', '$logged_in_user')");
            $new_remark = '';
        }
    }

    // ACTION 5: FINANCIAL EVALUATION
    elseif ($u_bid_status === 'Financial Evaluation') {
        $fe_date = $_POST['fineval_date'] ?? ''; $fe_comps = $_POST['fineval_company'] ?? []; $fe_prices = $_POST['fineval_price'] ?? [];
        if (empty($fe_date)) die("<script>alert('Error: Date & Time is required.'); history.back();</script>");
        
        $fe_file_names = process_strict_files('fineval_screens', '_FE_', $upload_dir);
        
       // BACKEND CALCULATION FOR RANKS (Sorted by Price Ascending L1 to L-N)
        $fe_data_calc = [];
        for($i=0; $i < count($fe_comps); $i++) {
            $c_raw = trim($fe_comps[$i]); if(empty($c_raw)) continue;
            // Gateway restrict
            if (!in_array($c_raw, $qualified_companies_tech)) {
                die("<script>alert('Error: Company \"$c_raw\" is NOT qualified in Technical Evaluation. They cannot proceed to Financial Evaluation.'); history.back();</script>");
            }
            $fe_data_calc[] = ['comp' => $c_raw, 'price' => floatval($fe_prices[$i] ?? 0)];
        }
        
        // Sort the array by price (Lowest to Highest)
        usort($fe_data_calc, function($a, $b) {
            return $a['price'] <=> $b['price'];
        });

        $fe_remark = "<div class='border border-primary rounded-3 shadow-sm mb-4 overflow-hidden'><div class='bg-primary bg-opacity-10 text-primary px-3 py-3 fw-bold border-bottom border-primary border-opacity-25 d-flex justify-content-between align-items-center'><span><i class='fas fa-chart-line me-1'></i> Financial Evaluation Record</span><span class='text-muted small fw-normal'><i class='far fa-clock'></i> {$current_datetime} | By: {$logged_in_user}</span></div><div class='p-3 bg-white text-dark'><div class='mb-3 small'><b>Evaluation Date:</b> " . date('d/m/Y h:i A', strtotime($fe_date)) . "</div>";
        $has_valid_company = false; $html_rows = "";
        
        $chat_detail_list = "<ul class='mb-0 mt-2' style='padding-left: 1.2rem; font-size: 0.85rem;'>";
        if(!empty($fe_data_calc)) {
            $has_valid_company = true;
            $lvl_idx = 1;
            foreach($fe_data_calc as $d) {
                $c = htmlspecialchars($d['comp'], ENT_QUOTES); 
                $p = $d['price'];
                $l = 'L' . $lvl_idx;
                
                $badge_clr = ($l == 'L1') ? 'success' : (($l == 'L2') ? 'warning text-dark' : 'danger');
                $icon = ($l == 'L1') ? "<i class='fas fa-trophy text-warning me-1'></i> " : "";
                $html_rows .= "<tr><td class='ps-3'>{$c}</td><td><span class='badge bg-{$badge_clr} px-3 py-2 fs-5 shadow-sm border border-light'>{$icon}{$l}</span></td><td class='fw-bold fs-5'>₹" . number_format($p, 2) . "</td></tr>";
                $chat_detail_list .= "<li><b>{$c}</b>: <span class='fw-bold text-primary'>{$l}</span> (₹" . number_format($p, 2) . ")</li>";
                $lvl_idx++;
            }
            $fe_remark .= "<div class='modern-table-wrapper mb-3'><table class='modern-table'><thead><tr><th class='ps-3'>Company Name</th><th style='width: 20%;'>Level</th><th style='width: 25%;'>Price</th></tr></thead><tbody>" . $html_rows . "</tbody></table></div>";
        }
        $chat_detail_list .= "</ul>";

        if (!empty($fe_file_names)) {
            $fe_remark .= "<div class='pt-2 border-top border-light'><b><i class='fas fa-paperclip'></i> Attached Assets:</b><br><div class='d-flex flex-wrap gap-2 mt-2'>";
            foreach ($fe_file_names as $file) { $fe_remark .= "<a href='uploads/bids/{$file}' target='_blank' class='badge bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm'><i class='fas fa-file-invoice-dollar me-1 text-primary'></i> " . preg_replace('/^[0-9]+_FE_\d+_/', '', $file) . "</a>"; }
            $fe_remark .= "</div></div>";
        }
        $fe_remark .= "</div></div>";
        
        if ($has_valid_company || !empty($fe_file_names)) { 
            mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, "[FINEVAL_CARD]" . $fe_remark) . "', '$logged_in_user')"); 
            $chat_msg = "📈 <b>Financial Evaluation Results:</b>" . $chat_detail_list;
            if (!empty($fe_file_names)) {
                $chat_msg .= "<br><b><i class='fas fa-paperclip text-muted'></i> Attached Files:</b> ";
                $chat_file_links = [];
                foreach ($fe_file_names as $file) {
                    $file_url = 'uploads/bids/' . $file;
                    $display_name = preg_replace('/^[0-9]+_FE_\d+_/', '', $file);
                    $chat_file_links[] = "<a href='{$file_url}' target='_blank' style='color: #3b82f6; text-decoration: none; font-weight: 500;'><i class='fas fa-file-download'></i> {$display_name}</a>";
                }
                $chat_msg .= implode(' &nbsp;|&nbsp; ', $chat_file_links);
            }
            
            $chat_msg .= $user_note_html;
            mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $chat_msg) . "', '$logged_in_user')");
            $new_remark = '';
        }
    }

    // ACTION 6: REVERSE AUCTION
    elseif ($u_bid_status === 'Reverse Auction') {
        $ra_sub = $_POST['ra_sub_status'] ?? '';
        if (empty($ra_sub)) die("<script>alert('Error: Please select a Reverse Auction option.'); history.back();</script>");

        if ($ra_sub === 'RA Inprogress' && !$has_ra_create) die("<script>alert('Error: You cannot proceed to RA Inprogress without Creating RA first.'); history.back();</script>");
        if ($ra_sub === 'RA Finalize' && !$has_ra_inprog) die("<script>alert('Error: You cannot proceed to RA Finalize without any RA Inprogress entry.'); history.back();</script>");

        if ($ra_sub === 'Create RA') {
            $cr_date = $_POST['ra_create_date'] ?? ''; $st_date = $_POST['ra_start_date'] ?? ''; $en_date = $_POST['ra_end_date'] ?? '';
            if(empty($cr_date) || empty($st_date) || empty($en_date)) die("<script>alert('Error: All dates are required.'); history.back();</script>");
            $ra_screens = process_strict_files('ra_create_screens', '_RAC_', $upload_dir);
            $ra_docs = process_strict_files('ra_create_docs', '_RACD_', $upload_dir);
            $all_ra_files = array_merge($ra_screens, $ra_docs);

            $ra_remark = "<div class='border border-info rounded-3 shadow-sm mb-4 overflow-hidden'><div class='bg-info bg-opacity-10 text-info px-3 py-3 fw-bold border-bottom border-info border-opacity-25 d-flex justify-content-between align-items-center'><span><i class='fas fa-gavel me-1'></i> Reverse Auction: Created</span><span class='text-muted small fw-normal'><i class='far fa-clock'></i> {$current_datetime} | By: {$logged_in_user}</span></div><div class='p-3 bg-white text-dark'><div class='row mb-3 small'><div class='col-md-4'><b>Creation Date:</b> " . date('d/m/Y', strtotime($cr_date)) . "</div><div class='col-md-4'><b>Start Date:</b> " . date('d/m/Y h:i A', strtotime($st_date)) . "</div><div class='col-md-4'><b>End Date:</b> <span class='text-primary fw-bold'>" . date('d/m/Y h:i A', strtotime($en_date)) . "</span></div></div>";
            if (!empty($all_ra_files)) {
                $ra_remark .= "<div class='pt-3 border-top border-light'><b><i class='fas fa-paperclip'></i> Attached Assets:</b><br><div class='d-flex flex-wrap gap-2 mt-2'>";
                foreach ($all_ra_files as $file) {
                    $icon = strpos($file, '_RACD_') !== false ? 'fa-file-pdf text-primary' : 'fa-image text-primary';
                    $ra_remark .= "<a href='uploads/bids/{$file}' target='_blank' class='badge bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm'><i class='fas {$icon} me-1'></i> " . preg_replace('/^[0-9]+_RAC[D]?_\d+_/', '', $file) . "</a>";
                }
                $ra_remark .= "</div></div>";
            }
            $ra_remark .= "</div></div>";
            mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, "[RA_CREATE_CARD]" . $ra_remark) . "', '$logged_in_user')");

            $chat_msg = "⚖️ <b>Reverse Auction Created:</b> Scheduled from " . date('d/m/Y h:i A', strtotime($st_date)) . " to " . date('d/m/Y h:i A', strtotime($en_date)) . ".";
            if (!empty($all_ra_files)) {
                $chat_msg .= "<br><br><b><i class='fas fa-paperclip text-muted'></i> Attached Files:</b> ";
                $chat_file_links = [];
                foreach ($all_ra_files as $file) {
                    $file_url = 'uploads/bids/' . $file;
                    $display_name = preg_replace('/^[0-9]+_RAC[D]?_\d+_/', '', $file);
                    $chat_file_links[] = "<a href='{$file_url}' target='_blank' style='color: #3b82f6; text-decoration: none; font-weight: 500;'><i class='fas fa-file-download'></i> {$display_name}</a>";
                }
                $chat_msg .= implode(' &nbsp;|&nbsp; ', $chat_file_links);
            }
            
            $chat_msg .= $user_note_html;
            mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $chat_msg) . "', '$logged_in_user')");
            $new_remark = '';
        }
        elseif ($ra_sub === 'RA Inprogress') {
            $ra_in_comp = $_POST['ra_in_company'] ?? ''; $ra_in_base = $_POST['ra_in_base_price'] ?? ''; $ra_in_price = $_POST['ra_in_ra_price'] ?? '';
            if(empty($ra_in_comp) || empty($ra_in_base) || empty($ra_in_price)) die("<script>alert('Error: Fields required.'); history.back();</script>");
            if (floatval($ra_in_price) >= floatval($ra_in_base)) die("<script>alert('Error: RA Total Price MUST be less than the Base Price.'); history.back();</script>");
            
            $ra_in_screens = process_strict_files('ra_in_screens', '_RAI_', $upload_dir);
            $ra_in_ip_screens = process_strict_files('ra_in_ip_screens', '_RAIIP_', $upload_dir);
            $all_ra_files = array_merge($ra_in_screens, $ra_in_ip_screens);

            $ra_remark = "<div class='border border-info rounded-3 shadow-sm mb-4 overflow-hidden'><div class='bg-info bg-opacity-10 text-info px-3 py-3 fw-bold border-bottom border-info border-opacity-25 d-flex justify-content-between align-items-center'><span><i class='fas fa-sync fa-spin me-1'></i> Reverse Auction: In Progress</span><span class='text-muted small fw-normal'><i class='far fa-clock'></i> {$current_datetime} | By: {$logged_in_user}</span></div><div class='p-3 bg-white text-dark'><div class='row mb-3 small'><div class='col-md-4'><b>Company:</b> " . htmlspecialchars($ra_in_comp) . "</div><div class='col-md-4'><b>Base Price:</b> ₹" . number_format((float)$ra_in_base, 2) . "</div><div class='col-md-4'><b>RA Total Price:</b> <span class='text-primary fw-bold'>₹" . number_format((float)$ra_in_price, 2) . "</span></div></div>";
            if (!empty($all_ra_files)) {
                $ra_remark .= "<div class='pt-3 border-top border-light'><b><i class='fas fa-paperclip'></i> Attached Assets:</b><br><div class='d-flex flex-wrap gap-2 mt-2'>";
                foreach ($all_ra_files as $file) {
                    $icon = strpos($file, '_RAIIP_') !== false ? 'fa-network-wired text-info' : 'fa-image text-primary';
                    $ra_remark .= "<a href='uploads/bids/{$file}' target='_blank' class='badge bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm'><i class='fas {$icon} me-1'></i> " . preg_replace('/^[0-9]+_RAI[IP]?_\d+_/', '', $file) . "</a>";
                }
                $ra_remark .= "</div></div>";
            }
            $ra_remark .= "</div></div>";
            mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, "[RA_INPROGRESS_CARD]" . $ra_remark) . "', '$logged_in_user')");

            $chat_msg = "🔄 <b>RA In Progress:</b> Company <b>" . htmlspecialchars($ra_in_comp) . "</b> placed a bid.<br><ul class='mb-0 mt-2' style='padding-left: 1.2rem; font-size: 0.85rem;'><li><b>Base Price:</b> ₹" . number_format((float)$ra_in_base, 2) . "</li><li><b>New RA Price:</b> <span class='fw-bold text-success'>₹" . number_format((float)$ra_in_price, 2) . "</span></li></ul>";
            if (!empty($all_ra_files)) {
                $chat_msg .= "<br><b><i class='fas fa-paperclip text-muted'></i> Attached Files:</b> ";
                $chat_file_links = [];
                foreach ($all_ra_files as $file) {
                    $file_url = 'uploads/bids/' . $file;
                    $display_name = preg_replace('/^[0-9]+_RAI[IP]?_\d+_/', '', $file);
                    $chat_file_links[] = "<a href='{$file_url}' target='_blank' style='color: #3b82f6; text-decoration: none; font-weight: 500;'><i class='fas fa-file-download'></i> {$display_name}</a>";
                }
                $chat_msg .= implode(' &nbsp;|&nbsp; ', $chat_file_links);
            }
            
            $chat_msg .= $user_note_html;
            mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $chat_msg) . "', '$logged_in_user')");
            $new_remark = '';
        }
        elseif ($ra_sub === 'RA Finalize') {
            $ra_fin_screens = process_strict_files('ra_fin_screens', '_RAF_', $upload_dir);
            $ra_remark = "<div class='border border-primary rounded-3 shadow-sm mb-4 overflow-hidden'><div class='bg-primary bg-opacity-10 text-primary px-3 py-3 fw-bold border-bottom border-primary border-opacity-25 d-flex justify-content-between align-items-center'><span><i class='fas fa-check-double me-1'></i> Reverse Auction: Finalized</span><span class='text-muted small fw-normal'><i class='far fa-clock'></i> {$current_datetime} | By: {$logged_in_user}</span></div><div class='p-3 bg-white text-dark'>";
            if (!empty($ra_fin_screens)) {
                $ra_remark .= "<b><i class='fas fa-paperclip'></i> Final Output Screenshots:</b><br><div class='d-flex flex-wrap gap-2 mt-2'>";
                foreach ($ra_fin_screens as $file) { $ra_remark .= "<a href='uploads/bids/{$file}' target='_blank' class='badge bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm'><i class='fas fa-image me-1 text-primary'></i> " . preg_replace('/^[0-9]+_RAF_\d+_/', '', $file) . "</a>"; }
                $ra_remark .= "</div>";
            }
            $ra_remark .= "</div></div>";
            mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, "[RA_FINALIZE_CARD]" . $ra_remark) . "', '$logged_in_user')");

            $chat_msg = "✅ <b>Reverse Auction Finalized:</b> The RA process is complete and final output has been uploaded.";
            if (!empty($ra_fin_screens)) {
                $chat_msg .= "<br><br><b><i class='fas fa-paperclip text-muted'></i> Attached Files:</b> ";
                $chat_file_links = [];
                foreach ($ra_fin_screens as $file) {
                    $file_url = 'uploads/bids/' . $file;
                    $display_name = preg_replace('/^[0-9]+_RAF_\d+_/', '', $file);
                    $chat_file_links[] = "<a href='{$file_url}' target='_blank' style='color: #3b82f6; text-decoration: none; font-weight: 500;'><i class='fas fa-file-download'></i> {$display_name}</a>";
                }
                $chat_msg .= implode(' &nbsp;|&nbsp; ', $chat_file_links);
            }
            
            $chat_msg .= $user_note_html;
            mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $chat_msg) . "', '$logged_in_user')");
            $new_remark = '';
        }
    }

   // ACTION 7: PRICE NEGOTIATION
    elseif ($u_bid_status === 'Price Negotiation window') {
        $pn_company = mysqli_real_escape_string($conn, trim($_POST['pn_company'] ?? ''));
        if(empty($pn_company)) die("<script>alert('Error: Please select a Company for Price Negotiation.'); history.back();</script>");

        $pn_prod_names = $_POST['pn_prod_name'] ?? []; $pn_prod_qtys = $_POST['pn_prod_qty'] ?? []; $pn_prod_prices = $_POST['pn_prod_price'] ?? [];
        $pn_file_names = process_strict_files('pn_screens', '_PN_', $upload_dir);
        $pn_grand_total = 0;
        
        $chat_detail_list = "<ul class='mb-0 mt-2' style='padding-left: 1.2rem; font-size: 0.85rem;'>";
        $pn_remark = "<div class='border border-primary rounded-3 shadow-sm mb-4 overflow-hidden'><div class='bg-primary bg-opacity-10 text-primary px-3 py-3 fw-bold border-bottom border-primary border-opacity-25 d-flex justify-content-between align-items-center'><span><i class='fas fa-handshake me-1'></i> Price Negotiation Record</span><span class='text-muted small fw-normal'><i class='far fa-clock'></i> {$current_datetime} | By: {$logged_in_user}</span></div><div class='p-3 bg-white text-dark'><div class='mb-3 small'><b>Company:</b> {$pn_company}</div>";
        if(!empty($pn_prod_names) && !empty($pn_prod_names[0])) {
            $pn_remark .= "<div class='modern-table-wrapper mb-3'><table class='modern-table'><thead><tr><th class='ps-3'>Product</th><th>Qty</th><th>Amount</th><th>Total</th></tr></thead><tbody>";
            for($i=0; $i < count($pn_prod_names); $i++) {
                if(empty(trim($pn_prod_names[$i]))) continue;
                $n = htmlspecialchars($pn_prod_names[$i]); $q = floatval($pn_prod_qtys[$i] ?? 0); $p = floatval($pn_prod_prices[$i] ?? 0); $t = $q * $p; $pn_grand_total += $t;
                $pn_remark .= "<tr><td class='ps-3'>{$n}</td><td>{$q}</td><td>₹" . number_format($p, 2) . "</td><td class='fw-bold text-dark'>₹" . number_format($t, 2) . "</td></tr>";
                $chat_detail_list .= "<li>{$n} ({$q} qty) - <b>₹" . number_format($t, 2) . "</b></li>";
            }
            $pn_remark .= "</tbody></table></div><div class='d-flex justify-content-end mb-3 mt-2'><div class='border border-primary px-4 py-2 rounded-3 d-flex align-items-center shadow-sm' style='background-color: #eff6ff;'><span class='fw-bold me-3 text-uppercase text-muted' style='letter-spacing: 0.5px; font-size: 14px;'>New Total</span><span class='fw-bolder' style='font-size: 26px !important; color: #3b82f6; line-height: 1;'>₹" . number_format($pn_grand_total, 2) . "</span></div></div>";
        }
        $chat_detail_list .= "</ul><div class='mt-2 fw-bold text-primary'>New Negotiated Total: ₹" . number_format($pn_grand_total, 2) . "</div>";

        if (!empty($pn_file_names)) {
            $pn_remark .= "<div class='pt-3 border-top border-light'><b><i class='fas fa-paperclip'></i> Attached Assets:</b><br><div class='d-flex flex-wrap gap-2 mt-2'>";
            foreach ($pn_file_names as $file) { $pn_remark .= "<a href='uploads/bids/{$file}' target='_blank' class='badge bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm'><i class='fas fa-image me-1 text-primary'></i> " . preg_replace('/^[0-9]+_PN_\d+_/', '', $file) . "</a>"; }
            $pn_remark .= "</div></div>";
        }
        $pn_remark .= "</div></div>";
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, "[PN_CARD]" . $pn_remark) . "', '$logged_in_user')");

        $chat_msg = "🤝 <b>Price Negotiation Updates for {$pn_company}:</b>" . $chat_detail_list;
        if (!empty($pn_file_names)) {
            $chat_msg .= "<br><b><i class='fas fa-paperclip text-muted'></i> Attached Files:</b> ";
            $chat_file_links = [];
            foreach ($pn_file_names as $file) {
                $file_url = 'uploads/bids/' . $file;
                $display_name = preg_replace('/^[0-9]+_PN_\d+_/', '', $file);
                $chat_file_links[] = "<a href='{$file_url}' target='_blank' style='color: #3b82f6; text-decoration: none; font-weight: 500;'><i class='fas fa-file-download'></i> {$display_name}</a>";
            }
            $chat_msg .= implode(' &nbsp;|&nbsp; ', $chat_file_links);
        }
        
        $chat_msg .= $user_note_html;
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $chat_msg) . "', '$logged_in_user')");
        $new_remark = '';
    }

    // ACTION 8: BID CANCEL
    elseif ($u_bid_status === 'Bid Cancel') {
        $cancel_date = $_POST['cancel_date'] ?? '';
        if (empty($cancel_date)) die("<script>alert('Error: Cancel Date is required.'); history.back();</script>");
        $cancel_file_names = process_strict_files('cancel_screens', '_BC_', $upload_dir);
        $cancel_remark = "<div class='border border-primary rounded-3 shadow-sm mb-4 overflow-hidden'><div class='bg-primary bg-opacity-10 text-primary px-3 py-3 fw-bold border-bottom border-primary border-opacity-25 d-flex justify-content-between align-items-center'><span><i class='fas fa-ban me-1'></i> Bid Cancelled</span><span class='text-muted small fw-normal'><i class='far fa-clock'></i> {$current_datetime} | By: {$logged_in_user}</span></div><div class='p-3 bg-white text-dark'><div class='mb-3 small'><b>Cancel Date:</b> <span class='text-primary fw-bold'>" . date('d/m/Y', strtotime($cancel_date)) . "</span></div>";
        if (!empty($cancel_file_names)) {
            $cancel_remark .= "<div class='pt-2 border-top border-light'><b><i class='fas fa-paperclip'></i> Attached Assets:</b><br><div class='d-flex flex-wrap gap-2 mt-2'>";
            foreach ($cancel_file_names as $file) { $cancel_remark .= "<a href='uploads/bids/{$file}' target='_blank' class='badge bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm'><i class='fas fa-file-excel me-1 text-primary'></i> " . preg_replace('/^[0-9]+_BC_\d+_/', '', $file) . "</a>"; }
            $cancel_remark .= "</div></div>";
        }
        $cancel_remark .= "</div></div>";
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, "[CANCEL_CARD]" . $cancel_remark) . "', '$logged_in_user')");

        $chat_msg = "🚫 <b>Bid Cancelled:</b> This bid has been permanently cancelled on " . date('d/m/Y', strtotime($cancel_date)) . ".";
        if (!empty($cancel_file_names)) {
            $chat_msg .= "<br><br><b><i class='fas fa-paperclip text-muted'></i> Attached Files:</b> ";
            $chat_file_links = [];
            foreach ($cancel_file_names as $file) {
                $file_url = 'uploads/bids/' . $file;
                $display_name = preg_replace('/^[0-9]+_BC_\d+_/', '', $file);
                $chat_file_links[] = "<a href='{$file_url}' target='_blank' style='color: #3b82f6; text-decoration: none; font-weight: 500;'><i class='fas fa-file-download'></i> {$display_name}</a>";
            }
            $chat_msg .= implode(' &nbsp;|&nbsp; ', $chat_file_links);
        }
        
        $chat_msg .= $user_note_html;
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $chat_msg) . "', '$logged_in_user')");
        $new_remark = '';
    }

    // ACTION 9: BID AWARD
    elseif ($u_bid_status === 'Bid Award') {
        $award_status = $_POST['award_status'] ?? ''; 
        $award_date = $_POST['award_date'] ?? ''; 
        $award_company = $_POST['award_company'] ?? ''; 
        $award_price = $_POST['award_price'] ?? '0'; 
        $award_value = $_POST['award_value'] ?? '0'; 

        if (empty($award_status) || empty($award_date) || empty($award_company)) die("<script>alert('Error: Required fields missing.'); history.back();</script>");
        
        if (in_array($award_company, $awarded_companies)) {
            die("<script>alert('Error: This company has already been awarded (Win/Loss).'); history.back();</script>");
        }
        
        $award_file_names = process_strict_files('award_screens', '_BA_', $upload_dir);
        $theme_color = ($award_status === 'Win') ? '#22c55e' : '#ef4444'; $bg_color = ($award_status === 'Win') ? '#f0fdf4' : '#fef2f2'; $icon_class = ($award_status === 'Win') ? 'fa-trophy' : 'fa-times-circle';

        $award_remark = "<div class='border rounded-3 shadow-sm mb-4 overflow-hidden' style='border-color: {$theme_color};'><div class='px-3 py-3 fw-bold border-bottom d-flex justify-content-between align-items-center' style='background-color: {$bg_color}; border-color: {$theme_color}; color: {$theme_color};'><span><i class='fas {$icon_class} me-1'></i> Bid Award Status: {$award_status}</span><span class='text-muted small fw-normal'><i class='far fa-clock'></i> {$current_datetime} | By: {$logged_in_user}</span></div><div class='p-3 bg-white text-dark'><div class='row mb-3 small'><div class='col-md-3 mb-2'><b>Status:</b> <span class='fw-bold' style='color: {$theme_color};'>{$award_status}</span></div><div class='col-md-3 mb-2'><b>Date:</b> " . date('d/m/Y', strtotime($award_date)) . "</div><div class='col-md-6 mb-2'><b>Company:</b> {$award_company}</div><div class='col-md-6 mb-2'><b>Price & Bid Value:</b> ₹" . number_format((float)$award_price, 2) . "</div></div>";
        if (!empty($award_file_names)) {
            $award_remark .= "<div class='pt-2 border-top border-light'><b><i class='fas fa-paperclip'></i> Attached Assets:</b><br><div class='d-flex flex-wrap gap-2 mt-2'>";
            foreach ($award_file_names as $file) { $award_remark .= "<a href='uploads/bids/{$file}' target='_blank' class='badge bg-light text-dark border px-3 py-2 text-decoration-none shadow-sm'><i class='fas fa-file-contract me-1' style='color: {$theme_color};'></i> " . preg_replace('/^[0-9]+_BA_\d+_/', '', $file) . "</a>"; }
            $award_remark .= "</div></div>";
        }
        $award_remark .= "</div></div>";
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, "[AWARD_CARD]" . $award_remark) . "', '$logged_in_user')");

        $chat_msg = "🏆 <b>Bid Awarded:</b> Status updated to <b>{$award_status}</b> for company <b>{$award_company}</b> on " . date('d/m/Y', strtotime($award_date)) . ".";
        if (!empty($award_file_names)) {
            $chat_msg .= "<br><br><b><i class='fas fa-paperclip text-muted'></i> Attached Files:</b> ";
            $chat_file_links = [];
            foreach ($award_file_names as $file) {
                $file_url = 'uploads/bids/' . $file;
                $display_name = preg_replace('/^[0-9]+_BA_\d+_/', '', $file);
                $chat_file_links[] = "<a href='{$file_url}' target='_blank' style='color: #3b82f6; text-decoration: none; font-weight: 500;'><i class='fas fa-file-download'></i> {$display_name}</a>";
            }
            $chat_msg .= implode(' &nbsp;|&nbsp; ', $chat_file_links);
        }
        
        $chat_msg .= $user_note_html;
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $chat_msg) . "', '$logged_in_user')");
        $new_remark = '';
    }

    // Append files to DB string
    if(!empty($new_uploaded_files_array)) {
        $new_files_str = implode(',', $new_uploaded_files_array);
        $updated_files = !empty($existing_files) ? ($existing_files . ',' . $new_files_str) : $new_files_str;
        mysqli_query($conn, "UPDATE bids SET uploaded_files = '$updated_files' WHERE id = $bid_id");
    }

    $update_query = "UPDATE bids SET bid_no = ?, org_name = ?, bid_status = ?, end_date = ? WHERE id = ?";
    if ($ustmt = mysqli_prepare($conn, $update_query)) {
       mysqli_stmt_bind_param($ustmt, "ssssi", $u_bid_no, $u_org_name, $u_bid_status, $u_end_date, $bid_id);
        mysqli_stmt_execute($ustmt); mysqli_stmt_close($ustmt);
    }

    // Auto-remark logic
    if ($existing_status !== $u_bid_status && !in_array($u_bid_status, ['Corrigendum', 'Extension date', 'Submitted', 'Re-submitted', 'Query window', 'Representation', 'Technical Evaluation', 'Re-Technical Evaluation', 'Financial Evaluation', 'Reverse Auction', 'Price Negotiation window', 'Bid Cancel', 'Bid Award'])) {
        if ($u_bid_status === 'Documents Awaited') {
            $check_da = mysqli_query($conn, "SELECT id FROM bid_remarks WHERE bid_id = $bid_id AND remark_text LIKE '%Documents Awaited:%'");
            if (mysqli_num_rows($check_da) == 0) {
                $da_msg = "<b>Documents Awaited:</b> Mail sent";
                $da_msg .= $user_note_html;
                mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $da_msg) . "', '$logged_in_user')");
                $new_remark = '';
            }
        } else {
            $auto_remark = "Bid status updated to: " . $u_bid_status;
            $auto_remark .= $user_note_html;
            mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '" . mysqli_real_escape_string($conn, $auto_remark) . "', '$logged_in_user')");
            $new_remark = '';
        }
    }
    
    if (!empty($new_remark)) {
        $safe_remark = htmlspecialchars($new_remark, ENT_QUOTES, 'UTF-8');
        mysqli_query($conn, "INSERT INTO bid_remarks (bid_id, remark_text, added_by) VALUES ($bid_id, '$safe_remark', '$logged_in_user')");
    } 

    echo "<script>alert('Bid details & updates saved successfully!'); window.location.href='view_bid.php?id=$bid_id';</script>";
    exit();
}

$bid_query = "SELECT * FROM bids WHERE id = ?";
$stmt = mysqli_prepare($conn, $bid_query);
mysqli_stmt_bind_param($stmt, "i", $bid_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$bid = mysqli_fetch_assoc($result);

if (!$bid) { echo "<script>alert('Bid not found in database!'); window.location.href='bid_report.php';</script>"; exit(); }

$status_color = 'bg-primary text-white'; 
$status = strtolower($bid['bid_status']);
if(strpos($status, 'award') !== false || strpos($status, 'submitted') !== false || strpos($status, 'qualified') !== false) $status_color = 'bg-success text-white';
if(strpos($status, 'cancel') !== false || strpos($status, 'lost') !== false || strpos($status, 'dis-qualified') !== false) $status_color = 'bg-danger text-white';

$html_sections = ['corrigendum' => "", 'extension' => "", 'submission' => "", 'query' => "", 'rep' => "", 'techeval' => "", 'fineval' => "", 'ra' => "", 'pn' => "", 'cancel' => "", 'award' => ""];
$hist_sections = ['master' => "", 'corrigendum' => "", 'extension' => "", 'submission' => "", 'query' => "", 'rep' => "", 'techeval' => "", 'fineval' => "", 'ra' => "", 'pn' => "", 'cancel' => "", 'award' => ""];

// --- PRE-CALCULATE DYNAMIC LEVELS FOR SUBMISSIONS (Lowest Price = L1) ---
$latest_company_submissions = [];

$sub_q_calc = mysqli_query($conn, "SELECT id, remark_text FROM bid_remarks WHERE bid_id = $bid_id AND remark_text LIKE '%[SUBMISSION_CARD]%' ORDER BY id ASC");
if ($sub_q_calc) {
    while($r_calc = mysqli_fetch_assoc($sub_q_calc)) {
        preg_match('/Grand Total.*?₹([\d,\.]+)/s', $r_calc['remark_text'], $p_m);
        preg_match('/<b>Company:<\/b>\s*(.*?)\s*<\/div>/', $r_calc['remark_text'], $c_m);
        if(isset($p_m[1]) && isset($c_m[1])) {
            $cname = htmlspecialchars_decode(trim($c_m[1]), ENT_QUOTES);
            $price = floatval(str_replace(',', '', $p_m[1]));
            
            $latest_company_submissions[$cname] = [
                'id' => $r_calc['id'],
                'price' => $price
            ];
        }
    }
}

$active_prices_map = [];
foreach($latest_company_submissions as $cname => $data) {
    $active_prices_map[$data['id']] = $data['price'];
}

asort($active_prices_map); 

$calculated_dynamic_levels = [];
$company_current_levels = [];
$lvl_idx = 1;
foreach($active_prices_map as $sub_id => $price) {
    $lvl = "L" . $lvl_idx;
    $calculated_dynamic_levels[$sub_id] = $lvl;
    
    foreach($latest_company_submissions as $cname => $data) {
        if($data['id'] == $sub_id) {
            $company_current_levels[$cname] = $lvl;
            break;
        }
    }
    $lvl_idx++;
}
// --------------------------------------------------------------------------

$rem_result = mysqli_query($conn, "SELECT * FROM bid_remarks WHERE bid_id = $bid_id ORDER BY created_at DESC");
if (mysqli_num_rows($rem_result) > 0) {
    while ($rem = mysqli_fetch_assoc($rem_result)) {
        $time = date('d/m/Y, h:i A', strtotime($rem['created_at']));
        $user = htmlspecialchars($rem['added_by']);
        $text = $rem['remark_text']; 
        
        $delete_form_card = ""; $delete_form_chat = "";
        if (strtolower($logged_in_user) === 'admin') {
            $delete_form_card = "<div class='text-end mb-2'><form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you want to delete this card and its associated files? This action cannot be undone.\");'><input type='hidden' name='delete_remark_id' value='{$rem['id']}'><input type='hidden' name='action_delete' value='1'><button type='submit' class='btn btn-sm btn-outline-danger shadow-sm fw-bold' style='border-radius: 6px; font-size: 0.8rem;'><i class='fas fa-trash-alt me-1'></i> Delete This Record</button></form></div>";
            $delete_form_chat = "<form method='POST' class='d-inline ms-2' onsubmit='return confirm(\"Delete this activity record?\");'><input type='hidden' name='delete_remark_id' value='{$rem['id']}'><input type='hidden' name='action_delete' value='1'><button type='submit' class='btn btn-sm text-danger p-0 m-0' title='Delete Record'><i class='fas fa-trash'></i></button></form>";
        }
        
        if (strpos($text, '[CORRIGENDUM_CARD]') !== false) { $html_sections['corrigendum'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[CORRIGENDUM_CARD]', '', $text) . "</div>"; }
        elseif (strpos($text, '[EXTENSION_CARD]') !== false) { $html_sections['extension'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[EXTENSION_CARD]', '', $text) . "</div>"; }
        elseif (strpos($text, '[SUBMISSION_CARD]') !== false) { 
            $row_id = $rem['id'];
            if (isset($calculated_dynamic_levels[$row_id])) {
                $auto_lvl = $calculated_dynamic_levels[$row_id];
                $badge_color = ($auto_lvl === 'L1') ? 'success' : (($auto_lvl === 'L2') ? 'warning text-dark' : 'danger'); 
                $icon = ($auto_lvl === 'L1') ? "<i class='fas fa-trophy text-warning me-1'></i> " : "";
                $text = preg_replace('/<b>(Level|Current Rank):<\/b>\s*<span[^>]*>.*?<\/span>/', "<b>Current Rank:</b> <span class='badge bg-{$badge_color} px-3 py-2 fs-5 shadow-sm border border-light'>{$icon}{$auto_lvl}</span>", $text);
            } else {
                $text = preg_replace('/<b>(Level|Current Rank):<\/b>\s*<span[^>]*>.*?<\/span>/', "<b>Status:</b> <span class='badge bg-secondary px-2 py-1 text-decoration-line-through'>Overridden</span>", $text);
            }
            $html_sections['submission'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[SUBMISSION_CARD]', '', $text) . "</div>"; 
        }
        elseif (strpos($text, '[QUERY_CARD]') !== false) { $html_sections['query'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[QUERY_CARD]', '', $text) . "</div>"; }
        elseif (strpos($text, '[REP_CARD]') !== false) { $html_sections['rep'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[REP_CARD]', '', $text) . "</div>"; }
        elseif (strpos($text, '[TECHEVAL_CARD]') !== false) { $html_sections['techeval'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[TECHEVAL_CARD]', '', $text) . "</div>"; }
        elseif (strpos($text, '[FINEVAL_CARD]') !== false) { $html_sections['fineval'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[FINEVAL_CARD]', '', $text) . "</div>"; }
        elseif (strpos($text, '[RA_CREATE_CARD]') !== false) { $html_sections['ra'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[RA_CREATE_CARD]', '', $text) . "</div>"; }
        elseif (strpos($text, '[RA_INPROGRESS_CARD]') !== false) { $html_sections['ra'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[RA_INPROGRESS_CARD]', '', $text) . "</div>"; }
        elseif (strpos($text, '[RA_FINALIZE_CARD]') !== false) { $html_sections['ra'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[RA_FINALIZE_CARD]', '', $text) . "</div>"; }
        elseif (strpos($text, '[PN_CARD]') !== false) { $html_sections['pn'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[PN_CARD]', '', $text) . "</div>"; }
        elseif (strpos($text, '[CANCEL_CARD]') !== false) { $html_sections['cancel'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[CANCEL_CARD]', '', $text) . "</div>"; }
        elseif (strpos($text, '[AWARD_CARD]') !== false) { $html_sections['award'] .= "<div class='position-relative mb-2'>" . $delete_form_card . str_replace('[AWARD_CARD]', '', $text) . "</div>"; }
        else {
            $formatted_text = ($text == strip_tags($text)) ? nl2br(htmlspecialchars($text)) : nl2br($text); 
            $formatted_item = "<div class='activity-item p-3 mb-3 rounded-3 shadow-sm' style='background-color: #ffffff; border-left: 4px solid #3b82f6;'><div class='d-flex justify-content-between align-items-center mb-2 border-bottom pb-2'><span class='fw-bold' style='color: #3b82f6; font-size: 0.95rem;'><i class='fas fa-user-circle me-1'></i> {$user}</span><span class='text-muted' style='font-size: 0.75rem;'><i class='far fa-clock me-1'></i> {$time} {$delete_form_chat}</span></div><div class='text-dark' style='font-size: 0.9rem; line-height: 1.6;'>{$formatted_text}</div></div>";
            $hist_sections['master'] .= $formatted_item;

            if (strpos($text, 'Corrigendum Update:') !== false) $hist_sections['corrigendum'] .= $formatted_item;
            elseif (strpos($text, 'Extension Date Update:') !== false) $hist_sections['extension'] .= $formatted_item;
            elseif (strpos($text, 'Bid Submitted:') !== false || strpos($text, 'Bid Re-Submitted:') !== false) $hist_sections['submission'] .= $formatted_item;
            elseif (strpos($text, 'Query Window:') !== false) $hist_sections['query'] .= $formatted_item;
            elseif (strpos($text, 'Representation Window:') !== false) $hist_sections['rep'] .= $formatted_item;
            elseif (strpos($text, 'Technical Evaluation:') !== false || strpos($text, 'Technical Evaluation Results:') !== false) $hist_sections['techeval'] .= $formatted_item;
            elseif (strpos($text, 'Financial Evaluation:') !== false || strpos($text, 'Financial Evaluation Results:') !== false) $hist_sections['fineval'] .= $formatted_item;
            elseif (strpos($text, 'Reverse Auction') !== false || strpos($text, 'RA In Progress:') !== false) $hist_sections['ra'] .= $formatted_item;
            elseif (strpos($text, 'Price Negotiation') !== false) $hist_sections['pn'] .= $formatted_item;
            elseif (strpos($text, 'Bid Cancelled:') !== false) $hist_sections['cancel'] .= $formatted_item;
            elseif (strpos($text, 'Bid Awarded:') !== false) $hist_sections['award'] .= $formatted_item;
        }
    }
} 

if(empty($hist_sections['master'])) {
    $hist_sections['master'] = "<div class='text-center text-muted py-5'><i class='fas fa-comment-slash fs-3 mb-2 opacity-50'></i><br>No activity or remarks found.</div>";
}
?>

<?php include 'include/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    body { background-color: #f1f5f9; font-family: 'Poppins', sans-serif;} 
    .modern-card { border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03); background: #ffffff; margin-bottom: 24px; overflow: hidden; }
    .section-heading { font-size: 1.05rem; font-weight: 700; color: #3b82f6; margin-bottom: 1.2rem; margin-top: 1.5rem; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
    .section-heading:first-child { margin-top: 0; }
    .modern-label { font-size: 0.8rem; font-weight: 600; color: #475569; margin-bottom: 6px; display: block; }
    .form-control, .form-select { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; font-size: 0.9rem; color: #0f172a; font-weight: 500; box-shadow: none !important; transition: all 0.2s ease; }
    .form-control:focus, .form-select:focus { background-color: #ffffff; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important; }
    .form-control[readonly], .form-control[disabled] { background-color: #e2e8f0 !important; color: #475569; border-color: transparent; pointer-events: none; }
    
    /* --- NEW EYE-CATCHING TABLE UI --- */
    .modern-table-wrapper { border-radius: 12px; overflow: hidden; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; background: #fff; margin-bottom: 1.5rem; }
    .modern-table { margin-bottom: 0; width: 100%; border-collapse: separate; border-spacing: 0; }
    .modern-table thead { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #ffffff; }
    .modern-table thead th { font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; padding: 14px 16px; border: none; vertical-align: middle; }
    .modern-table tbody tr { transition: all 0.2s ease; border-bottom: 1px solid #e2e8f0; }
    .modern-table tbody tr:last-child { border-bottom: none; }
    .modern-table tbody tr:hover { background-color: #f8fafc; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.04); z-index: 1; position: relative; }
    .modern-table tbody td { padding: 14px 16px; vertical-align: middle; font-size: 0.9rem; color: #334155; }
    .table-input { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; transition: 0.3s; width: 100%; outline: none; }
    .table-input:focus, .table-input:hover { background-color: #fff; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    /* --------------------------------- */

    .nav-modern-tabs { border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; flex-wrap: nowrap; overflow-x: auto; white-space: nowrap; padding-bottom: 5px; }
    .nav-modern-tabs::-webkit-scrollbar { height: 4px; }
    .nav-modern-tabs::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .nav-modern-tabs .nav-link { color: #64748b; font-weight: 600; border: none; border-bottom: 3px solid transparent; padding: 12px 20px; transition: all 0.3s; background: transparent; border-radius: 0; font-size: 0.9rem; }
    .nav-modern-tabs .nav-link:hover { color: #3b82f6; border-bottom-color: #bfdbfe; }
    .nav-modern-tabs .nav-link.active { color: #3b82f6; border-bottom-color: #3b82f6; background: transparent; }
    .doc-pill { display: inline-flex; align-items: center; padding: 8px 14px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; color: #334155; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: 0.2s; }
    .doc-pill:hover { background: #f8fafc; border-color: #94a3b8; color: #0f172a; transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    
    .paste-zone { border: 1.5px dashed #fca5a5; border-radius: 8px; cursor: pointer; transition: 0.3s; background: #ffffff; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 80px; padding: 15px; }
    .paste-zone:hover, .paste-zone:focus { background-color: #fef2f2; border-color: #ef4444; outline: none; }
    
    .btn-indigo { background-color: #3b82f6; color: white; border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600; transition: 0.3s; }
    .btn-indigo:hover { background-color: #2563eb; color: white; }
    .history-container { max-height: 500px; overflow-y: auto; padding-right: 10px; background-color: #f1f5f9; padding: 15px; border-radius: 12px; }
    .history-container::-webkit-scrollbar { width: 6px; }
    .history-container::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 10px; }
</style>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
        <div>
            <h3 class="fw-bold text-dark mb-1" style="font-size: 1.5rem;">Bid No: <?= htmlspecialchars($bid['bid_no']) ?> | <?= htmlspecialchars($bid['org_name'] ?: 'Unknown Organisation') ?></h3>
            <p class="text-muted small mb-0">Navigate tabs to view specific details or use Overview to update the bid.</p>
        </div>
        <div>
            <a href="bid_report.php" class="btn btn-outline-secondary bg-white text-dark fw-bold rounded-pill px-4 py-2 border-light shadow-sm"><i class="fas fa-arrow-left me-2"></i> Back to List</a>
        </div>
    </div>

    <ul class="nav nav-modern-tabs px-2" id="bidTabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button></li>
        <?php if(!empty($html_sections['corrigendum'])): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_corrigendum" type="button">Corrigendum</button></li><?php endif; ?>
        <?php if(!empty($html_sections['extension'])): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_extension" type="button">Extension</button></li><?php endif; ?>
        <?php if(!empty($html_sections['submission'])): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_submission" type="button">Submission</button></li><?php endif; ?>
        <?php if(!empty($html_sections['query'])): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_query" type="button">Query</button></li><?php endif; ?>
        <?php if(!empty($html_sections['rep'])): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_rep" type="button">Representation</button></li><?php endif; ?>
        <?php if(!empty($html_sections['techeval'])): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_techeval" type="button">Tech Eval</button></li><?php endif; ?>
        <?php if(!empty($html_sections['fineval'])): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_fineval" type="button">Fin Eval</button></li><?php endif; ?>
        <?php if(!empty($html_sections['ra'])): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_ra" type="button">Reverse Auction</button></li><?php endif; ?>
        <?php if(!empty($html_sections['pn'])): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_pn" type="button">Price Negotiation</button></li><?php endif; ?>
        <?php if(!empty($html_sections['cancel'])): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_cancel" type="button">Cancelled</button></li><?php endif; ?>
        <?php if(!empty($html_sections['award'])): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_award" type="button">Award</button></li><?php endif; ?>
    </ul>

    <div class="tab-content" id="bidTabsContent">
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <form id="bidUpdateForm" method="POST" action="" enctype="multipart/form-data" novalidate>
                <div class="card modern-card p-2 p-md-4">
                    <div class="card-body">
                        <div class="section-heading mt-0">Bid Core Details <span class="badge <?= $status_color ?> float-end fw-normal" style="font-size: 0.8rem;"><?= htmlspecialchars($bid['bid_status']) ?></span></div>
                        <div class="row g-4 mb-2">
                           <div class="col-md-3"><label class="modern-label">BID No.</label><input type="text" name="bid_no" class="form-control" value="<?= htmlspecialchars($bid['bid_no']) ?>" readonly></div>
                           <div class="col-md-3"><label class="modern-label">Organisation Name</label><input type="text" name="org_name" class="form-control" value="<?= htmlspecialchars($bid['org_name'] ?: '') ?>" readonly></div>
                            <div class="col-md-3"><label class="modern-label">Department Name</label><input type="text" class="form-control" value="<?= htmlspecialchars($bid['dept_name'] ?: 'N/A') ?>" readonly disabled></div>
                            <div class="col-md-3"><label class="modern-label">Internal Name</label><input type="text" class="form-control" value="<?= htmlspecialchars($bid['internal_name'] ?: 'N/A') ?>" readonly disabled></div>
                        </div>

                        <div class="section-heading">Internal Assignment & Status</div>
                        <div class="row g-4 mb-2">
                            <div class="col-md-3"><label class="modern-label">Submitted By</label><input type="text" class="form-control" value="<?= htmlspecialchars($bid['submitted_by'] ?: 'N/A') ?>" readonly disabled></div>
                            <div class="col-md-3"><label class="modern-label">Managed By (Manager)</label><input type="text" class="form-control" value="<?= htmlspecialchars($bid['managed_by'] ?: 'N/A') ?>" readonly disabled></div>
                            <div class="col-md-3"><label class="modern-label">Sale Employee</label><input type="text" class="form-control" value="<?= htmlspecialchars($bid['sale_employee'] ?: 'N/A') ?>" readonly disabled></div>
                            <div class="col-md-3"><label class="modern-label">Dept. Contact & Location</label><input type="text" class="form-control" value="<?= htmlspecialchars($bid['dept_contact'] ?: 'N/A') ?> | <?= htmlspecialchars($bid['location'] ?: 'N/A') ?>" readonly disabled></div>
                            
                            <div class="col-md-3">
                                <label class="modern-label text-primary">Update Bid Status</label>
                               <select name="bid_status" id="bid_status_select" class="form-select border-primary fw-bold" style="background-color: #eff6ff;">
   <?php 
if ($is_canceled) {
    $status_options = ['Bid Cancel'];
} else {
    // Award hone ke baad bhi sare options open rahenge
    $status_options = ['In Progress', 'Corrigendum', 'Extension date'];
    if (!$doc_awaited_exists || strtolower($bid['bid_status']) == 'documents awaited') {
        $status_options[] = 'Documents Awaited';
    }
    
    // Base options without RA
    $base_opts = ['Submitted', 'Re-submitted', 'Query window', 'Representation', 'Technical Evaluation', 'Re-Technical Evaluation', 'Financial Evaluation'];
    
    // Logic: Show RA only if RA Status was 'Yes' when creating bid
    $db_ra_status = isset($bid['ra_status']) ? strtolower(trim($bid['ra_status'])) : 'no';
    if ($db_ra_status === 'yes') {
        $base_opts[] = 'Reverse Auction';
    }
    
    // Add remaining options
    $base_opts = array_merge($base_opts, ['Price Negotiation window', 'Bid Cancel', 'Bid Award']);
    $status_options = array_merge($status_options, $base_opts);
}

foreach($status_options as $opt){
    $selected = (strtolower($bid['bid_status']) == strtolower($opt)) ? "selected" : "";
    echo "<option value='$opt' $selected>$opt</option>";
}
?>
</select>
                            </div>
                            <div class="col-md-3" id="ra_sub_status_container" style="display: none;">
                                <label class="modern-label text-primary">RA Options <span class="text-danger">*</span></label>
                                <select name="ra_sub_status" id="ra_sub_status_select" class="form-select border-primary fw-bold" style="background-color: #eff6ff;">
                                    <option value="">Select Option...</option>
                                    <option value="Create RA">Create RA</option>
                                    <option value="RA Inprogress">RA Inprogress</option>
                                    <option value="RA Finalize">RA Finalize</option>
                                </select>
                            </div>
                            <div class="col-md-3"><label class="modern-label">Item Category</label><input type="text" name="item_category" class="form-control" value="<?= htmlspecialchars($bid['item_category'] ?: '') ?>" readonly></div>
                            <div class="col-md-3"><label class="modern-label">Bid Type</label><input type="text" class="form-control" value="<?= htmlspecialchars($bid['bid_type'] ?: 'N/A') ?>" readonly disabled></div>
                            <div class="col-md-3"><label class="modern-label">Record Created On</label><input type="text" class="form-control" value="<?= !empty($bid['created_at']) ? date('d/m/Y, h:i A', strtotime($bid['created_at'])) : 'N/A' ?>" readonly disabled></div>
                        </div>

                        <div class="section-heading">Dates & Commercials</div>
                        <div class="row g-4 mb-2">
                            <div class="col-md-3"><label class="modern-label">Bid Start Date</label><input type="text" class="form-control" value="<?= !empty($bid['start_date']) ? date('d/m/Y, h:i A', strtotime($bid['start_date'])) : 'N/A' ?>" readonly disabled></div>
                            <div class="col-md-3"><label class="modern-label">Bid End Date & Time</label><input type="datetime-local" class="form-control text-primary fw-bold" value="<?= !empty($bid['end_date']) ? date('Y-m-d\TH:i', strtotime($bid['end_date'])) : '' ?>" readonly disabled></div>
                            <div class="col-md-3"><label class="modern-label">Estimated Value (₹)</label><input type="text" class="form-control text-primary fw-bold" value="<?= number_format((float)($bid['estimated_value'] ?? 0), 2) ?>" readonly disabled></div>
                            <div class="col-md-3"><label class="modern-label">EMD Amount (₹)</label><input type="text" id="master_emd_amount" class="form-control text-primary fw-bold <?= floatval($bid['emd_amount']) > 0 ? 'border-success' : '' ?>" value="<?= number_format((float)($bid['emd_amount'] ?? 0), 2) ?>" readonly disabled></div>
                        </div>

                        <div class="col-md-12 mt-4 action-extra" style="display: none;">
                            <div class="p-4 rounded-3" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="fw-bold mb-3" id="action_box_title" style="color: #1e3a8a;"><i class="fas fa-exclamation-triangle me-1"></i> Corrigendum Details</h6>
                                <div class="row g-3">
                                    <div class="col-md-4"><label class="modern-label">Current Date <span class="text-danger">*</span></label><input type="date" name="action_date" class="form-control bg-white"  max="<?= $max_date_val ?>" value="<?= $current_date_val ?>"></div>
                                    <div class="col-md-4"><label class="modern-label">New End Date <span class="text-danger">*</span></label><input type="datetime-local" id="action_end_date" name="action_end_date" class="form-control bg-white"  max="<?= $max_datetime_val ?>" value="<?= $current_datetime_val ?>"></div>
                                    <div class="col-md-4" id="action_file_upload_div">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-paperclip me-1"></i> Upload Reference Files <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_action">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="action_files[]" id="action_files" class="d-none" multiple>
                                            <div id="paste_preview_action" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4 submitted-extra" style="display: none;">
                            <div class="p-4 rounded-3" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="fw-bold mb-4" style="color: #1e3a8a;"><i class="fas fa-check-circle me-1"></i> <span id="submission_box_title">Final Submission Form</span></h6>
                                <div class="row g-4 mb-4">
                                    <div class="col-md-3">
                                        <label class="modern-label">Company Name <span class="text-danger">*</span></label>
                                        <select id="sub_company" name="sub_company" class="form-select bg-white fw-bold shadow-sm">
                                            <?= $comp_options_sub_html ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3"><label class="modern-label">Date & Time <span class="text-danger">*</span></label><input type="datetime-local" id="sub_datetime" name="sub_datetime" class="form-control bg-white" value="<?= $current_datetime_val ?>"></div>
                                    <div class="col-md-3"><label class="modern-label">IP Address <span class="text-danger">*</span></label><input type="text" id="sub_ip" name="sub_ip" class="form-control bg-white" placeholder="e.g. 192.168.1.1" pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$" title="Valid IP required (e.g. 192.168.1.1)"></div>
                                    
                                   <?php $requires_emd = (floatval($bid['emd_amount'] ?? 0) > 0); ?>
                                   
<?php if($requires_emd): ?>
    <div class="col-md-3">
        <label class="modern-label">EMD Submitted? <span class="text-danger">*</span>
            <span class="badge bg-success ms-1">₹<?= number_format((float)$bid['emd_amount'], 2) ?> Required</span>
        </label>
        <select id="sub_emd" name="sub_emd" class="form-select bg-white fw-bold" required>
            <option value="">Select Option...</option>
            <option value="Yes">Yes</option>
            <option value="No (Exempted)">No (Exempted)</option>
        </select>
    </div>
<?php else: ?>
    <div class="col-md-3">
        <label class="modern-label text-muted">EMD Submitted?</label>
        <input type="text" class="form-control bg-light text-muted fw-bold shadow-sm" value="N/A (Not Required)" readonly>
        <input type="hidden" id="sub_emd" name="sub_emd" value="N/A">
    </div>
<?php endif; ?>
</div>

                                <div class="row g-4 mb-4">
                                    <div class="col-md-3" id="emd_upload_div" style="display:none;">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-file-invoice-dollar me-1"></i> EMD Proof / Exemption <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_sub_emd">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> to paste</span>
                                            <input type="file" name="sub_emd_docs[]" id="sub_emd_docs" class="d-none" multiple>
                                            <div id="paste_preview_sub_emd" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-file-pdf me-1"></i> Sub Docs <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_sub_docs">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> to paste</span>
                                            <input type="file" name="sub_docs[]" id="sub_docs" class="d-none" multiple>
                                            <div id="paste_preview_sub_docs" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-network-wired me-1"></i> IP Screen <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_sub_ip">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> to paste</span>
                                            <input type="file" name="sub_ip_screens[]" id="sub_ip_screens" class="d-none" multiple>
                                            <div id="paste_preview_sub_ip" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-rupee-sign me-1"></i> Price Screen <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_sub_price">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> to paste</span>
                                            <input type="file" name="sub_price_screens[]" id="sub_price_screens" class="d-none" multiple>
                                            <div id="paste_preview_sub_price" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <h6 class="text-dark fw-bold mb-3 mt-2" style="font-size: 0.95rem;"><i class="fas fa-box-open text-primary me-1"></i> Add Products</h6>
                                <div class="modern-table-wrapper">
                                    <table class="modern-table" id="product_table">
                                        <thead>
                                            <tr>
                                                <th class="ps-3">Product Name</th><th style="width: 15%;">Qty</th><th style="width: 20%;">Price (₹)</th><th style="width: 20%;">Total (₹)</th><th style="width: 5%;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="product_body">
                                            <tr>
                                               <td class="ps-3"><input type="text" name="prod_name[]" class="table-input" placeholder="Product Details" pattern="^[A-Za-z0-9\s]+$" title="Only letters, numbers, and spaces allowed" maxlength="150" oninput="this.value = this.value.replace(/[^A-Za-z0-9\s]/g, '').substring(0, 150)"></td>
                                                <td><input type="number" name="prod_qty[]" class="table-input qty-input" value="1" min="1"></td>
                                                <td><input type="number" name="prod_price[]" class="table-input price-input" value="0" min="0" step="0.01"></td>
                                                <td><input type="text" name="prod_total[]" class="table-input text-primary fw-bold border-0 total-input" style="background: transparent; box-shadow: none;" readonly></td>
                                                <td><button type="button" class="btn btn-sm btn-light text-danger remove-row shadow-sm"><i class="fas fa-times"></i></button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="p-3 bg-light d-flex justify-content-between align-items-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3 py-2" id="add_product_btn" style="border-radius: 8px;"><i class="fas fa-plus me-1"></i> Add Item</button>
                                        <div class="d-flex align-items-center bg-white px-4 py-2 rounded shadow-sm border border-primary">
                                            <span class="text-muted fw-bold me-3 text-uppercase small" style="letter-spacing: 0.5px;">Grand Total</span>
                                            <span class="fw-bolder text-primary" style="font-size: 24px;">₹</span>
                                            <input type="text" id="grand_total" class="form-control fw-bolder text-primary border-0 bg-transparent p-0 ms-1 w-auto" style="min-width: 120px; font-size: 26px; outline: none; box-shadow: none;" readonly value="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4 query-extra" style="display: none;">
                            <div class="p-4 rounded-3 shadow-sm" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="fw-bold mb-4" style="color: #1e3a8a;"><i class="fas fa-question-circle me-2"></i>Query Window Actions</h6>
                                <div class="row g-4 mb-4">
                                    <div class="col-md-4"><label class="modern-label text-dark">Query Start Date & Time <span class="text-danger">*</span></label><input type="datetime-local" name="query_start_date" class="form-control bg-white shadow-sm"  max="<?= $max_datetime_val ?>" value="<?= $current_datetime_val ?>"></div>
                                    <div class="col-md-4"><label class="modern-label text-dark">Query End Date & Time <span class="text-danger">*</span></label><input type="datetime-local" name="query_end_date" class="form-control bg-white shadow-sm"  max="<?= $max_datetime_val ?>" value="<?= $current_datetime_val ?>"></div>
                                    <div class="col-md-4"><label class="modern-label text-dark">IP Address <span class="text-danger">*</span></label><input type="text" name="query_ip" class="form-control bg-white shadow-sm" placeholder="e.g. 192.168.1.1" pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$" title="Valid IP required"></div>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-camera me-1"></i> Query Screenshots <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_q_scr">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="query_screens[]" id="query_screens" class="d-none" multiple>
                                            <div id="paste_preview_q_scr" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-file-pdf me-1"></i> Upload Documents <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_q_docs">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="query_docs[]" id="query_docs" class="d-none" multiple>
                                            <div id="paste_preview_q_docs" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-check-square me-1"></i> Reply Screenshots <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_q_sub">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="query_sub_screens[]" id="query_sub_screens" class="d-none" multiple>
                                            <div id="paste_preview_q_sub" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4 representation-extra" style="display: none;">
                            <div class="p-4 rounded-3 shadow-sm" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="fw-bold mb-4" style="color: #1e3a8a;"><i class="fas fa-gavel me-2"></i>Representation Actions</h6>
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6"><label class="modern-label text-dark">IP Address <span class="text-danger">*</span></label><input type="text" name="rep_ip" class="form-control bg-white shadow-sm" placeholder="e.g. 192.168.1.1" pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$" title="Valid IP required"></div>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-camera me-1"></i> Screenshots <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_rep_scr">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="rep_screens[]" id="rep_screens" class="d-none" multiple>
                                            <div id="paste_preview_rep_scr" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-file-pdf me-1"></i> Upload Documents <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_rep_docs">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="rep_docs[]" id="rep_docs" class="d-none" multiple>
                                            <div id="paste_preview_rep_docs" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4 techeval-extra" style="display: none;">
                            <div class="p-4 rounded-3 shadow-sm" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="fw-bold mb-4" style="color: #1e3a8a;" id="techeval_box_title"><i class="fas fa-tasks me-2"></i>Technical Evaluation Actions</h6>
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6"><label class="modern-label text-dark">Date & Time <span class="text-danger">*</span></label><input type="datetime-local" id="techeval_date" name="techeval_date" class="form-control bg-white shadow-sm"  max="<?= $max_datetime_val ?>" value="<?= $current_datetime_val ?>"></div>
                                    <div class="col-md-6">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-camera me-1"></i> Evaluation Screenshots <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_te">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="techeval_screens[]" id="techeval_screens" class="d-none" multiple>
                                            <div id="paste_preview_te" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modern-table-wrapper mb-2">
                                    <table class="modern-table" id="techeval_table">
                                        <thead>
                                            <tr><th class="ps-3">Company Name</th><th style="width: 30%;">Status</th><th style="width: 5%;"></th></tr>
                                        </thead>
                                        <tbody id="techeval_body">
                                            <tr>
                                                <td class="ps-3"><select name="techeval_company[]" class="table-input fw-bold"><?= $comp_options_tech_html ?></select></td>
                                                <td><select name="techeval_status[]" class="table-input text-primary fw-bold"><option value="Qualified">Qualified</option><option value="Dis-Qualified" class="text-danger">Dis-Qualified</option></select></td>
                                                <td><button type="button" class="btn btn-sm btn-light text-danger remove-te-row shadow-sm"><i class="fas fa-times"></i></button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="p-3 bg-light d-flex justify-content-start align-items-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3 py-2" id="add_te_company_btn" style="border-radius: 8px;"><i class="fas fa-plus me-1"></i> Add Company</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4 fineval-extra" style="display: none;">
                            <div class="p-4 rounded-3 shadow-sm" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="fw-bold mb-4" style="color: #1e3a8a;"><i class="fas fa-chart-line me-2"></i>Financial Evaluation Actions</h6>
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6"><label class="modern-label text-dark">Date & Time <span class="text-danger">*</span></label><input type="datetime-local" id="fineval_date" name="fineval_date" class="form-control bg-white shadow-sm"  max="<?= $max_datetime_val ?>" value="<?= $current_datetime_val ?>"></div>
                                    <div class="col-md-6">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-camera me-1"></i> Evaluation Screenshots <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_fe">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="fineval_screens[]" id="fineval_screens" class="d-none" multiple>
                                            <div id="paste_preview_fe" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modern-table-wrapper mb-2">
                                    <table class="modern-table" id="fineval_table">
                                        <thead>
                                            <tr><th class="ps-3">Company Name</th><th style="width: 20%;">Sequence Level</th><th style="width: 25%;">Price (₹)</th><th style="width: 5%;"></th></tr>
                                        </thead>
                                        <tbody id="fineval_body">
                                            <tr>
                                                <td class="ps-3"><select name="fineval_company[]" id="fe_comp_initial" class="table-input fe-company-select fw-bold"><?= $comp_options_qualified_html ?></select></td>
                                                <td><select name="fineval_level[]" class="table-input fw-bold text-success fineval-level-select" style="pointer-events: none; background-color: #f1f5f9;"><option value="">Auto-Sequence</option></select></td>
                                                <td><input type="number" step="0.01" min="0" name="fineval_price[]" class="table-input fineval-price-input fw-bold" placeholder="0.00"></td>
                                                <td><button type="button" class="btn btn-sm btn-light text-danger remove-fe-row shadow-sm"><i class="fas fa-times"></i></button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="p-3 bg-light d-flex justify-content-start align-items-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3 py-2" id="add_fe_company_btn" style="border-radius: 8px;"><i class="fas fa-plus me-1"></i> Add Company</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4 ra-create-extra" style="display: none;">
                            <div class="p-4 rounded-3 shadow-sm" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="fw-bold mb-4" style="color: #1e3a8a;"><i class="fas fa-gavel me-2"></i>Create Reverse Auction</h6>
                                <div class="row g-4 mb-4">
                                    <div class="col-md-4"><label class="modern-label text-dark">Create Date <span class="text-danger">*</span></label><input type="date" name="ra_create_date" class="form-control bg-white shadow-sm" min="<?= $current_date_val ?>" max="<?= $max_date_val ?>" value="<?= $current_date_val ?>"></div>
                                    <div class="col-md-4"><label class="modern-label text-dark">RA Start Date & Time <span class="text-danger">*</span></label><input type="datetime-local" name="ra_start_date" class="form-control bg-white shadow-sm"  max="<?= $max_datetime_val ?>" value="<?= $current_datetime_val ?>"></div>
                                    <div class="col-md-4"><label class="modern-label text-dark">RA End Date & Time <span class="text-danger">*</span></label><input type="datetime-local" name="ra_end_date" class="form-control bg-white shadow-sm"  max="<?= $max_datetime_val ?>" value="<?= $current_datetime_val ?>"></div>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-camera me-1"></i> RA Screenshots <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_ra_cr">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="ra_create_screens[]" id="ra_create_screens" class="d-none" multiple>
                                            <div id="paste_preview_ra_cr" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-file-pdf me-1"></i> RA Documents <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_ra_docs">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="ra_create_docs[]" id="ra_create_docs" class="d-none" multiple>
                                            <div id="paste_preview_ra_docs" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4 ra-inprogress-extra" style="display: none;">
                            <div class="p-4 rounded-3 shadow-sm" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="fw-bold mb-4" style="color: #1e3a8a;"><i class="fas fa-sync fa-spin me-2"></i>RA Inprogress Entry</h6>
                                <div class="row g-4 mb-4">
                                    <div class="col-md-4"><label class="modern-label text-dark">Company Name <span class="text-danger">*</span></label><select name="ra_in_company" class="form-select bg-white shadow-sm fw-bold"><?= $comp_options_qualified_html ?></select></div>
                                    <div class="col-md-4"><label class="modern-label text-dark">Base Price (Manual) <span class="text-danger">*</span></label><input type="number" step="0.01" name="ra_in_base_price" class="form-control bg-white shadow-sm text-secondary fw-bold" value="<?= $base_price_val ?>"></div>
                                    <div class="col-md-4"><label class="modern-label text-dark">RA Total Price <span class="text-danger">*</span></label><input type="number" step="0.01" name="ra_in_ra_price" class="form-control bg-white shadow-sm text-primary fw-bold" placeholder="0.00"></div>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-camera me-1"></i> RA Screenshot <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_ra_in">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="ra_in_screens[]" id="ra_in_screens" class="d-none" multiple>
                                            <div id="paste_preview_ra_in" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-network-wired me-1"></i> IP Screenshot <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_ra_in_ip">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="ra_in_ip_screens[]" id="ra_in_ip_screens" class="d-none" multiple>
                                            <div id="paste_preview_ra_in_ip" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4 ra-finalize-extra" style="display: none;">
                            <div class="p-4 rounded-3 shadow-sm" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="fw-bold mb-4" style="color: #1e3a8a;"><i class="fas fa-check-double me-2"></i>Finalize Reverse Auction</h6>
                                <div class="row g-4">
                                    <div class="col-md-12">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-camera me-1"></i> Final Screenshot <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_ra_fin">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="ra_fin_screens[]" id="ra_fin_screens" class="d-none" multiple>
                                            <div id="paste_preview_ra_fin" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4 price-neg-extra" style="display: none;">
                            <div class="p-4 rounded-3 shadow-sm" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="fw-bold mb-4" style="color: #1e3a8a;"><i class="fas fa-handshake me-2"></i>Price Negotiation</h6>
                                <div class="row g-4 mb-4">
                                    <div class="col-md-4">
                                        <label class="modern-label text-dark">Company Name <span class="text-danger">*</span></label>
                                        <select name="pn_company" class="form-select bg-white shadow-sm fw-bold">
                                            <?= $comp_options_award_html ?>
                                        </select>
                                    </div>
                                </div>



                                <div class="modern-table-wrapper mb-4">
                                    <table class="modern-table" id="pn_product_table">
                                        <thead>
                                            <tr><th class="ps-3">Product Name</th><th style="width: 15%;">Qty</th><th style="width: 20%;">Amount (₹)</th><th style="width: 20%;">Total (₹)</th><th style="width: 5%;"></th></tr>
                                        </thead>
                                        <tbody id="pn_product_body">
                                            <tr>
                                                <td class="ps-3"><input type="text" name="pn_prod_name[]" class="table-input" placeholder="Product Details" pattern="^[A-Za-z0-9\s]+$" title="Only letters, numbers, and spaces allowed" maxlength="150" oninput="this.value = this.value.replace(/[^A-Za-z0-9\s]/g, '').substring(0, 150)"></td>
                                                <td><input type="number" name="pn_prod_qty[]" class="table-input pn-qty-input" value="1" min="1"></td>
                                                <td><input type="number" name="pn_prod_price[]" class="table-input pn-price-input" value="0" min="0" step="0.01"></td>
                                                <td><input type="text" name="pn_prod_total[]" class="table-input text-primary fw-bold border-0 pn-total-input" style="background: transparent; box-shadow: none;" readonly></td>
                                                <td><button type="button" class="btn btn-sm btn-light text-danger remove-pn-row shadow-sm"><i class="fas fa-times"></i></button></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="p-3 bg-light d-flex justify-content-between align-items-center">
                                        <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-3 py-2 text-primary" id="add_pn_product_btn" style="border-radius: 8px;"><i class="fas fa-plus me-1"></i> Add Product</button>
                                        <div class="d-flex align-items-center bg-white px-4 py-2 rounded shadow-sm border border-primary"><span class="text-muted fw-bold me-3 text-uppercase small">Grand Total</span><span class="fw-bolder text-primary" style="font-size: 24px;">₹</span><input type="text" id="pn_grand_total" class="form-control fw-bolder text-primary border-0 bg-transparent p-0 ms-1 w-auto" style="min-width: 120px; font-size: 26px; outline: none; box-shadow: none;" readonly value="0.00"></div>
                                    </div>
                                </div>
                                <div class="row g-4">
                                    <div class="col-md-12">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-paperclip me-1"></i> Attachments (Screenshots/Docs) <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_pn">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> to upload or press Ctrl+V to paste</span>
                                            <input type="file" name="pn_screens[]" id="pn_screens" class="d-none" multiple>
                                            <div id="paste_preview_pn" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4 cancel-extra" style="display: none;">
                            <div class="p-4 rounded-3 shadow-sm" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="fw-bold mb-4" style="color: #1e3a8a;"><i class="fas fa-ban me-2"></i>Bid Cancel Details</h6>
                                <div class="row g-4">
                                    <div class="col-md-6"><label class="modern-label text-dark">Cancel Date <span class="text-danger">*</span></label><input type="date" name="cancel_date" class="form-control bg-white shadow-sm" min="<?= $current_date_val ?>" max="<?= $max_date_val ?>" value="<?= $current_date_val ?>"></div>
                                    <div class="col-md-6">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-camera me-1"></i> Attachments / Screenshots <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_cancel">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="cancel_screens[]" id="cancel_screens" class="d-none" multiple>
                                            <div id="paste_preview_cancel" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-4 award-extra" style="display: none;">
                            <div class="p-4 rounded-3 shadow-sm" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <h6 class="fw-bold mb-4" style="color: #1e3a8a;"><i class="fas fa-trophy me-2"></i>Bid Award Details</h6>
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="modern-label text-dark">Award Status <span class="text-danger">*</span></label>
                                        <select name="award_status" class="form-select bg-white shadow-sm fw-bold">
                                            <option value="">Select Status...</option>
                                            <option value="Win" class="text-success">Win</option>
                                            <option value="Loss" class="text-danger">Loss</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="modern-label text-dark">Company <span class="text-danger">*</span></label>
                                       <select name="award_company" id="award_company_select" class="form-select bg-white shadow-sm fw-bold">
                                            <?= $comp_options_award_html ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="modern-label text-dark">Date <span class="text-danger">*</span></label>
                                        <input type="date" name="award_date" class="form-control bg-white shadow-sm" min="<?= $current_date_val ?>" max="<?= $max_date_val ?>" value="<?= $current_date_val ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="modern-label text-dark">Price (₹) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="award_price" id="award_price_input" class="form-control bg-white shadow-sm fw-bold text-primary" placeholder="Auto-filled" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="modern-label text-dark">Bid Value (₹) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" name="award_value" id="award_value_input" class="form-control bg-white shadow-sm fw-bold text-success" placeholder="0.00">
                                    </div>
                                    <div class="col-md-12 mt-4">
                                        <label class="modern-label text-primary mb-2"><i class="fas fa-paperclip me-1"></i> Attachments / Screenshots <span class="text-danger">*</span></label>
                                        <div class="paste-zone shadow-sm" tabindex="0" id="paste_zone_award">
                                            <span class="text-muted small fw-bold"><span class="upload-trigger text-primary text-decoration-underline" style="cursor:pointer;">Click</span> & press Ctrl+V to paste</span>
                                            <input type="file" name="award_screens[]" id="award_screens" class="d-none" multiple>
                                            <div id="paste_preview_award" class="mt-2 text-primary fw-bold small text-start w-100" style="max-height: 60px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!$is_canceled): ?>
                        <div class="mt-5 pt-4 border-top">
                            <div class="row align-items-end">
                                <div class="col-md-9"><label class="modern-label text-primary"><i class="fas fa-edit me-1"></i> Activity Note / Update Remarks</label><textarea name="remark_text" class="form-control" rows="2" placeholder="Enter reason for edit, or latest conversation details..."></textarea></div>
                                <div class="col-md-3 text-end"><button type="submit" name="save_all" class="btn btn-indigo w-100 py-3 shadow-sm"><i class="fas fa-save me-2"></i> Save Changes</button></div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="mt-5 pt-4 border-top text-center">
                            <div class="alert alert-warning fw-bold d-inline-block">
                                <i class="fas fa-lock me-2"></i> This Bid is permanently cancelled and cannot be modified further.
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </form>

            <?php if (!empty($bid['uploaded_files'])): ?>
            <div class="card modern-card p-2 p-md-4">
                <div class="card-body">
                    <div class="section-heading mt-0">All Attached Documents</div>
                    <div class="d-flex flex-wrap gap-3 mt-3">
                        <?php
                        $files = explode(',', $bid['uploaded_files']);
                        foreach ($files as $file) {
                            $filename = trim($file); if(empty($filename)) continue;
                            $filepath = 'uploads/bids/' . $filename;
                            $display_name = preg_replace('/^[0-9]+_(C_|E_|S_|SD_|SS_|SIP_|SPR_|SEMD_|QS_|QD_|QSS_|REPS_|REPD_|TE_|FE_|RAC_|RACD_|RAI_|RAIIP_|RAF_|PN_|BC_|BA_)?\d+_/', '', $filename);
                            $badge_class = "text-secondary"; $badge_text = "Main Doc";
                            
                            if (strpos($filename, '_C_') !== false) { $badge_class = "text-primary"; $badge_text = "Corrigendum"; }
                            elseif (strpos($filename, '_SD_') !== false) { $badge_class = "text-primary"; $badge_text = "Sub Doc"; }
                            elseif (strpos($filename, '_SIP_') !== false) { $badge_class = "text-primary"; $badge_text = "IP Screen"; }
                            elseif (strpos($filename, '_SPR_') !== false) { $badge_class = "text-primary"; $badge_text = "Price Screen"; }
                            elseif (strpos($filename, '_SS_') !== false) { $badge_class = "text-primary"; $badge_text = "Screenshot"; }
                            elseif (strpos($filename, '_SEMD_') !== false) { $badge_class = "text-primary"; $badge_text = "EMD Doc"; }
                            elseif (strpos($filename, '_QD_') !== false) { $badge_class = "text-primary"; $badge_text = "Query Doc"; }
                            elseif (strpos($filename, '_QS_') !== false) { $badge_class = "text-primary"; $badge_text = "Query Screen"; }
                            elseif (strpos($filename, '_QSS_') !== false) { $badge_class = "text-primary"; $badge_text = "Query Sub"; }
                            elseif (strpos($filename, '_REPD_') !== false) { $badge_class = "text-primary"; $badge_text = "Rep Doc"; }
                            elseif (strpos($filename, '_REPS_') !== false) { $badge_class = "text-primary"; $badge_text = "Rep Screen"; }
                            elseif (strpos($filename, '_TE_') !== false) { $badge_class = "text-primary"; $badge_text = "Tech Eval"; }
                            elseif (strpos($filename, '_FE_') !== false) { $badge_class = "text-primary"; $badge_text = "Fin Eval"; }
                            
                            if (file_exists($filepath)) {
                                echo "<div class='text-center' style='width: 140px;'><a href='{$filepath}' target='_blank' class='doc-pill w-100 justify-content-center text-truncate d-inline-block' title='{$display_name}'><i class='fas fa-file-download me-2 text-primary'></i> <span style='font-size: 0.8rem;'>{$display_name}</span></a><div class='small fw-bold mt-1 {$badge_class}' style='font-size:0.7rem;'>{$badge_text}</div></div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card modern-card p-2 p-md-4 mb-5">
                <div class="card-body"><div class="section-heading mt-0"><i class="fas fa-history me-2"></i>Master Activity History</div><div class="history-container mt-3"><?= $hist_sections['master'] ?></div></div>
            </div>
        </div>

        <?php foreach(['corrigendum' => 'Exclamation-Triangle/Corrigendum', 'extension' => 'Calendar-Plus/Extension', 'submission' => 'File-Invoice-Dollar/Submission', 'query' => 'Question-Circle/Query', 'rep' => 'Gavel/Representation', 'techeval' => 'Tasks/Tech Eval', 'fineval' => 'Chart-Line/Fin Eval', 'ra' => 'Gavel/Reverse Auction', 'pn' => 'Handshake/Price Negotiation', 'cancel' => 'Ban/Bid Cancel', 'award' => 'Trophy/Bid Award'] as $key => $tabData): list($icon, $title) = explode('/', $tabData); if(!empty($html_sections[$key])): ?>
            <div class="tab-pane fade" id="tab_<?= $key ?>" role="tabpanel">
                <div class="card modern-card p-2 p-md-4"><div class="card-body"><div class="section-heading mt-0 text-primary border-bottom-0"><i class="fas fa-<?= strtolower($icon) ?> me-2"></i><?= $title ?> Records</div><?= $html_sections[$key] ?><div class="section-heading mt-4 text-secondary"><i class="fas fa-history me-2"></i>History</div><div class="history-container bg-white border"><?= !empty($hist_sections[$key]) ? $hist_sections[$key] : '<div class="text-muted small">No specific chat history.</div>' ?></div></div></div>
            </div>
        <?php endif; endforeach; ?>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const allCompanies = <?= json_encode($company_list); ?>;
        const submittedCompaniesList = <?= json_encode($submitted_companies); ?>; 
        
        // Validation Flags
        const hasRaCreate = <?= $has_ra_create ? 'true' : 'false'; ?>;
        const hasRaInprog = <?= $has_ra_inprog ? 'true' : 'false'; ?>;

        const bidStatusSelect = document.getElementById('bid_status_select');
        const actionExtra = document.querySelector('.action-extra');
        const submittedExtra = document.querySelector('.submitted-extra');
        const subCompanySelect = document.getElementById('sub_company');
        
        // EMD Specific fields
        const subEmdSelect = document.getElementById('sub_emd');
        const emdUploadDiv = document.getElementById('emd_upload_div');

        const queryExtra = document.querySelector('.query-extra'); const repExtra = document.querySelector('.representation-extra'); const techevalExtra = document.querySelector('.techeval-extra'); const finevalExtra = document.querySelector('.fineval-extra'); const priceNegExtra = document.querySelector('.price-neg-extra'); const cancelExtra = document.querySelector('.cancel-extra'); const awardExtra = document.querySelector('.award-extra');
        const raSubStatusContainer = document.getElementById('ra_sub_status_container'); const raSubStatusSelect = document.getElementById('ra_sub_status_select'); const raCreateExtra = document.querySelector('.ra-create-extra'); const raInprogressExtra = document.querySelector('.ra-inprogress-extra'); const raFinalizeExtra = document.querySelector('.ra-finalize-extra');
        const actionBoxTitle = document.getElementById('action_box_title'); const submissionBoxTitle = document.getElementById('submission_box_title'); const actionFileUploadDiv = document.getElementById('action_file_upload_div');

        function hideAllDynamicSections() {
            [actionExtra, submittedExtra, queryExtra, repExtra, techevalExtra, finevalExtra, priceNegExtra, cancelExtra, awardExtra, raSubStatusContainer, raCreateExtra, raInprogressExtra, raFinalizeExtra].forEach(el => el.style.display = 'none');
            if(raSubStatusSelect) raSubStatusSelect.removeAttribute('required');
        }
        
        if(subEmdSelect && emdUploadDiv) {
            subEmdSelect.addEventListener('change', function() {
                if(this.value === 'Yes' || this.value === 'No' || this.value === 'No (Exempted)') {
                    emdUploadDiv.style.display = 'block';
                } else {
                    emdUploadDiv.style.display = 'none';
                }
            });
        }

        function handleStatusChange() {
            hideAllDynamicSections();
            if(!bidStatusSelect) return;
            let val = bidStatusSelect.value;
            
            if (val === 'Corrigendum') { actionExtra.style.display = 'block'; actionBoxTitle.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Corrigendum Action Details'; actionFileUploadDiv.style.display = 'block'; } 
            else if (val === 'Extension date') { actionExtra.style.display = 'block'; actionBoxTitle.innerHTML = '<i class="fas fa-calendar-plus me-1"></i> Extension Date Details'; actionFileUploadDiv.style.display = 'none'; } 
            else if (val === 'Submitted') { 
                submittedExtra.style.display = 'block'; submissionBoxTitle.innerText = 'Final Submission Form'; 
                subCompanySelect.innerHTML = '<option value="">Select Company...</option>';
                allCompanies.forEach(c => {
                    if(!submittedCompaniesList.includes(c)) subCompanySelect.innerHTML += `<option value="${c}">${c}</option>`;
                });
            } 
            else if (val === 'Re-submitted') { 
                submittedExtra.style.display = 'block'; submissionBoxTitle.innerText = 'Re-Submission Form'; 
                subCompanySelect.innerHTML = '<option value="">Select Company...</option>';
                submittedCompaniesList.forEach(c => { subCompanySelect.innerHTML += `<option value="${c}">${c}</option>`; });
            } 
            else if (val === 'Query window') { queryExtra.style.display = 'block'; } 
            else if (val === 'Representation') { repExtra.style.display = 'block'; } 
            else if (val === 'Technical Evaluation' || val === 'Re-Technical Evaluation') { 
                techevalExtra.style.display = 'block'; 
                let titleEl = document.getElementById('techeval_box_title');
                if (val === 'Re-Technical Evaluation') titleEl.innerHTML = '<i class="fas fa-tasks me-2"></i>Re-Technical Evaluation Actions';
                else titleEl.innerHTML = '<i class="fas fa-tasks me-2"></i>Technical Evaluation Actions';
            } 
            else if (val === 'Financial Evaluation') { finevalExtra.style.display = 'block'; } 
          else if (val === 'Reverse Auction') { 
            raSubStatusContainer.style.display = 'block'; raSubStatusSelect.setAttribute('required', 'required'); 
            Array.from(raSubStatusSelect.options).forEach(opt => {
                opt.disabled = false; // Pehle sabko enable karo
                
                if(opt.value === 'RA Inprogress') {
                    if(!hasRaCreate) {
                        opt.disabled = true;
                        opt.innerHTML = "RA Inprogress (Create RA First)";
                    } else {
                        opt.innerHTML = "RA Inprogress";
                    }
                }

                if(opt.value === 'RA Finalize') {
                    if(!hasRaInprog) {
                        opt.disabled = true;
                        opt.innerHTML = "RA Finalize (Do Inprogress First)";
                    } else {
                        opt.innerHTML = "RA Finalize";
                    }
                }
            });
            handleRaSubStatusChange(); 
        }
            else if (val === 'Price Negotiation window') { priceNegExtra.style.display = 'block'; } 
            else if (val === 'Bid Cancel') { cancelExtra.style.display = 'block'; } 
            else if (val === 'Bid Award') { awardExtra.style.display = 'block'; }
        }

        function handleRaSubStatusChange() {
            raCreateExtra.style.display = 'none'; raInprogressExtra.style.display = 'none'; raFinalizeExtra.style.display = 'none';
            if(!raSubStatusSelect) return;
            let raVal = raSubStatusSelect.value;
            if(raVal === 'Create RA') raCreateExtra.style.display = 'block';
            else if(raVal === 'RA Inprogress') raInprogressExtra.style.display = 'block';
            else if(raVal === 'RA Finalize') raFinalizeExtra.style.display = 'block';
        }
        
        if(bidStatusSelect) { bidStatusSelect.addEventListener('change', handleStatusChange); handleStatusChange(); }
        if(raSubStatusSelect) { raSubStatusSelect.addEventListener('change', handleRaSubStatusChange); }

        const updateForm = document.getElementById('bidUpdateForm');
        if(updateForm) {
            updateForm.addEventListener('submit', function(e) {
                if(!bidStatusSelect) return;
                let statusVal = bidStatusSelect.value; let activeSection = null;
                if (statusVal === 'Corrigendum' || statusVal === 'Extension date') activeSection = actionExtra;
                else if (statusVal === 'Submitted' || statusVal === 'Re-submitted') activeSection = submittedExtra;
                else if (statusVal === 'Query window') activeSection = queryExtra; else if (statusVal === 'Representation') activeSection = repExtra;
                else if (statusVal === 'Technical Evaluation' || statusVal === 'Re-Technical Evaluation') activeSection = techevalExtra; 
                else if (statusVal === 'Financial Evaluation') activeSection = finevalExtra;
                else if (statusVal === 'Price Negotiation window') activeSection = priceNegExtra; 
                else if (statusVal === 'Bid Cancel') activeSection = cancelExtra; else if (statusVal === 'Bid Award') activeSection = awardExtra;
                else if (statusVal === 'Reverse Auction') {
                    let raVal = raSubStatusSelect.value;
                    if(raVal === 'Create RA') activeSection = raCreateExtra; else if(raVal === 'RA Inprogress') activeSection = raInprogressExtra; else if(raVal === 'RA Finalize') activeSection = raFinalizeExtra;
                }

                if (activeSection) {
                    let inputsToValidate = activeSection.querySelectorAll('input:not([readonly]):not([disabled]), select, textarea');
                    let emptyFields = [];
                    for (let i = 0; i < inputsToValidate.length; i++) {
                        let input = inputsToValidate[i];
                        let isVisible = input.offsetParent !== null;
                        if (!isVisible && input.type === 'file' && input.parentElement && input.parentElement.offsetParent !== null) { isVisible = true; }

                        if (isVisible) {
                            let isEmpty = false;
                            if (input.type === 'file') { if (input.files.length === 0) isEmpty = true; } 
                            else { if (input.value.trim() === '') isEmpty = true; }

                            if (isEmpty) {
                                let labelName = "Required field"; let wrapper = input.closest('.col-md-3, .col-md-4, .col-md-6, .col-md-12, td');
                                if (wrapper && wrapper.querySelector('.modern-label')) { labelName = wrapper.querySelector('.modern-label').innerText.replace(/[\*:]/g, '').trim(); } 
                                else if (wrapper && wrapper.tagName === 'TD') {
                                    let cellIndex = Array.from(wrapper.parentElement.children).indexOf(wrapper); let table = input.closest('table');
                                    if (table && table.querySelector('thead th:nth-child(' + (cellIndex + 1) + ')')) { labelName = table.querySelector('thead th:nth-child(' + (cellIndex + 1) + ')').innerText.trim(); }
                                } else if (input.placeholder) { labelName = input.placeholder; }
                                
                                input.style.border = '2px solid #ef4444';
                                emptyFields.push(labelName);
                            } else {
                                input.style.border = '';
                            }
                        }
                    }
                    
                    if(emptyFields.length > 0) {
                        e.preventDefault();
                        alert("⚠️ Please complete the following required fields or attachments:\n- " + emptyFields.join("\n- "));
                        return false;
                    }
                }

                if (statusVal === 'Technical Evaluation' || statusVal === 'Re-Technical Evaluation' || statusVal === 'Financial Evaluation') {
                    let prefix = (statusVal === 'Technical Evaluation' || statusVal === 'Re-Technical Evaluation') ? 'techeval_company[]' : 'fineval_company[]';
                    let selects = document.querySelectorAll(`select[name="${prefix}"]`);
                    let selectedComps = [];
                    for(let i=0; i<selects.length; i++){
                        let val = selects[i].value;
                        if(val !== '') {
                            if(selectedComps.includes(val)) { e.preventDefault(); alert("⚠️ Error: You have selected '" + val + "' multiple times."); return false; }
                            selectedComps.push(val);
                        }
                    }
                }
            });
        }

        const productBody = document.getElementById('product_body'); const addProductBtn = document.getElementById('add_product_btn'); const grandTotalInput = document.getElementById('grand_total');
        function calculateTotals() {
            let grandTotal = 0;
            document.querySelectorAll('#product_body tr').forEach(row => {
                let qtyInput = row.querySelector('.qty-input'); let priceInput = row.querySelector('.price-input'); let totalInput = row.querySelector('.total-input');
                if(qtyInput && priceInput && totalInput) {
                    let qty = parseFloat(qtyInput.value) || 0; let price = parseFloat(priceInput.value) || 0; let total = qty * price;
                    totalInput.value = total.toFixed(2); grandTotal += total;
                }
            });
            if(grandTotalInput) grandTotalInput.value = grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        if(productBody) {
            productBody.addEventListener('input', function(e) { if(e.target.classList.contains('qty-input') || e.target.classList.contains('price-input')) calculateTotals(); });
            productBody.addEventListener('click', function(e) { if(e.target.closest('.remove-row')) { e.target.closest('tr').remove(); calculateTotals(); } });
            if(addProductBtn) {
               add_product_btn.addEventListener('click', function() {
    const tr = document.createElement('tr'); tr.className = "border-bottom border-light";
    tr.innerHTML = `<td class="ps-3"><input type="text" name="prod_name[]" class="table-input" placeholder="Product Details" pattern="^[A-Za-z0-9\\s]+$" title="Only letters, numbers, and spaces allowed" maxlength="150" oninput="this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '').substring(0, 150)"></td><td><input type="number" name="prod_qty[]" class="table-input qty-input" value="1" min="1"></td><td><input type="number" name="prod_price[]" class="table-input price-input" value="0" min="0" step="0.01"></td><td><input type="text" name="prod_total[]" class="table-input text-primary fw-bold border-0 total-input" style="background: transparent; box-shadow: none;" readonly></td><td><button type="button" class="btn btn-sm btn-light text-danger remove-row shadow-sm"><i class="fas fa-times"></i></button></td>`;
    productBody.appendChild(tr);
});
            }
        }

        const pnProductBody = document.getElementById('pn_product_body'); const addPnProductBtn = document.getElementById('add_pn_product_btn'); const pnGrandTotalInput = document.getElementById('pn_grand_total');
        function calculatePnTotals() {
            let grandTotal = 0;
            document.querySelectorAll('#pn_product_body tr').forEach(row => {
                let qtyInput = row.querySelector('.pn-qty-input'); let priceInput = row.querySelector('.pn-price-input'); let totalInput = row.querySelector('.pn-total-input');
                if(qtyInput && priceInput && totalInput) { let qty = parseFloat(qtyInput.value) || 0; let price = parseFloat(priceInput.value) || 0; let total = qty * price; totalInput.value = total.toFixed(2); grandTotal += total; }
            });
            if(pnGrandTotalInput) pnGrandTotalInput.value = grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
        if(pnProductBody) {
            pnProductBody.addEventListener('input', function(e) { if(e.target.classList.contains('pn-qty-input') || e.target.classList.contains('pn-price-input')) calculatePnTotals(); });
            pnProductBody.addEventListener('click', function(e) { if(e.target.closest('.remove-pn-row')) { e.target.closest('tr').remove(); calculatePnTotals(); } });
            if(addPnProductBtn) {
                addPnProductBtn.addEventListener('click', function() {
                    const tr = document.createElement('tr'); tr.className = "border-bottom border-light";
                   tr.innerHTML = `<td class="ps-3"><input type="text" name="pn_prod_name[]" class="table-input" placeholder="Product Details" pattern="^[A-Za-z0-9\\s]+$" title="Only letters, numbers, and spaces allowed" maxlength="150" oninput="this.value = this.value.replace(/[^A-Za-z0-9\\s]/g, '').substring(0, 150)"></td><td><input type="number" name="pn_prod_qty[]" class="table-input pn-qty-input" value="1" min="1"></td><td><input type="number" name="pn_prod_price[]" class="table-input pn-price-input" value="0" min="0" step="0.01"></td><td><input type="text" name="pn_prod_total[]" class="table-input text-primary fw-bold border-0 pn-total-input" style="background: transparent; box-shadow: none;" readonly></td><td><button type="button" class="btn btn-sm btn-light text-danger remove-pn-row shadow-sm"><i class="fas fa-times"></i></button></td>`;
                    pnProductBody.appendChild(tr);
                });
            }
        }

        const techevalBody = document.getElementById('techeval_body'); const addTechevalBtn = document.getElementById('add_te_company_btn'); const compOptionsTechStr = `<?= $comp_options_tech_html ?>`;
        if(techevalBody && addTechevalBtn) {
            techevalBody.addEventListener('click', function(e) { if(e.target.closest('.remove-te-row')) { e.target.closest('tr').remove(); } });
            addTechevalBtn.addEventListener('click', function() {
                const tr = document.createElement('tr'); tr.className = "border-bottom border-light";
                tr.innerHTML = `<td class="ps-3"><select name="techeval_company[]" class="table-input fw-bold">${compOptionsTechStr}</select></td><td><select name="techeval_status[]" class="table-input text-primary fw-bold"><option value="Qualified">Qualified</option><option value="Dis-Qualified" class="text-danger">Dis-Qualified</option></select></td><td><button type="button" class="btn btn-sm btn-light text-danger remove-te-row shadow-sm"><i class="fas fa-times"></i></button></td>`;
                techevalBody.appendChild(tr);
            });
        }

       // --- FINANCIAL EVALUATION LOGIC (Sorted by Lowest Price = L1) ---
        const finevalBody = document.getElementById('fineval_body'); 
        const addFinevalBtn = document.getElementById('add_fe_company_btn'); 
        const compOptionsFinStr = `<?= $comp_options_qualified_html ?>`;
        
        function calculateFinEvalLevels() {
            let rows = Array.from(document.querySelectorAll('#fineval_body tr'));
            let data = [];
            
            rows.forEach((row, index) => {
                let compSelect = row.querySelector('.fe-company-select');
                let priceInput = row.querySelector('.fineval-price-input');
                let lvlSelect = row.querySelector('.fineval-level-select');
                
                if (compSelect && compSelect.value !== '') {
                    let price = parseFloat(priceInput.value) || 0;
                    data.push({ select: lvlSelect, price: price });
                } else if (lvlSelect) {
                    lvlSelect.innerHTML = `<option value="">N/A</option>`;
                }
            });

            // Sort data by price ascending (lowest price first)
            data.sort((a, b) => a.price - b.price);

            // Assign L1, L2, L3 based on sorted position
            data.forEach((item, index) => {
                let assignedLevel = 'L' + (index + 1);
                if (item.select) {
                    item.select.innerHTML = `<option value="${assignedLevel}" selected>${assignedLevel}</option>`;
                }
            });
        }
        
        if(finevalBody && addFinevalBtn) {
            finevalBody.addEventListener('change', function(e) { 
                if(e.target.classList.contains('fe-company-select')) { calculateFinEvalLevels(); } 
            });
            // NEW: Price type karte hi live L1, L2 change hoga
            finevalBody.addEventListener('input', function(e) { 
                if(e.target.classList.contains('fineval-price-input')) { calculateFinEvalLevels(); } 
            });
            finevalBody.addEventListener('click', function(e) { 
                if(e.target.closest('.remove-fe-row')) { 
                    e.target.closest('tr').remove(); 
                    calculateFinEvalLevels(); 
                } 
            });
            
            addFinevalBtn.addEventListener('click', function() {
                const tr = document.createElement('tr'); tr.className = "border-bottom border-light";
                let selectId = 'fe_comp_' + Date.now();
                tr.innerHTML = `
                    <td class="ps-3"><select name="fineval_company[]" id="${selectId}" class="table-input fe-company-select fw-bold">${compOptionsFinStr}</select></td>
                    <td><select name="fineval_level[]" class="table-input fw-bold text-success fineval-level-select" style="pointer-events: none; background-color: #f1f5f9;"><option value="">Auto-Sequence</option></select></td>
                    <td><input type="number" step="0.01" min="0" name="fineval_price[]" class="table-input fineval-price-input fw-bold" placeholder="0.00"></td>
                    <td><button type="button" class="btn btn-sm btn-light text-danger remove-fe-row shadow-sm"><i class="fas fa-times"></i></button></td>
                `;
                finevalBody.appendChild(tr);
            });
        }
        // -----------------------------------------------------------

        const companyPrices = <?= json_encode($company_prices); ?>;
        const pnTotal = <?= json_encode($pn_total); ?>;
        
        const awardCompSelect = document.getElementById('award_company_select');
        const awardPriceInput = document.getElementById('award_price_input');
        
        if(awardCompSelect) {
            awardCompSelect.addEventListener('change', function() {
                let selectedComp = this.value;
                let finalPrice = 0;
                
              if (selectedComp !== '') {
                    if(pnTotal > 0) {
                        finalPrice = pnTotal;
                    } else if(companyPrices[selectedComp]) {
                        finalPrice = companyPrices[selectedComp];
                    }
                }
                if(awardPriceInput) awardPriceInput.value = finalPrice;
            });
        }

        function setupPasteZone(zoneId, inputId, previewId) {
            const pasteZone = document.getElementById(zoneId); const fileInput = document.getElementById(inputId); const pastePreview = document.getElementById(previewId);
            if(pasteZone && fileInput) {
                fileInput.dataTransferObj = new DataTransfer();
                fileInput.renderPreview = function() {
                    pastePreview.innerHTML = '';
                    for (let i = 0; i < fileInput.files.length; i++) {
                        pastePreview.innerHTML += `<div class="d-flex justify-content-between align-items-center mb-1 p-1 border rounded bg-white text-dark shadow-sm"><span class="text-truncate small"><i class="fas fa-check text-success me-1"></i> ${fileInput.files[i].name}</span><button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 remove-zone-file-btn" data-index="${i}" title="Remove file" style="z-index: 10;"><i class="fas fa-times"></i></button></div>`;
                    }
                    const removeBtns = pastePreview.querySelectorAll('.remove-zone-file-btn');
                    removeBtns.forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.stopPropagation(); const idxToRemove = parseInt(this.getAttribute('data-index')); let dt = new DataTransfer();
                            for (let j = 0; j < fileInput.files.length; j++) { if (j !== idxToRemove) dt.items.add(fileInput.files[j]); }
                            fileInput.dataTransferObj = dt; fileInput.files = fileInput.dataTransferObj.files; fileInput.renderPreview();
                        });
                    });
                };
                pasteZone.addEventListener('click', function(e) {
                    if(e.target.classList.contains('upload-trigger') || e.target.closest('.upload-trigger')) { fileInput.click(); } else if(!e.target.closest('.remove-zone-file-btn')) { pasteZone.focus(); }
                });
                fileInput.addEventListener('change', function() {
                    for(let i = 0; i < this.files.length; i++) {
                        let exists = false;
                        for(let j = 0; j < fileInput.dataTransferObj.items.length; j++) { if(fileInput.dataTransferObj.items[j].getAsFile().name === this.files[i].name) exists = true; }
                        if(!exists) fileInput.dataTransferObj.items.add(this.files[i]);
                    }
                    this.files = fileInput.dataTransferObj.files; this.renderPreview(); pasteZone.style.backgroundColor = '#eff6ff';
                });
               pasteZone.addEventListener('paste', function(e) {
    e.preventDefault(); 
    const items = e.clipboardData.items; 
    let hasFile = false;
    
    for (let i = 0; i < items.length; i++) {
        // CHANGED: Accept ANY file type (Document, PDF, ZIP, Image, Excel, etc.)
        if (items[i].kind === 'file') {
            const file = items[i].getAsFile();
            if (!file) continue;

            // Handle default names for generic pasted items (like screenshots or raw data)
            let fileName = file.name;
            if (fileName === 'image.png') {
                fileName = `Pasted_Image_${Date.now()}.png`;
            } else if (fileName === 'blob' || !fileName) {
                let ext = file.type.split('/')[1] || 'tmp';
                fileName = `Pasted_File_${Date.now()}.${ext}`;
            }

            let newFile = new File([file], fileName, {type: file.type});
            fileInput.dataTransferObj.items.add(newFile); 
            hasFile = true;
        }
    }
    
    if (hasFile) { 
        fileInput.files = fileInput.dataTransferObj.files; 
        fileInput.renderPreview(); 
        pasteZone.style.backgroundColor = '#eff6ff'; 
    } else { 
        alert('No valid file found in clipboard! Please copy a file to paste.'); 
    }
});
            }
        }

        // Initialize All Paste Zones
        setupPasteZone('paste_zone_action', 'action_files', 'paste_preview_action'); 
        setupPasteZone('paste_zone_sub_docs', 'sub_docs', 'paste_preview_sub_docs');
        setupPasteZone('paste_zone_sub_emd', 'sub_emd_docs', 'paste_preview_sub_emd'); 
        setupPasteZone('paste_zone_sub_ip', 'sub_ip_screens', 'paste_preview_sub_ip');
        setupPasteZone('paste_zone_sub_price', 'sub_price_screens', 'paste_preview_sub_price');

        setupPasteZone('paste_zone_q_scr', 'query_screens', 'paste_preview_q_scr'); 
        setupPasteZone('paste_zone_q_docs', 'query_docs', 'paste_preview_q_docs'); 
        setupPasteZone('paste_zone_q_sub', 'query_sub_screens', 'paste_preview_q_sub'); 
        
        setupPasteZone('paste_zone_rep_scr', 'rep_screens', 'paste_preview_rep_scr');
        setupPasteZone('paste_zone_rep_docs', 'rep_docs', 'paste_preview_rep_docs');
        
        setupPasteZone('paste_zone_te', 'techeval_screens', 'paste_preview_te');
        setupPasteZone('paste_zone_fe', 'fineval_screens', 'paste_preview_fe'); 
        setupPasteZone('paste_zone_pn', 'pn_screens', 'paste_preview_pn'); 
        setupPasteZone('paste_zone_cancel', 'cancel_screens', 'paste_preview_cancel'); 
        setupPasteZone('paste_zone_award', 'award_screens', 'paste_preview_award'); 
        
        setupPasteZone('paste_zone_ra_cr', 'ra_create_screens', 'paste_preview_ra_cr'); 
        setupPasteZone('paste_zone_ra_docs', 'ra_create_docs', 'paste_preview_ra_docs'); 
        setupPasteZone('paste_zone_ra_in', 'ra_in_screens', 'paste_preview_ra_in'); 
        setupPasteZone('paste_zone_ra_in_ip', 'ra_in_ip_screens', 'paste_preview_ra_in_ip'); 
        setupPasteZone('paste_zone_ra_fin', 'ra_fin_screens', 'paste_preview_ra_fin'); 
    });
</script>

<?php include 'include/footer.php'; ?>