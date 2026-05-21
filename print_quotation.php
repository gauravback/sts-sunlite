<?php
// ERROR REPORTING ON (Taaki blank 500 error na aaye future me)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Document ID");
}

$q_id = $_GET['id'];

// Check if it's a PI or Quotation based on URL parameter (?type=pi)
$is_pi = (isset($_GET['type']) && $_GET['type'] === 'pi');
$doc_type = $is_pi ? 'PROFORMA INVOICE' : 'QUOTATION';
$doc_label = $is_pi ? 'PROFORMA INVOICE NO.' : 'QUOTATION NO.';
$doc_filename = $is_pi ? 'Proforma_Invoice_' : 'Quotation_';

// 1. Fetch Main Quotation Details
$q_query = mysqli_query($conn, "SELECT * FROM quotations WHERE id = $q_id");
$quotation = mysqli_fetch_assoc($q_query);

if (!$quotation) {
    die("Document not found.");
}

$company_type = $quotation['company_type'];
$company_id = $quotation['company_id'];
$issuer_id = $quotation['issuer_id'];

// ==========================================
// HISTORY TRACKING (VIEWED / DOWNLOADED) - SAFE MODE
// ==========================================
try {
    if (isset($_SESSION['name']) && !empty($_SESSION['name'])) {
        $viewed_by = $_SESSION['name'];
    } elseif (isset($_SESSION['user_name']) && !empty($_SESSION['user_name'])) {
        $viewed_by = $_SESSION['user_name'];
    } elseif (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
        $viewed_by = $_SESSION['username'];
    } else {
        $viewed_by = 'Admin'; 
    }

    $user_id_hist = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0);
    $q_no_safe = mysqli_real_escape_string($conn, $quotation['quotation_no']);
    $doc_name_short = $is_pi ? 'Proforma Invoice (PI)' : 'Quotation (QTN)';
    
    $history_remark = "📥 <b>$doc_name_short</b> Viewed/Generated for QTN: $q_no_safe";

    // FIX: Columns changed to history_note, user_id, updated_by and created_at
    if ($company_type === 'Customer') {
        mysqli_query($conn, "INSERT INTO customer_history (customer_id, history_note, user_id, created_at) VALUES ($company_id, '$history_remark', $user_id_hist, NOW())");
    } else if ($company_type === 'Lead') {
        mysqli_query($conn, "INSERT INTO lead_history (lead_id, history_note, updated_by, created_at) VALUES ($company_id, '$history_remark', '$viewed_by', NOW())");
    }
} catch (Exception $e) { 
    // Agar history insert fail hoti hai database structure ki wajah se, 
    // toh page crash nahi hoga, loop yahan silently aage badh jayega!
}
// ==========================================


// 2. FETCH ISSUER DETAILS (Your Company)
$iss_query = mysqli_query($conn, "SELECT * FROM issuer_companies WHERE id = $issuer_id");
$issuer = mysqli_fetch_assoc($iss_query);

$iss_name    = htmlspecialchars($issuer['company_name'] ?? 'CORPORATION');
$iss_address = htmlspecialchars($issuer['address'] ?? '');
$iss_phone   = htmlspecialchars($issuer['phone'] ?? '');
$iss_gstin   = htmlspecialchars($issuer['gstin'] ?? '');
$iss_pan     = htmlspecialchars($issuer['pan'] ?? '');
$iss_signature = $issuer['signature_image'] ?? ''; 

// 3. Fetch Customer OR Lead Details based on type
if ($company_type === 'Customer') {
    $c_query = mysqli_query($conn, "SELECT * FROM customers WHERE id = $company_id");
    $customer = mysqli_fetch_assoc($c_query);
    
    $cust_company = htmlspecialchars($customer['company_name'] ?? '');
    $cust_name    = htmlspecialchars($customer['customer_name'] ?? '');
    $cust_phone   = htmlspecialchars($customer['contact_no'] ?? '');
    $cust_pan     = htmlspecialchars($customer['pan'] ?? 'N/A');
    $cust_gst     = htmlspecialchars($customer['gstin'] ?? 'N/A');
} else {
    $c_query = mysqli_query($conn, "SELECT * FROM leads WHERE id = $company_id");
    $customer = mysqli_fetch_assoc($c_query);
    
    $cust_company = htmlspecialchars($customer['company_name'] ?? '');
    $cust_name    = htmlspecialchars($customer['contact_person'] ?? ''); 
    $cust_phone   = htmlspecialchars($customer['contact_number'] ?? ''); 
    $cust_pan     = htmlspecialchars($customer['pan'] ?? 'N/A');
    $cust_gst     = htmlspecialchars($customer['gstin'] ?? 'N/A');
}

// 4. Fetch Bank Details specific to this company
$bank_query = mysqli_query($conn, "SELECT * FROM company_bank_details WHERE company_type = '$company_type' AND company_id = $company_id");
$bank = mysqli_fetch_assoc($bank_query);

$acc_holder = htmlspecialchars($bank['account_holder'] ?? 'N/A');
$acc_num    = htmlspecialchars($bank['account_number'] ?? 'N/A');
$bank_name  = htmlspecialchars($bank['bank_name'] ?? 'N/A');
$branch     = htmlspecialchars($bank['branch'] ?? 'N/A');
$ifsc       = htmlspecialchars($bank['ifsc_code'] ?? 'N/A');
$upi        = htmlspecialchars($bank['upi_id'] ?? 'N/A');

// 5. Fetch Items AND chunk them
$items_result = mysqli_query($conn, "SELECT * FROM quotation_items WHERE quotation_id = $q_id");
$all_items = [];
while($row = mysqli_fetch_assoc($items_result)) {
    $all_items[] = $row;
}

