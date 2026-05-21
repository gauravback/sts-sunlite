<?php
session_start();
error_reporting(0); // Hide direct PHP errors for clean JSON
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config/database.php';

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired.']); exit;
    }

    $user_id = $_SESSION['user_id'];
    $row_id = $_POST['row_id'] ?? '';
    $entity_type = $_POST['entity_type'] ?? '';
    $entity_id = $_POST['entity_id'] ?? '';

    // User ka naam nikalna session ke liye (Log me dikhane ke liye)
    $user_query = mysqli_query($conn, "SELECT name FROM users WHERE id = '$user_id'");
    $user_data = mysqli_fetch_assoc($user_query);
    $user_name = $user_data['name'] ?? 'Admin/User';

    // Update Status to NO in Main Table
    $update_sql = "UPDATE gov_sales_entries SET doc_upload_status = 'no' WHERE id = '$row_id'";
    mysqli_query($conn, $update_sql);

    // Chat Style Log formatting
    $history_note = "<div style='background:#fff1f2; padding:10px; border-radius:8px; border:1px solid #fecdd3; color:#be123c;'>";
    $history_note .= "<strong><i class='ti ti-alert-circle'></i> Document Status Changed to NO</strong><br>";
    $history_note .= "<span style='font-size:12px; color:#881337;'>Action performed by: <b>$user_name</b></span>";
    $history_note .= "</div>";
    
    $escaped_note = mysqli_real_escape_string($conn, $history_note);

    if ($entity_type === 'lead') {
        $sql = "INSERT INTO lead_history (lead_id, history_note, user_id, created_at) VALUES ('$entity_id', '$escaped_note', '$user_id', NOW())";
    } else {
        $sql = "INSERT INTO customer_history (customer_id, history_note, user_id, created_at) VALUES ('$entity_id', '$escaped_note', '$user_id', NOW())";
    }

    mysqli_query($conn, $sql);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>