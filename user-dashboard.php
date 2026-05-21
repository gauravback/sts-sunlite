<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
include __DIR__ . '/include/header.php';

$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
$name    = $_SESSION['name'];

/* FETCH USER DETAILS */
$stmt = $conn->prepare("SELECT email, designation, contact_no, last_login FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

$email       = $userData['email'] ?? 'Not Available';
$designation = $userData['designation'] ?? 'User';
$contact_no  = $userData['contact_no'] ?? 'Not Available';
$last_login  = $userData['last_login'] ?? null;

$stmt->close();

/* ROLE BASED STATS */
if($role == "admin"){

    $deals = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM deals"))[0];
    $leads = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM leads"))[0];
    $tasks = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM tasks"))[0];

} else {

    $deals = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM deals WHERE user_id = '$user_id'"))[0];

    $leads = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM leads WHERE lead_by = '$name'"))[0];

    $tasks = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM tasks WHERE user_id = '$user_id'"))[0];
}

/* AVATAR INITIALS */
$nameParts = explode(' ', trim($name));
$initials = strtoupper(
    substr($nameParts[0],0,1) .
    (isset($nameParts[1]) ? substr($nameParts[1],0,1) : '')
);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">

<style>
    :root {
        --bg-body: #f4f7fe;
        --bg-surface: #ffffff;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
        --primary: #4318FF;
        --primary-hover: #3311db;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-card: 0px 4px 20px rgba(0, 0, 0, 0.03);
        --gradient-admin: linear-gradient(135deg, #4318FF 0%, #8b5cf6 100%);
        --gradient-user: linear-gradient(135deg, #05cd99 0%, #10b981 100%);
    }

    body { background-color: var(--bg-body); font-family: 'Inter', sans-serif; color: var(--text-main); }
    .page-wrapper { padding: 2rem 0; min-height: calc(100vh - 60px); }
    .content-modern { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }

    /* Premium Cards */
    .card-modern {
        background: var(--bg-surface);
        border: none;
        border-radius: 20px;
        box-shadow: var(--shadow-card);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 1.5rem;
    }

    /* Profile Cover & Avatar */
    .profile-cover {
        height: 150px;
        background: <?= ($role == 'admin') ? 'var(--gradient-admin)' : 'var(--gradient-user)' ?>;
        position: relative;
    }
    
    .profile-avatar-wrapper {
        position: relative;
        margin-top: -60px;
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid var(--bg-surface);
        background-color: #f1f5f9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        object-fit: cover;
    }

    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 16px;
        background: #f1f5f9;
        color: var(--text-main);
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Info List */
    .info-list { list-style: none; padding: 0; margin: 0; }
    .info-list li {
        display: flex;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .info-list li:last-child { border-bottom: none; padding-bottom: 0; }
    .info-list-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: #f8fafc;
        color: var(--primary);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; margin-right: 1rem;
    }
    .info-label { font-size: 0.8rem; color: var(--text-muted); font-weight: 500; margin-bottom: 2px; }
    .info-value { font-size: 0.95rem; font-weight: 600; color: var(--text-main); word-break: break-all; }

    /* Stats Grid */
    .stat-box {
        padding: 1.5rem;
        border-radius: 16px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        gap: 1.2rem;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }
    .stat-box:hover {
        background: var(--bg-surface);
        border-color: var(--border-color);
        box-shadow: var(--shadow-sm);
        transform: translateY(-2px);
    }
    .stat-icon {
        width: 54px; height: 54px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem;
    }
    .icon-deals { background: #e0e7ff; color: #4318FF; }
    .icon-leads { background: #ccfbf1; color: #05cd99; }
    .icon-tasks { background: #fef3c7; color: #f59e0b; }
    
    .stat-val { font-size: 1.5rem; font-weight: 700; color: var(--text-main); line-height: 1; margin-bottom: 4px; }
    .stat-label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

    /* Tabs */
    .nav-tabs-modern {
        border-bottom: 1px solid var(--border-color);
        gap: 1rem;
    }
    .nav-tabs-modern .nav-link {
        border: none;
        color: var(--text-muted);
        font-weight: 600;
        padding: 1rem 1.5rem;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
    }
    .nav-tabs-modern .nav-link:hover { color: var(--primary); }
    .nav-tabs-modern .nav-link.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
        background: transparent;
    }
</style>

<div class="">
<div class="content-modern">

<div class="d-flex align-items-center justify-content-between mb-4">
<div>
<h3 class="fw-bold mb-1" style="color: var(--text-main); letter-spacing: -0.5px;">My Profile</h3>
<p class="text-muted mb-0" style="font-size: 0.9rem;">Manage your account settings and preferences.</p>
</div>
</div>

<div class="row g-4">

<div class="col-xl-4 col-lg-5">
<div class="card-modern">

<div class="profile-cover"></div>

<div class="profile-avatar-wrapper">
<div class="profile-avatar"><?= $initials ?></div>
</div>

<div class="text-center px-4 pb-4">

<h4 class="fw-bold mb-2"><?= htmlspecialchars($name); ?></h4>

<div class="role-badge mb-4">
<?php if($role == 'admin'): ?>
<i class="ti ti-shield-check text-primary fs-5"></i> System Admin
<?php else: ?>
<i class="ti ti-user-check text-success fs-5"></i> <?= ucfirst($designation); ?>
<?php endif; ?>
</div>

<?php
/* USER TOTAL LEADS */
$user = $_SESSION['name'];
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM leads WHERE lead_by=?");
$stmt->bind_param("s",$user);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();
$total_leads = $data['total'];
?>

<div class="alert alert-light border mb-3">
<strong>Total Leads Added:</strong> <?= number_format($total_leads); ?>
</div>

<div class="text-start mt-2">

<h6 class="text-muted fw-bold text-uppercase mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">
Contact Information
</h6>

<ul class="info-list">

<li>
<div class="info-list-icon"><i class="ti ti-phone"></i></div>
<div>
<div class="info-label">Contact Number</div>
<div class="info-value"><?= htmlspecialchars($contact_no); ?></div>
</div>
</li>

<li>
<div class="info-list-icon"><i class="ti ti-mail"></i></div>
<div>
<div class="info-label">Email Address</div>
<div class="info-value"><?= htmlspecialchars($email); ?></div>
</div>
</li>

<li>
<div class="info-list-icon"><i class="ti ti-id"></i></div>
<div>
<div class="info-label">Employee ID</div>
<div class="info-value">#EMP-<?= str_pad($user_id,4,'0',STR_PAD_LEFT); ?></div>
</div>
</li>

<li>
<div class="info-list-icon"><i class="ti ti-login"></i></div>
<div>
<div class="info-label">Last Login</div>
<div class="info-value">
<?= $last_login ? date("d M Y, h:i A", strtotime($last_login)) : "First Login"; ?>
</div>
</div>
</li>

</ul>

<div class="mt-4 text-center d-flex justify-content-center flex-wrap gap-2">

    <a href="my-leads.php" class="btn btn-primary btn-sm">
        <i class="ti ti-list"></i> My Leads
    </a>

    <a href="leads.php" class="btn btn-success btn-sm">
        <i class="ti ti-plus"></i> Add Lead
    </a>

    <a href="my-customers.php" class="btn btn-info btn-sm text-white">
        <i class="ti ti-users"></i> My Customers
    </a>

</div>

</div>
</div>
</div>
</div>

<div class="col-xl-8 col-lg-7">

<div class="card-modern">
<div class="p-4 border-bottom">
<h5 class="fw-bold mb-0">
<?= ($role == "admin") ? "System Overview" : "My Performance"; ?>
</h5>
</div>

<div class="p-4">
<div class="row g-3">

<div class="col-md-4 col-sm-6">
<div class="stat-box">
<div class="stat-icon icon-deals">
<i class="ti ti-briefcase"></i>
</div>
<div>
<div class="stat-val"><?= number_format($deals); ?></div>
<div class="stat-label">Total Deals</div>
</div>
</div>
</div>

<div class="col-md-4 col-sm-6">
<div class="stat-box">
<div class="stat-icon icon-leads">
<i class="ti ti-magnet"></i>
</div>
<div>
<div class="stat-val"><?= number_format($leads); ?></div>
<div class="stat-label">Active Leads</div>
</div>
</div>
</div>

<div class="col-md-4 col-sm-12">
<div class="stat-box">
<div class="stat-icon icon-tasks">
<i class="ti ti-checkbox"></i>
</div>
<div>
<div class="stat-val"><?= number_format($tasks); ?></div>
<div class="stat-label">Pending Tasks</div>
</div>
</div>
</div>

</div>
</div>
</div>

</div>

</div>
</div>



<?php include __DIR__ . '/include/footer.php'; ?>