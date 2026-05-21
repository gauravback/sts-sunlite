<?php
require_once 'config/auth.php';
require_once 'config/database.php';
include 'include/header.php';

if(!isset($_GET['id']) || empty($_GET['id'])){
    echo '<div class="container mt-5"><div class="alert alert-danger text-center shadow-sm rounded-4">Lead ID Missing or Invalid.</div></div>';
    include 'include/footer.php';
    exit;
}

$lead_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM leads WHERE id=?");
$stmt->bind_param("i", $lead_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    echo '<div class="container mt-5"><div class="alert alert-warning text-center shadow-sm rounded-4">Lead not found. It might have been deleted.</div></div>';
    include 'include/footer.php';
    exit;
}

$lead = $result->fetch_assoc();

// UPDATE: YAHAN JOIN LAGAYA HAI MANAGER KA NAAM BHI FETCH KARNE KE LIYE
$history = $conn->prepare("
    SELECT h.*, l.manager 
    FROM lead_history h
    LEFT JOIN leads l ON h.lead_id = l.id
    WHERE h.lead_id=? 
    ORDER BY h.created_at DESC
");
$history->bind_param("i", $lead_id);
$history->execute();
$history_result = $history->get_result();

$created_date = !empty($lead['created_at']) ? date("d-m-Y H:i", strtotime($lead['created_at'])) : "N/A";

/* Latest Followup */
$follow = $conn->prepare("
SELECT followup_time 
FROM lead_history
WHERE lead_id=? AND followup_time IS NOT NULL
ORDER BY followup_time DESC
LIMIT 1
");
$follow->bind_param("i",$lead_id);
$follow->execute();
$follow_query_result = $follow->get_result();

$followup_date = "dd-mm-yyyy --:--";
if($follow_query_result->num_rows > 0) {
    $follow_result = $follow_query_result->fetch_assoc();
    if(!empty($follow_result['followup_time'])){
        $followup_date = date("d-m-Y H:i", strtotime($follow_result['followup_time']));
    }
}

function getAvatarColor($char) {
    $colors = ['#ef4444','#f97316','#f59e0b','#84cc16','#10b981','#06b6d4','#3b82f6','#6366f1','#8b5cf6','#d946ef','#f43f5e'];
    $index = ord(strtoupper($char)) % count($colors);
    return $colors[$index];
}

$initial = strtoupper(substr(trim($lead['contact_person'] ?: $lead['company_name']), 0, 1));
if(empty($initial)) $initial = "#";
$avatarColor = getAvatarColor($initial);
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">

<style>
:root {
    --primary: #4f46e5;
    --primary-light: #e0e7ff;
    --text-main: #0f172a;
    --text-muted: #64748b;
    --bg-body: #f8fafc;
    --bg-surface: #ffffff;
    --border-color: #e2e8f0;
    --radius-xl: 16px;
    --radius-lg: 12px;
}

body {
    background-color: var(--bg-body);
    font-family: 'Inter', sans-serif;
    color: var(--text-main);
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.btn-soft {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    color: var(--text-main);
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.btn-soft:hover { background: #f1f5f9; color: var(--primary); border-color: #cbd5e1; }

.card-modern {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-xl);
    padding: 24px;
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -2px rgba(0,0,0,0.02);
    margin-bottom: 24px;
}

/* Profile Section */
.profile-header { text-align: center; margin-bottom: 20px; }
.profile-avatar {
    width: 80px; height: 80px;
    border-radius: 24px;
    color: white;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 2rem;
    margin: 0 auto 16px auto;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}
.profile-name { font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin-bottom: 4px; }
.profile-company { font-size: 0.9rem; color: var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 6px; }

.status-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 30px;
    font-size: 0.85rem; font-weight: 600;
    background: var(--primary-light); color: var(--primary);
    border: 1px solid #c7d2fe;
}

/* Quick Info List */
.info-list { list-style: none; padding: 0; margin: 0; }
.info-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 12px 0; border-bottom: 1px solid var(--border-color);
}
.info-item:last-child { border-bottom: none; }
.info-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: #f1f5f9; color: var(--text-muted);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.info-content { flex-grow: 1; }
.info-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); font-weight: 600; margin-bottom: 2px; }
.info-value { font-size: 0.95rem; color: var(--text-main); font-weight: 500; display: flex; align-items: center; gap: 8px; }

