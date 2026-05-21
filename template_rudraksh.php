<style>
    /* =======================================================
       RUDRAKSH INDUSTRIES - EXACT MATCH LETTERHEAD TEMPLATE
       ======================================================= */
    @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap');

    :root {
        --rud-primary: #852a19; /* Exact Deep Rust / Maroon Brown from image */
        --rud-bg-light: #fdfaf8; 
        --rud-text: #334155;
        --rud-muted: #64748b;
        --rud-border: #eaddd7; 
    }
    
    .quotation-page {
        width: 210mm; 
        height: 296mm; 
        background: #ffffff;
        box-sizing: border-box; 
        position: relative; 
        display: flex;
        flex-direction: column; 
        overflow: hidden; 
        box-shadow: 0 5px 20px rgba(0,0,0,0.05); 
        margin: 0 auto;
        font-family: 'Open Sans', sans-serif; /* Default for body */
        padding: 0; 
        z-index: 1;
    }

    /* --- 1. EXACT HEADER --- */
    .rud-header-wrap {
        padding: 10mm 15mm 6mm 15mm; 
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .rud-logo-box {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        width: 120px; /* Fixed width for perfect center alignment of text */
    }
    .rud-logo-box img {
        width: 75px; /* Adjust size based on your actual logo */
        height: auto;
        object-fit: contain;
    }

    .rud-company-info {
        flex: 1;
        text-align: center;
    }
    .rud-company-info h1 {
        font-family: 'Times New Roman', Times, serif; /* Exact font from image */
        color: var(--rud-primary);
        font-size: 28px; 
        font-weight: bold; 
        margin: 0 0 4px 0;
        letter-spacing: 0.5px;
    }
    .rud-company-info p {
        font-family: 'Times New Roman', Times, serif; /* Exact font for address */
        font-size: 13px;
        color: var(--rud-primary); /* Address text is maroon in image */
        margin: 0;
    }
    .rud-company-info p span {
        font-weight: bold;
    }

    /* Spacer matching logo width to keep center aligned */
    .rud-header-spacer { width: 120px; }

    /* Header Bottom Line */
    .rud-header-line {
        height: 2px;
        background-color: var(--rud-primary);
        margin: 0 15mm;
    }

    /* --- 2. MAIN BODY CONTENT --- */
    .rud-body {
        padding: 8mm 15mm 20mm 15mm; 
        flex: 1; 
        display: flex; 
        flex-direction: column; 
        z-index: 2;
    }
    
    .rud-meta-bar {
        display: flex; justify-content: space-between; align-items: center;
        background: var(--rud-bg-light); border: 1px solid var(--rud-border);
        padding: 8px 12px; margin-bottom: 15px; border-radius: 4px;
    }
    .rud-meta-bar div { display: flex; flex-direction: column; gap: 2px;}
    .rud-meta-label { font-size: 9px; font-weight: 700; color: var(--rud-muted); text-transform: uppercase; }
    .rud-meta-value { font-size: 13px; font-weight: 800; color: var(--rud-primary); }

    .rud-address-grid { display: flex; gap: 15px; margin-bottom: 18px; }
    .rud-addr-card { flex: 1; padding: 12px 15px; border: 1px solid var(--rud-border); border-radius: 4px; background: #ffffff; }
    .rud-addr-card .badge-title { display: inline-block; color: white; background: var(--rud-primary); font-size: 9px; font-weight: 600; padding: 3px 8px; border-radius: 3px; margin-bottom: 8px; text-transform: uppercase; }
    .rud-addr-card h6 { font-weight: 700; margin: 0 0 4px 0; font-size: 13px; color: var(--rud-text); }
    .rud-addr-card p { margin: 0 0 2px 0; font-size: 10.5px; color: var(--rud-muted); font-weight: 500; }
    .rud-addr-card .tax-info { margin-top: 6px; font-size: 10px; font-weight: 600; color: var(--rud-text); }

    .rud-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border: 1px solid var(--rud-border); }
    .rud-table th, .rud-table td { border: 1px solid var(--rud-border); padding: 9px 10px; font-size: 10.5px; }
    .rud-table th { background-color: var(--rud-bg-light); color: var(--rud-primary); text-align: center; text-transform: uppercase; font-weight: 700; font-size: 10px;}
    .rud-table td { text-align: center; color: var(--rud-text); font-weight: 500; background: #ffffff;}
    .rud-table td:first-child, .rud-table th:first-child { text-align: left; }
    .rud-table td:last-child { text-align: right; font-weight: 600; }
    .rud-table-item-name { font-size: 11px; font-weight: 600; color: var(--rud-primary); }

    .rud-bottom-grid { display: flex; gap: 20px; margin-top: auto; }
    
    .rud-info-col { flex: 1.2; display: flex; flex-direction: column; gap: 12px; }
    .rud-bank-card { border: 1px solid var(--rud-border); padding: 10px; background: var(--rud-bg-light); border-radius: 4px;}
    .rud-bank-card h6 { font-size: 10px; font-weight: 700; color: var(--rud-primary); margin-bottom: 6px; text-transform: uppercase; border-bottom: 1px solid var(--rud-border); padding-bottom: 4px; }
    .rud-bank-table { width: 100%; font-size: 10px; }
    .rud-bank-table td { padding: 3px 0; }
    .rud-bank-table td:first-child { font-weight: 600; color: var(--rud-muted); width: 85px; }
    .rud-bank-table td:last-child { font-weight: 600; color: var(--rud-text); }

    .rud-terms-container { font-size: 9.5px; color: var(--rud-muted); line-height: 1.5; border-left: 2px solid var(--rud-primary); padding-left: 10px;}
    .rud-terms-container h6 { font-size: 10px; font-weight: 700; color: var(--rud-text); margin-bottom: 4px; text-transform: uppercase; }

    .rud-totals-card { flex: 1; }
    .rud-tot-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 11px; font-weight: 500; color: var(--rud-muted); }
    .rud-tot-row span:last-child { color: var(--rud-text); font-weight: 600; }
    
    .rud-grand-tot-box { 
        background: var(--rud-primary); 
        color: white; 
        padding: 8px 12px; 
        margin-top: 8px; 
        border-radius: 4px;
        font-size: 14px; 
        font-weight: 700; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
    }

    .rud-signature-wrapper { text-align: right; margin-top: 20px; display: flex; flex-direction: column; align-items: flex-end; }
    .rud-sig-img { max-height: 80px; max-width: 180px; object-fit: contain; margin-bottom: 5px; }
    .rud-sig-line { border-bottom: 1px solid var(--rud-muted); width: 180px; margin: 25px 0 5px 0; }
    .rud-sig-role { font-size: 9px; font-weight: 600; color: var(--rud-muted); }
    .rud-sig-company { font-size: 11px; font-weight: 700; color: var(--rud-primary); margin-top: 2px; }

    /* --- 3. EXACT FOOTER --- */
    .rud-footer-absolute { 
        position: absolute; 
        bottom: 0; 
        left: 0; 
        width: 100%; 
        background: white; 
        z-index: 2;
    }
    .rud-footer-line { 
        height: 2px; 
        background-color: var(--rud-primary); 
        margin: 0 15mm; 
    }
    .rud-footer-content { 
        text-align: center; 
        padding: 6px 15mm 12px 15mm; 
        font-family: 'Times New Roman', Times, serif; /* Exact match font */
        font-size: 11.5px; 
        color: var(--rud-primary); 
        line-height: 1.6; 
    }
    .rud-footer-content .label { font-weight: bold; }
</style>

<?php 
$total_qty = 0; 
foreach($item_chunks as $page_index => $chunk): 
    $is_last_page = ($page_index === $total_pages - 1);
?>

<div class="quotation-page">
    
    <div class="rud-header-wrap">
        <div class="rud-logo-box">
            <img src="assets/img/rudra-1.jpeg" alt="Rudraksh Logo">
        </div>
        
        <div class="rud-company-info">
            <h1 style="text-transform: capitalize;"><?= strtolower($iss_name) ?></h1>
            <p>
                <span>Address:</span> <?= $iss_address ?>
            </p>
        </div>
        
        <div class="rud-header-spacer"></div> 
    </div>
    
    <div class="rud-header-line"></div>

    <div class="rud-body">
        
        <div class="rud-meta-bar">
            <div>
                <span class="rud-meta-label"><?= $doc_label ?></span>
                <span class="rud-meta-value">#<?= $quotation['quotation_no'] ?></span>
            </div>
            <div style="text-align: right;">
                <span class="rud-meta-label">DATE ISSUED</span>
                <span class="rud-meta-value"><?= $display_date ?></span>
            </div>
        </div>

        <div class="rud-address-grid">
            <div class="rud-addr-card">
                <span class="badge-title">Billed To</span>
                <h6><?= $cust_company ?: $cust_name ?></h6>
                <p><?= $cust_name ?></p>
                <p>Phone: <?= $cust_phone ?></p>
                <div class="tax-info">GSTIN: <?= $cust_gst ?> &nbsp;|&nbsp; PAN: <?= $cust_pan ?></div>
            </div>
            <div class="rud-addr-card">
                <span class="badge-title">Shipped To</span>
                <h6><?= $cust_company ?: $cust_name ?></h6>
                <p style="font-style:italic; color:#94a3b8; margin-top:8px;">Same as billing address unless specified otherwise.</p>
            </div>
        </div>

        <table class="rud-table">
            <thead>
                <tr>
                    <th style="width: 42%;">Item Description</th>
                    <th style="width: 10%;">Qty</th>
                    <th style="width: 16%;">Price</th>
                    <th style="width: 16%;">Tax</th>
                    <th style="width: 16%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach($chunk as $item): 
                    $total_qty += $item['qty'];
                    $base_amt = $item['qty'] * $item['price'];
                    $tax_amt = ($base_amt * $item['tax_percent']) / 100;
                ?>
                <tr>
                    <td><div class="rud-table-item-name"><?= htmlspecialchars($item['item_name']) ?></div></td>
                    <td><?= $item['qty'] ?></td>
                    <td>₹<?= number_format($item['price'], 2) ?></td>
                    <td>
                        ₹<?= number_format($tax_amt, 2) ?><br>
                        <span style="font-size: 9px; color: #64748b; font-weight:600;">(<?= $item['tax_percent'] ?>%)</span>
                    </td>
                    <td>₹<?= number_format($item['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if($is_last_page): ?>
        <div class="rud-bottom-grid">
            
            <div class="rud-info-col">
                <div class="rud-bank-card">
                    <h6>Payment Information</h6>
                    <table class="rud-bank-table">
                        <tr><td>A/c Holder:</td><td><?= $acc_holder ?></td></tr>
                        <tr><td>A/c Number:</td><td><?= $acc_num ?></td></tr>
                        <tr><td>Bank:</td><td><?= $bank_name ?>, <?= $branch ?></td></tr>
                        <tr><td>IFSC Code:</td><td><?= $ifsc ?></td></tr>
                        <?php if($upi !== 'N/A' && !empty(trim($upi))): ?>
                        <tr><td>UPI ID:</td><td><?= $upi ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
                
                <?php if(!empty(trim($quotation['terms']))): ?>
                <div class="rud-terms-container">
                    <h6>Terms & Conditions</h6>
                    <div><?= nl2br(htmlspecialchars($quotation['terms'])) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="rud-totals-card">
                <div class="rud-tot-row"><span>Total Quantity</span><span><?= $total_qty ?></span></div>
                <div class="rud-tot-row"><span>Taxable Amount</span><span>₹<?= number_format($quotation['sub_total'], 2) ?></span></div>
                <div class="rud-tot-row"><span>Total Tax (GST)</span><span>₹<?= number_format($quotation['total_tax'], 2) ?></span></div>
                
                <?php if($quotation['discount'] > 0): ?>
                <div class="rud-tot-row" style="color:#ef4444;"><span>Discount</span><span>- ₹<?= number_format($quotation['discount'], 2) ?></span></div>
                <?php endif; ?>
                
                <div class="rud-grand-tot-box">
                    <span>GRAND TOTAL</span>
                    <span>₹<?= number_format($quotation['grand_total'], 2) ?></span>
                </div>

                <div class="rud-signature-wrapper">
                    <?php if(!empty($iss_signature) && file_exists('uploads/signatures/' . $iss_signature)): ?>
                        <div style="margin-top: 15px;"></div>
                        <img src="uploads/signatures/<?= $iss_signature ?>" alt="Signature" class="rud-sig-img">
                    <?php else: ?>
                        <div class="rud-sig-line"></div>
                    <?php endif; ?>
                    
                    <div class="rud-sig-role">AUTHORISED SIGNATORY FOR</div>
                    <div class="rud-sig-company" style="text-transform: capitalize;"><?= strtolower($iss_name) ?></div>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div style="text-align: right; font-size: 10px; font-weight: 600; color: var(--rud-muted); margin-top: auto;">
                Continued on next page... (Page <?= $page_index + 1 ?> of <?= $total_pages ?>)
            </div>
        <?php endif; ?>
        
    </div>

    <div class="rud-footer-absolute">
        <div class="rud-footer-line"></div>
        <div class="rud-footer-content">
            <span class="label">Contact No.:</span> <?= $iss_phone ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <span class="label">Email:</span> sales@rudrakshind.in <br>
            <span class="label">Website:</span> www.rudrakshind.in &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <span class="label">GST No.:</span> <?= $iss_gstin ?>
        </div>
    </div>

</div>

<?php if(!$is_last_page): ?>
    <div class="html2pdf__page-break"></div>
<?php endif; ?>

<?php endforeach; ?>