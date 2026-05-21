<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
include __DIR__ . '/include/header.php';

$role = $_SESSION['role'] ?? 'user';
$user_id = $_SESSION['user_id'] ?? 0;

// AUTO-DATABASE UPDATE: Naya column khud banayega agar nahi hai
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM gov_sales_entries LIKE 'doc_upload_status'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE gov_sales_entries ADD COLUMN doc_upload_status ENUM('no', 'yes') DEFAULT 'no'");
}

// ----------------- FILTERS HANDLE KARNA -----------------
$where_clauses = [];

// Role Based Filtering
$filter_user = isset($_GET['staff_id']) ? mysqli_real_escape_string($conn, $_GET['staff_id']) : '';
if ($role != 'admin') {
    $where_clauses[] = "g.created_by = '$user_id'";
} elseif (!empty($filter_user)) {
    $where_clauses[] = "g.created_by = '$filter_user'";
}

// 1. Universal Search
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
if (!empty($search_query)) {
    $where_clauses[] = "(g.gem_contact_no LIKE '%$search_query%' OR g.department_name LIKE '%$search_query%' OR g.product LIKE '%$search_query%' OR g.company LIKE '%$search_query%')";
}

// 2. ADVANCED FILTERS 
$f_gem = isset($_GET['f_gem']) ? mysqli_real_escape_string($conn, $_GET['f_gem']) : '';
$f_cdate = isset($_GET['f_cdate']) ? mysqli_real_escape_string($conn, $_GET['f_cdate']) : '';
$f_ddate = isset($_GET['f_ddate']) ? mysqli_real_escape_string($conn, $_GET['f_ddate']) : '';
$f_dept = isset($_GET['f_dept']) ? mysqli_real_escape_string($conn, $_GET['f_dept']) : '';
$f_company = isset($_GET['f_company']) ? mysqli_real_escape_string($conn, $_GET['f_company']) : ''; 
$f_prod = isset($_GET['f_prod']) ? mysqli_real_escape_string($conn, $_GET['f_prod']) : '';
$f_qty = isset($_GET['f_qty']) ? mysqli_real_escape_string($conn, $_GET['f_qty']) : '';
$f_amt = isset($_GET['f_amt']) ? mysqli_real_escape_string($conn, $_GET['f_amt']) : '';

if (!empty($f_gem)) $where_clauses[] = "g.gem_contact_no LIKE '%$f_gem%'";
if (!empty($f_cdate)) $where_clauses[] = "g.contract_date = '$f_cdate'";
if (!empty($f_ddate)) $where_clauses[] = "g.delivery_last_date = '$f_ddate'";
if (!empty($f_dept)) $where_clauses[] = "g.department_name LIKE '%$f_dept%'";
if (!empty($f_company)) $where_clauses[] = "g.company LIKE '%$f_company%'"; 
if (!empty($f_prod)) $where_clauses[] = "g.product LIKE '%$f_prod%'";
if (!empty($f_qty)) $where_clauses[] = "g.quantity = '$f_qty'";
if (!empty($f_amt)) $where_clauses[] = "g.amount = '$f_amt'";

// Building Final Query
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// UPDATED SQL
$sql = "SELECT g.*, u.name as creator_name,
        CASE 
            WHEN g.entity_type = 'lead' THEN (SELECT history_note FROM lead_history WHERE lead_id = g.entity_id AND history_note NOT LIKE '%<a %' AND history_note NOT LIKE '%@View%' ORDER BY id DESC LIMIT 1)
            WHEN g.entity_type = 'customer' THEN (SELECT history_note FROM customer_history WHERE customer_id = g.entity_id AND history_note NOT LIKE '%<a %' AND history_note NOT LIKE '%@View%' ORDER BY id DESC LIMIT 1)
        END as latest_remark,
        CASE 
            WHEN g.entity_type = 'lead' THEN (SELECT history_note FROM lead_history WHERE lead_id = g.entity_id AND (history_note LIKE '%<a %' OR history_note LIKE '%@View%') ORDER BY id DESC LIMIT 1)
            WHEN g.entity_type = 'customer' THEN (SELECT history_note FROM customer_history WHERE customer_id = g.entity_id AND (history_note LIKE '%<a %' OR history_note LIKE '%@View%') ORDER BY id DESC LIMIT 1)
        END as uploaded_docs_note,
        CASE 
            WHEN g.entity_type = 'lead' THEN (SELECT company_name FROM leads WHERE id = g.entity_id)
            WHEN g.entity_type = 'customer' THEN (SELECT company_name FROM customers WHERE id = g.entity_id)
        END as original_company_name
        FROM gov_sales_entries g 
        LEFT JOIN users u ON g.created_by = u.id 
        $where_sql 
        ORDER BY g.id DESC";

