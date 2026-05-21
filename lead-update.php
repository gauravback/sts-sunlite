<?php
// ERROR REPORTING ON (Agar fir bhi white screen aaye toh exact error dikhega)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session start karna zaroori hai admin/user check ke liye
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Indian Timezone set kar diya taaki reminders sahi time par jayein
date_default_timezone_set('Asia/Kolkata');

require_once 'config/auth.php';
require_once 'config/database.php';

// --- PHPMailer Setup ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';

/* CHECK ID */
if(!isset($_GET['id']) || empty($_GET['id'])){
    include 'include/header.php';
    echo "<div class='container mt-4'><div class='alert alert-danger'>Lead not found or ID missing.</div></div>";
    exit;
}

$id = intval($_GET['id']);

/* FETCH USERS FOR DROPDOWNS */
$user_query = "SELECT id, name, email FROM users ORDER BY name ASC";
$user_result = mysqli_query($conn, $user_query);
$users_list = [];
if($user_result && mysqli_num_rows($user_result) > 0) {
    while($u = mysqli_fetch_assoc($user_result)) {
        $users_list[] = $u;
    }
}

/* FETCH LEAD DETAILS */
$stmt = $conn->prepare("SELECT * FROM leads WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$result = $stmt->get_result();
$lead = $result->fetch_assoc();

if(!$lead){
    include 'include/header.php';
    echo "<div class='container mt-4'><div class='alert alert-warning'>Lead not found.</div></div>";
    exit;
}

/* SAFE VARIABLES FOR FORM DISPLAY */
$contact_person   = htmlspecialchars($lead['contact_person'] ?? '');
$phone            = htmlspecialchars($lead['contact_number'] ?? '');
$email            = htmlspecialchars($lead['email'] ?? '');
$company_name     = htmlspecialchars($lead['company_name'] ?? '');
$customer_type    = htmlspecialchars($lead['customer_type'] ?? '');
$location         = htmlspecialchars($lead['location'] ?? '');
$alternate_number = htmlspecialchars($lead['alternate_number'] ?? '');
$assigned_by      = htmlspecialchars($lead['lead_by'] ?? '');
$lead_type        = htmlspecialchars($lead['lead_type'] ?? ''); 
$reporting_manager= htmlspecialchars($lead['manager'] ?? '');
$status           = htmlspecialchars($lead['lead_status'] ?? '');
$lead_priority    = htmlspecialchars($lead['lead_priority'] ?? ''); 
$created_at       = htmlspecialchars($lead['created_at'] ?? '');
$followed_by      = htmlspecialchars($lead['followed_by'] ?? '');
$support_team     = htmlspecialchars($lead['support_team'] ?? '');
$followup_date    = $lead['followup_time'] ?? '';
$is_customer      = intval($lead['is_customer'] ?? 0); 

if(empty($reporting_manager)) {
    $reporting_manager = $assigned_by;
}

$followup_formatted = (!empty($followup_date) && $followup_date != "0000-00-00 00:00:00")
    ? date('Y-m-d\TH:i', strtotime($followup_date))
    : '';

/* ================= SINGLE FORM SUBMIT (UPDATE + SALE) ================= */
if(isset($_POST['update_lead'])){

    $new_assigned_by    = trim($_POST['assigned_by'] ?? '');
    $category           = trim($_POST['lead_type'] ?? '');
    $manager            = trim($_POST['reporting_manager'] ?? '');
    $new_status         = trim($_POST['status'] ?? '');
    $new_lead_priority  = trim($_POST['lead_priority'] ?? '');
    $new_customer_type  = trim($_POST['customer_type'] ?? '');
    $message            = trim($_POST['message'] ?? ''); 
    $new_followed_by    = trim($_POST['followed_by'] ?? '');
    $new_support_team   = trim($_POST['support_team'] ?? '');
    $db_followup        = !empty($_POST['followup_time']) ? $_POST['followup_time'] : NULL;
    $updated_by         = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'System';
    $updated_by_id      = $_SESSION['user_id'] ?? 0;

    $is_new_sale = false;
    $attachment_path = NULL;
    $sale_amount_for_history = 0;
    $invoice_no_for_history = '';

    /* 1. UPDATE LEAD TABLE */
    $update_stmt = $conn->prepare("UPDATE leads SET lead_by=?, lead_type=?, manager=?, lead_status=?, lead_priority=?, customer_type=?, followed_by=?, support_team=?, followup_time=? WHERE id=?");
    if($update_stmt) {
        $update_stmt->bind_param("sssssssssi", $new_assigned_by, $category, $manager, $new_status, $new_lead_priority, $new_customer_type, $new_followed_by, $new_support_team, $db_followup, $id);
        $update_stmt->execute();
        $update_stmt->close();
    }

    /* 2. CHECK FOR NEW SALE LOGIC & FILE UPLOAD */
    if ($new_status === 'Sale') {
        
        // --- GOVERNMENT SALE LOGIC ---
        if ($category === 'Government') {
            $gem_contact_no     = trim($_POST['gem_contact_no'] ?? '');
            $contract_date      = trim($_POST['contract_date'] ?? '');
            $delivery_last_date = trim($_POST['delivery_last_date'] ?? '');
            $department_name    = trim($_POST['department_name'] ?? '');
            $gov_company        = trim($_POST['gov_company'] ?? '');
            $product            = trim($_POST['product'] ?? '');
            $quantity           = trim($_POST['quantity'] ?? '');
            $gov_amount         = trim($_POST['gov_amount'] ?? '');

            if (!empty($gem_contact_no) && !empty($gov_amount)) {
                // Hum check kar rahe hain ki ye exact same sale na ho (Unique GeM No)
                $check_sale = mysqli_query($conn, "SELECT id FROM gov_sales_entries WHERE entity_type='lead' AND entity_id=$id AND gem_contact_no='$gem_contact_no'");
                if ($check_sale && mysqli_num_rows($check_sale) == 0) {
                    $is_new_sale = true;
                    
                    $gem_file_path = NULL;
                    $bid_file_path = NULL;
                    $upload_dir = 'uploads/gov_sales/';
                    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                    
                    if (isset($_FILES['gem_contract_file']) && $_FILES['gem_contract_file']['error'] == 0) {
                        $gem_file_path = $upload_dir . time() . "_gem_" . basename($_FILES['gem_contract_file']['name']);
                        move_uploaded_file($_FILES['gem_contract_file']['tmp_name'], $gem_file_path);
                    }
                    if (isset($_FILES['bid_file']) && $_FILES['bid_file']['error'] == 0) {
                        $bid_file_path = $upload_dir . time() . "_bid_" . basename($_FILES['bid_file']['name']);
                        move_uploaded_file($_FILES['bid_file']['tmp_name'], $bid_file_path);
                    }

                    $sale_stmt = $conn->prepare("INSERT INTO gov_sales_entries (entity_type, entity_id, gem_contact_no, contract_date, delivery_last_date, department_name, company, product, quantity, amount, gem_contract_file, bid_file, created_by) VALUES ('lead', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    if ($sale_stmt) {
                        $sale_stmt->bind_param("issssssssssi", $id, $gem_contact_no, $contract_date, $delivery_last_date, $department_name, $gov_company, $product, $quantity, $gov_amount, $gem_file_path, $bid_file_path, $updated_by_id);
                        $sale_stmt->execute();
                        $sale_stmt->close();
                    }

                    $sale_amount_for_history = $gov_amount;
                    $invoice_no_for_history = "GeM No: " . $gem_contact_no;
                    $attachment_path = $gem_file_path;
                }
            }
        } 
        // --- NORMAL SALE LOGIC ---
        else {
            $invoice_no = trim($_POST['invoice_no'] ?? '');
            $sale_date  = trim($_POST['sale_date'] ?? '');
            $amount     = trim($_POST['amount'] ?? '');

            if (!empty($invoice_no) && !empty($amount)) {
                // Hum check kar rahe hain ki ye exact same sale na ho (Unique Invoice No)
                $check_sale = mysqli_query($conn, "SELECT id FROM sales_entries WHERE entity_type='lead' AND entity_id=$id AND invoice_no='$invoice_no'");
                if ($check_sale && mysqli_num_rows($check_sale) == 0) {
                    $is_new_sale = true;
                    
                    if (isset($_FILES['sale_attachment']) && $_FILES['sale_attachment']['error'] == 0) {
                        $upload_dir = 'uploads/sales/';
                        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                        $file_ext = strtolower(pathinfo($_FILES['sale_attachment']['name'], PATHINFO_EXTENSION));
                        $file_name = time() . '_' . rand(1000,9999) . '.' . $file_ext;
                        $target_file = $upload_dir . $file_name;
                        if (move_uploaded_file($_FILES['sale_attachment']['tmp_name'], $target_file)) {
                            $attachment_path = $target_file;
                        }
                    }

                    $sale_stmt = $conn->prepare("INSERT INTO sales_entries (entity_type, entity_id, invoice_no, sale_date, amount, attachment, created_by, created_at) VALUES ('lead', ?, ?, ?, ?, ?, ?, NOW())");
                    if($sale_stmt){
                        $sale_stmt->bind_param("isssss", $id, $invoice_no, $sale_date, $amount, $attachment_path, $updated_by);
                        $sale_stmt->execute();
                        $sale_stmt->close();
                    }

                    $sale_amount_for_history = $amount;
                    $invoice_no_for_history = $invoice_no;
                }
            }
        }
    }

    /* 3. CREATE HISTORY NOTE */
    if ($is_new_sale) {
        $doc_link = !empty($attachment_path) ? " | <b>Doc:</b> <a href='$attachment_path' target='_blank'>View File</a>" : "";
        $history_note = "🎉 <b>CONGRATULATIONS! Additional Sale Closed.</b><br><b>Invoice/GeM:</b> $invoice_no_for_history | <b>Amount:</b> ₹$sale_amount_for_history $doc_link <br><b>User Note:</b> " . $message;
    } elseif ($lead['lead_status'] !== $new_status) {
        $history_note = "[Status Updated to: ".$new_status."] " . $message;
    } elseif ($lead['lead_priority'] !== $new_lead_priority && !empty($new_lead_priority)) {
        $history_note = "[Priority Updated to: ".$new_lead_priority."] " . $message;
    } else {
        $history_note = $message;
    }

    /* SAVE TO HISTORY TABLE */
    if(!empty($history_note)){
        $stmt3 = $conn->prepare("INSERT INTO lead_history (lead_id, history_note, followup_time, updated_by) VALUES (?,?,?,?)");
        if($stmt3){
            $stmt3->bind_param("isss", $id, $history_note, $db_followup, $updated_by);
            $stmt3->execute();
            $stmt3->close();
        }
    }

    // =========================================================================
    // PROFESSIONAL NOTIFICATION & EMAIL LOGIC
    // =========================================================================
    $notif_targets = [];
    $admin_q = mysqli_query($conn, "SELECT id, name, email FROM users WHERE role = 'admin'");
    while($adm = mysqli_fetch_assoc($admin_q)){ 
        if(!empty($adm['email'])) { $notif_targets[trim($adm['name'])] = ['id' => $adm['id'], 'email' => trim($adm['email'])]; }
    }

    $safe_manager  = mysqli_real_escape_string($conn, trim($manager));
    $safe_assigned = mysqli_real_escape_string($conn, trim($assigned_by));
    $safe_followed = mysqli_real_escape_string($conn, trim($new_followed_by));

    $query_extra = mysqli_query($conn, "SELECT id, name, email FROM users WHERE TRIM(name) IN ('$safe_followed', '$safe_manager', '$safe_assigned')");
    if($query_extra){
        while($extra = mysqli_fetch_assoc($query_extra)){ 
            if(!empty($extra['email'])) { $notif_targets[trim($extra['name'])] = ['id' => $extra['id'], 'email' => trim($extra['email'])]; }
        }
    }

    foreach($notif_targets as $target_name => $target_data) {
        if(strtolower(trim($target_name)) === strtolower(trim($updated_by))) continue;

        $target_id = $target_data['id'];
        $target_email = $target_data['email'];

        if ($is_new_sale) {
            $notif_title = "🎉 New Sale Closed!";
            $notif_msg = "Congratulations! $updated_by just closed a sale for '$company_name'. Amount: ₹$sale_amount_for_history";
            $mail_subject = "🎉 CONGRATULATIONS: New Sale Closed - $company_name";
        } else {
            $notif_title = "Lead Updated";
            $notif_msg = "Lead: '" . $company_name . "' updated by " . $updated_by . ". Status: " . $new_status;
            $mail_subject = (!empty($db_followup)) ? "Action Required: Lead Follow-up - $company_name" : "CRM Update: Status changed for $company_name";
        }

        $notif_link = "lead-update.php?id=" . $id;
        
        $n_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, status, title, link, created_at) VALUES (?, ?, 0, ?, ?, NOW())");
        if($n_stmt){
            $n_stmt->bind_param("isss", $target_id, $notif_msg, $notif_title, $notif_link);
            $n_stmt->execute();
            $n_stmt->close();
        }

        if(!empty($target_email)) {
            $lead_url = "https://sts.sunlitesystems.com/lead-update.php?id=" . $id;
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'sts@ssplworld.com';
                $mail->Password   = 'nyjb bkvm kseg slfg'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                $mail->CharSet    = 'UTF-8'; 
                $mail->setFrom('sts@ssplworld.com', 'STS CRM System');
                $mail->addAddress($target_email, $target_name);
                $mail->isHTML(true);
                $mail->Subject = $mail_subject;
                
                $email_html = "
                <div style='font-family: \"Segoe UI\", Helvetica, Arial, sans-serif; background-color: #f4f7fe; padding: 40px 20px; text-align: center;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: left;'>
                        <div style='background-color: " . ($is_new_sale ? '#10b981' : '#4318ff') . "; padding: 25px 20px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0; font-size: 22px; font-weight: 600; letter-spacing: 0.5px;'>STS CRM System</h2>
                        </div>
                        <div style='padding: 35px 30px;'>
                            <h3 style='color: #1b2559; margin-top: 0; font-size: 19px; font-weight: 600;'>Dear " . htmlspecialchars($target_name) . ",</h3>";
                            
                if ($is_new_sale) {
                    $email_html .= "
                            <div style='background-color: #ecfdf5; border-left: 4px solid #10b981; padding: 15px 20px; margin-bottom: 25px; border-radius: 4px;'>
                                <p style='margin: 0 0 5px 0; font-weight: 700; color: #065f46; font-size: 15px;'>🎉 A NEW SALE HAS BEEN CLOSED!</p>
                                <p style='margin: 0; color: #047857; font-size: 14px;'><strong>$updated_by</strong> successfully converted the lead into a sale.</p>
                            </div>";
                } elseif(!empty($db_followup) && $db_followup != '0000-00-00 00:00:00') {
                    $f_time = date("d M Y, h:i A", strtotime($db_followup));
                    $email_html .= "
                            <div style='background-color: #fff8e1; border-left: 4px solid #ffc107; padding: 15px 20px; margin-bottom: 25px; border-radius: 4px;'>
                                <p style='margin: 0 0 5px 0; font-weight: 700; color: #b45309; font-size: 13px; letter-spacing: 0.5px;'>ACTION REQUIRED: UPCOMING FOLLOW-UP</p>
                                <p style='margin: 0; color: #78350f; font-size: 15px;'>Meeting/Follow-up scheduled for <strong style='color: #d9534f;'>" . $f_time . "</strong>.</p>
                            </div>";
                } else {
                    $email_html .= "<p style='color: #4b5563; font-size: 15px; line-height: 1.6; margin-bottom: 25px;'>A lead associated with your portfolio has been recently updated.</p>";
                }

                $email_html .= "
                            <div style='background-color: #f8fafc; border: 1px solid #e0e5f2; border-radius: 8px; padding: 25px;'>
                                <table style='width: 100%; border-collapse: collapse; font-size: 15px;'>
                                    <tr>
                                        <td style='padding: 12px 0; color: #64748b; font-weight: 600; width: 45%; border-bottom: 1px solid #e0e5f2;'>Company Name</td>
                                        <td style='padding: 12px 0; color: #1b2559; font-weight: 700; border-bottom: 1px solid #e0e5f2;'>" . htmlspecialchars($company_name) . "</td>
                                    </tr>";
                
                if ($is_new_sale) {
                    $email_html .= "
                                    <tr>
                                        <td style='padding: 12px 0; color: #64748b; font-weight: 600; border-bottom: 1px solid #e0e5f2;'>Invoice/GeM Number</td>
                                        <td style='padding: 12px 0; color: #10b981; font-weight: 700; border-bottom: 1px solid #e0e5f2;'>" . htmlspecialchars($invoice_no_for_history) . "</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 12px 0; color: #64748b; font-weight: 600; border-bottom: 1px solid #e0e5f2;'>Sale Amount</td>
                                        <td style='padding: 12px 0; color: #1b2559; font-weight: 700; border-bottom: 1px solid #e0e5f2;'>₹" . htmlspecialchars($sale_amount_for_history) . "</td>
                                    </tr>";
                }

                $email_html .= "
                                    <tr>
                                        <td style='padding: 12px 0; color: #64748b; font-weight: 600; border-bottom: 1px solid #e0e5f2;'>Current Status</td>
                                        <td style='padding: 12px 0; border-bottom: 1px solid #e0e5f2;'>
                                            <span style='background-color: #e0e7ff; color: #4318ff; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 13px;'>" . htmlspecialchars($new_status) . "</span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div style='text-align: center; margin-top: 40px;'>
                                <a href='" . $lead_url . "' style='background-color: " . ($is_new_sale ? '#10b981' : '#4318ff') . "; color: #ffffff; padding: 14px 35px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; display: inline-block; transition: 0.3s;'>View Lead in CRM</a>
                            </div>
                        </div>
                    </div>
                </div>";

                $mail->Body = $email_html;
                $mail->send();
            } catch (Exception $e) {
                error_log("PHPMailer Error: " . $mail->ErrorInfo);
            }
        }
    }

    if ($is_new_sale) {
        if ($category === 'Government') {
            header("Location: gov-sales.php?msg=sale_locked"); 
        } else {
            header("Location: sales-list.php?msg=sale_locked");
        }
        exit();
    } else {
        header("Location: lead-update.php?id=$id&msg=updated");
        exit();
    }
}

