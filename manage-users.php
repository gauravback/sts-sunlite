<?php
require_once 'config/auth.php';

// Check if user is admin
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header("Location: index.php");
    exit();
}

require_once 'config/database.php';

/* ================= UPDATE USER ================= */
if(isset($_POST['update_user'])){
    $id = intval($_POST['id']);
    $role = mysqli_real_escape_string($conn, $_POST['role'] ?? '');
    $department = mysqli_real_escape_string($conn, $_POST['department'] ?? '');
    $designation = mysqli_real_escape_string($conn, $_POST['designation'] ?? '');

    $updateQuery = "UPDATE users 
                    SET role='$role', department='$department', designation='$designation' 
                    WHERE id=$id";
                    
    mysqli_query($conn, $updateQuery);

    header("Location: manage-users.php?msg=updated");
    exit();
}

/* ================= DELETE USER ================= */
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);

    $check = mysqli_query($conn,"SELECT role FROM users WHERE id=$id");
    $row = mysqli_fetch_assoc($check);

    if($row && $row['role'] !== 'admin'){
        mysqli_query($conn,"DELETE FROM users WHERE id=$id");
    }

    header("Location: manage-users.php?msg=deleted");
    exit();
}

/* ================= FETCH USERS ================= */
$users = mysqli_query($conn,"SELECT * FROM users ORDER BY id DESC");
$totalUsers = mysqli_num_rows($users);

?>

