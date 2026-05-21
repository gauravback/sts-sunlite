<?php
require_once 'config/auth.php';
require_once 'config/database.php';
include 'include/header.php';

// Indian Timezone set kar diya
date_default_timezone_set('Asia/Kolkata');

$user = $_SESSION['name'];

$stmt = $conn->prepare("SELECT * FROM leads WHERE lead_by=? ORDER BY id DESC");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --brand-primary: #4F46E5; 
        --brand-hover: #4338CA;
        --bg-body: #F8FAFC;
        --card-bg: #FFFFFF;
        --text-main: #0F172A;
        --text-muted: #64748B;
        --border-light: #E2E8F0;
        --radius-lg: 16px;
        --radius-md: 12px;
        --shadow-sm: 0 2px 4px rgba(15, 23, 42, 0.04);
        --shadow-hover: 0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -2px rgba(15, 23, 42, 0.04);
    }

    body, .leads-wrapper { 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        background-color: var(--bg-body); 
        color: var(--text-main); 
    }
    
    .leads-wrapper { padding: 32px; min-height: 100vh; }

    .page-title { 
        font-weight: 800; 
        font-size: 1.85rem; 
        color: var(--text-main); 
        letter-spacing: -0.02em; 
    }

    /* Buttons */
    .btn-brand { 
        background: var(--brand-primary); 
        color: #fff; 
        border: none; 
        border-radius: 10px; 
        padding: 10px 24px; 
        font-weight: 600; 
        font-size: 0.95rem; 
        transition: all 0.2s ease;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
    }
    .btn-brand:hover { 
        background: var(--brand-hover); 
        color: #fff; 
        box-shadow: 0 6px 8px -1px rgba(79, 70, 229, 0.3);
        transform: translateY(-1px);
    }
    
    .btn-outline-custom {
        background: #F8FAFC;
        border: 1px solid var(--border-light);
        color: var(--text-main);
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .btn-outline-custom:hover {
        background: #E2E8F0;
        border-color: #CBD5E1;
    }

    /* Card Styling */
    .new-gen-card { 
        background: var(--card-bg); 
        border: 1px solid rgba(226, 232, 240, 0.8); 
        border-radius: var(--radius-lg); 
        box-shadow: var(--shadow-sm); 
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        overflow: hidden;
    }
    .new-gen-card:hover { 
        box-shadow: var(--shadow-hover); 
        transform: translateY(-2px);
    }

    /* Inputs */
    .clean-input { 
        border-radius: 10px; 
        border: 1px solid var(--border-light); 
        padding: 10px 16px; 
        font-size: 0.95rem; 
        color: var(--text-main); 
        background-color: #F8FAFC;
        transition: all 0.2s ease;
    }
    .clean-input:focus { 
        background-color: #FFF;
        border-color: var(--brand-primary); 
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15); 
        outline: none; 
    }

    /* Table Styling */
    .table-custom { width: 100%; border-collapse: separate; border-spacing: 0; }
    .table-custom th { 
        background: rgba(248, 250, 252, 0.8); 
        backdrop-filter: blur(8px);
        color: var(--text-muted); 
        font-weight: 700; 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 0.05em; 
        padding: 16px 24px; 
        text-align: left; 
        border-bottom: 1px solid var(--border-light); 
    }
    .table-custom td { 
        padding: 20px 24px; 
        border-bottom: 1px solid var(--border-light); 
        vertical-align: middle; 
        font-size: 0.9rem; 
        transition: background-color 0.2s ease;
    }
    .table-custom tbody tr:hover td { background-color: #F8FAFC; cursor: pointer; }
    .table-custom tbody tr:last-child td { border-bottom: none; }

    /* Avatar */
    .avatar-circle { 
        width: 44px; height: 44px; 
        display: flex; align-items: center; justify-content: center; 
        border-radius: 12px; 
        font-weight: 700; font-size: 1.1rem; 
        background: linear-gradient(135deg, var(--brand-primary) 0%, #818CF8 100%);
        color: white;
    }

    /* Modern Subtle Badges */
    .badge-subtle {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        display: inline-flex;
        align-items: center;
    }
    .badge-subtle-primary { background-color: #EEF2FF; color: #4338CA; border: 1px solid #E0E7FF; }
    .badge-subtle-success { background-color: #ECFDF5; color: #047857; border: 1px solid #D1FAE5; }
    .badge-subtle-danger { background-color: #FEF2F2; color: #E11D48; border: 1px solid #FECDD3; } /* Hot ke liye */
    .badge-subtle-info { background-color: #ECFEFF; color: #0891B2; border: 1px solid #A5F3FC; } /* Cold ke liye */
    .badge-subtle-secondary { background-color: #F8FAFC; color: #475569; border: 1px solid #E2E8F0; } /* Normal ke liye */

    /* Empty State */
    .empty-state { text-align: center; padding: 5rem 2rem; }
    .empty-state-icon-wrapper {
        width: 96px; height: 96px;
        background: #F1F5F9;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1.5rem auto;
    }
    .empty-state-icon { font-size: 2.5rem; color: #94A3B8; }
</style>

<div class="leads-wrapper">
    <div class="container-fluid content px-0">

        <div class="page-header mb-4 pb-2">
            <div class="row align-items-center">
                <div class="col">
                    <h3 class="page-title mb-1">My Leads</h3>
                    <p class="text-muted mb-0 fw-medium" style="font-size: 0.95rem;">Manage and track all leads assigned to or created by you.</p>
                </div>
                <div class="col-auto">
                    <a href="leads.php" class="btn-brand d-inline-flex align-items-center gap-2 text-decoration-none">
                        <i class="fa-solid fa-plus"></i> Add New Lead
                    </a>
                </div>
            </div>
        </div>

        <div class="new-gen-card">
            
            <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="input-group" style="max-width: 350px;">
                    <span class="input-group-text bg-light border-end-0 clean-input" style="border-radius: 10px 0 0 10px; border-right: none;"><i class="fa-solid fa-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control clean-input border-start-0 ps-0" style="border-radius: 0 10px 10px 0; border-left: none; background-color: #F8FAFC;" placeholder="Search companies or contacts...">
                </div>
                <div>
                    <button class="btn btn-outline-custom d-flex align-items-center gap-2 px-3 py-2">
                        <i class="fa-solid fa-sliders"></i> Filters
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <?php if($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Lead Details</th>
                                <th>Contact Info</th>
                                <th>Location</th>
                                <th>Category, Type & Priority</th> <th>Added Date</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="leadTableBody">
                            <?php while($row = $result->fetch_assoc()) { ?>
                            <tr onclick="window.location='view-lead.php?id=<?= $row['id'] ?>'">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3 shadow-sm">
                                            <?= strtoupper(substr($row['company_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($row['company_name']) ?></h6>
                                            <div class="text-muted mt-1" style="font-size: 0.8rem; font-weight: 600;">ID: #<?= str_pad($row['id'], 4, "0", STR_PAD_LEFT) ?></div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-bold text-dark" style="font-size: 0.9rem;"><?= htmlspecialchars($row['contact_person']) ?></div>
                                    <div class="text-muted d-flex align-items-center gap-2 mt-1" style="font-size: 0.85rem; font-weight: 500;">
                                        <i class="fa-solid fa-phone" style="font-size: 0.75rem;"></i> <?= htmlspecialchars($row['contact_number']) ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center gap-2 text-muted fw-medium" style="font-size: 0.85rem;">
                                        <i class="fa-solid fa-location-dot" style="font-size: 0.8rem; color: #94A3B8;"></i> 
                                        <?= htmlspecialchars($row['location'] ? $row['location'] : 'N/A') ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php if(!empty($row['lead_type'])): ?>
                                        <span class="badge-subtle badge-subtle-primary">
                                            <?= htmlspecialchars($row['lead_type']) ?>
                                        </span>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($row['customer_type'])): ?>
                                        <span class="badge-subtle badge-subtle-success">
                                            <?= htmlspecialchars($row['customer_type']) ?>
                                        </span>
                                        <?php endif; ?>

                                        <?php if(!empty($row['lead_priority'])): ?>
                                            <?php 
                                            $prioClass = 'badge-subtle-secondary';
                                            if($row['lead_priority'] == 'Hot') $prioClass = 'badge-subtle-danger';
                                            elseif($row['lead_priority'] == 'Cold') $prioClass = 'badge-subtle-info';
                                            ?>
                                            <span class="badge-subtle <?= $prioClass ?>">
                                                <?= htmlspecialchars($row['lead_priority']) ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if(empty($row['lead_type']) && empty($row['customer_type']) && empty($row['lead_priority'])): ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="text-dark fw-bold" style="font-size: 0.85rem;">
                                        <?php 
                                            // Timezone fix for "Added Date"
                                            $date = new DateTime($row['created_at']);
                                            $date->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                            echo $date->format('d M, Y');
                                        ?>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 0.75rem; font-weight: 500;">
                                        <?php echo $date->format('h:i A'); ?>
                                    </div>
                                </td>

                                <td class="text-end">
                                    <a href="view-lead.php?id=<?= $row['id'] ?>" class="btn-outline-custom d-inline-flex align-items-center gap-2 px-3 py-2 text-decoration-none" onclick="event.stopPropagation();">
                                        <i class="fa-solid fa-arrow-right"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-white border-top p-4 d-flex align-items-center justify-content-between flex-wrap gap-3" style="border-radius: 0 0 var(--radius-lg) var(--radius-lg);">
                    <span class="text-muted fw-bold" style="font-size: 0.85rem;">Showing <span class="text-dark"><?= $result->num_rows ?></span> results</span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0 gap-1">
                            <li class="page-item disabled"><a class="page-link border-0 bg-light text-muted rounded px-3 fw-bold" href="#">Prev</a></li>
                            <li class="page-item active"><a class="page-link border-0 text-white rounded px-3 fw-bold" style="background: var(--brand-primary);" href="#">1</a></li>
                            <li class="page-item disabled"><a class="page-link border-0 bg-light text-muted rounded px-3 fw-bold" href="#">Next</a></li>
                        </ul>
                    </nav>
                </div>

                <?php else: ?>
                <div class="empty-state bg-white">
                    <div class="empty-state-icon-wrapper">
                        <i class="fa-solid fa-folder-open empty-state-icon"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">No Leads Found</h4>
                    <p class="text-muted mb-4">You haven't added or been assigned any leads yet.</p>
                    <a href="leads.php" class="btn-brand d-inline-flex align-items-center gap-2 text-decoration-none">
                        <i class="fa-solid fa-plus"></i> Add Your First Lead
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filterValue = this.value.toLowerCase();
        let tableRows = document.querySelectorAll('#leadTableBody tr');

        tableRows.forEach(function(row) {
            let rowText = row.textContent.toLowerCase();
            if (rowText.includes(filterValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

<?php include 'include/footer.php'; ?>