$item_count = count($all_items);
// LIMIT SET STRICTLY TO 4 ITEMS PER PAGE
$items_per_page = 4; 
$item_chunks = array_chunk($all_items, $items_per_page);
$total_pages = count($item_chunks);

$layout_class = ($item_count > 4) ? 'compact-mode' : 'relaxed-mode';
$display_date = date('d F Y', strtotime($quotation['quotation_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $doc_type ?> - <?= $quotation['quotation_no'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 80px 0 40px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .action-bar {
            position: fixed;
            top: 15px;
            width: 210mm;
            background: #fff;
            padding: 12px 24px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            z-index: 1000;
            border: 1px solid #cbd5e1;
        }

        #pdf-wrapper {
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 25px; /* Screen par gap dikhane ke liye */
        }
        
        /* MAGIC CSS 1: PDF Download karte waqt saari spacing Zero kar dega! */
        #pdf-wrapper.pdf-export-mode {
            gap: 0 !important;
            display: block !important;
        }

        /* Yeh universal page class hai, jo sabhi templates use karenge */
        .quotation-page {
            background: white;
            width: 210mm; 
            min-height: 296mm; /* Safe A4 height */
            box-sizing: border-box; 
            position: relative;
            display: flex;
            flex-direction: column; 
            overflow: hidden; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); /* Screen shadow */
        }

        /* MAGIC CSS 2: PDF me shadow aur margin khatam */
        #pdf-wrapper.pdf-export-mode .quotation-page {
            box-shadow: none !important;
            margin: 0 !important;
        }
    </style>
</head>
<body>

    <div class="action-bar" id="actionBar">
        <div>
            <h6 class="m-0 fw-bold text-dark"><?= ucfirst(strtolower($doc_type)) ?> Preview</h6>
        </div>
        <div>
            <button onclick="window.close()" class="btn btn-light border btn-sm px-3 py-2 fw-semibold me-2">
                <i class="fas fa-times me-1"></i> Close
            </button>
            <button onclick="triggerDownload()" class="btn btn-primary btn-sm px-3 py-2 fw-semibold text-white" id="downloadBtn">
                <i class="fas fa-download me-1"></i> Download PDF
            </button>
        </div>
    </div>

   <div id="pdf-wrapper">
        
        <?php 
        // Check array is not empty (Items added check)
        if (empty($item_chunks)) {
            echo "<div style='padding:40px; text-align:center; color:#b45309; background:#fef3c7; border-radius:8px; margin:20px; width:210mm;'>
                    <h3>⚠️ No Items Found</h3>
                    <p>Is quotation mein koi items add nahi hain. Please pehle items add karein.</p>
                  </div>";
        } else {
            // ==============================================
            // TEMPLATE ROUTER MAGIC 
            // ==============================================
            $upper_iss_name = strtoupper($iss_name);
            $template_path = '';

            // 🔥 SABSE PEHLE CHECK KARO KI USER NE DEFAULT MANGA HAI KYA? (Toggle Check)
            if (isset($quotation['use_default_template']) && $quotation['use_default_template'] == 1) {
                $template_path = 'template_default.php';
            } 
            else {
                // 🚨 NAYA LOGIC: Agar database me header/footer image upload hui hai, toh Image wala Template chalega!
                if (!empty($issuer['header_image']) || !empty($issuer['footer_image'])) {
                    $template_path = 'template_image_letterhead.php'; 
                }
                // Agar images upload nahi ki hain, tabhi purane text-based letterheads check honge
                elseif (strpos($upper_iss_name, 'SUNLITE') !== false) {
                    $template_path = 'template_sunlite.php'; 
                }
                elseif (strpos($upper_iss_name, 'TECHNOLOGY CENTRE') !== false) {
                    $template_path = 'template_technology.php'; 
                }
                elseif (strpos($upper_iss_name, 'BEST DEALS') !== false) {
                    $template_path = 'template_best_deals.php'; 
                }
                elseif (strpos($upper_iss_name, 'RUDRAKSH') !== false) {
                    $template_path = 'template_rudraksh.php'; 
                }
                else {
                    $template_path = 'template_default.php'; 
                }
            }
            
            // SMART CHECK: Include template only if it exists
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                echo "<div style='padding: 40px; text-align: center; color: #dc2626; background: #fee2e2; margin: 20px; border-radius: 10px; border: 1px solid #f87171; width:210mm;'>";
                echo "<h3>🚨 Error: Template File Missing!</h3>";
                echo "<p>System ne <b>" . htmlspecialchars($iss_name) . "</b> detect kiya, lekin <b><code>" . $template_path . "</code></b> nahi mili.</p>";
                echo "</div>";
            }
        }
        ?>

    </div>

    <script>
        function triggerDownload() {
            var wrapper = document.getElementById('pdf-wrapper');
            
            // MAGIC JS: Sirf download hote waqt extra spaces remove kardo
            wrapper.classList.add('pdf-export-mode'); 
            
            var opt = {
                margin:       0, 
                filename:     '<?= $doc_filename ?><?= $quotation['quotation_no'] ?>.pdf',
                image:        { type: 'jpeg', quality: 1 },
                html2canvas:  { scale: 2, useCORS: true, scrollY: 0 }, 
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak:    { mode: ['css', 'legacy'] } 
            };
            
            let btn = document.getElementById('downloadBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating...';
            btn.disabled = true;

            html2pdf().set(opt).from(wrapper).save().then(function() {
                // Download hone ke baad screen par wapas gaps dikha do
                wrapper.classList.remove('pdf-export-mode');
                btn.innerHTML = '<i class="fas fa-download me-1"></i> Download PDF';
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>