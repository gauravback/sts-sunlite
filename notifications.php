<?php
require_once 'config/auth.php';
require_once 'config/database.php';
include 'include/header.php';

$session_user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// --- Helper Function for "Time Ago" ---
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array(
        'y' => 'year', 'm' => 'month', 'w' => 'week', 'd' => 'day',
        'h' => 'hour', 'i' => 'minute', 's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) { $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); } 
        else { unset($string[$k]); }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        --text-main: #1e293b;
        --text-muted: #64748b;
    }
    body { background-color: #f1f5f9; font-family: 'Inter', sans-serif; color: var(--text-main); }
    .page-title { font-weight: 700; background: var(--primary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .filter-card { background: rgba(255, 255, 255, 0.9); border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; backdrop-filter: blur(10px); }
    
    /* Pure card ko link banane ke liye style */
    .notif-link { text-decoration: none !important; color: inherit !important; display: block; }
    .notif-item { background: white; border-radius: 16px; padding: 1.25rem; margin-bottom: 1rem; border: 1px solid #edf2f7; transition: 0.3s; display: flex; align-items: center; gap: 1rem; }
    .notif-item:hover { border-color: #6366f1; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); background-color: #fcfcff; }
    
    .avatar-circle { width: 48px; height: 48px; background: #eef2ff; color: #6366f1; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0; }
    .btn-upgrade { background: var(--primary-gradient); border: none; color: white; border-radius: 10px; padding: 10px 20px; font-weight: 600; }
</style>

<div class="container py-5">
    <div class="row mb-5 align-items-center">
        <div class="col-md-6">
            <h1 class="page-title mb-1">Activity Feed</h1>
            <p class="text-muted"><?= ($role == 'admin') ? "Monitoring all system activities" : "Your latest updates and alerts"; ?></p>
        </div>
        
        <?php if($role == 'admin'): ?>
        <div class="col-md-6 text-md-end">
            <div class="filter-card d-inline-block w-100 w-md-auto">
                <form method="GET" class="d-flex gap-2">
                    <select name="user_id" class="form-select border-0 bg-light" style="border-radius: 10px;">
                        <option value="">All Active Users</option>
                        <?php
                        $users = mysqli_query($conn,"SELECT id, name FROM users");
                        while($u=mysqli_fetch_assoc($users)){
                            $selected = (isset($_GET['user_id']) && $_GET['user_id']==$u['id']) ? "selected" : "";
                            echo "<option value='".$u['id']."' $selected>".htmlspecialchars($u['name'])."</option>";
                        }
                        ?>
                    </select>
                    <button class="btn btn-upgrade">Filter</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <?php
            if($role == 'admin'){
                $where = "";
                if(!empty($_GET['user_id'])){
                    $uid = mysqli_real_escape_string($conn, $_GET['user_id']);
                    $where = "WHERE n.user_id='$uid'";
                }
                // Dhyan de: n.link aapki table mein hona chahiye
                $queryStr = "SELECT n.*, u.name FROM notifications n LEFT JOIN users u ON u.id=n.user_id $where ORDER BY n.created_at DESC";
            } else {
                $queryStr = "SELECT n.*, u.name FROM notifications n LEFT JOIN users u ON u.id=n.user_id WHERE n.user_id='$session_user_id' ORDER BY n.created_at DESC";
            }
            
            $q = mysqli_query($conn, $queryStr);

            if(mysqli_num_rows($q) > 0){
                while($row = mysqli_fetch_assoc($q)){
                    $displayName = ($role == 'admin') ? ($row['name'] ?? 'System') : "Notification";
                    $initial = strtoupper(substr($displayName, 0, 1));
                    
                    // Link fetch karein (Agar empty hai toh '#' use karein)
                    $targetLink = !empty($row['link']) ? $row['link'] : "#";
                    ?>
                    
                    <a href="<?= htmlspecialchars($targetLink) ?>" class="notif-link">
                        <div class="notif-item">
                            <div class="avatar-circle">
                                <?= ($role == 'admin') ? $initial : '<i class="fa-solid fa-bell"></i>' ?>
                            </div>
                            <div class="notif-content flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold" style="font-size: 1rem; color: var(--text-main);">
                                        <?= htmlspecialchars($displayName) ?>
                                    </span>
                                    <span class="text-muted small text-uppercase" style="font-size: 0.7rem;">
                                        <i class="fa-regular fa-clock me-1"></i>
                                        <?= time_elapsed_string($row['created_at']); ?>
                                    </span>
                                </div>
                                <div class="text-muted mt-1" style="font-size: 0.95rem;">
                                    <?= htmlspecialchars($row['message']); ?>
                                </div>
                            </div>
                        </div>
                    </a>

                    <?php
                }
            } else {
                echo '<div class="text-center py-5 opacity-50"><i class="fa-solid fa-ghost fa-3x mb-3"></i><h4>Nothing to see here</h4></div>';
            }
            ?>
        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>