$result = mysqli_query($conn, $sql);
$total_sales = ($result) ? mysqli_num_rows($result) : 0;

function getFileViewerLink($filePath) {
    if(empty($filePath)) return "";
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if (in_array($ext, ['doc', 'docx'])) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $full_url = $protocol . $_SERVER['HTTP_HOST'] . "/" . ltrim($filePath, '/');
        return "https://docs.google.com/viewer?url=" . urlencode($full_url);
    }
    return htmlspecialchars($filePath);
}
function limit_text($text, $limit = 25) {
    return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '...' : $text;
}

$is_advanced_active = (!empty($f_gem) || !empty($f_cdate) || !empty($f_ddate) || !empty($f_dept) || !empty($f_company) || !empty($f_prod) || !empty($f_qty) || !empty($f_amt));
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
    :root {
        --primary: #0d6efd; 
        --primary-light: #e9ecef;
        --text-heading: #212529; 
        --text-body: #495057; 
        --text-muted: #6c757d; 
        --border-color: #dee2e6;
        --bg-main: #f4f6f9;
    }
    body { font-family: 'Inter', sans-serif; background-color: var(--bg-main); color: var(--text-body); }
    
    /* Corporate Card */
    .premium-card { background: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); border: 1px solid var(--border-color); margin-bottom: 20px; }
    
    /* Corporate Bordered Table */
    .table-responsive { overflow-x: auto; border-radius: 6px; }
    .table-corporate { width: 100%; border-collapse: collapse; margin-bottom: 0; background-color: #fff; }
    .table-corporate th { background-color: #f8f9fa; color: #495057; font-size: 13px; font-weight: 600; text-transform: uppercase; padding: 12px 10px; border: 1px solid var(--border-color); white-space: nowrap; text-align: left; vertical-align: middle; }
    .table-corporate td { padding: 10px 10px; font-size: 14px; border: 1px solid var(--border-color); vertical-align: middle; color: #333; }
    .table-corporate tbody tr:hover { background-color: #f8f9fa; }
    
    /* Badges & Buttons */
    .badge-corporate { display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
    .badge-lead { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .badge-cust { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    
    .action-btn { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 4px; font-size: 13px; font-weight: 500; text-decoration: none; border: 1px solid transparent; transition: 0.2s; }
    .btn-view-profile { color: #0d6efd; background-color: #e9ecef; border-color: #dee2e6; }
    .btn-view-profile:hover { background-color: #0d6efd; color: #fff; }
    
    .btn-doc { color: #055160; background-color: #cff4fc; border: 1px solid #b6effb; }
    .btn-bid { color: #41464b; background-color: #e2e3e5; border: 1px solid #d3d6d8; }

    /* Forms & Inputs */
    .form-control, .form-select { border-radius: 4px; font-size: 14px; padding: 8px 12px; border: 1px solid #ced4da; }
    .form-control:focus, .form-select:focus { box-shadow: none; border-color: #86b7fe; }
    
    /* Remarks & Docs Areas */
    .remarks-container { background-color: #fafbfc; border: 1px solid #e9ecef; border-radius: 4px; padding: 8px; font-size: 13px; max-height: 80px; overflow-y: auto; line-height: 1.4; min-width: 200px; max-width: 280px; white-space: normal; }
    .btn-add-remark { border: 1px solid #ced4da; background: #fff; color: #495057; transition: 0.2s; padding: 4px 8px; border-radius: 4px; font-size: 13px; cursor: pointer; }
    .btn-add-remark:hover { background: #e9ecef; }
    
    /* Clean Switch */
    .switch-container { display: flex; align-items: center; gap: 6px; justify-content: center; }
    .switch { position: relative; display: inline-block; width: 36px; height: 20px; margin: 0; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ced4da; transition: .3s; border-radius: 20px; }
    .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
    input:checked + .slider { background-color: #198754; }
    input:checked + .slider:before { transform: translateX(16px); }

    .doc-note-box { font-size: 12px; background: #f8f9fa; padding: 6px; border-radius: 4px; border: 1px solid #dee2e6; max-width: 200px; white-space: normal; word-wrap: break-word; line-height: 1.3; }
</style>

<div class="py-4">
    <div class="container-fluid" style="max-width: 1600px;"> 
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-3 gap-3">
            <div>
                <h3 class="fw-bold mb-1" style="color: var(--text-heading);">Government Sales Register</h3>
                <p class="text-muted mb-0" style="font-size: 14px;">Manage and track all your GeM contracts and government billings.</p>
            </div>
            <div class="d-flex align-items-center">
                <div style="background: #fff; border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 600;">
                    <i class="ti ti-chart-bar text-primary me-1"></i> Total Entries: <span class="text-primary ms-1"><?= $total_sales; ?></span>
                </div>
            </div>
        </div>

        <div class="premium-card p-3 mb-3">
            <form method="GET" action="gov-sales.php" class="m-0 w-100">
                <div class="row g-2 align-items-center">
                    <div class="col-auto">
                        <span class="fw-bold" style="font-size: 14px; color: #495057;"><i class="ti ti-filter me-1"></i> Search</span>
                    </div>
                    
                    <div class="col-md-auto ms-auto d-flex gap-2 flex-wrap">
                        <input type="text" name="search" class="form-control" placeholder="Universal Search..." value="<?= htmlspecialchars($search_query) ?>" style="width: 250px;">
                        
                        <?php if($role == 'admin'): ?>
                        <select name="staff_id" class="form-select" style="width: 200px;">
                            <option value="">-- All Employees --</option>
                            <?php
                            $users_sql = mysqli_query($conn, "SELECT id, name FROM users ORDER BY name ASC");
                            while($u_row = mysqli_fetch_assoc($users_sql)) {
                                $selected = ($filter_user == $u_row['id']) ? 'selected' : '';
                                echo "<option value='{$u_row['id']}' $selected>" . htmlspecialchars($u_row['name']) . "</option>";
                            }
                            ?>
                        </select>
                        <?php endif; ?>

                        <button type="button" class="btn btn-light border" onclick="toggleAdvancedFilters()" style="font-size: 14px; font-weight: 500;">
                            <i class="ti ti-adjustments-horizontal me-1"></i> Advanced
                        </button>
                        
                        <button type="submit" class="btn btn-primary" style="font-size: 14px; font-weight: 500;">Search</button>
                        
                        <?php if(!empty($filter_user) || !empty($search_query) || $is_advanced_active): ?>
                            <a href="gov-sales.php" class="btn btn-outline-danger" style="font-size: 14px; font-weight: 500;">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="advanced-filters" class="mt-3 p-3 rounded bg-light border <?= $is_advanced_active ? '' : 'd-none' ?>">
                    <h6 class="fw-bold mb-3" style="font-size: 13px; text-transform: uppercase;"><i class="ti ti-list-search me-1"></i> Advanced Filters</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 12px; font-weight: 600;">GeM Contact No</label>
                            <input type="text" name="f_gem" class="form-control form-control-sm" value="<?= htmlspecialchars($f_gem) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 12px; font-weight: 600;">Contract Date</label>
                            <input type="date" name="f_cdate" class="form-control form-control-sm" value="<?= htmlspecialchars($f_cdate) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 12px; font-weight: 600;">Delivery Date</label>
                            <input type="date" name="f_ddate" class="form-control form-control-sm" value="<?= htmlspecialchars($f_ddate) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 12px; font-weight: 600;">Department</label>
                            <input type="text" name="f_dept" class="form-control form-control-sm" value="<?= htmlspecialchars($f_dept) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 12px; font-weight: 600;">Company</label>
                            <input type="text" name="f_company" class="form-control form-control-sm" value="<?= htmlspecialchars($f_company) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 12px; font-weight: 600;">Product</label>
                            <input type="text" name="f_prod" class="form-control form-control-sm" value="<?= htmlspecialchars($f_prod) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 12px; font-weight: 600;">Quantity</label>
                            <input type="number" name="f_qty" class="form-control form-control-sm" value="<?= htmlspecialchars($f_qty) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 12px; font-weight: 600;">Amount (₹)</label>
                            <input type="number" step="0.01" name="f_amt" class="form-control form-control-sm" value="<?= htmlspecialchars($f_amt) ?>">
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="premium-card p-0">
            <div class="table-responsive">
                <table class="table-corporate">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">Origin</th>
                            <th>GeM Contact No</th>
                            <th>Contract Date</th>
                            <th>Delivery Date</th>
                            <th>Department</th>
                            <th>Lead/Cust Org</th>
                            <th>Company</th>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Amount (₹)</th>
                            <th style="min-width: 250px;">Remarks</th>
                            <th>Documents</th>
                            <th class="text-center" style="min-width: 150px;">Upload Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="text-center">
                                        <?php if($row['entity_type'] == 'lead'): ?>
                                            <span class="badge-corporate badge-lead mb-1">LEAD</span><br>
                                            <a href="lead-update.php?id=<?= $row['entity_id'] ?>" class="action-btn btn-view-profile">Profile</a>
                                        <?php else: ?>
                                            <span class="badge-corporate badge-cust mb-1">CUST</span><br>
                                            <a href="customer-details.php?id=<?= $row['entity_id'] ?>" class="action-btn btn-view-profile">Profile</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="fw-bold"><?= htmlspecialchars($row['gem_contact_no'] ?? 'N/A') ?></span></td>
                                    <td><?= !empty($row['contract_date']) ? date('d M Y', strtotime($row['contract_date'])) : 'N/A' ?></td>
                                    <td><span class="text-danger fw-bold"><?= !empty($row['delivery_last_date']) ? date('d M Y', strtotime($row['delivery_last_date'])) : 'N/A' ?></span></td>
                                    <td><?= htmlspecialchars(limit_text($row['department_name'] ?? 'N/A', 20)) ?></td>
                                    <td><?= htmlspecialchars(limit_text($row['original_company_name'] ?? 'N/A', 20)) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars(limit_text($row['company'] ?? 'N/A', 20)) ?></td>
                                    <td><?= htmlspecialchars(limit_text($row['product'] ?? 'N/A', 20)) ?></td>
                                    <td class="text-center fw-bold"><?= htmlspecialchars($row['quantity'] ?? '0') ?></td>
                                    <td class="text-end fw-bold text-success">₹<?= number_format($row['amount'] ?? 0, 2) ?></td>
                                    
                                    <td>
                                        <div class="d-flex align-items-start gap-2">
                                            <div class="flex-grow-1">
                                                <?php if(!empty($row['latest_remark'])): ?>
                                                    <div class="remarks-container"><?= $row['latest_remark'] ?></div>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size: 12px; font-style: italic;">No remarks</span>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="btn-add-remark add-remark-btn" 
                                                data-entity-type="<?= $row['entity_type'] ?>" 
                                                data-entity-id="<?= $row['entity_id'] ?>"
                                                data-contact-no="<?= htmlspecialchars($row['gem_contact_no']) ?>" title="Add Remark">
                                                <i class="ti ti-pencil"></i>
                                            </button>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <?php if(!empty($row['gem_contract_file'])): ?><a href="<?= getFileViewerLink($row['gem_contract_file']) ?>" target="_blank" class="action-btn btn-doc"><i class="ti ti-file-description"></i> GeM</a><?php endif; ?>
                                            <?php if(!empty($row['bid_file'])): ?><a href="<?= getFileViewerLink($row['bid_file']) ?>" target="_blank" class="action-btn btn-bid"><i class="ti ti-file-zip"></i> Bid</a><?php endif; ?>
                                            <?php if(empty($row['gem_contract_file']) && empty($row['bid_file'])): ?><span class="text-muted" style="font-size: 12px;">No files</span><?php endif; ?>
                                        </div>
                                    </td>
                                    
                                    <td class="text-center" style="vertical-align: top;">
                                        <?php $is_uploaded = (isset($row['doc_upload_status']) && $row['doc_upload_status'] == 'yes'); ?>
                                        <div class="switch-container mb-1">
                                            <span style="font-size: 12px; font-weight: 500; color: #6c757d;">No</span>
                                            <label class="switch">
                                                <input type="checkbox" class="upload-doc-toggle" 
                                                    data-row-id="<?= $row['id'] ?>"
                                                    data-entity-type="<?= $row['entity_type'] ?>" 
                                                    data-entity-id="<?= $row['entity_id'] ?>"
                                                    data-contact-no="<?= htmlspecialchars($row['gem_contact_no']) ?>"
                                                    <?= $is_uploaded ? 'checked' : '' ?>>
                                                <span class="slider"></span>
                                            </label>
                                            <span style="font-size: 12px; font-weight: 600; color: <?= $is_uploaded ? '#198754' : '#6c757d' ?>;">Yes</span>
                                        </div>
                                        
                                        <?php if(!empty($row['uploaded_docs_note'])): ?>
                                            <div class="mx-auto mt-2 doc-note-box">
                                                <?= $row['uploaded_docs_note'] ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="13" class="text-center py-4 text-muted">No Government Sales Found matching your filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Toggle Advanced Filters Function
function toggleAdvancedFilters() {
    let advancedDiv = document.getElementById('advanced-filters');
    if(advancedDiv.classList.contains('d-none')) {
        advancedDiv.classList.remove('d-none');
    } else {
        advancedDiv.classList.add('d-none');
    }
}

document.addEventListener("DOMContentLoaded", function() {
    
    // REMARK LOGIC
    document.querySelectorAll('.add-remark-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let entityType = this.getAttribute('data-entity-type');
            let entityId = this.getAttribute('data-entity-id');
            Swal.fire({
                title: 'Add New Remark', input: 'textarea', inputPlaceholder: 'Type your update here...', showCancelButton: true, confirmButtonText: 'Save Remark', confirmButtonColor: '#0d6efd',
                preConfirm: (text) => { if (!text.trim()) { Swal.showValidationMessage('Remark cannot be empty'); return false; } return text; }
            }).then((result) => {
                if (result.isConfirmed) {
                    let formData = new FormData();
                    formData.append('entity_type', entityType); formData.append('entity_id', entityId); formData.append('note', result.value);
                    Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
                    fetch('ajax_add_remark.php', { method: 'POST', body: formData })
                    .then(res => res.json()).then(data => {
                        if(data.status === 'success') { Swal.fire({ icon: 'success', title: 'Saved!', timer: 1500 }).then(() => window.location.reload()); } else { Swal.fire('Error', data.message, 'error'); }
                    });
                }
            });
        });
    });

    // UPLOAD & YES/NO TOGGLE LOGIC
    document.querySelectorAll('.upload-doc-toggle').forEach(toggle => {
        toggle.addEventListener('change', function(e) {
            let entityType = this.getAttribute('data-entity-type');
            let entityId = this.getAttribute('data-entity-id');
            let rowId = this.getAttribute('data-row-id');
            let contactNo = this.getAttribute('data-contact-no');
            let currentToggle = this;

            if(this.checked) {
                // Changing NO to YES (Upload Modal)
                Swal.fire({
                    title: 'Upload Documents',
                    html: `
                        <div id="pasteArea" style="border: 2px dashed #0d6efd; padding: 20px; border-radius: 6px; background: #f8f9fa; cursor: pointer;">
                            <i class="ti ti-files mb-2" style="font-size: 30px; color: #0d6efd;"></i><br>
                            Click here & <b>Ctrl+V</b> to Paste Files
                        </div>
                        <input type="file" id="docFileInput" class="form-control mt-3" accept=".zip,.pdf,.doc,.docx,.jpg,.jpeg,.png" multiple>
                        <textarea id="docNoteInput" class="form-control mt-3" rows="2" placeholder="Optional remark for these files..."></textarea>
                        <div id="filePreview" class="mt-2 text-start" style="font-size: 12px; max-height: 100px; overflow-y: auto;"></div>
                    `,
                    showCancelButton: true, confirmButtonText: 'Upload All', confirmButtonColor: '#0d6efd',
                    didOpen: () => {
                        const pasteArea = document.getElementById('pasteArea'); const fileInput = document.getElementById('docFileInput'); const preview = document.getElementById('filePreview'); let dt = new DataTransfer();
                        const updatePreview = () => { preview.innerHTML = ''; Array.from(fileInput.files).forEach(f => { preview.innerHTML += `<span class="badge bg-light text-dark border me-1 mb-1"><i class="ti ti-file"></i> ${f.name}</span>`; }); };
                        pasteArea.addEventListener('paste', (e) => {
                            if (e.clipboardData.files.length > 0) { Array.from(e.clipboardData.files).forEach(file => dt.items.add(file)); fileInput.files = dt.files; updatePreview(); pasteArea.style.background = "#e9ecef"; }
                        });
                        fileInput.addEventListener('change', updatePreview);
                    },
                    preConfirm: () => {
                        const files = document.getElementById('docFileInput').files;
                        if (files.length === 0) { Swal.showValidationMessage('Select or paste at least one file.'); return false; }
                        return { files: files, note: document.getElementById('docNoteInput').value };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        let formData = new FormData();
                        formData.append('row_id', rowId); 
                        formData.append('entity_type', entityType);
                        formData.append('entity_id', entityId);
                        formData.append('note', result.value.note);
                        Array.from(result.value.files).forEach(file => formData.append('document_files[]', file));

                        Swal.fire({ title: 'Uploading...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

                        fetch('ajax_upload_history_doc.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if(data.status === 'success') {
                                Swal.fire('Success!', data.message, 'success').then(() => window.location.reload());
                            } else {
                                Swal.fire('Error', data.message, 'error'); currentToggle.checked = false;
                            }
                        }).catch(err => { Swal.fire('Error', 'Network Error', 'error'); currentToggle.checked = false; });
                    } else { currentToggle.checked = false; }
                });

            } else {
                // Changing YES to NO (Revert / Change Status)
                Swal.fire({
                    title: 'Remove "Yes" Status?',
                    text: 'You are switching the document status to NO. This action will be logged in history under your name.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, change to NO',
                    confirmButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        let formData = new FormData();
                        formData.append('row_id', rowId);
                        formData.append('entity_type', entityType);
                        formData.append('entity_id', entityId);
                        formData.append('action', 'set_no');

                        fetch('ajax_toggle_doc_status.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if(data.status === 'success') {
                                Swal.fire('Updated', 'Status changed to NO successfully.', 'success').then(()=> window.location.reload());
                            } else {
                                Swal.fire('Error', data.message, 'error'); currentToggle.checked = true;
                            }
                        });
                    } else {
                        currentToggle.checked = true; // Keep it Yes if cancelled
                    }
                });
            }
        });
    });
});
</script>

<?php include __DIR__ . '/include/footer.php'; ?>