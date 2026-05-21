<?php
// ERROR REPORTING ON (Testing ke liye achha hai)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Session aur Database Setup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// Timezone set kiya
date_default_timezone_set('Asia/Kolkata');

// Security Check
if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized access! Please login first.");
}

/* --- 2. FORM DATA FETCH --- */
$company          = $_POST['company_name'] ?? '';
$department       = $_POST['department'] ?? '';
$person           = $_POST['contact_person'] ?? '';
$number           = $_POST['contact_number'] ?? '';
$email            = $_POST['email'] ?? '';
$location         = $_POST['location'] ?? '';
$type             = $_POST['customer_type'] ?? '';

$lead_by          = $_POST['lead_by'] ?? '';         
$lead_type        = $_POST['lead_type'] ?? '';       
$manager          = $_POST['manager'] ?? '';         
$lead_status      = $_POST['lead_status'] ?? '';

$followed_by      = $_POST['followed_by'] ?? '';     
$support_team     = $_POST['support_team'] ?? '';

$alternate_number = $_POST['alternate_number'] ?? '';
$lead_priority    = $_POST['lead_priority'] ?? '';   

$history_note     = $_POST['history_note'] ?? '';
$followup         = (!empty($_POST['followup_time'])) ? $_POST['followup_time'] : null;

/* =========================================================
   NEW LOGIC: CHECK IF LEAD IS GOVERNMENT
   ========================================================= */
if ($lead_type === 'Government') {
    
    /* --- SAVE TO CUSTOMERS TABLE --- */
    $query_cust = "INSERT INTO customers (
        company_name, 
        customer_name, 
        contact_no, 
        alternate_no, 
        email, 
        address, 
        customer_source, 
        customer_type, 
        status, 
        customer_priority, 
        followed_by, 
        reporting_manager, 
        created_by, 
        support_team
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_cust = $conn->prepare($query_cust);

    if (!$stmt_cust) {
        die("<h3>Customers Table Query Error:</h3>" . $conn->error);
    }

    $stmt_cust->bind_param(
        "ssssssssssssss", 
        $company, 
        $person, 
        $number, 
        $alternate_number, 
        $email, 
        $location, 
        $lead_type, 
        $type, 
        $lead_status, 
        $lead_priority, 
        $followed_by, 
        $manager, 
        $lead_by, 
        $support_team
    );

    if ($stmt_cust->execute()) {
        
        // YAHAN SE NAYA CODE SHURU HOTA HAI (History Save Karne Ke Liye)
        $new_customer_id = $conn->insert_id; // Naye bane customer ki ID nikal li
        $current_user_id = $_SESSION['user_id']; // Jo form bhar raha hai uski ID
        
        // Agar history note form mein dala gaya hai, toh customer_history mein save karo
        if (!empty($history_note)) {
            $stmt_hist = $conn->prepare("INSERT INTO customer_history (customer_id, history_note, followup_time, user_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            if ($stmt_hist) {
                // "issi" matlab: Integer (cust_id), String (note), String (followup), Integer (user_id)
                $stmt_hist->bind_param("issi", $new_customer_id, $history_note, $followup, $current_user_id);
                $stmt_hist->execute();
            }
        }
        // NAYA CODE KHATAM
        
        // Customer save hone aur history lagne par customer list par bhej do
        header("Location: customers-list.php?msg=success");
        exit;
    } else {
        die("<h3>Execution Error (Customers):</h3>" . $conn->error);
    }

} else {

    /* =========================================================
       NORMAL LEADS LOGIC (Aapka Purana Code)
       ========================================================= */
    $query_leads = "INSERT INTO leads (company_name, department, contact_person, contact_number, email, location, customer_type, lead_by, lead_type, manager, lead_status, followed_by, support_team, followup_time, alternate_number, lead_priority) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query_leads);

    if (!$stmt) {
        die("<h3>Leads Table Query Error:</h3>" . $conn->error);
    }

    $stmt->bind_param(
        "ssssssssssssssss", 
        $company, 
        $department, 
        $person, 
        $number, 
        $email, 
        $location, 
        $type, 
        $lead_by, 
        $lead_type, 
        $manager, 
        $lead_status, 
        $followed_by, 
        $support_team, 
        $followup, 
        $alternate_number, 
        $lead_priority
    );

    if ($stmt->execute()) {
        $lead_id = $conn->insert_id;

        /* --- 4. LEAD HISTORY INSERT --- */
        if (!empty($history_note)) {
            $updated_by_name = $_SESSION['name'] ?? $lead_by;
            
            $stmt2 = $conn->prepare("INSERT INTO lead_history (lead_id, history_note, followup_time, updated_by) VALUES (?, ?, ?, ?)");
            if ($stmt2) {
                $stmt2->bind_param("isss", $lead_id, $history_note, $followup, $updated_by_name);
                $stmt2->execute();
            }
        }

        /* --- 5. NOTIFICATION LOGIC --- */
        $creator_name = $_SESSION['name'] ?? 'System';
        $users_to_notify = array_unique(array_filter([$followed_by, $manager]));

        foreach ($users_to_notify as $user_val) {
            if (is_numeric($user_val)) {
                $u_stmt = $conn->prepare("SELECT id, email, name FROM users WHERE id = ? LIMIT 1");
                $u_stmt->bind_param("i", $user_val);
            } else {
                $u_stmt = $conn->prepare("SELECT id, email, name FROM users WHERE name = ? LIMIT 1");
                $u_stmt->bind_param("s", $user_val);
            }
            
            $u_stmt->execute();
            $u_res = $u_stmt->get_result();

            if ($u_row = $u_res->fetch_assoc()) {
                $target_id    = $u_row['id'];
                $target_email = $u_row['email'];
                $target_name  = $u_row['name'];

                $notif_title = "New Lead Alert";
                $notif_msg   = "Lead '$company' created by $creator_name. Status: $lead_status";
                
                if (!empty($followup) && $followup != '0000-00-00 00:00:00') {
                    $formatted_time = date("d M, h:i A", strtotime($followup));
                    $notif_msg .= " | Follow-up: " . $formatted_time;
                    
                    $followup_title = "Follow Up Reminder";
                    $followup_msg = "Follow up reminder for $company at $followup";
                    
                    $n_stmt_follow = $conn->prepare("INSERT INTO notifications (user_id, message, status, title, created_at) VALUES (?, ?, 0, ?, NOW())");
                    if ($n_stmt_follow) {
                        $n_stmt_follow->bind_param("iss", $target_id, $followup_msg, $followup_title);
                        $n_stmt_follow->execute();
                    }
                }

                $n_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, status, title, created_at) VALUES (?, ?, 0, ?, NOW())");
                if ($n_stmt) {
                    $n_stmt->bind_param("iss", $target_id, $notif_msg, $notif_title);
                    $n_stmt->execute();
                }
            }
        }

        header("Location: view-lead.php?id=" . $lead_id . "&msg=success");
        exit;

    } else {
        die("<h3>Execution Error (Leads):</h3>" . $conn->error);
    }
}
?>