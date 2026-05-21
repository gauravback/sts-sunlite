<?php
// ERROR REPORTING ON
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config/database.php';

// ==========================================
// HANDLE DELETE REQUEST
// ==========================================
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    
    // Server se images bhi delete karne ka logic
    $img_query = mysqli_query($conn, "SELECT signature_image, header_image, footer_image FROM issuer_companies WHERE id='$del_id'");
    if(mysqli_num_rows($img_query) > 0) {
        $img_data = mysqli_fetch_assoc($img_query);
        $target_dir = "uploads/company_files/";
        
        if(!empty($img_data['signature_image']) && file_exists($target_dir.$img_data['signature_image'])) {
            @unlink($target_dir.$img_data['signature_image']);
        }
        if(!empty($img_data['header_image']) && file_exists($target_dir.$img_data['header_image'])) {
            @unlink($target_dir.$img_data['header_image']);
        }
        if(!empty($img_data['footer_image']) && file_exists($target_dir.$img_data['footer_image'])) {
            @unlink($target_dir.$img_data['footer_image']);
        }
    }

    // Database se delete
    $delete_query = "DELETE FROM issuer_companies WHERE id='$del_id'";
    if (mysqli_query($conn, $delete_query)) {
        echo "<script>alert('Company deleted successfully!'); window.location.href='view_companies.php';</script>";
    } else {
        echo "<script>alert('Error deleting company.');</script>";
    }
}
?>

<?php include 'include/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    body { background-color: #f4f7fe; font-family: 'Inter', sans-serif; }
    .modern-card { border-radius: 16px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: #fff; }
    
    /* Modern Bordered Table UX */
    .table-responsive { border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
    .table { margin-bottom: 0; }
    .table-bordered { border: 1px solid #e2e8f0; }
    .table-bordered th, .table-bordered td { border: 1px solid #e2e8f0; }
    
    /* Table Header */
    .table thead th { 
        background-color: #f8fafc; 
        color: #475569; 
        font-weight: 700; 
        border-bottom: 2px solid #cbd5e1 !important; 
        text-transform: uppercase; 
        font-size: 0.85rem;
        padding: 16px 12px;
        letter-spacing: 0.5px;
    }
    
    /* Table Body */
    .table tbody td { 
        padding: 16px 12px; 
        vertical-align: middle; 
        color: #334155; 
        font-size: 0.95rem; 
        transition: background-color 0.2s ease;
    }
    .table-hover tbody tr:hover td { background-color: #f1f5f9; }
    
    /* Badges */
    .badge-status { font-size: 0.75rem; padding: 6px 12px; border-radius: 6px; font-weight: 600; display: inline-block; margin-bottom: 4px; }
    .badge-yes { background-color: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
    .badge-no { background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-custom { font-size: 0.8rem; font-weight: 500; padding: 4px 8px; border-radius: 4px; }
</style>

<div class="container-fluid mt-4 mb-5 px-3 px-md-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <h3 class="fw-bold text-dark mb-0"><i class="fas fa-building me-2 text-primary"></i> Manage Companies</h3>
        <div>
            <a href="create_quotation.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                <i class="fas fa-plus me-2"></i> Create Quotation
            </a>
        </div>
    </div>

    <div class="card modern-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">S.No</th>
                        <th width="22%">Company Name</th>
                        <th width="15%">Contact Details</th>
                        <th width="18%">GSTIN / PAN</th>
                        <th width="20%">Bank Account</th>
                        <th width="12%" class="text-center">Images Status</th>
                        <th width="8%" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $fetch_companies = mysqli_query($conn, "SELECT * FROM issuer_companies ORDER BY id DESC");
                    $count = 1;
                    
                    if(mysqli_num_rows($fetch_companies) > 0) {
                        while($row = mysqli_fetch_assoc($fetch_companies)) {
                            // Check images status
                            $has_head = !empty($row['header_image']) ? '<span class="badge-status badge-yes" title="Header Uploaded">H</span>' : '<span class="badge-status badge-no" title="No Header">H</span>';
                            $has_foot = !empty($row['footer_image']) ? '<span class="badge-status badge-yes" title="Footer Uploaded">F</span>' : '<span class="badge-status badge-no" title="No Footer">F</span>';
                            $has_sign = !empty($row['signature_image']) ? '<span class="badge-status badge-yes" title="Signature Uploaded">S</span>' : '<span class="badge-status badge-no" title="No Signature">S</span>';
                            
                            echo "<tr>
                                    <td class='text-center fw-bold text-secondary'>{$count}</td>
                                    <td>
                                        <div style='font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px;'>{$row['company_name']}</div>
                                        <div class='text-muted text-truncate' style='font-size: 13px; max-width: 250px;' title='{$row['address']}'><i class='fas fa-map-marker-alt me-1'></i> {$row['address']}</div>
                                    </td>
                                    <td>
                                        <div class='text-dark fw-medium'><i class='fas fa-phone-alt text-muted me-2' style='font-size: 0.8rem;'></i>{$row['phone']}</div>
                                    </td>
                                    <td>
                                        <div class='mb-2'><span class='badge bg-light text-dark border badge-custom'><i class='fas fa-file-invoice text-muted me-1'></i> GST: {$row['gstin']}</span></div>
                                        <div><span class='badge bg-light text-dark border badge-custom'><i class='fas fa-id-card text-muted me-1'></i> PAN: {$row['pan']}</span></div>
                                    </td>
                                    <td>
                                        <div class='fw-bold text-dark mb-1'><i class='fas fa-university text-primary me-2'></i>{$row['bank_name']}</div>
                                        <div class='text-muted small mb-1'><strong>A/C:</strong> {$row['account_number']}</div>
                                        <div class='text-muted small'><strong>IFSC:</strong> {$row['ifsc_code']}</div>
                                    </td>
                                    <td class='text-center'>
                                        <div class='d-flex justify-content-center gap-1 flex-wrap'>
                                            {$has_head} {$has_foot} {$has_sign}
                                        </div>
                                    </td>
                                    <td class='text-center'>
                                        <div class='d-flex justify-content-center gap-2'>
                                            <a href='edit_company.php?id={$row['id']}' class='btn btn-sm btn-light text-primary border rounded-3' title='Edit Company'>
                                                <i class='fas fa-edit'></i>
                                            </a>
                                            <a href='view_companies.php?delete_id={$row['id']}' class='btn btn-sm btn-light text-danger border rounded-3' title='Delete Company' onclick=\"return confirm('Are you sure you want to delete this company? All associated letterhead images will also be deleted.');\">
                                                <i class='fas fa-trash-alt'></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>";
                            $count++;
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center py-5 text-danger fw-bold fs-5'><i class='fas fa-box-open mb-3 d-block fs-1'></i>No Companies Found!</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>