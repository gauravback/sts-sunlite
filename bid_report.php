<?php
// Session aur DB connection
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
include 'config/database.php'; 

// User Role nikalna taaki action buttons hide kar sakein
$user_role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';

// 1. FILTER VARIABLES CATCH KARNA
$f_bid_no = isset($_GET['f_bid_no']) ? trim($_GET['f_bid_no']) : '';
$f_org    = isset($_GET['f_org']) ? trim($_GET['f_org']) : '';
$f_dept   = isset($_GET['f_dept']) ? trim($_GET['f_dept']) : '';
$f_manage = isset($_GET['f_manage']) ? trim($_GET['f_manage']) : '';
$f_exec   = isset($_GET['f_exec']) ? trim($_GET['f_exec']) : '';

// Check agar koi bhi filter active hai (Clear button show karne ke liye)
$is_filtered = (!empty($f_bid_no) || !empty($f_org) || !empty($f_dept) || !empty($f_manage) || !empty($f_exec));
?>
<?php include 'include/header.php'; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* Modern UI/UX Styling */
    body { background-color: #f4f7fa; font-family: 'Inter', sans-serif; }
    .modern-card { border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03); background: #ffffff; }
    .btn-modern { border-radius: 10px; font-weight: 600; padding: 10px 20px; transition: all 0.3s ease; }
    
    /* Responsive Table Styling */
    .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 10px; }
    .table-modern { width: 100%; border-collapse: separate; border-spacing: 0 12px; margin-top: -10px; }
    
    /* Table Headers */
    .table-modern th { 
        background-color: transparent; 
        color: #64748b; 
        font-weight: 700; 
        text-transform: uppercase; 
        font-size: 0.75rem; 
        letter-spacing: 0.8px; 
        border-bottom: 2px solid #e2e8f0; 
        padding: 16px 20px; 
        white-space: nowrap; 
    }
    
    /* Table Rows */
    .table-modern tbody tr { 
        background-color: #ffffff; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.02); 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        border-radius: 12px; 
    }
    .table-modern tbody tr:hover { 
        transform: translateY(-3px); 
        box-shadow: 0 12px 20px rgba(0,0,0,0.06); 
        z-index: 1; 
        position: relative; 
    }
    
    /* Table Cells */
    .table-modern td { 
        padding: 18px 20px; 
        vertical-align: middle; 
        color: #334155; 
        font-size: 0.9rem; 
        border-top: 1px solid #f8fafc; 
        border-bottom: 1px solid #f8fafc; 
    }
    
    /* Single Line Text Truncation */
    .truncate-text {
        max-width: 220px; 
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block; 
    }

    /* Clickable Bid Link UI */
    .bid-link {
        color: #2563eb;
        text-decoration: none;
        transition: all 0.2s ease;
        display: inline-block;
        padding-bottom: 2px;
        border-bottom: 1px dashed transparent;
    }
    .bid-link:hover {
        color: #1d4ed8;
        border-bottom: 1px dashed #1d4ed8;
    }
    
    /* Table Rounded Corners */
    .table-modern td:first-child { border-left: 1px solid #f8fafc; border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
    .table-modern td:last-child { border-right: 1px solid #f8fafc; border-top-right-radius: 12px; border-bottom-right-radius: 12px; }

    /* Modern Color Accents */
    .row-won td:first-child { border-left: 4px solid #10b981 !important; }
    .row-lost td:first-child { border-left: 4px solid #ef4444 !important; }
    .row-ongoing td:first-child { border-left: 4px solid #f59e0b !important; }
    .row-cancel td:first-child { border-left: 4px solid #64748b !important; }

    /* Status Badges */
    .badge-modern { padding: 6px 14px; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.3px; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .badge-won { background-color: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
    .badge-lost { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .badge-ongoing { background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-cancel { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    /* Search Input */
    .search-input { border-radius: 10px; border: 1px solid #e2e8f0; padding: 10px 16px; font-size: 0.9rem; background: #f8fafc; transition: all 0.3s; }
    .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); outline: none; background: #fff; }
</style>

<div class="container-fluid mt-4 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <div>
            <h3 class="fw-bold text-dark mb-1" style="letter-spacing: -0.5px;">Bid Report & List</h3>
            <p class="text-muted small mb-0">Manage, track, and analyze your bid submissions.</p>
        </div>
        <a href="add-bid.php" class="btn btn-primary btn-modern shadow-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border: none;">
            <i class="fas fa-plus me-2"></i> Add New Bid
        </a>
    </div>

    <div class="card modern-card mb-4 border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-secondary fw-bold mb-0"><i class="fas fa-sliders-h me-2 text-primary"></i> Filter Records</h6>
                <?php if($is_filtered): ?>
                    <a href="bid_report.php" class="btn btn-sm btn-light border text-danger" style="border-radius: 8px;">
                        <i class="fas fa-times me-1"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
            <form method="GET" action="bid_report.php">
                <div class="row g-3">
                    <div class="col-md-2"><input type="text" name="f_bid_no" value="<?= htmlspecialchars($f_bid_no) ?>" class="form-control search-input" placeholder="BID Number"></div>
                    <div class="col-md-2"><input type="text" name="f_org" value="<?= htmlspecialchars($f_org) ?>" class="form-control search-input" placeholder="Organisation Name"></div>
                    <div class="col-md-2"><input type="text" name="f_dept" value="<?= htmlspecialchars($f_dept) ?>" class="form-control search-input" placeholder="Department Name"></div>
                    <div class="col-md-2"><input type="text" name="f_manage" value="<?= htmlspecialchars($f_manage) ?>" class="form-control search-input" placeholder="Manager"></div>
                    <div class="col-md-2"><input type="text" name="f_exec" value="<?= htmlspecialchars($f_exec) ?>" class="form-control search-input" placeholder="Executive"></div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 btn-modern" style="background-color: #3b82f6; border: none;"><i class="fas fa-search me-1"></i> Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card modern-card border-0 shadow-sm">
        <div class="card-body p-4">
            
            <div class="d-flex flex-wrap gap-4 mb-4 pb-3 border-bottom" style="font-size: 0.8rem; font-weight: 600; color: #64748b;">
                <div class="d-flex align-items-center"><span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background: #10b981; box-shadow: 0 0 0 3px #d1fae5;"></span> WON / QUALIFIED</div>
                <div class="d-flex align-items-center"><span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background: #ef4444; box-shadow: 0 0 0 3px #fee2e2;"></span> LOST / DIS-QUALIFIED</div>
                <div class="d-flex align-items-center"><span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background: #f59e0b; box-shadow: 0 0 0 3px #fef3c7;"></span> ONGOING</div>
                <div class="d-flex align-items-center"><span class="d-inline-block rounded-circle me-2" style="width: 12px; height: 12px; background: #64748b; box-shadow: 0 0 0 3px #f1f5f9;"></span> CANCELLED</div>
            </div>

            <div class="table-responsive px-1 pb-2">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th style="min-width: 160px;">BID Details</th>
                            <th style="min-width: 220px;">Organisation</th>
                            <th style="min-width: 200px;">Department</th>
                            <th style="min-width: 180px;">Item Category</th>
                            <th style="min-width: 130px;">Last Date</th>
                            <th style="min-width: 160px;" class="text-center">Status</th>
                            <th class="text-center" style="min-width: 140px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // 2. DYNAMIC SQL QUERY FOR FILTERS
                        $where_conditions = ["1=1"]; // Base condition taaki AND lagana aasan ho

                        if (!empty($f_bid_no)) {
                            $where_conditions[] = "bid_no LIKE '%" . mysqli_real_escape_string($conn, $f_bid_no) . "%'";
                        }
                        if (!empty($f_org)) {
                            $where_conditions[] = "org_name LIKE '%" . mysqli_real_escape_string($conn, $f_org) . "%'";
                        }
                        if (!empty($f_dept)) {
                            $where_conditions[] = "dept_name LIKE '%" . mysqli_real_escape_string($conn, $f_dept) . "%'";
                        }
                        if (!empty($f_manage)) {
                            $where_conditions[] = "managed_by LIKE '%" . mysqli_real_escape_string($conn, $f_manage) . "%'";
                        }
                        if (!empty($f_exec)) {
                            $where_conditions[] = "sale_employee LIKE '%" . mysqli_real_escape_string($conn, $f_exec) . "%'";
                        }

                        $where_clause = implode(" AND ", $where_conditions);
                        $sql = "SELECT * FROM bids WHERE $where_clause ORDER BY id DESC";
                        
                        $result = mysqli_query($conn, $sql);

                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                
                                // Status & Row UI Logic
                                $status = strtolower($row['bid_status']);
                                $row_class = 'row-ongoing'; 
                                $badge_class = 'badge-ongoing';
                                
                                if (strpos($status, 'won') !== false || strpos($status, 'qualified') !== false || strpos($status, 'received') !== false) {
                                    $row_class = 'row-won';
                                    $badge_class = 'badge-won';
                                } elseif (strpos($status, 'lost') !== false || strpos($status, 'dis-qualified') !== false) {
                                    $row_class = 'row-lost';
                                    $badge_class = 'badge-lost';
                                } elseif (strpos($status, 'cancel') !== false) {
                                    $row_class = 'row-cancel';
                                    $badge_class = 'badge-cancel';
                                }

                                $last_date = (!empty($row['end_date'])) ? date('d M Y', strtotime($row['end_date'])) : '<span class="text-muted">-</span>';
                                
                                $org_name = htmlspecialchars($row['org_name'] ?: '-');
                                $dept_name = htmlspecialchars($row['dept_name'] ?: '-');
                                $item_cat = htmlspecialchars($row['item_category'] ?: '-');
                                
                                $bid_no = htmlspecialchars($row['bid_no']);
                                $bid_type = htmlspecialchars($row['bid_type'] ?: 'Standard');

                                // Buttons Logic
                                $action_buttons = "<div class='d-flex justify-content-center gap-2'>
                                    <a href='view_bid.php?id={$row['id']}' class='btn btn-sm btn-light border shadow-sm text-primary transition-all' title='View Details' style='width: 36px; height: 36px; display:flex; align-items:center; justify-content:center; border-radius:10px;'>
                                        <i class='fas fa-eye'></i>
                                    </a>";

                                if ($user_role === 'admin') {
                                    $action_buttons .= "
                                        <a href='edit_bid.php?id={$row['id']}' class='btn btn-sm btn-light border shadow-sm text-success transition-all' title='Edit Bid' style='width: 36px; height: 36px; display:flex; align-items:center; justify-content:center; border-radius:10px;'>
                                            <i class='fas fa-pen'></i>
                                        </a>
                                        <a href='delete_bid.php?id={$row['id']}' class='btn btn-sm btn-light border shadow-sm text-danger transition-all' title='Delete Bid' onclick=\"return confirm('Are you sure you want to delete this bid completely?');\" style='width: 36px; height: 36px; display:flex; align-items:center; justify-content:center; border-radius:10px;'>
                                            <i class='fas fa-trash'></i>
                                        </a>";
                                }

                                $action_buttons .= "</div>";

                                // HTML Render 
                                echo "<tr class='{$row_class}'>
                                        <td>
                                            <a href='view_bid.php?id={$row['id']}' class='fw-bold mb-1 truncate-text bid-link' title='View Bid Details' style='max-width: 150px; font-size: 1rem;'>{$bid_no}</a>
                                            <div class='text-muted truncate-text' style='font-size: 0.75rem; max-width: 150px; font-weight: 500;' title='{$bid_type}'><i class='fas fa-tag me-1 opacity-50'></i>{$bid_type}</div>
                                        </td>
                                        <td>
                                            <div class='fw-bold text-dark truncate-text' title='{$org_name}'>{$org_name}</div>
                                        </td>
                                        <td>
                                            <div class='text-secondary truncate-text fw-medium' style='max-width: 180px;' title='{$dept_name}'>{$dept_name}</div>
                                        </td>
                                        <td>
                                            <div class='text-secondary truncate-text' style='max-width: 160px;' title='{$item_cat}'>{$item_cat}</div>
                                        </td>
                                        <td>
                                            <div class='fw-medium text-dark' style='white-space: nowrap;'>{$last_date}</div>
                                        </td>
                                        <td class='text-center'>
                                            <span class='badge badge-modern rounded-pill {$badge_class}'>".htmlspecialchars($row['bid_status'] ?: 'Pending')."</span>
                                        </td>
                                        <td class='text-center'>
                                            {$action_buttons}
                                        </td>
                                      </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-5 text-muted border-0'>
                                    <div class='p-5 bg-light d-inline-block border' style='border-radius: 20px; border-style: dashed !important;'>
                                        <i class='fas fa-search mb-3 text-primary opacity-50' style='font-size: 3rem;'></i><br>
                                        <span class='fw-bold text-dark fs-5'>No Matches Found</span> <br>
                                        <p class='text-muted small mt-1'>We couldn't find any bids matching your filters.</p>
                                        <a href='bid_report.php' class='btn btn-outline-primary mt-3 px-4 rounded-pill shadow-sm'>Clear Filters</a>
                                    </div>
                                  </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>