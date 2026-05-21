<?php
session_start();
error_reporting(E_ALL); ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config/database.php';

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']); exit;
    }

    $user_id = $_SESSION['user_id'];
    $row_id = $_POST['row_id'] ?? '';
    $entity_type = $_POST['entity_type'] ?? '';
    $entity_id = $_POST['entity_id'] ?? '';
    $note = $_POST['note'] ?? '';

    // User ka naam nikalna
    $user_query = mysqli_query($conn, "SELECT name FROM users WHERE id = '$user_id'");
    $user_data = mysqli_fetch_assoc($user_query);
    $user_name = $user_data['name'] ?? 'Admin/User';

    if (!isset($_FILES['document_files']) || empty($_FILES['document_files']['name'][0])) {
        echo json_encode(['status' => 'error', 'message' => 'No valid files received.']); exit;
    }

    $upload_dir = __DIR__ . '/uploads/history_docs/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

    $uploaded_links = "";
    $count = 0;

    foreach ($_FILES['document_files']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['document_files']['error'][$key] !== UPLOAD_ERR_OK) continue; 
        
        $original_name = basename($_FILES['document_files']['name'][$key]);
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $safe_name = preg_replace("/[^a-zA-Z0-9]/", "_", pathinfo($original_name, PATHINFO_FILENAME));
        $new_file_name = time() . "_" . $key . "_" . $safe_name . "." . $file_ext;
        
        $target_file = $upload_dir . $new_file_name;
        $db_path = 'uploads/history_docs/' . $new_file_name;

        if (move_uploaded_file($tmp_name, $target_file)) {
            $uploaded_links .= "<div style='margin-top:8px;'>
                <a href='$db_path' target='_blank' style='background:#f0f9ff; color:#0ea5e9; padding:6px 12px; border-radius:6px; text-decoration:none; display:inline-block; font-size:13px; border:1px solid #bae6fd; font-weight:600;'>
                    <i class='ti ti-paperclip'></i> View $original_name
                </a>
            </div>";
            $count++;
        }
    }

    if ($count > 0) {
        // Toggle Switch ko Yes (Light Blue) karna database me
        if(!empty($row_id)) {
            mysqli_query($conn, "UPDATE gov_sales_entries SET doc_upload_status = 'yes' WHERE id = '$row_id'");
        }

        $note_escaped = mysqli_real_escape_string($conn, htmlspecialchars(trim($note)));
        
        // Chat UI with User Name
        $final_history_note = "<div style='background:#f8fafc; padding:10px; border-radius:8px; border:1px solid #e2e8f0;'>";
        $final_history_note .= "<strong style='color:#0ea5e9;'><i class='ti ti-folder'></i> Uploaded $count Document(s)</strong><br>";
        $final_history_note .= "<span style='font-size:12px; color:#64748b;'>Uploaded by: <b>$user_name</b></span>";
        if(!empty($note_escaped)) {
            $final_history_note .= "<div style='margin-top:8px; color:#334155;'>$note_escaped</div>";
        }
        $final_history_note .= $uploaded_links;
        $final_history_note .= "</div>";
        
        $escaped_final_note = mysqli_real_escape_string($conn, $final_history_note);
        
        if ($entity_type === 'lead') {
            $sql = "INSERT INTO lead_history (lead_id, history_note, user_id, created_at) VALUES ('$entity_id', '$escaped_final_note', '$user_id', NOW())";
        } else {
            $sql = "INSERT INTO customer_history (customer_id, history_note, user_id, created_at) VALUES ('$entity_id', '$escaped_final_note', '$user_id', NOW())";
        }

        if (mysqli_query($conn, $sql)) {
            echo json_encode(['status' => 'success', 'message' => "Files saved and status updated to Yes!"]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'DB error: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Files could not be saved.']);
    }

} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'PHP Error: ' . $e->getMessage()]);
}
?>