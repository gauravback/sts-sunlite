<?php
session_start();
include 'config/database.php';

// ==========================================
// ADMIN ACCESS SECURITY CHECK
// ==========================================
// Yahan hum check kar rahe hain ki user admin hai ya nahi.
// NOTE: Agar aapke project me admin check karne ka session variable alag hai 
// (jaise $_SESSION['user_type'] ya $_SESSION['is_admin']), toh usko yahan 'role' ki jagah change kar lena.

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    // Agar user admin nahi hai, toh wapas bhej do (jaise index.php ya dashboard par)
    header("Location: index.php"); 
    exit();
}

// ==========================================
// --- UPDATE LOGIC ---
// ==========================================
if (isset($_POST['update_signature'])) {
    $issuer_id = $_POST['issuer_id'];
    $signature_filename = "";

    if (isset($_FILES['signature_image']) && $_FILES['signature_image']['error'] == 0) {
        $target_dir = "uploads/signatures/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }

        $file_extension = pathinfo($_FILES["signature_image"]["name"], PATHINFO_EXTENSION);
        $signature_filename = "sign_" . time() . "_" . rand(1000, 9999) . "." . $file_extension;
        $target_file = $target_dir . $signature_filename;

        if (move_uploaded_file($_FILES["signature_image"]["tmp_name"], $target_file)) {
            // Purani image delete karne ka logic taaki server par jagah na bhare
            $old_query = mysqli_query($conn, "SELECT signature_image FROM issuer_companies WHERE id = '$issuer_id'");
            $old_data = mysqli_fetch_assoc($old_query);
            if (!empty($old_data['signature_image']) && file_exists($target_dir . $old_data['signature_image'])) {
                unlink($target_dir . $old_data['signature_image']);
            }

            // Database update karein
            $update_sql = "UPDATE issuer_companies SET signature_image = '$signature_filename' WHERE id = '$issuer_id'";
            mysqli_query($conn, $update_sql);
            $_SESSION['msg'] = "Signature updated successfully!";
        } else {
            $_SESSION['msg'] = "Error uploading signature!";
        }
    }
    // Wapas ishi page par aane ke liye
    header("Location: manage_issuers.php");
    exit();
}
?>

<?php include 'include/header.php'; ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Manage Company Signatures</h3>
        <a href="create_quotation.php" class="btn btn-secondary btn-sm">Back to Quotation</a>
    </div>

    <?php if(isset($_SESSION['msg'])): ?>
        <div class="alert alert-info shadow-sm"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Company Name</th>
                        <th>Current Signature</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = mysqli_query($conn, "SELECT id, company_name, signature_image FROM issuer_companies ORDER BY company_name ASC");
                    while($row = mysqli_fetch_assoc($query)):
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['company_name']) ?></strong></td>
                        <td>
                            <?php if(!empty($row['signature_image'])): ?>
                                <img src="uploads/signatures/<?= htmlspecialchars($row['signature_image']) ?>" alt="sign" style="height: 50px; border: 1px solid #eee; padding: 2px; border-radius: 4px;">
                            <?php else: ?>
                                <span class="text-muted small"><i class="fas fa-times-circle me-1"></i>No Signature Uploaded</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form action="" method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="issuer_id" value="<?= $row['id'] ?>">
                                <input type="file" name="signature_image" class="form-control form-control-sm" style="max-width: 250px;" required accept="image/png, image/jpeg, image/jpg">
                                <button type="submit" name="update_signature" class="btn btn-primary btn-sm"><i class="fas fa-upload me-1"></i> Upload</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>