<?php
// 1. Session start karna zaroori hai user ki ID lene ke liye
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

require_once 'config/database.php';

// Check karo ki user login hai ya nahi
if(!isset($_SESSION['user_id'])){
    exit;
}

$user_id = $_SESSION['user_id'];

/* LOGIC FIX:
   Chahe banda Admin ho ya Sales, jab wo bell par click karega 
   toh sirf USKI ID wali notifications status 1 (Read) honi chahiye.
   
   Isse ye hoga:
   - Manager ne click kiya -> Manager ki rows status 1 hui (Manager ka count 0).
   - Sales waale ki row status 0 hi rahi (Sales waale ka badge active rahega).
*/

$update_query = "UPDATE notifications SET status = 1 WHERE user_id = '$user_id' AND status = 0";

if(mysqli_query($conn, $update_query)){
    // JSON response taaki frontend ko pata chale kaam ho gaya
    echo json_encode(['status' => 'success', 'message' => 'Your notifications marked as read']);
} else {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
}

exit;
?>