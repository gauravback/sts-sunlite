<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
include __DIR__ . '/include/header.php';

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$name    = $_SESSION['name']; // User ka naam session se liya

// Filters aur Search inputs ko handle karna
$filter_staff = isset($_GET['staff_name']) ? mysqli_real_escape_string($conn, trim($_GET['staff_name'])) : '';
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

// ==========================================
// DATA FETCHING LOGIC
// ==========================================
$where_conditions = [];

// 1. Role based condition
if($role == 'admin') {
    if(!empty($filter_staff)) {
        $where_conditions[] = "followed_by = '$filter_staff'"; 
    }
} else {
    $where_conditions[] = "followed_by = '$name'"; 
}

// 2. Search box condition
if(!empty($search_query)) {
    // Multiple columns mein search
    $where_conditions[] = "(
        company_name LIKE '%$search_query%' OR 
        customer_name LIKE '%$search_query%' OR 
        email LIKE '%$search_query%' OR 
        contact_no LIKE '%$search_query%'
    )";
}

// WHERE clause banana
$where_sql = "";
if(count($where_conditions) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Final Query execute karna
$sql = "SELECT * FROM customers $where_sql ORDER BY id DESC";
$result = mysqli_query($conn, $sql);

// Total records ka count nikalna
$total_customers = ($result) ? mysqli_num_rows($result) : 0;
// ==========================================

?>

<div class="">
    <div class="content-modern">
        
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h3 class="fw-bold mb-1" style="color: var(--text-main);">My Customers</h3>
                <p class="text-muted mb-0">Search and manage your customer list.</p>
            </div>
            <a href="user-dashboard.php" class="btn btn-secondary btn-sm">
                <i class="ti ti-arrow-left"></i> Back to Profile
            </a>
        </div>

        <div class="card-modern p-3 mb-4 bg-white shadow-sm rounded">
            <form method="GET" action="my-customers.php" class="row g-2 align-items-center">
                
                <div class="col-md-4 col-12">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, company, email, phone..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                </div>

                <?php if($role == 'admin'): ?>
                <div class="col-md-4 col-12">
                    <select name="staff_name" class="form-select form-select-sm">
                        <option value="">-- All Employees --</option>
                        <?php
                        $users_sql = mysqli_query($conn, "SELECT id, name FROM users");
                        if($users_sql) {
                            while($user_row = mysqli_fetch_assoc($users_sql)) {
                                $selected = ($filter_staff == $user_row['name']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($user_row['name']) . "' $selected>" . htmlspecialchars($user_row['name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-md-4 col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3">Search</button>
                    <?php if(!empty($search_query) || !empty($filter_staff)): ?>
                        <a href="my-customers.php" class="btn btn-danger btn-sm px-3">Clear</a>
                    <?php endif; ?>
                </div>

            </form>
        </div>
        
        <div class="card-modern p-4 bg-white shadow-sm rounded">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0 text-dark">Customer Records</h5>
                <div class="bg-success text-white rounded-pill shadow-sm d-flex align-items-center" style="padding: 8px 20px; font-size: 15px; font-weight: 600;">
                    <i class="ti ti-users me-2" style="font-size: 20px;"></i> 
                    Total Customers: 
                    <span class="badge bg-white text-success ms-2  rounded-pill" style="padding: 5px 10px;">
                        <?= $total_customers; ?>
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Company Name</th>
                            <th>Customer Name</th>
                            <th>Contact No</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Table render karna
                        if($result && mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                
                                // Hyperlink for details page
                                echo "<td>
                                        <a href='customer-details.php?id=" . $row['id'] . "' class='text-primary fw-bold text-decoration-none'>
                                            " . htmlspecialchars($row['company_name'] ?? 'N/A') . "
                                        </a>
                                      </td>";
                                
                                echo "<td>" . htmlspecialchars($row['customer_name'] ?? 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($row['contact_no'] ?? 'N/A') . "</td>";
                                
                                // Status badge
                                $status = htmlspecialchars($row['status'] ?? 'N/A');
                                echo "<td><span class='badge bg-info'>" . $status . "</span></td>";
                                
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center text-muted py-4'>No matching customers found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/include/footer.php'; ?>