/* Grid Details */
.detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
.detail-box { padding: 16px; background: #f8fafc; border-radius: var(--radius-lg); border: 1px solid var(--border-color); }

/* Timeline Modern */
.timeline-modern { position: relative; padding-left: 30px; margin-top: 20px; }
.timeline-modern::before {
    content: ''; position: absolute; left: 11px; top: 0; bottom: 0;
    width: 2px; background: #e2e8f0; border-radius: 2px;
}
.timeline-item { position: relative; margin-bottom: 24px; }
.timeline-item:last-child { margin-bottom: 0; }
.timeline-dot {
    position: absolute; left: -30px; top: 4px;
    width: 24px; height: 24px; border-radius: 50%;
    background: white; border: 2px solid var(--primary);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 0 4px var(--bg-surface);
}
.timeline-dot::after { content: ''; width: 8px; height: 8px; background: var(--primary); border-radius: 50%; }
.timeline-card { background: white; border: 1px solid var(--border-color); padding: 16px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); transition: transform 0.2s; }
.timeline-card:hover { transform: translateX(4px); border-color: #cbd5e1; }
.timeline-text { font-size: 0.95rem; color: var(--text-main); margin-bottom: 8px; font-weight: 500; line-height: 1.5; }
.timeline-meta { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 12px; font-size: 0.8rem; color: var(--text-muted); }
.timeline-meta span { display: flex; align-items: center; gap: 4px; }

@media (max-width: 768px) {
    .page-header { flex-direction: column; align-items: flex-start; gap: 15px; }
    .detail-grid { grid-template-columns: 1fr; }
}
</style>

<div class="container-fluid py-4 mb-5" style="max-width:1200px;">

    <div class="page-header">
        <div>
            <h3 class="fw-bold text-dark mb-1">Lead Details</h3>
            <p class="text-muted mb-0">View complete information and history</p>
        </div>

        <div class="d-flex gap-2">
            <a href="leads-list.php" class="btn-soft">
                <i class="ti ti-arrow-left"></i> Back to Leads
            </a>

            <a href="edit-lead.php?id=<?= $lead_id ?>" class="btn-soft" style="background:var(--primary);color:white;border:none;">
                <i class="ti ti-edit"></i> Edit Lead
            </a>
        </div>
    </div>


    <div class="row">
        <div class="col-lg-4">
            <div class="card-modern position-sticky" style="top:20px;">
                <div class="profile-header">
                    <div class="profile-avatar" style="background-color:<?= $avatarColor ?>;">
                        <?= $initial ?>
                    </div>
                    <div class="profile-name">
                        <?= htmlspecialchars($lead['contact_person'] ?: 'e.g. John Doe'); ?>
                    </div>
                    <div class="profile-company">
                        <i class="ti ti-building"></i>
                        <?= htmlspecialchars($lead['company_name'] ?? 'Company Ltd.'); ?>
                    </div>
                    <div class="mt-3">
                        <span class="status-badge">
                            <i class="ti ti-activity"></i>
                            <?= htmlspecialchars($lead['lead_status'] ?: 'New Lead'); ?>
                        </span>
                    </div>
                </div>

                <hr class="my-4" style="border-color:var(--border-color);">

                <h6 class="text-muted fw-bold text-uppercase mb-3" style="font-size:0.8rem;letter-spacing:0.5px;">Contact Information</h6>

                <ul class="info-list">
                    <li class="info-item">
                        <div class="info-icon"><i class="ti ti-phone"></i></div>
                        <div class="info-content">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value">
                                <span><?= htmlspecialchars($lead['contact_number'] ?: '+91 98765 43210'); ?></span>
                            </div>
                        </div>
                    </li>
                    
                    <li class="info-item">
                        <div class="info-icon"><i class="ti ti-phone-plus"></i></div>
                        <div class="info-content">
                            <div class="info-label">Alternate No.</div>
                            <div class="info-value">
                                <span><?= htmlspecialchars($lead['alternate_number'] ?: 'Alternate Number'); ?></span>
                            </div>
                        </div>
                    </li>

                    <li class="info-item">
                        <div class="info-icon"><i class="ti ti-mail"></i></div>
                        <div class="info-content">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?= htmlspecialchars($lead['email'] ?: 'john@example.com'); ?></div>
                        </div>
                    </li>
                    <li class="info-item">
                        <div class="info-icon"><i class="ti ti-map-pin"></i></div>
                        <div class="info-content">
                            <div class="info-label">Address</div>
                            <div class="info-value"><?= htmlspecialchars($lead['location'] ?: 'City, State'); ?></div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            
            <div class="card-modern mb-4">
                <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                    <i class="ti ti-file-info text-primary"></i> Lead Overview
                </h5>

                <div class="detail-grid">
                    <div class="detail-box">
                        <div class="info-label">Lead Source</div>
                        <div class="info-value fw-bold text-dark">
                            <?= htmlspecialchars($lead['lead_source'] ?? $lead['lead_type'] ?? '-'); ?>
                        </div>
                    </div>
                    <div class="detail-box">
                        <div class="info-label">Customer Type</div>
                        <div class="info-value fw-bold text-dark">
                            <?= htmlspecialchars($lead['customer_type'] ?: 'New Customer'); ?>
                        </div>
                    </div>
                    <div class="detail-box">
                        <div class="info-label">Current Status</div>
                        <div class="info-value fw-bold text-dark">
                            <?= htmlspecialchars($lead['lead_status'] ?: 'New Lead'); ?>
                        </div>
                    </div>
                    <div class="detail-box">
                        <div class="info-label">Lead Type</div>
                        <div class="info-value fw-bold text-dark">
                            <?= htmlspecialchars($lead['lead_priority'] ?? 'Hot'); ?>
                        </div>
                    </div>

                    <div class="detail-box">
                        <div class="info-label">Followed By</div>
                        <div class="info-value text-dark">
                            <?= htmlspecialchars($lead['followed_by'] ?: 'Select User'); ?>
                        </div>
                    </div>
                    <div class="detail-box">
                        <div class="info-label">Reporting Manager <span class="text-danger">*</span></div>
                        <div class="info-value text-dark">
                            <?= htmlspecialchars($lead['manager'] ?: 'Select Manager'); ?>
                        </div>
                    </div>

                    <div class="detail-box">
                        <div class="info-label">Lead Created By <span class="text-danger">*</span></div>
                        <div class="info-value text-dark">
                            <?= htmlspecialchars($lead['lead_by'] ?: 'Sam'); ?>
                        </div>
                    </div>
                    <div class="detail-box">
                        <div class="info-label">Support Team</div>
                        <div class="info-value text-dark">
                            <?= htmlspecialchars($lead['support_team'] ?: 'Select Team Member'); ?>
                        </div>
                    </div>

                    <div class="detail-box">
                        <div class="info-label">Lead Update Date</div>
                        <div class="info-value text-dark">
                            <i class="ti ti-calendar me-1 text-muted"></i>
                            <?= $created_date; ?>
                        </div>
                    </div>
                    <div class="detail-box" style="background:#fffbeb;border-color:#fef3c7;">
                        <div class="info-label text-warning">Lead Followup date</div>
                        <div class="info-value text-dark fw-bold">
                            <i class="ti ti-bell-ringing me-1 text-warning"></i>
                            <?= $followup_date; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if(!empty($lead['description'])): ?>
            <div class="card-modern mb-4">
                <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="font-size: 0.95rem; color: var(--text-muted);">
                    Activity Note / History Update
                </h5>
                <div class="p-3 bg-light rounded-3 border" style="font-size: 0.95rem; color: var(--text-main);">
                    <?= nl2br(htmlspecialchars($lead['description'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card-modern">
                <h5 class="fw-bold mb-4 d-flex align-items-center gap-2">
                    <i class="ti ti-history text-primary"></i> Activity History
                </h5>

                <?php if($history_result->num_rows > 0): ?>
                    <div class="timeline-modern">
                        <?php while($row = $history_result->fetch_assoc()): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-card">
                                    <div class="timeline-text">
                                        <?= nl2br(htmlspecialchars($row['history_note'])); ?>
                                    </div>
                                    <div class="timeline-meta mt-3 pt-2 border-top">
                                        <div class="d-flex align-items-center gap-3">
                                            <span>
                                                <i class="ti ti-clock"></i>
                                                <?= date("d M Y, h:i A", strtotime($row['created_at'])); ?>
                                            </span>
                                            <?php if(!empty($row['followup_time']) && $row['followup_time'] != '0000-00-00 00:00:00'): ?>
                                                <span class="text-warning fw-medium">
                                                    <i class="ti ti-bell"></i>
                                                    Follow-up: <?= date("d M Y, h:i A", strtotime($row['followup_time'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="text-dark fw-medium">
                                            <i class="ti ti-user me-1"></i> 
                                            <?php echo htmlspecialchars(!empty($row['updated_by']) ? $row['updated_by'] : ($lead['lead_by'] ?? 'System')); ?>
                                            <span class="text-muted fw-normal ms-1" style="font-size: 0.75rem;">
                                                | Mgr: <?= htmlspecialchars($row['manager'] ?? $lead['manager'] ?? 'Unassigned'); ?>
                                            </span>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 bg-light rounded-3 border" style="border-style:dashed!important;">
                        <i class="ti ti-message-2 text-muted mb-2" style="font-size:2.5rem;opacity:0.5;"></i>
                        <h6 class="fw-bold text-dark mt-2">No History Yet</h6>
                        <p class="text-muted mb-0" style="font-size:0.9rem;">Activity and follow-ups will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>