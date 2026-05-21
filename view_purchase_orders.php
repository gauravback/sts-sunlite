<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config/database.php';

// ==========================================
// DELETE PO LOGIC (ONLY FOR ADMIN)
// ==========================================
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; 

if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $isAdmin) {
    $del_id = (int)$_GET['delete'];
    
    // Pehle us PO ke saare items delete karo
    mysqli_query($conn, "DELETE FROM po_items WHERE po_id = $del_id");
    // Phir main PO delete karo
    if(mysqli_query($conn, "DELETE FROM purchase_orders WHERE id = $del_id")){
        echo "<script>alert('Purchase Order Deleted Successfully!'); window.location.href='view_purchase_orders.php';</script>";
    } else {
        echo "<script>alert('Error deleting PO.'); window.location.href='view_purchase_orders.php';</script>";
    }
    exit();
} elseif (isset($_GET['delete']) && !$isAdmin) {
    echo "<script>alert('Unauthorized! Only Admins can delete.'); window.location.href='view_purchase_orders.php';</script>";
    exit();
}

$search = "";
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

// Fetch POs with Vendor and Issuer Names
$query = "SELECT po.*, v.vendor_name, i.company_name AS issuer_name 
          FROM purchase_orders po 
          LEFT JOIN vendors v ON po.vendor_id = v.id 
          LEFT JOIN po_issuer_companies i ON po.issuer_id = i.id";

if (!empty($search)) {
    $query .= " WHERE po.po_number LIKE '%$search%' 
                OR v.vendor_name LIKE '%$search%' 
                OR i.company_name LIKE '%$search%'";
}

$query .= " ORDER BY po.id DESC";
$result = mysqli_query($conn, $query);
?>

<?php include 'include/header.php'; ?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
    
    /* Card & Container */
    .page-title { font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
    .list-card { border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03); background: #fff; overflow: hidden; }
    
    /* Search Bar */
    .search-wrapper { background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; display: flex; align-items: center; overflow: hidden; transition: 0.3s; }
    .search-wrapper:focus-within { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
    .search-input { border: none; box-shadow: none; padding: 12px 15px; font-size: 0.95rem; width: 100%; outline: none; }
    .search-icon { padding-left: 15px; color: #94a3b8; }
    .btn-filter { background: #0f172a; color: white; font-weight: 600; padding: 10px 25px; border-radius: 8px; transition: 0.3s; border: none; }
    .btn-filter:hover { background: #1e293b; transform: translateY(-1px); }

    /* Table Styling */
    .table { margin-bottom: 0; }
    .table thead th { background-color: #f1f5f9; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; color: #475569; border-bottom: 1px solid #e2e8f0; padding: 16px 20px; font-weight: 600; white-space: nowrap; }
    .table tbody td { padding: 16px 20px; vertical-align: middle; font-size: 0.9rem; color: #334155; border-bottom: 1px solid #f1f5f9; }
    .table-hover tbody tr:hover { background-color: #f8fafc; transition: 0.2s ease-in-out; }

    /* Badges & Text */
    .po-badge { background-color: #eff6ff; color: #2563eb; font-weight: 700; padding: 5px 10px; border-radius: 6px; font-size: 0.85rem; border: 1px solid #bfdbfe; display: inline-block; margin-bottom: 4px; }
    .truncate-text { max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; font-weight: 500; color: #0f172a; }

    /* Modern Soft Action Buttons */
    .action-group { display: flex; gap: 8px; justify-content: center; }
    .btn-soft { height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; font-size: 0.85rem; font-weight: 600; text-decoration: none; border: none; padding: 0 12px; }
    .btn-soft i { font-size: 0.95rem; }
    
    .btn-pdf { background-color: #ecfdf5; color: #059669; }
    .btn-pdf:hover { background-color: #d1fae5; color: #047857; transform: translateY(-2px); }
    
    .btn-edit { background-color: #eff6ff; color: #2563eb; width: 36px; padding: 0; }
    .btn-edit:hover { background-color: #dbeafe; color: #1d4ed8; transform: translateY(-2px); }
    
    .btn-delete { background-color: #fef2f2; color: #dc2626; width: 36px; padding: 0; }
    .btn-delete:hover { background-color: #fee2e2; color: #b91c1c; transform: translateY(-2px); }
</style>

<div class="container-fluid mt-4 mb-5 px-md-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="page-title mb-1">Purchase Orders</h3>
            <p class="text-muted small mb-0">Manage and view all your generated POs</p>
        </div>
        <div>
            <a href="create_purchase_order.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" style="background-color: #2563eb; border: none;">
                <i class="fas fa-plus me-2"></i> Create New PO
            </a>
        </div>
    </div>

    <div class="card list-card mb-4 border-0 p-1">
        <div class="card-body p-3">
            <form method="GET" action="">
                <div class="row g-3 align-items-center">
                    <div class="col-md-10">
                        <div class="search-wrapper">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="search-input" placeholder="Search by PO Number, Vendor or Issuer Company..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn-filter w-100 h-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card list-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" width="5%">#</th>
                            <th width="20%">PO Details</th>
                            <th width="25%">Issued By (Our Company)</th>
                            <th width="25%">Issued To (Vendor)</th>
                            <th class="text-end" width="10%">Grand Total</th>
                            <th class="text-center" width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && mysqli_num_rows($result) > 0): ?>
                            <?php $sr_no = 1; while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="text-center font-weight-bold text-muted"><?= sprintf("%02d", $sr_no++); ?></td>
                                
                                <td>
                                    <span class="po-badge"><?= htmlspecialchars($row['po_number']) ?></span>
                                    <div class="small text-muted mt-1"><i class="far fa-calendar-alt me-1"></i> <?= date('d M, Y', strtotime($row['po_date'])) ?></div>
                                </td>
                                
                                <td>
                                    <?php if($row['issuer_name']): ?>
                                        <div class="truncate-text" title="<?= htmlspecialchars($row['issuer_name']) ?>">
                                            <?= htmlspecialchars($row['issuer_name']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge bg-light text-danger border border-danger">Not Selected</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <div class="truncate-text" title="<?= htmlspecialchars($row['vendor_name']) ?>">
                                        <i class="fas fa-building text-muted me-1 small"></i> <?= htmlspecialchars($row['vendor_name']) ?>
                                    </div>
                                </td>
                                
                                <td class="text-end fw-bold" style="color: #0f172a;">
                                    ₹ <?= number_format($row['grand_total'], 2) ?>
                                </td>
                                
                                <td>
                                    <div class="action-group">
                                        <a href="print_po.php?id=<?= $row['id'] ?>" target="_blank" class="btn-soft btn-pdf" title="View / Print PDF">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </a>
                                        <a href="edit_purchase_order.php?id=<?= $row['id'] ?>" class="btn-soft btn-edit" title="Edit PO">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <?php if ($isAdmin): ?>
                                        <a href="view_purchase_orders.php?delete=<?= $row['id'] ?>" class="btn-soft btn-delete" title="Delete PO" onclick="return confirm('Are you sure you want to permanently delete this Purchase Order?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fs-1 mb-3 opacity-25 d-block"></i>
                                        <span class="fw-semibold">No Purchase Orders found.</span>
                                        <p class="small mt-1">Try adjusting your search or create a new PO.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>