// ==== HTML Design & Header Starts FROM HERE ====
include 'include/header.php';

/* ================= FETCH HISTORY FOR TABLE ================= */
$hist_stmt = $conn->prepare("SELECT * FROM lead_history WHERE lead_id=? ORDER BY id DESC");
if($hist_stmt) {
    $hist_stmt->bind_param("i", $id);
    $hist_stmt->execute();
    $history_result = $hist_stmt->get_result();
    $history = [];
    if($history_result) {
        while($row = $history_result->fetch_assoc()){
            $history[] = $row;
        }
    }
    $hist_stmt->close();
}

/* ================= FETCH EXISTING SALE DATA IF ANY ================= */
$is_already_sold_normal = false;
$is_already_sold_gov = false;

// Variables for Normal
$saved_invoice = ''; $saved_date = ''; $saved_amount = ''; $saved_attachment = '';
// Variables for Gov
$saved_gem_no = ''; $saved_contract_date = ''; $saved_delivery_date = ''; $saved_dept = ''; $saved_gov_company = ''; $saved_product = ''; $saved_qty = ''; $saved_gov_amount = ''; $saved_gem_file = ''; $saved_bid_file = '';

if ($lead_type === 'Government') {
    $ex_gov = $conn->prepare("SELECT * FROM gov_sales_entries WHERE entity_type='lead' AND entity_id=? ORDER BY id DESC LIMIT 1");
    if($ex_gov) {
        $ex_gov->bind_param("i", $id);
        $ex_gov->execute();
        $gov_res_obj = $ex_gov->get_result();
        if ($gov_res_obj) {
            $gov_res = $gov_res_obj->fetch_assoc();
            if ($gov_res) {
                $is_already_sold_gov = true;
                $saved_gem_no = $gov_res['gem_contact_no'] ?? '';
                $saved_contract_date = $gov_res['contract_date'] ?? '';
                $saved_delivery_date = $gov_res['delivery_last_date'] ?? '';
                $saved_dept = $gov_res['department_name'] ?? '';
                $saved_gov_company = $gov_res['company'] ?? '';
                $saved_product = $gov_res['product'] ?? '';
                $saved_qty = $gov_res['quantity'] ?? '';
                $saved_gov_amount = $gov_res['amount'] ?? '';
                $saved_gem_file = $gov_res['gem_contract_file'] ?? '';
                $saved_bid_file = $gov_res['bid_file'] ?? '';
            }
        }
        $ex_gov->close();
    }
} else {
    $ex_sale_stmt = $conn->prepare("SELECT invoice_no, sale_date, amount, attachment FROM sales_entries WHERE entity_type='lead' AND entity_id=? ORDER BY id DESC LIMIT 1");
    if($ex_sale_stmt) {
        $ex_sale_stmt->bind_param("i", $id);
        $ex_sale_stmt->execute();
        $ex_sale_res = $ex_sale_stmt->get_result();
        if($ex_sale_res) {
            $existing_sale = $ex_sale_res->fetch_assoc();
            if ($existing_sale) {
                $is_already_sold_normal = true;
                $saved_invoice = $existing_sale['invoice_no'] ?? '';
                $saved_date = $existing_sale['sale_date'] ?? '';
                $saved_amount = $existing_sale['amount'] ?? '';
                $saved_attachment = $existing_sale['attachment'] ?? '';
            }
        }
        $ex_sale_stmt->close();
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<style>
:root { --primary:#4318ff; --bg-main:#f4f7fe; --card-bg:#ffffff; --text-dark:#1b2559; --border-color:#e0e5f2; --danger: #ff5b5c; }
body{ background:var(--bg-main); font-family:'Plus Jakarta Sans',sans-serif; color:var(--text-dark); }
.form-container{ margin:auto; }
.glass-card{ background:var(--card-bg); border-radius:20px; box-shadow:0px 20px 50px rgba(0,0,0,0.05); padding:35px; margin-bottom:30px; }
.custom-label{ font-size:0.85rem; font-weight:700; margin-bottom:8px; display:block; }
.input-group-custom{ position:relative; }
.input-group-custom i{ position:absolute; left:15px; top:50%; transform:translateY(-50%); color:#a3aed0; z-index: 10; }
.custom-input{ border:1px solid var(--border-color); border-radius:12px; padding:12px 15px 12px 45px; background:#f8fafc; width:100%; font-size:0.95rem; }
select.custom-input-no-icon { padding-left: 15px; }
.custom-input:focus{ outline:none; border-color:var(--primary); background:#fff; box-shadow:0 0 0 4px rgba(67,24,255,0.1); }
.readonly-field{ background:#e2e8f0; cursor:not-allowed; color: #6c757d; }
.field-disabled { background-color: #f1f5f9 !important; color: #94a3b8 !important; cursor: not-allowed; border-color: #e2e8f0 !important; }
.section-title{ font-size:1.1rem; font-weight:700; color:var(--primary); margin:25px 0 20px 0; display:flex; align-items:center; }
.section-title::after{ content:""; flex:1; height:1px; background:var(--border-color); margin-left:15px; }
.btn-primary-custom{ background:var(--primary); color:#fff; border:none; border-radius:12px; padding:14px 30px; font-weight:600; box-shadow:0 10px 20px rgba(67,24,255,0.2); cursor: pointer; transition: all 0.3s; }
.btn-primary-custom:hover{ opacity:.9; transform: translateY(-2px); }
.btn-success-custom{ background:#28a745; color:#fff; border:none; border-radius:12px; padding:14px 30px; font-weight:600; box-shadow:0 10px 20px rgba(40, 167, 69, 0.2); cursor: pointer; text-decoration: none; transition: all 0.3s; }
.btn-success-custom:hover{ opacity:.9; transform: translateY(-2px); color: #fff; }
.history-box { background: #f8fafc; padding: 20px; border-radius: 12px; max-height: 300px; overflow-y: auto; border: 1px solid var(--border-color); }
.history-item { border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px; }
.history-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
#loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.85); z-index: 9999; display: none; align-items: center; justify-content: center; flex-direction: column; }
.spinner-ui { border: 5px solid #f3f3f3; border-top: 5px solid var(--primary); border-radius: 50%; width: 50px; height: 50px; animation: spin-ui 1s linear infinite; }
@keyframes spin-ui { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.loading-text { margin-top: 15px; font-weight: 600; color: var(--text-dark); }
input[type=file]::file-selector-button { border: none; background: #e0e5f2; padding: 6px 12px; border-radius: 8px; color: #1b2559; cursor: pointer; transition: background .2s ease-in-out; margin-right: 10px; }
input[type=file]::file-selector-button:hover { background: #cbd5e1; }
</style>

<div id="loading-overlay">
    <div class="spinner-ui"></div>
    <div class="loading-text">Processing & Sending Notifications...</div>
</div>

<div class="container-fluid py-5">
    <div class="form-container">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold m-0 text-dark">Update Lead Details</h2>
                <p class="text-muted">Review and update the lead information below.</p>
            </div>
            <a href="leads-list.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="ti ti-arrow-left me-1"></i> Back to Leads
            </a>
        </div>

        <form method="POST" id="leadUpdateForm" enctype="multipart/form-data">
            <div class="glass-card mb-0" style="border-bottom-left-radius: 0; border-bottom-right-radius: 0; border-bottom: 1px dashed #e2e8f0; box-shadow: none;">
                <div class="section-title">Customer Information</div>
                
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="custom-label">Contact Person</label>
                        <div class="input-group-custom">
                            <i class="ti ti-user"></i>
                            <input type="text" class="custom-input readonly-field" value="<?= $contact_person ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Phone Number</label>
                        <div class="input-group-custom">
                            <i class="ti ti-phone"></i>
                            <input type="text" class="custom-input readonly-field" value="<?= $phone ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Email Address</label>
                        <div class="input-group-custom">
                            <i class="ti ti-mail"></i>
                            <input type="text" class="custom-input readonly-field" value="<?= $email ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="custom-label">Company Name</label>
                        <div class="input-group-custom">
                            <i class="ti ti-building"></i>
                            <input type="text" class="custom-input readonly-field" value="<?= $company_name ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Location</label>
                        <div class="input-group-custom">
                            <i class="ti ti-map-pin"></i>
                            <input type="text" class="custom-input readonly-field" value="<?= $location ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Alternate No.</label>
                        <div class="input-group-custom">
                            <i class="ti ti-phone-plus"></i>
                            <input type="text" class="custom-input readonly-field" value="<?= $alternate_number ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="section-title">Internal Assignment & Status</div>

                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <label class="custom-label">Lead Source</label>
                        <select name="lead_type" id="leadSourceDropdown" class="custom-input custom-input-no-icon">
                            <option value="Corporate" <?= ($lead_type=='Corporate')?'selected':'' ?>>Corporate</option>
                            <option value="Government" <?= ($lead_type=='Government')?'selected':'' ?>>Government</option>
                            <option value="Dealer" <?= ($lead_type=='Dealer')?'selected':'' ?>>Dealer</option>
                            <option value="End User" <?= ($lead_type=='End User')?'selected':'' ?>>End User</option>
                            <option value="Education" <?= ($lead_type=='Education')?'selected':'' ?>>Education</option>
                            <option value="Retailor" <?= ($lead_type=='Retailor')?'selected':'' ?>>Retailor</option>
                            <option value="Online" <?= ($lead_type=='Online')?'selected':'' ?>>Online</option>
                            <option value="Other" <?= ($lead_type=='Other')?'selected':'' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="custom-label">Customer Type</label>
                        <div class="input-group-custom">
                            <i class="ti ti-users"></i>
                            <select name="customer_type" id="customerTypeDropdown" class="custom-input">
                                <option value="New Customer" <?= ($customer_type=='New Customer')?'selected':'' ?>>New Customer</option>
                                <option value="Existing Customer" <?= ($customer_type=='Existing Customer')?'selected':'' ?>>Existing Customer</option>
                                <option value="Dealer" <?= ($customer_type=='Dealer')?'selected':'' ?>>Dealer</option>
                                <option value="Online" <?= ($customer_type=='Online')?'selected':'' ?>>Online</option>
                                <option value="Call" <?= ($customer_type=='Call')?'selected':'' ?>>Call</option>
                                <option value="Meeting" <?= ($customer_type=='Meeting')?'selected':'' ?>>Meeting</option>
                                <option value="Demo" <?= ($customer_type=='Demo')?'selected':'' ?>>Demo</option>
                                <option value="Refrence" <?= ($customer_type=='Refrence')?'selected':'' ?>>Refrence</option>
                                <option value="Other" <?= ($customer_type=='Other')?'selected':'' ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="custom-label text-primary">Current Status <span class="text-danger">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-chart-bar text-primary"></i>
                            <select name="status" id="leadStatusDropdown" class="custom-input" style="border-color: var(--primary);">
                                <?php 
                                $status_options = ['New Lead', 'Follow-up', 'Won', 'Lost', 'Meeting', 'Closed', 'Mature', 'Sale', 'Calling', 'Work in Progress', 'Payment'];
                                foreach($status_options as $opt){
                                    $selected = ($status == $opt) ? "selected" : "";
                                    echo "<option value='$opt' $selected>$opt</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="custom-label">Lead Priority</label>
                        <div class="input-group-custom">
                            <i class="ti ti-chart-bar"></i>
                            <select name="lead_priority" class="custom-input">
                                <option value="Hot" <?= ($lead_priority=='Hot')?'selected':'' ?>>Hot</option>
                                <option value="Cold" <?= ($lead_priority=='Cold')?'selected':'' ?>>Cold</option>
                                <option value="Normal" <?= ($lead_priority=='Normal')?'selected':'' ?>>Normal</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="custom-label">Followed By</label>
                        <div class="input-group-custom">
                            <i class="ti ti-user-share"></i>
                            <select name="followed_by" class="custom-input">
                                <option value="">Select User</option>
                                <?php foreach($users_list as $u): ?>
                                    <?php $isFollowed = (trim(strtolower($followed_by)) == trim(strtolower($u['name']))) ? 'selected' : ''; ?>
                                    <option value="<?= htmlspecialchars($u['name']) ?>" <?= $isFollowed ?>><?= htmlspecialchars($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="custom-label">Reporting Manager</label>
                        <div class="input-group-custom">
                            <i class="ti ti-user-circle"></i>
                            <input type="text" name="reporting_manager" class="custom-input readonly-field" value="<?= $reporting_manager ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="custom-label">Lead Created By (Assigned By)</label>
                        <div class="input-group-custom">
                            <i class="ti ti-user-check"></i>
                            <input type="text" name="assigned_by" class="custom-input readonly-field" value="<?= $assigned_by ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="custom-label">Support Team</label>
                        <div class="input-group-custom">
                            <i class="ti ti-headset"></i>
                            <select name="support_team" class="custom-input">
                                <option value="">Select Team Member</option>
                                <?php foreach($users_list as $u): ?>
                                    <?php $isSupport = (trim(strtolower($support_team)) == trim(strtolower($u['name']))) ? 'selected' : ''; ?>
                                    <option value="<?= htmlspecialchars($u['name']) ?>" <?= $isSupport ?>><?= htmlspecialchars($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="custom-label">Lead Initiation Date</label>
                        <div class="input-group-custom">
                            <i class="ti ti-calendar-event"></i>
                            <input type="text" class="custom-input readonly-field" value="<?= $created_at ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="custom-label text-primary">Scheduled Follow-up</label>
                        <div class="input-group-custom">
                            <i class="ti ti-bell-ringing text-primary"></i>
                            <input type="datetime-local" name="followup_time" class="custom-input" style="border-color: var(--primary);" value="<?= $followup_formatted ?>">
                        </div>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-12">
                        <label class="custom-label text-primary">Activity Note / Update Remarks <span class="text-danger">*</span></label>
                        <textarea name="message" class="custom-input custom-input-no-icon" rows="3" placeholder="Enter reason for edit, or latest conversation details..." style="border-radius: 12px; border-color: var(--primary);" required></textarea>
                    </div>
                </div>
            </div>

            <div id="saleDetailsCard" class="glass-card mb-0" style="display: none; border-top-left-radius: 0; border-top-right-radius: 0; border-top: 4px solid #10b981; background: #f8fafc;">
                <div class="d-flex align-items-center mb-4">
                    <div style="background: #d1fae5; padding: 12px; border-radius: 12px; color: #10b981; margin-right: 15px;">
                        <i class="ti ti-trophy" style="font-size: 1.5rem;"></i>
                    </div>
                    <div>
                        <h4 id="saleTitle" class="fw-bold m-0" style="color: #047857;">Log Sale Details</h4>
                        <p class="text-muted m-0" style="font-size: 0.9rem;">Fill in the details to log this sale based on category.</p>
                    </div>
                </div>

                <div id="normalSaleFields" class="row g-4" style="display: none;">
                    <div class="col-md-4">
                        <label class="custom-label">Invoice No <span class="text-danger">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-receipt"></i>
                            <input type="text" name="invoice_no" id="normal_invoice" class="custom-input <?= $is_already_sold_normal ? 'field-disabled' : '' ?>" placeholder="INV-1001" value="<?= htmlspecialchars($saved_invoice) ?>" <?= $is_already_sold_normal ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Sale Date <span class="text-danger">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-calendar"></i>
                            <input type="date" name="sale_date" id="normal_date" class="custom-input <?= $is_already_sold_normal ? 'field-disabled' : '' ?>" value="<?= htmlspecialchars($saved_date) ?>" <?= $is_already_sold_normal ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Amount (₹) <span class="text-danger">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-currency-rupee"></i>
                            <input type="number" step="0.01" name="amount" id="normal_amount" class="custom-input <?= $is_already_sold_normal ? 'field-disabled' : '' ?>" placeholder="0.00" value="<?= htmlspecialchars($saved_amount) ?>" <?= $is_already_sold_normal ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label class="custom-label">Attach PO / Invoice Copy</label>
                        <div class="input-group-custom">
                            <i class="ti ti-paperclip"></i>
                            <input type="file" name="sale_attachment" id="normal_file" class="custom-input <?= $is_already_sold_normal ? 'field-disabled' : '' ?>" <?= $is_already_sold_normal ? 'disabled' : '' ?> accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <?php if(!empty($saved_attachment)): ?>
                            <div class="mt-2 sale-logged-msg"><a href="<?= htmlspecialchars($saved_attachment) ?>" target="_blank" class="badge" style="background: #10b981; text-decoration: none;"><i class="ti ti-download me-1"></i> View Document</a></div>
                        <?php endif; ?>
                    </div>
                    <?php if($is_already_sold_normal): ?>
                        <div class="col-12 mt-2 text-muted sale-logged-msg" style="font-size: 0.85rem;"><i class="ti ti-info-circle"></i> Last sale record shown. Change Customer Type to log a new one.</div>
                    <?php endif; ?>
                </div>

                <div id="govSaleFields" class="row g-4" style="display: none;">
                    <div class="col-md-4">
                        <label class="custom-label">GeM Contact No <span class="text-danger">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-hash"></i>
                            <input type="text" name="gem_contact_no" id="gov_gem_no" class="custom-input <?= $is_already_sold_gov ? 'field-disabled' : '' ?>" value="<?= htmlspecialchars($saved_gem_no) ?>" <?= $is_already_sold_gov ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Contract Date <span class="text-danger">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-calendar"></i>
                            <input type="date" name="contract_date" id="gov_c_date" class="custom-input <?= $is_already_sold_gov ? 'field-disabled' : '' ?>" value="<?= htmlspecialchars($saved_contract_date) ?>" <?= $is_already_sold_gov ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Delivery Last Date <span class="text-danger">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-truck-delivery"></i>
                            <input type="date" name="delivery_last_date" id="gov_d_date" class="custom-input <?= $is_already_sold_gov ? 'field-disabled' : '' ?>" value="<?= htmlspecialchars($saved_delivery_date) ?>" <?= $is_already_sold_gov ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="custom-label">Department Name <span class="text-danger">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-building-bank"></i>
                            <input type="text" name="department_name" id="gov_dept" class="custom-input <?= $is_already_sold_gov ? 'field-disabled' : '' ?>" value="<?= htmlspecialchars($saved_dept) ?>" <?= $is_already_sold_gov ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="custom-label">Company <span class="text-danger">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-building"></i>
                            <input type="text" name="gov_company" id="gov_company" class="custom-input <?= $is_already_sold_gov ? 'field-disabled' : '' ?>" value="<?= htmlspecialchars($saved_gov_company) ?>" <?= $is_already_sold_gov ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Product <span class="text-danger">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-box"></i>
                            <input type="text" name="product" id="gov_product" class="custom-input <?= $is_already_sold_gov ? 'field-disabled' : '' ?>" value="<?= htmlspecialchars($saved_product) ?>" <?= $is_already_sold_gov ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Quantity <span class="text-danger">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-stack"></i>
                            <input type="number" name="quantity" id="gov_qty" class="custom-input <?= $is_already_sold_gov ? 'field-disabled' : '' ?>" value="<?= htmlspecialchars($saved_qty) ?>" <?= $is_already_sold_gov ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="custom-label">Amount (₹) <span class="text-danger">*</span></label>
                        <div class="input-group-custom">
                            <i class="ti ti-currency-rupee"></i>
                            <input type="number" step="0.01" name="gov_amount" id="gov_amount" class="custom-input <?= $is_already_sold_gov ? 'field-disabled' : '' ?>" value="<?= htmlspecialchars($saved_gov_amount) ?>" <?= $is_already_sold_gov ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="custom-label">GeM Contract File (Upload)</label>
                        <div class="input-group-custom">
                            <i class="ti ti-file-text"></i>
                            <input type="file" name="gem_contract_file" id="gov_file_1" class="custom-input <?= $is_already_sold_gov ? 'field-disabled' : '' ?>" <?= $is_already_sold_gov ? 'disabled' : '' ?> accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>
                        <?php if(!empty($saved_gem_file)): ?>
                            <div class="mt-2 sale-logged-msg"><a href="<?= htmlspecialchars($saved_gem_file) ?>" target="_blank" class="badge" style="background: #10b981; text-decoration: none;">View GeM File</a></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="custom-label">Bid File (Upload)</label>
                        <div class="input-group-custom">
                            <i class="ti ti-file-zip"></i>
                            <input type="file" name="bid_file" id="gov_file_2" class="custom-input <?= $is_already_sold_gov ? 'field-disabled' : '' ?>" <?= $is_already_sold_gov ? 'disabled' : '' ?> accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>
                        <?php if(!empty($saved_bid_file)): ?>
                            <div class="mt-2 sale-logged-msg"><a href="<?= htmlspecialchars($saved_bid_file) ?>" target="_blank" class="badge" style="background: #10b981; text-decoration: none;">View Bid File</a></div>
                        <?php endif; ?>
                    </div>
                    <?php if($is_already_sold_gov): ?>
                        <div class="col-12 mt-2 text-muted sale-logged-msg" style="font-size: 0.85rem;"><i class="ti ti-info-circle"></i> Last sale record shown. Change Customer Type to log a new one.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="glass-card mt-0" style="border-top-left-radius: 0; border-top-right-radius: 0; background: transparent; padding-top: 20px; box-shadow: none;">
                <div class="d-flex justify-content-end align-items-center">
                    <?php if($is_customer == 0): ?>
                    <a href="convert-customer.php?id=<?= $id ?>" class="btn-success-custom me-3 d-inline-block">
                        <i class="ti ti-exchange me-2"></i> Convert to Customer
                    </a>
                    <?php else: ?>
                    <a href="javascript:void(0);" class="btn-success-custom me-3 d-inline-block" style="background-color: #28a745; cursor: default;">
                        <i class="ti ti-check me-2"></i> Already a Customer
                    </a>
                    <?php endif; ?>

                    <button type="submit" name="update_lead" class="btn-primary-custom">
                        <i class="ti ti-device-floppy me-2"></i> Save & Log Everything
                    </button>
                </div>
            </div>
        </form>

        <div class="glass-card">
            <div class="section-title"><i class="ti ti-history me-2"></i> Activity History</div>
            <div class="history-box">
                <?php if(empty($history)){ echo '<div class="text-muted text-center py-3">No history records found.</div>'; } else { ?>
                    <?php foreach($history as $h){ ?>
                        <div class="history-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <b style="color: var(--text-dark);"><?= date("d M Y h:i A", strtotime($h['created_at'])) ?></b>
                                <?php if(!empty($h['followup_time']) && $h['followup_time'] != '0000-00-00 00:00:00'){ ?>
                                    <span class="badge" style="background: var(--primary); font-size: 0.75rem; padding: 5px 10px; border-radius: 6px;">
                                        <i class="ti ti-clock me-1"></i> Followup: <?= date("d M Y h:i A", strtotime($h['followup_time'])) ?>
                                    </span>
                                <?php } ?>
                            </div>
                            <div style="font-size: 0.95rem;">
                                <b style="color: var(--primary);"><?= htmlspecialchars($h['updated_by'] ?: 'System') ?> :</b> 
                                <span style="color: #4b5563;"><?= nl2br($h['history_note'] ?? '') ?></span>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const statusDropdown = document.getElementById('leadStatusDropdown');
    const sourceDropdown = document.getElementById('leadSourceDropdown');
    const custDropdown = document.getElementById('customerTypeDropdown'); // Naya dropdown
    const saleCard = document.getElementById('saleDetailsCard');
    
    // Normal elements
    const normalFields = document.getElementById('normalSaleFields');
    const n_inv = document.getElementById('normal_invoice');
    const n_date = document.getElementById('normal_date');
    const n_amt = document.getElementById('normal_amount');
    const n_file = document.getElementById('normal_file');
    let isNormalSold = n_inv.hasAttribute('readonly');

    // Gov elements
    const govFields = document.getElementById('govSaleFields');
    const g_gem = document.getElementById('gov_gem_no');
    const g_cdate = document.getElementById('gov_c_date');
    const g_ddate = document.getElementById('gov_d_date');
    const g_dept = document.getElementById('gov_dept');
    const g_comp = document.getElementById('gov_company');
    const g_prod = document.getElementById('gov_product');
    const g_qty = document.getElementById('gov_qty');
    const g_amt = document.getElementById('gov_amount');
    const g_file1 = document.getElementById('gov_file_1');
    const g_file2 = document.getElementById('gov_file_2');
    let isGovSold = g_gem.hasAttribute('readonly');

    // Naya function: Purani lock hui fields ko wapas kholne ke liye
    if(custDropdown) {
        custDropdown.addEventListener('change', function() {
            if(this.value === 'Existing Customer' || this.value === 'New Customer') {
                
                document.getElementById('saleTitle').innerText = "Log New Additional Sale";

                // Unlock Normal
                n_inv.readOnly = false; n_date.readOnly = false; n_amt.readOnly = false;
                n_inv.classList.remove('field-disabled'); n_date.classList.remove('field-disabled'); n_amt.classList.remove('field-disabled');
                n_inv.value = ''; n_date.value = ''; n_amt.value = '';
                n_file.disabled = false; n_file.classList.remove('field-disabled');
                isNormalSold = false; // Reset lock flag

                // Unlock Gov
                let govInputs = [g_gem, g_cdate, g_ddate, g_dept, g_comp, g_prod, g_qty, g_amt];
                govInputs.forEach(inp => {
                    if(inp) {
                        inp.readOnly = false;
                        inp.classList.remove('field-disabled');
                        inp.value = '';
                    }
                });
                g_file1.disabled = false; g_file2.disabled = false;
                g_file1.classList.remove('field-disabled'); g_file2.classList.remove('field-disabled');
                isGovSold = false; // Reset lock flag

                // Purani warnings hata do
                document.querySelectorAll('.sale-logged-msg').forEach(el => el.style.display = 'none');
                
                checkSaleStatus(); // Re-run validation logic
            }
        });
    }

    function checkSaleStatus() {
        if (statusDropdown && statusDropdown.value === 'Sale') {
            saleCard.style.display = 'block';
            
            // GOV SELECTION
            if (sourceDropdown && sourceDropdown.value === 'Government') {
                normalFields.style.display = 'none';
                govFields.style.display = 'flex';
                
                // Set normal to false
                n_inv.required = false; n_date.required = false; n_amt.required = false;
                
                // Set gov to true if not sold
                if (!isGovSold) {
                    g_gem.required = true; g_cdate.required = true; g_ddate.required = true;
                    g_dept.required = true; g_comp.required = true; g_prod.required = true;
                    g_qty.required = true; g_amt.required = true;
                }
            } 
            // NORMAL SELECTION
            else {
                govFields.style.display = 'none';
                normalFields.style.display = 'flex';
                
                // Set gov to false
                g_gem.required = false; g_cdate.required = false; g_ddate.required = false;
                g_dept.required = false; g_comp.required = false; g_prod.required = false;
                g_qty.required = false; g_amt.required = false;
                
                // Set normal to true if not sold
                if (!isNormalSold) {
                    n_inv.required = true; n_date.required = true; n_amt.required = true;
                }
            }
        } else {
            saleCard.style.display = 'none';
            
            n_inv.required = false; n_date.required = false; n_amt.required = false;
            g_gem.required = false; g_cdate.required = false; g_ddate.required = false;
            g_dept.required = false; g_comp.required = false; g_prod.required = false;
            g_qty.required = false; g_amt.required = false;
        }
    }

    if(statusDropdown) statusDropdown.addEventListener('change', checkSaleStatus);
    if(sourceDropdown) sourceDropdown.addEventListener('change', checkSaleStatus);
    
    checkSaleStatus(); // Run on load

    // Sweet Alert logic
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('msg') === 'updated') {
        Swal.fire({ icon: 'success', title: 'Updated Successfully', text: 'Records synced.', timer: 2000, showConfirmButton: false, toast: true, position: 'top-end' });
        window.history.replaceState({}, '', "?id=<?= $id ?>");
    } else if(urlParams.get('msg') === 'sale_locked') {
        Swal.fire({ icon: 'success', title: 'Sale Locked!', text: 'Sale & Note saved successfully.', timer: 2500, showConfirmButton: false, toast: true, position: 'top-end' });
        window.history.replaceState({}, '', "?id=<?= $id ?>");
    }

    document.getElementById('leadUpdateForm').onsubmit = function() {
        document.getElementById('loading-overlay').style.display = 'flex';
    };
</script>

<?php include 'include/footer.php'; ?>