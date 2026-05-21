<?php
// Session start taaki logged-in user ki ID mil sake
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

require_once 'config/database.php';

// Agar user login nahi hai toh seedha 0 bhej do
if(!isset($_SESSION['user_id'])){
    header('Content-Type: application/json');
    echo json_encode(['unread_count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

/* LOGIC CHANGE: 
   Humne Admin wala 'if-else' hata diya hai. 
   Chahe user Admin ho ya Sales, badge par sirf USI ki notifications 
   ka count aana chahiye jo uske liye 'user_id' se tagged hain.
*/

$query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = '$user_id' AND status = 0";
$count_q = mysqli_query($conn, $query);

if($count_q) {
    $count_res = mysqli_fetch_assoc($count_q);
    $final_count = (int)($count_res['total'] ?? 0);
} else {
    $final_count = 0;
}

// Browser ko batao ki ye JSON data hai
header('Content-Type: application/json');
echo json_encode(['unread_count' => $final_count]);
exit;