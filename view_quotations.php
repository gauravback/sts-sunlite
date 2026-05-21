<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config/database.php';

// ==========================================
// DELETE QUOTATION LOGIC (ONLY FOR ADMIN)
// ==========================================
// Yahan hum ensure kar rahe hain ki URL hack karke koi delete na kar sake, 
// jab tak uska role admin na ho.
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; 
// NOTE: Agar tumhara session variable alag hai (jaise $_SESSION['user_role']), toh upar wali line me 'role' ko usse replace kar dena.

if (isset($_GET['delete']) && is_numeric($_GET['delete']) && $isAdmin) {
    $del_id = (int)$_GET['delete'];
    
    // Pehle us quotation ke saare items delete karo
    mysqli_query($conn, "DELETE FROM quotation_items WHERE quotation_id = $del_id");
    // Phir main quotation delete karo
    mysqli_query($conn, "DELETE FROM quotations WHERE id = $del_id");
    
    echo "<script>alert('Quotation Deleted Successfully!'); window.location.href='view_quotations.php';</script>";
    exit();
} elseif (isset($_GET['delete']) && !$isAdmin) {
    echo "<script>alert('Unauthorized! Only Admins can delete quotations.'); window.location.href='view_quotations.php';</script>";
    exit();
}

// Search logic
$search = "";
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

// Fetch Quotations dynamically from BOTH Customers and Leads tables
$query = "SELECT q.*, 
                 COALESCE(c.company_name, l.company_name) AS company_name,
                 COALESCE(c.customer_name, l.contact_person) AS person_name
          FROM quotations q 
          LEFT JOIN customers c ON q.company_type = 'Customer' AND q.company_id = c.id
          LEFT JOIN leads l ON q.company_type = 'Lead' AND q.company_id = l.id";

if (!empty($search)) {
    $query .= " WHERE q.quotation_no LIKE '%$search%' 
                OR c.company_name LIKE '%$search%' 
                OR l.company_name LIKE '%$search%'";
}

$query .= " ORDER BY q.id DESC";
$result = mysqli_query($conn, $query);
?>

<?php include 'include/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    body { background-color: #f1f5f9; }
    .list-card { border-radius: 15px; border: none; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: #fff; }
    .table thead th { background-color: #f8fafc; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; color: #64748b; border-bottom: 2px solid #edf2f7; padding: 15px; }
    .table tbody td { padding: 15px; vertical-align: middle; font-size: 0.9rem; color: #334155; }
    
    /* Hover highlight for rows */
    .table-hover tbody tr:hover { background-color: #f8fafc; }

    /* Icon only buttons (Edit, Delete) */
    .btn-action { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.3s; margin-right: 5px; }
    
    /* Text + Icon buttons (QTN, PI) */
    .btn-text-action { height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.3s; margin-right: 5px; font-size: 0.85rem; padding: 0 12px; font-weight: 600; }
    
    .btn-action:hover, .btn-text-action:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    
    /* Truncate CSS for long text */
    .truncate-text {
        max-width: 250px; /* Isse zyada lamba text aate hi ... ban jayega */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
    }
</style>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 px-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">Quotation & PI List</h3>
            <p class="text-muted small">Manage and view all generated Quotations and Proforma Invoices</p>
        </div>
        <div>
            <a href="create_quotation.php" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                <i class="fas fa-plus me-2"></i> Create New
            </a>
        </div>
    </div>

    <div class="card list-card mb-4">
        <div class="card-body">
            <form method="GET" action="">
                <div class="row g-3">
                    <div class="col-md-10">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 shadow-none" placeholder="Search by Quotation No or Company Name..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark w-100 fw-semibold">Filter</button>
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
                            <th>QTN No.</th>
                            <th>Date</th>
                            <th>Company Type</th>
                            <th>Company / Client</th>
                            <th>Grand Total</th>
                            <th>Created By</th>
                            <th class="text-center" style="min-width: 220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result && mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): 
                                $compName = htmlspecialchars($row['company_name'] ?? 'Unknown Company');
                                $persName = htmlspecialchars($row['person_name'] ?? 'N/A');
                            ?>
                            <tr>
                                <td class="fw-bold text-primary"><?= htmlspecialchars($row['quotation_no']) ?></td>
                                <td><?= date('d M Y', strtotime($row['quotation_date'])) ?></td>
                                <td>
                                    <?php if($row['company_type'] == 'Lead'): ?>
                                        <span class="badge bg-warning text-dark border">Lead</span>
                                    <?php else: ?>
                                        <span class="badge bg-success border">Customer</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td style="max-width: 250px;">
                                    <div class="fw-bold text-dark truncate-text" title="<?= $compName ?>">
                                        <?= $compName ?>
                                    </div>
                                    <div class="text-muted small truncate-text" title="<?= $persName ?>">
                                        <?= $persName ?>
                                    </div>
                                </td>
                                
                                <td class="fw-bold text-success">₹<?= number_format($row['grand_total'], 2) ?></td>
                                <td><span class="badge bg-light text-dark border shadow-sm"><?= htmlspecialchars($row['created_by']) ?></span></td>
                                <td class="text-center text-nowrap">
                                    
                                    <a href="print_quotation.php?id=<?= $row['id'] ?>" target="_blank" class="btn-text-action btn btn-outline-info" title="View Quotation (PDF)">
                                        <i class="fas fa-file-pdf me-2"></i> QTN
                                    </a>
                                    
                                    <a href="print_quotation.php?id=<?= $row['id'] ?>&type=pi" target="_blank" class="btn-text-action btn btn-outline-success" title="Generate PI (PDF)">
                                        <i class="fas fa-file-invoice-dollar me-2"></i> PI
                                    </a>
                                    
                                    <a href="edit_quotation.php?id=<?= $row['id'] ?>" class="btn-action btn btn-outline-primary" title="Edit Data">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if ($isAdmin): ?>
                                    <a href="view_quotations.php?delete=<?= $row['id'] ?>" class="btn-action btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to permanently delete this record?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fs-2 mb-3 d-block opacity-25"></i>
                                    No records found.
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