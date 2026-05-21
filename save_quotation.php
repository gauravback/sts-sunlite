<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Get logged in user
    if (isset($_SESSION['name']) && !empty($_SESSION['name'])) {
        $created_by = $_SESSION['name'];
    } elseif (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
        $created_by = $_SESSION['user_name'];
    } elseif (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        $created_by = $_SESSION['username'];
    } else {
        $created_by = 'Admin'; 
    }

    // Get Form Data
    $issuer_id      = (int)$_POST['issuer_id']; 
    $quotation_no   = mysqli_real_escape_string($conn, $_POST['quotation_no']);
    $quotation_date = mysqli_real_escape_string($conn, $_POST['quotation_date']);
    $notes          = mysqli_real_escape_string($conn, $_POST['notes']);
    $terms          = mysqli_real_escape_string($conn, $_POST['terms']);
    
    $sub_total      = (float)$_POST['sub_total'];
    $total_tax      = (float)$_POST['total_tax'];
    $discount       = (float)$_POST['discount'];
    $grand_total    = (float)$_POST['grand_total'];

    // TOGGLE BUTTON VALUE PAKADNE KA LOGIC (0 for Letterhead, 1 for Default)
    $use_default_template = isset($_POST['use_default_template']) ? 1 : 0;

    // Extract Company Type and ID from Selection
    $company_selection = $_POST['company_selection']; // Format: "Customer_5" or "Lead_12"
    $parts = explode('_', $company_selection);
    $company_type = $parts[0];
    $company_id = (int)$parts[1];

    // ==========================================
    // 1. SAVE BANK DETAILS
    // ==========================================
    $acc_holder = mysqli_real_escape_string($conn, $_POST['account_holder']);
    $acc_num    = mysqli_real_escape_string($conn, $_POST['account_number']);
    $bank_name  = mysqli_real_escape_string($conn, $_POST['bank_name']);
    $branch     = mysqli_real_escape_string($conn, $_POST['branch']);
    $ifsc       = mysqli_real_escape_string($conn, $_POST['ifsc_code']);
    $upi        = mysqli_real_escape_string($conn, $_POST['upi_id']);

    $bank_query = "INSERT INTO company_bank_details (company_type, company_id, account_holder, account_number, bank_name, branch, ifsc_code, upi_id) 
                   VALUES ('$company_type', $company_id, '$acc_holder', '$acc_num', '$bank_name', '$branch', '$ifsc', '$upi')
                   ON DUPLICATE KEY UPDATE 
                   account_holder='$acc_holder', account_number='$acc_num', bank_name='$bank_name', branch='$branch', ifsc_code='$ifsc', upi_id='$upi'";
    mysqli_query($conn, $bank_query);


    // ==========================================
    // 2. INSERT INTO QUOTATIONS TABLE (With Toggle Value)
    // ==========================================
    $insert_q = "INSERT INTO quotations (quotation_no, issuer_id, company_type, company_id, quotation_date, sub_total, total_tax, discount, grand_total, notes, terms, created_by, use_default_template) 
                 VALUES ('$quotation_no', $issuer_id, '$company_type', $company_id, '$quotation_date', $sub_total, $total_tax, $discount, $grand_total, '$notes', '$terms', '$created_by', $use_default_template)";
    
    if (mysqli_query($conn, $insert_q)) {
        $quotation_id = mysqli_insert_id($conn);

        // ==========================================
        // 3. INSERT INTO QUOTATION_ITEMS TABLE
        // ==========================================
        $item_names   = $_POST['item_name'];
        $qtys         = $_POST['qty'];
        $prices       = $_POST['price'];
        $tax_percents = $_POST['tax_percent'];
        $row_totals   = $_POST['row_total'];

        for ($i = 0; $i < count($item_names); $i++) {
            $name  = mysqli_real_escape_string($conn, $item_names[$i]);
            $qty   = (float)$qtys[$i];
            $price = (float)$prices[$i];
            $tax_p = (float)$tax_percents[$i];
            $total = (float)$row_totals[$i];

            $insert_item = "INSERT INTO quotation_items (quotation_id, item_name, qty, price, tax_percent, total) 
                            VALUES ($quotation_id, '$name', $qty, $price, $tax_p, $total)";
            mysqli_query($conn, $insert_item);
        }

        // ==========================================
        // 4. CLIENT HISTORY TRACKING (QUOTATION CREATED)
        // ==========================================
        $manager_name = 'N/A';
        
        // Try-Catch lagaya hai taaki agar manager_name column na ho toh page crash na ho
        try {
            $mgr_query = mysqli_query($conn, "SELECT manager_name FROM users WHERE username = '$created_by' OR name = '$created_by' LIMIT 1");
            if ($mgr_query && mysqli_num_rows($mgr_query) > 0) {
                $mgr_row = mysqli_fetch_assoc($mgr_query);
                $manager_name = !empty($mgr_row['manager_name']) ? $mgr_row['manager_name'] : 'N/A';
            }
        } catch (Exception $e) {
            // Agar column nahi milega toh error ignore karke 'N/A' set kar dega
            $manager_name = 'N/A'; 
        }

        // User ID for history joining
        $user_id_hist = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0);

        // History message tayyar karo
        $history_remark = "📄 <b>Quotation ($quotation_no)</b> created by $created_by (Manager: $manager_name)";

        // Customer ya Lead ki history table me insert karo
        try {
            // FIX: Columns changed to history_note, user_id, updated_by and created_at
            if ($company_type == 'Customer') {
                mysqli_query($conn, "INSERT INTO customer_history (customer_id, history_note, user_id, created_at) VALUES ($company_id, '$history_remark', $user_id_hist, NOW())");
            } else if ($company_type == 'Lead') {
                mysqli_query($conn, "INSERT INTO lead_history (lead_id, history_note, updated_by, created_at) VALUES ($company_id, '$history_remark', '$created_by', NOW())");
            }
        } catch (Exception $e) {
            // History insert fail hone par bhi quotation PDF generate ho jayegi bina roke
        }

        // REDIRECT TO PDF PRINT PAGE
        header("Location: print_quotation.php?id=$quotation_id");
        exit();

    } else {
        echo "Error saving quotation: " . mysqli_error($conn);
    }
} else {
    header("Location: create_quotation.php");
    exit();
}
?>