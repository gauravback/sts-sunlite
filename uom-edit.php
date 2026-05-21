<?php
// 1. Include your database connection file (verify the path)
// include 'include/db.php'; 

// 2. Include header file
include 'include/header.php'; 

$message = ""; // Variable to hold success/error alerts

// Fetch existing data from URL ID
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET['id']); // Added security

    // Fetch Query
    $fetch_sql = "SELECT * FROM uom_master WHERE id = '$id'";
    $result = mysqli_query($conn, $fetch_sql);
    $row = mysqli_fetch_assoc($result);
}

// Update data on form submit
if (isset($_POST['update_uom'])) {
    $uom_name = mysqli_real_escape_string($conn, $_POST['uom_name']);
    $uom_code = mysqli_real_escape_string($conn, $_POST['uom_code']);
    $status = $_POST['status']; // Active or Inactive

    // Update Query
    $update_sql = "UPDATE uom_master SET uom_name='$uom_name', uom_code='$uom_code', status='$status' WHERE id='$id'";
    
    if (mysqli_query($conn, $update_sql)) {
        // Modern UI Alert
        $message = '<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                      <strong>Success!</strong> UOM details updated successfully.
                      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
        
        // Auto redirect to list page after 1.5 seconds
        echo "<script>setTimeout(function(){ window.location.href='uom-list.php'; }, 1500);</script>";
    } else {
        $message = '<div class="alert alert-danger mt-3" role="alert">Error: ' . mysqli_error($conn) . '</div>';
    }
}
?>

<div class="content-wrapper p-4">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-6 mt-5">
                
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title fw-bold text-primary mb-0">Edit UOM</h4>
                            <p class="text-muted small mt-1">Update unit of measurement details</p>
                        </div>
                        <a href="uom-list.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">
                            &larr; Back
                        </a>
                    </div>
                    
                    <div class="card-body p-4">
                        
                        <?= $message; ?>

                        <form method="POST" action="">
                            
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control shadow-none" id="uom_name" name="uom_name" value="<?php echo $row['uom_name']; ?>" placeholder="Kilogram" required>
                                <label for="uom_name" class="text-muted">UOM Name (e.g., Kilogram)</label>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control shadow-none" id="uom_code" name="uom_code" value="<?php echo $row['uom_code']; ?>" placeholder="KG" required>
                                <label for="uom_code" class="text-muted">UOM Code (e.g., KG)</label>
                            </div>

                            <div class="form-floating mb-4">
                                <select class="form-select shadow-none" id="status" name="status">
                                    <option value="1" <?php if($row['status'] == 1) echo 'selected'; ?>>Active</option>
                                    <option value="0" <?php if($row['status'] == 0) echo 'selected'; ?>>Inactive</option>
                                </select>
                                <label for="status" class="text-muted">Status</label>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="update_uom" class="btn btn-primary btn-lg rounded-pill shadow-sm">
                                    Update UOM
                                </button>
                            </div>
                            
                        </form>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<?php
// 3. Include footer file
include 'include/footer.php'; 
?>