<?php include 'include/header.php'; ?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
    /* Premium SaaS Dashboard Styling (Matching Leads Page) */
    body { background-color: #f8f9fa; }
    
    .admin-card {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        background: #ffffff;
        width: 100%;
    }
    
    /* Table Specific Styles */
    .saas-table { margin-bottom: 0; }
    .saas-table th {
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #e5e7eb !important;
        padding: 1rem 1.5rem;
        background-color: #f9fafb;
    }
    .saas-table td {
        padding: 1rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
        color: #374151;
        font-size: 0.875rem;
    }
    .saas-table tbody tr:hover { background-color: #f9fafb; }

    /* Custom Avatar */
    .avatar-circle { 
        width: 40px; 
        height: 40px; 
        border-radius: 50%; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-weight: 600; 
        color: #fff;
        font-size: 1.1rem;
    }
    /* Sidebar Overlap Fix */
.page-wrapper { 
    padding: 2rem 0; 
    margin-left: 260px; /* Ye aapke sidebar ki width ke barabar hona chahiye */
    width: calc(100% - 260px); /* Taaki screen ke bahar na nikle */
}

/* Mobile/Tablet ke liye jab sidebar hide ho jata hai */
@media (max-width: 991px) {
    .page-wrapper {
        margin-left: 0;
        width: 100%;
    }
}

    /* Soft Inputs for Table Selects */
    .form-select-soft { 
        background-color: #f9fafb; 
        border: 1px solid #d1d5db; 
        border-radius: 0.375rem; 
        transition: all 0.2s ease-in-out; 
        font-size: 0.85rem;
        padding: 0.4rem 1.8rem 0.4rem 0.75rem;
        color: #4b5563;
    }
    .form-select-soft:focus { 
        border-color: #3b82f6; 
        background-color: #ffffff; 
        outline: 0;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); 
    }

    /* DataTables Custom Overrides */
    .dataTables_wrapper .dataTables_filter input {
        background-color: #f9fafb;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        padding: 0.4rem 1rem;
        font-size: 0.875rem;
        margin-left: 0.5rem;
        outline: none;
        transition: all 0.2s ease;
    }
    .dataTables_wrapper .dataTables_filter input:focus {
        background-color: #ffffff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }
    .dataTables_wrapper .dataTables_length select {
        border-radius: 0.375rem;
        border: 1px solid #d1d5db;
        padding: 0.2rem 1.5rem 0.2rem 0.5rem;
    }
    
    /* Action Buttons */
    .btn-action { 
        width: 32px; 
        height: 32px; 
        padding: 0; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 0.375rem; 
        border: 1px solid transparent;
        transition: all 0.2s;
    }
    .btn-action:hover { border-color: #d1d5db; background-color: #fff; }
</style>

<div class="container py-4 px-lg-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 mb-1 text-gray-800 fw-bold d-flex align-items-center">
                User Management
                <span class="badge bg-primary rounded-pill ms-3 fs-6 px-3"><?= $totalUsers ?></span>
            </h2>
            <p class="text-muted fs-6 mb-0">Manage roles, departments, and system access.</p>
        </div>
        <button class="btn btn-primary shadow-sm px-4">
            + Add New User
        </button>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="admin-card p-2 p-lg-4">
                
                <div class="table-responsive">
                    <table class="table saas-table w-100" id="usersTable">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="25%">User Profile</th>
                                <th width="12%">Phone</th>
                                <th width="15%">Role</th>
                                <th width="15%">Department</th>
                                <th width="15%">Designation</th>
                                <th width="13%" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>

                        <?php 
                        $count = 1;
                        $colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger', 'bg-dark']; 
                        while($user = mysqli_fetch_assoc($users)) :
                            $formId = "update_form_" . $user['id']; 
                            // Deterministic color assignment based on ID so color doesn't change on refresh
                            $colorIndex = $user['id'] % count($colors);
                            $avatarColor = $colors[$colorIndex]; 
                            $initials = strtoupper(substr($user['name'], 0, 1));
                        ?>

                            <tr>
                                <td class="text-muted fw-medium"><?= $count++ ?></td>

                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle <?= $avatarColor ?> me-3 shadow-sm">
                                            <?= $initials ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($user['name']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td class="text-muted"><?= htmlspecialchars($user['phone'] ?? '-') ?></td>

                                <td>
                                    <?php if($user['role']=='admin'): ?>
                                        <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill" style="font-size: 0.75rem;">Admin</span>
                                    <?php else: ?>
                                        <select name="role" form="<?= $formId ?>" class="form-select form-select-soft">
                                            <option value="user" <?= $user['role']=='user'?'selected':'' ?>>User</option>
                                            <option value="manager" <?= $user['role']=='manager'?'selected':'' ?>>Manager</option>
                                            <option value="executive" <?= $user['role']=='executive'?'selected':'' ?>>Executive</option>
                                        </select>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if($user['role']=='admin'): ?>
                                        <span class="text-muted small">—</span>
                                    <?php else: ?>
                                        <select name="department" form="<?= $formId ?>" class="form-select form-select-soft">
                                            <option value="">Select Dept</option>
                                            <option value="sales" <?= $user['department']=='sales'?'selected':'' ?>>Sales</option>
                                            <option value="purchase" <?= $user['department']=='purchase'?'selected':'' ?>>Purchase</option>
                                            <option value="accounts" <?= $user['department']=='accounts'?'selected':'' ?>>Accounts</option>
                                            <option value="inventory" <?= $user['department']=='inventory'?'selected':'' ?>>Inventory</option>
                                            <option value="logistics" <?= $user['department']=='logistics'?'selected':'' ?>>Logistics</option>
                                            <option value="customer_support" <?= $user['department']=='customer_support'?'selected':'' ?>>Support</option>
                                            <option value="hr" <?= $user['department']=='hr'?'selected':'' ?>>HR</option>
                                            <option value="it" <?= $user['department']=='it'?'selected':'' ?>>IT</option>
                                        </select>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if($user['role']=='admin'): ?>
                                        <span class="text-muted small">—</span>
                                    <?php else: ?>
                                        <select name="designation" form="<?= $formId ?>" class="form-select form-select-soft">
                                            <option value="">Select Desig</option>
                                            <option value="intern" <?= $user['designation']=='intern'?'selected':'' ?>>Intern</option>
                                            <option value="trainee" <?= $user['designation']=='trainee'?'selected':'' ?>>Trainee</option>
                                            <option value="executive" <?= $user['designation']=='executive'?'selected':'' ?>>Executive</option>
                                            <option value="senior_executive" <?= $user['designation']=='senior_executive'?'selected':'' ?>>Senior Exec.</option>
                                            <option value="manager" <?= $user['designation']=='manager'?'selected':'' ?>>Manager</option>
                                            <option value="general_manager" <?= $user['designation']=='general_manager'?'selected':'' ?>>General Manager</option>
                                            <option value="department_head" <?= $user['designation']=='department_head'?'selected':'' ?>>Dept. Head</option>
                                        </select>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">
                                    <?php if($user['role'] !== 'admin'): ?>
                                        
                                        <div class="d-flex justify-content-end gap-1">
                                            <form id="<?= $formId ?>" method="POST" action="manage-users.php" class="m-0 p-0">
                                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                <button type="submit" name="update_user" class="btn btn-action bg-light text-success" title="Save Changes">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                                                </button>
                                            </form>

                                            <a href="manage-users.php?delete=<?= $user['id'] ?>" class="btn btn-action bg-light text-danger btn-delete" title="Delete User">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                            </a>
                                        </div>

                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-3 py-1 rounded-pill" style="font-size: 0.75rem;">Protected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>


<script src="assets/plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="assets/plugins/datatables/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function(){
    
    // Initialize DataTable with clean styling wrappers
    $('#usersTable').DataTable({
        "language": {
            "search": "",
            "searchPlaceholder": "🔍 Search users...",
            "lengthMenu": "Show _MENU_ users"
        },
        "dom": '<"d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"lf>rt<"d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2"ip>',
        "pageLength": 10,
        "ordering": true,
        "columnDefs": [
            { "orderable": false, "targets": 6 } // Disable sorting on action column
        ]
    });

    // Handle SweetAlert Notifications
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    
    if(msg === 'updated') {
        Swal.fire({
            icon: 'success',
            title: 'Updated Successfully',
            text: 'User roles and details have been saved.',
            timer: 2500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
        window.history.replaceState(null, null, window.location.pathname);
    } else if(msg === 'deleted') {
        Swal.fire({
            icon: 'success',
            title: 'User Removed',
            text: 'The user account has been successfully deleted.',
            timer: 2500,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
        window.history.replaceState(null, null, window.location.pathname);
    }

    // Delete Confirmation
    $('.btn-delete').on('click', function(e){
        e.preventDefault();
        const deleteUrl = $(this).attr('href');
        
        Swal.fire({
            title: 'Delete User?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#e5e7eb',
            confirmButtonText: 'Yes, delete!',
            cancelButtonText: '<span class="text-dark">Cancel</span>',
            customClass: {
                confirmButton: 'shadow-sm px-4',
                cancelButton: 'shadow-sm px-4'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = deleteUrl;
            }
        });
    });
});
</script>

<?php include 'include/footer.php'; ?>