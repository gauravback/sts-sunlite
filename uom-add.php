<?php
// 1. Include your database connection file
// include 'include/db.php'; 

// 2. Include header file
include 'include/header.php'; 

$message = ""; 

if(isset($_POST['add_uom'])){
    $uom_name = mysqli_real_escape_string($conn, $_POST['uom_name']);
    $uom_code = mysqli_real_escape_string($conn, $_POST['uom_code']);
    $status = $_POST['status']; 

    // Insert Query
    $sql = "INSERT INTO uom_master (uom_name, uom_code, status) VALUES ('$uom_name', '$uom_code', '$status')";
    
    if(mysqli_query($conn, $sql)){
        $message = '<div class="custom-alert success-alert">
                      <i class="fas fa-check-circle alert-icon"></i>
                      <div>
                          <strong>Success!</strong><br>
                          <span class="small">Unit of Measurement has been added successfully. Redirecting...</span>
                      </div>
                    </div>';
        
        echo "<script>setTimeout(function(){ window.location.href='uom-list.php'; }, 2000);</script>";
    } else {
        $message = '<div class="custom-alert error-alert">
                      <i class="fas fa-exclamation-circle alert-icon"></i>
                      <div>
                          <strong>Error!</strong><br>
                          <span class="small">' . mysqli_error($conn) . '</span>
                      </div>
                    </div>';
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* Content Background */
    .saas-content-wrapper {
        background-color: #f8faff;
        min-height: calc(100vh - 60px);
        padding: 40px 20px;
        font-family: 'Inter', 'Segoe UI', sans-serif;
    }

    /* Card Styling */
    .saas-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03), 0 1px 3px rgba(0, 0, 0, 0.02);
        border: 1px solid rgba(0,0,0,0.04);
        overflow: hidden;
    }

    .saas-card-header {
        padding: 30px 40px 10px;
        border-bottom: none;
        background: transparent;
    }

    .saas-title {
        color: #1a1f36;
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .saas-subtitle {
        color: #697386;
        font-size: 0.9rem;
        margin-bottom: 0;
    }

    /* Form Elements */
    .saas-card-body {
        padding: 20px 40px 40px;
    }

    .custom-input-group {
        position: relative;
        margin-bottom: 25px;
    }

    .custom-input-group label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #3c4257;
        margin-bottom: 8px;
    }

    .custom-input {
        width: 100%;
        padding: 14px 16px;
        padding-left: 45px; /* Space for icon */
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        color: #1a1f36;
        background-color: #fcfdfe;
        transition: all 0.2s ease;
    }

    .custom-input:focus {
        outline: none;
        border-color: #5469d4;
        box-shadow: 0 0 0 3px rgba(84, 105, 212, 0.15);
        background-color: #ffffff;
    }

    .input-icon {
        position: absolute;
        bottom: 16px;
        left: 16px;
        color: #a0aec0;
        font-size: 1.1rem;
        transition: color 0.2s ease;
    }

    .custom-input:focus + .input-icon, 
    .custom-input:focus ~ .input-icon {
        color: #5469d4;
    }

    /* Select Dropdown */
    .custom-select {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23a0aec0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 16px center;
        background-size: 16px;
    }

    /* Buttons */
    .btn-action-group {
        display: flex;
        gap: 15px;
        margin-top: 35px;
    }

    .btn-saas {
        padding: 14px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border: none;
    }

    .btn-saas-primary {
        background-color: #5469d4;
        color: #ffffff;
        flex: 2;
        box-shadow: 0 4px 6px rgba(84, 105, 212, 0.2);
    }

    .btn-saas-primary:hover {
        background-color: #4a5fc1;
        transform: translateY(-1px);
        box-shadow: 0 6px 10px rgba(84, 105, 212, 0.25);
    }

    .btn-saas-secondary {
        background-color: #ffffff;
        color: #3c4257;
        border: 1px solid #e2e8f0;
        flex: 1;
    }

    .btn-saas-secondary:hover {
        background-color: #f8fafc;
        color: #1a1f36;
        border-color: #cbd5e1;
    }

    /* Custom Alerts */
    .custom-alert {
        padding: 16px 20px;
        border-radius: 10px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
        animation: fadeInDown 0.4s ease forwards;
    }

    .success-alert {
        background-color: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #065f46;
    }

    .error-alert {
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .alert-icon {
        font-size: 1.5rem;
    }

    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="saas-content-wrapper">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9"> <div class="saas-card">
                    <div class="saas-card-header">
                        <h4 class="saas-title">
                            <i class="fas fa-balance-scale-right text-primary opacity-75"></i>
                            Create New UOM
                        </h4>
                        <p class="saas-subtitle">Add a new unit of measurement for inventory tracking.</p>
                    </div>
                    
                    <div class="saas-card-body">
                        
                        <?= $message; ?>

                        <form method="POST" action="">
                            
                            <div class="custom-input-group">
                                <label for="uom_name">UOM Name</label>
                                <input type="text" class="custom-input" id="uom_name" name="uom_name" placeholder="e.g., Kilogram, Box, Dozen" required autocomplete="off">
                                <i class="fas fa-tag input-icon"></i>
                            </div>
                            
                            <div class="custom-input-group">
                                <label for="uom_code">Short Code / Symbol</label>
                                <input type="text" class="custom-input" id="uom_code" name="uom_code" placeholder="e.g., KG, BOX, DZ" required autocomplete="off">
                                <i class="fas fa-code input-icon"></i>
                            </div>

                            <div class="custom-input-group">
                                <label for="status">Current Status</label>
                                <select class="custom-input custom-select" id="status" name="status">
                                    <option value="1" selected>Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                                <i class="fas fa-toggle-on input-icon"></i>
                            </div>
                            
                            <div class="btn-action-group">
                                <a href="uom-list.php" class="btn-saas btn-saas-secondary">
                                    Cancel
                                </a>
                                <button type="submit" name="add_uom" class="btn-saas btn-saas-primary">
                                    <i class="fas fa-save"></i> Save Measurement Unit
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