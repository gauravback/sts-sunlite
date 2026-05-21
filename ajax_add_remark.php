<?php
session_start();
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$entity_type = $_POST['entity_type'] ?? '';
$entity_id = $_POST['entity_id'] ?? '';
$note = $_POST['note'] ?? '';

if (empty($entity_type) || empty($entity_id) || empty(trim($note))) {
    echo json_encode(['status' => 'error', 'message' => 'Remark cannot be empty.']);
    exit;
}

$history_note = mysqli_real_escape_string($conn, trim($note));

// Database ID fixed as user_id based on your screenshot
if ($entity_type === 'lead') {
    $sql = "INSERT INTO lead_history (lead_id, history_note, user_id, created_at) 
            VALUES ('$entity_id', '$history_note', '$user_id', NOW())";
} else {
    $sql = "INSERT INTO customer_history (customer_id, history_note, user_id, created_at) 
            VALUES ('$entity_id', '$history_note', '$user_id', NOW())";
}

if (mysqli_query($conn, $sql)) {
    echo json_encode(['status' => 'success', 'message' => 'Remark added successfully!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>