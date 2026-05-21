<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<h2 style='text-align:center; margin-top:50px; color:red;'>Invalid Purchase Order ID!</h2>");
}

$po_id = (int)$_GET['id'];

// 1. Fetch PO and Vendor Data
$query = "SELECT po.*, v.vendor_name, v.address, v.gstin, v.pan, v.email, v.phone 
          FROM purchase_orders po 
          JOIN vendors v ON po.vendor_id = v.id 
          WHERE po.id = $po_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    die("<h2 style='text-align:center; margin-top:50px; color:red;'>Purchase Order Not Found!</h2>");
}

$po = mysqli_fetch_assoc($result);
$items_result = mysqli_query($conn, "SELECT * FROM po_items WHERE po_id = $po_id");

// 2. Fetch the SPECIFIC Company (Issuer) selected during PO creation
$issuer_id = isset($po['issuer_id']) ? (int)$po['issuer_id'] : 0;
$issuer_query = mysqli_query($conn, "SELECT * FROM po_issuer_companies WHERE id = $issuer_id");
$issuer = mysqli_fetch_assoc($issuer_query);

if(!$issuer) {
    $issuer = [
        'company_name' => 'Sunlite Systems Private Limited',
        'address' => 'A-41, Rachna, Sector-3, Vaishali, Ghaziabad, UP, India - 201010',
        'gstin' => '09AAXCS1234J1ZU',
        'pan' => 'AAXCS1234J',
        'email' => 'sales@sunlitesystems.com',
        'phone' => '+91 99108 09450'
    ];
}

