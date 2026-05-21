<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config/database.php'; // Tumhara DB connection file

// ==========================================
// DELETE VENDOR LOGIC (ONLY FOR ADMIN)
// ==========================================
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; 

if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $isAdmin) {
    $del_id = (int)$_GET['delete'];
    
    // Vendor ko delete karna
    $delete_query = "DELETE FROM vendors WHERE id = $del_id";
    if(mysqli_query($conn, $delete_query)){
        echo "<script>alert('Vendor Deleted Successfully!'); window.location.href='view_vendors.php';</script>";
    } else {
        echo "<script>alert('Error deleting vendor.'); window.location.href='view_vendors.php';</script>";
    }
    exit();
} elseif (isset($_GET['delete']) && !$isAdmin) {
    echo "<script>alert('Unauthorized! Only Admins can delete vendors.'); window.location.href='view_vendors.php';</script>";
    exit();
}

// Fetch All Vendors (Live search JS handle karega, isliye backend search nikal diya)
$query = "SELECT * FROM vendors ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>

<?php include 'include/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    body { background-color: #f4f7f9; }
    .list-card { border-radius: 12px; border: none; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04); background: #fff; overflow: hidden; }
    
    /* Table Styling */
    .table-bordered { border: 1px solid #e2e8f0; }
    .table-bordered th, .table-bordered td { border-color: #e2e8f0; }
    .table thead th { background-color: #f8fafc; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; color: #64748b; padding: 12px 15px; white-space: nowrap; border-bottom: 2px solid #e2e8f0 !important; }
    .table tbody td { padding: 12px 15px; vertical-align: middle; font-size: 0.85rem; color: #334155; }
    
    /* Hover highlight for rows */
    .table-hover tbody tr:hover { background-color: #f1f5f9; }

    /* Action Buttons */
    .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.3s; margin-right: 5px; font-size: 0.85rem; }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    
    /* Truncate CSS for long text */
    .truncate-text {
        max-width: 180px; 
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }
</style>

<div class="container-fluid mt-4 mb-5">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 px-2 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Vendor List</h3>
            <p class="text-muted small mb-0">Manage all your suppliers and vendors</p>
        </div>
        <div>
            <a href="vendor_register.php" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
                <i class="fas fa-plus me-2"></i> Add Vendor
            </a>
        </div>
    </div>

    <div class="card list-card mb-4">
        <div class="card-body p-3">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" id="liveSearchInput" class="form-control border-start-0 shadow-none py-2" placeholder="Start typing to instantly search Vendor Name, GSTIN, Email, Phone or PAN...">
            </div>
        </div>
    </div>

    <div class="card list-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 align-middle" id="vendorTable">
                    <thead>
                        <tr>
                            <th class="text-center">S.No.</th>
                            <th>Vendor Name</th>
                            <th>Address</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>GSTIN</th>
                            <th>PAN</th>
                            <th class="text-center">Status</th>
                            <th>Created On</th>
                            <th class="text-center" style="min-width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && mysqli_num_rows($result) > 0): ?>
                            <?php 
                                $sr_no = 1; 
                                while($row = mysqli_fetch_assoc($result)): 
                                $vendorName = htmlspecialchars($row['vendor_name']);
                                $address = htmlspecialchars($row['address']);
                            ?>
                            <tr class="searchable-row">
                                <td class="text-center fw-bold text-muted"><?= $sr_no++; ?></td>
                                
                                <td>
                                    <div class="fw-bold text-primary truncate-text" title="<?= $vendorName ?>">
                                        <?= $vendorName ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="text-muted truncate-text" title="<?= $address ?>">
                                        <?= $address ?>
                                    </div>
                                </td>
                                
                                <td><?= htmlspecialchars($row['email']) ?: '<span class="text-muted">N/A</span>' ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($row['phone']) ?: '<span class="text-muted">N/A</span>' ?></td>
                                <td class="fw-bold text-dark text-nowrap"><?= htmlspecialchars($row['gstin']) ?></td>
                                <td class="text-nowrap"><?= htmlspecialchars($row['pan']) ?></td>

                                <td class="text-center">
                                    <?php if($row['status'] == 'Active'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-muted text-nowrap"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                
                                <td class="text-center text-nowrap">
                                    <a href="edit_vendor.php?id=<?= $row['id'] ?>" class="btn-action btn btn-outline-primary" title="Edit Vendor">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if ($isAdmin): ?>
                                    <a href="view_vendors.php?delete=<?= $row['id'] ?>" class="btn-action btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to permanently delete this vendor?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr id="noDataRow">
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fas fa-users-slash fs-2 mb-3 d-block opacity-25"></i>
                                    No vendors found. Please add a new vendor.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr id="noMatchRow" style="display: none;">
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="fas fa-search fs-2 mb-3 d-block opacity-25"></i>
                                No matching records found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('liveSearchInput');
    const rows = document.querySelectorAll('.searchable-row');
    const noMatchRow = document.getElementById('noMatchRow');

    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        let matchFound = false;

        rows.forEach(row => {
            const rowText = row.innerText.toLowerCase();
            
            if (rowText.includes(filter)) {
                row.style.display = '';
                matchFound = true;
            } else {
                row.style.display = 'none';
            }
        });

        // Agar form me data hai par filter se kuch nahi mila, toh 'No Match' dikhao
        if (!matchFound && rows.length > 0) {
            noMatchRow.style.display = '';
        } else {
            noMatchRow.style.display = 'none';
        }
    });
});
</script>

<?php include 'include/footer.php'; ?>