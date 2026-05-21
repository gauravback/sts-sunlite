<?php
// 1. Include your database connection file (verify the path)
// include 'include/db.php'; 

// 2. Include header file
include 'include/header.php'; 
?>

<div class="content-wrapper p-4">
    <div class="container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-0">UOM Directory</h3>
                <p class="text-muted small mb-0">Manage all your units of measurement here</p>
            </div>
            <a href="uom-add.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
                + Add New UOM
            </a>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4 py-3 border-bottom-0">ID</th>
                                <th class="border-bottom-0">UOM Name</th>
                                <th class="border-bottom-0">UOM Code</th>
                                <th class="border-bottom-0">Status</th>
                                <th class="pe-4 text-end border-bottom-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php
                            // Fetch Query
                            $sql = "SELECT * FROM uom_master ORDER BY id DESC";
                            $result = mysqli_query($conn, $sql);

                            if(mysqli_num_rows($result) > 0){
                                while($row = mysqli_fetch_assoc($result)){
                                    
                                    // Modern Badges for Status
                                    if($row['status'] == 1) {
                                        $statusBadge = '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">Active</span>';
                                    } else {
                                        $statusBadge = '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-2">Inactive</span>';
                                    }
                                    
                                    // Professional ID formatting (e.g., #0001)
                                    $display_id = "#" . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                                    ?>
                                    <tr>
                                        <td class="ps-4 text-muted small"><?php echo $display_id; ?></td>
                                        <td class="fw-bold text-dark"><?php echo $row['uom_name']; ?></td>
                                        <td>
                                            <span class="bg-light px-2 py-1 rounded text-secondary fw-semibold border border-light-subtle">
                                                <?php echo $row['uom_code']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $statusBadge; ?></td>
                                        <td class="pe-4 text-end">
                                            <a href="uom-edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold">Edit</a>
                                            <a href="uom-delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 ms-1 fw-semibold" onclick="return confirm('Are you sure you want to delete this? This action cannot be undone.');">Delete</a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                // Empty State Design in English
                                echo "<tr><td colspan='5' class='text-center py-5'>
                                        <div class='text-muted mb-3'>No UOM records found.</div>
                                        <a href='uom-add.php' class='btn btn-sm btn-primary rounded-pill px-4'>Add your first UOM</a>
                                      </td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php
// 3. Include footer file
include 'include/footer.php'; 
?>