// Function to convert Number to Words
function numberToWords($number) {
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_1 = strlen($no);
    $i = 0;
    $str = array();
    $words = array('0' => '', '1' => 'One', '2' => 'Two', '3' => 'Three', '4' => 'Four', '5' => 'Five', '6' => 'Six', '7' => 'Seven', '8' => 'Eight', '9' => 'Nine', '10' => 'Ten', '11' => 'Eleven', '12' => 'Twelve', '13' => 'Thirteen', '14' => 'Fourteen', '15' => 'Fifteen', '16' => 'Sixteen', '17' => 'Seventeen', '18' => 'Eighteen', '19' =>'Nineteen', '20' => 'Twenty', '30' => 'Thirty', '40' => 'Forty', '50' => 'Fifty', '60' => 'Sixty', '70' => 'Seventy', '80' => 'Eighty', '90' => 'Ninety');
    $digits = array('', 'Hundred', 'Thousand', 'Lakh', 'Crore');
    while ($i < $digits_1) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number] . " " . $digits[$counter] . $plural . " " . $hundred
                : $words[floor($number / 10) * 10] . " " . $words[$number % 10] . " " . $digits[$counter] . $plural . " " . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    return strtoupper($result) . " RUPEES ONLY";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Download PO - <?= htmlspecialchars($po['po_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <style>
        :root {
            --brand-blue: #1e3a8a; 
            --brand-light: #eff6ff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }
        
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; padding: 40px 0; color: var(--text-main); margin: 0; }
        
        .action-bar { max-width: 780px; margin: 0 auto 20px; display: flex; justify-content: space-between; }
        .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; border: none; display: inline-flex; align-items: center; transition: 0.2s; }
        .btn-download { background: #10b981; color: white; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2); }
        .btn-download:hover { background: #059669; transform: translateY(-1px); }
        .btn-back { background: white; color: var(--text-main); border: 1px solid var(--border-color); }

        /* A4 Page Container - Fixed for A4 PDF Scaling */
        #po-content { 
            max-width: 780px; /* Safe width for A4 */
            width: 100%;
            margin: 0 auto; 
            background: white; 
            padding: 30px 40px; /* Reduced side padding to avoid squishing */
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            position: relative;
            box-sizing: border-box; /* IMPORTANT: Keeps padding inside the width */
        }
        
        .top-accent { position: absolute; top: 0; left: 0; width: 100%; height: 6px; background-color: var(--brand-blue); }

        /* Header Section */
        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; margin-top: 10px; }
        .company-logo { font-size: 20px; font-weight: 800; color: var(--brand-blue); letter-spacing: -0.5px; }
        .po-title { font-size: 24px; font-weight: 800; color: var(--border-color); text-transform: uppercase; letter-spacing: 1px; margin: 0; text-align: right; line-height: 1; }
        .po-meta { text-align: right; margin-top: 8px; font-size: 12px; color: var(--text-muted); }
        .po-meta span { color: var(--text-main); font-weight: 600; margin-left: 5px; }

        /* Two Column Addresses */
        .address-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 25px; }
        .address-box { font-size: 12px; line-height: 1.5; word-wrap: break-word; }
        .address-head { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; border-bottom: 1px solid var(--border-color); padding-bottom: 4px; }
        .address-box strong.comp-name { color: var(--brand-blue); font-size: 14px; font-weight: 700; display: block; margin-bottom: 4px; }
        
        /* Info Strip */
        .info-strip { display: flex; background: var(--brand-light); border-radius: 6px; padding: 12px; margin-bottom: 25px; }
        .info-item { flex: 1; border-right: 1px solid #bfdbfe; padding: 0 12px; }
        .info-item:last-child { border-right: none; }
        .info-item label { display: block; font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 3px; }
        .info-item div { font-size: 12px; font-weight: 600; color: var(--text-main); }

        /* Items Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .items-table th { border-bottom: 2px solid var(--text-main); padding: 10px 8px; font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; text-align: right; }
        .items-table th:first-child, .items-table th:nth-child(2) { text-align: left; }
        .items-table td { padding: 12px 8px; font-size: 12px; border-bottom: 1px solid var(--border-color); text-align: right; vertical-align: top; word-wrap: break-word; }
        .items-table td:first-child, .items-table td:nth-child(2) { text-align: left; }
        .items-table .item-name { font-weight: 600; color: var(--text-main); }

        /* Summary Section */
        .summary-container { display: flex; justify-content: flex-end; margin-bottom: 25px; }
        .summary-table { width: 280px; border-collapse: collapse; font-size: 12px; }
        .summary-table td { padding: 8px 10px; }
        .summary-table .label { font-weight: 500; color: var(--text-muted); }
        .summary-table .value { font-weight: 600; text-align: right; }
        .summary-table .total-row td { border-top: 2px solid var(--text-main); border-bottom: 2px solid var(--text-main); font-size: 15px; font-weight: 800; color: var(--brand-blue); padding: 12px 10px; }

        /* Footer info */
        .footer-info { font-size: 12px; }
        .words-section { margin-bottom: 20px; }
        .words-section span { display: block; font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 3px; }
        .words-section div { font-weight: 600; color: var(--brand-blue); }
        
        .terms-section h6 { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin: 0 0 5px 0; }
        .terms-section p { font-size: 11px; color: var(--text-muted); line-height: 1.5; margin: 0; white-space: pre-line; word-wrap: break-word; }

        /* Signature */
        .signature-section { margin-top: 40px; text-align: right; }
        .signature-section p { font-size: 11px; color: var(--text-muted); font-weight: 600; margin: 0 0 35px 0; }
        .sign-line { border-top: 1px solid var(--text-main); display: inline-block; min-width: 180px; padding-top: 5px; font-size: 12px; font-weight: 700; }
    </style>
</head>
<body>

    <div class="action-bar">
        <a href="javascript:history.back()" class="btn btn-back"><i class="fas fa-arrow-left me-2"></i> Go Back</a>
        <button id="download-btn" class="btn btn-download"><i class="fas fa-file-download me-2"></i> Download PDF</button>
    </div>

    <div id="po-content">
        <div class="top-accent"></div>

        <div class="invoice-header">
            <div class="company-logo">
                <i class="fas fa-cube me-1"></i> <?= htmlspecialchars($issuer['company_name']) ?>
            </div>
            <div>
                <h1 class="po-title">Purchase Order</h1>
                <div class="po-meta">
                    <div>PO Number: <span><?= htmlspecialchars($po['po_number']) ?></span></div>
                    <div>Date: <span><?= date('d M, Y', strtotime($po['po_date'])) ?></span></div>
                </div>
            </div>
        </div>

        <div class="address-grid">
            <div class="address-box">
                <div class="address-head">Issued By</div>
                <strong class="comp-name"><?= htmlspecialchars($issuer['company_name']) ?></strong>
                <?= nl2br(htmlspecialchars($issuer['address'])) ?><br><br>
                <span style="color: var(--text-muted);">GSTIN:</span> <?= htmlspecialchars($issuer['gstin']) ?> <br>
                <span style="color: var(--text-muted);">PAN:</span> <?= htmlspecialchars($issuer['pan']) ?><br>
                <?php if(!empty($issuer['email'])): ?><span style="color: var(--text-muted);">Email:</span> <?= htmlspecialchars($issuer['email']) ?><br><?php endif; ?>
                <?php if(!empty($issuer['phone'])): ?><span style="color: var(--text-muted);">Phone:</span> <?= htmlspecialchars($issuer['phone']) ?><?php endif; ?>
            </div>
            
            <div class="address-box">
                <div class="address-head">Issued To (Vendor)</div>
                <strong class="comp-name"><?= htmlspecialchars($po['vendor_name']) ?></strong>
                <?= nl2br(htmlspecialchars($po['address'])) ?><br><br>
                <span style="color: var(--text-muted);">GSTIN:</span> <?= htmlspecialchars($po['gstin']) ?> <br>
                <span style="color: var(--text-muted);">PAN:</span> <?= htmlspecialchars($po['pan']) ?><br>
                <?php if(!empty($po['email'])): ?><span style="color: var(--text-muted);">Email:</span> <?= htmlspecialchars($po['email']) ?><br><?php endif; ?>
                <?php if(!empty($po['phone'])): ?><span style="color: var(--text-muted);">Phone:</span> <?= htmlspecialchars($po['phone']) ?><?php endif; ?>
            </div>
        </div>

        <div class="info-strip">
            <div class="info-item" style="padding-left: 0;">
                <label>Payment Terms</label>
                <div><?= htmlspecialchars($po['payment_terms']) ?: 'N/A' ?></div>
            </div>
            <div class="info-item">
                <label>Delivery Terms</label>
                <div><?= htmlspecialchars($po['delivery_terms']) ?: 'N/A' ?></div>
            </div>
            <div class="info-item">
                <label>Total Items</label>
                <div><?= mysqli_num_rows($items_result) ?></div>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="40%">Item Description</th>
                    <th width="10%">GST</th>
                    <th width="10%">Qty</th>
                    <th width="15%">Rate</th>
                    <th width="20%">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; while($row = mysqli_fetch_assoc($items_result)): ?>
                <tr>
                    <td style="color: var(--text-muted);"><?= sprintf("%02d", $i++) ?></td>
                    <td class="item-name"><?= htmlspecialchars($row['item_description']) ?></td>
                    <td><?= $row['gst_percent'] ?>%</td>
                    <td><?= $row['quantity'] ?></td>
                    <td><?= number_format($row['rate'], 2) ?></td>
                    <td style="font-weight: 600;"><?= number_format($row['total'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="summary-container">
            <table class="summary-table">
                <tr>
                    <td class="label">Sub Total</td>
                    <td class="value">₹ <?= number_format($po['sub_total'], 2) ?></td>
                </tr>
                <tr>
                    <td class="label">Total Tax</td>
                    <td class="value">₹ <?= number_format($po['tax_total'], 2) ?></td>
                </tr>
                <tr class="total-row">
                    <td>Grand Total</td>
                    <td style="text-align: right;">₹ <?= number_format($po['grand_total'], 2) ?></td>
                </tr>
            </table>
        </div>

        <div class="footer-info">
            <div class="words-section">
                <span>Amount in Words</span>
                <div><?= numberToWords($po['grand_total']) ?></div>
            </div>

            <div class="terms-section">
                <h6>Terms & Conditions</h6>
                <p><?= htmlspecialchars($po['terms_conditions']) ?></p>
            </div>

            <div class="signature-section">
                <p>For <?= htmlspecialchars($issuer['company_name']) ?></p>
                <div class="sign-line"><?= htmlspecialchars($po['authorized_signatory'] ?: 'Authorized Signatory') ?></div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('download-btn').addEventListener('click', function() {
        const element = document.getElementById('po-content');
        const poNumber = "<?= htmlspecialchars($po['po_number']) ?>";
        
        // Temporarily hide shadow to keep PDF edge clean
        const originalShadow = element.style.boxShadow;
        element.style.boxShadow = "none";
        
        const opt = {
            margin:       [0.3, 0.3, 0.3, 0.3], // Add small margins all around
            filename:     'PO_' + poNumber + '.pdf',
            image:        { type: 'jpeg', quality: 1 },
            html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
        };

        html2pdf().set(opt).from(element).save().then(() => {
            // Restore shadow after generation
            element.style.boxShadow = originalShadow;
        });
    });
    </script>

</body>
</html>