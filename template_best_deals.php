<style>
    /* =======================================================
       BEST DEALS CORPORATION - EXACT MATCH TEMPLATE
       ======================================================= */
    
    :root {
        --bd-navy: #101c32; /* Exact Deep Corporate Navy Blue */
        --bd-red: #8B0000;  
        --bd-text: #000000; /* Pure black for crisp PDF rendering */
        --bd-muted: #475569;
        --bd-border: #cbd5e1; 
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
        font-family: Arial, Helvetica, sans-serif;
        padding: 0; 
    }

    /* --- 1. EXACT HEADER (No wrapping, lighter font) --- */
    .bd-header-wrap {
        padding: 12mm 15mm 0 15mm; 
        display: flex;
        align-items: center;
        justify-content: flex-start; /* Changed to let text breathe */
        gap: 15px; /* Space between logo and text */
    }
    
    .bd-logo-box {
        width: 175px; 
        height: 55px;
        flex-shrink: 0; /* Logo ko chhota hone se rokega */
        display: flex;
        align-items: center;
    }
    .bd-logo-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .bd-company-info {
        flex: 1; /* Baaki saari jagah le lega taaki text na toote */
        text-align: center;
    }
    .bd-company-info h1 {
        color: var(--bd-navy);
        font-size: 23px; /* Perfect size */
        font-weight: 700; /* Halka/Light kar diya (800 se 700) */
        margin: 0 0 6px 0;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        white-space: nowrap; /* Ye text ko hamesha 1 line me rakhega */
    }
    .bd-company-info p {
        font-size: 11.5px;
        color: var(--bd-text);
        margin: 0;
        white-space: nowrap; 
    }
    .bd-company-info p strong {
        font-weight: 700; /* Isko bhi halka bold rakha hai */
    }

    .bd-header-line {
        height: 2.5px; /* Exact line thickness */
        background-color: var(--bd-navy);
        margin: 10px 15mm 0 15mm;
    }

    /* --- 2. MAIN BODY CONTENT --- */
    .bd-body {
        padding: 6mm 15mm 20mm 15mm; 
        flex: 1; 
        display: flex; 
        flex-direction: column; 
    }
    
    .bd-meta-bar {
        display: flex; justify-content: space-between; align-items: center;
        background: #f8fafc; border-left: 3px solid var(--bd-navy);
        padding: 8px 12px; margin-bottom: 15px;
    }
    .bd-meta-bar div { display: flex; flex-direction: column; gap: 2px;}
    .bd-meta-label { font-size: 9px; font-weight: 700; color: var(--bd-muted); text-transform: uppercase; }
    .bd-meta-value { font-size: 13px; font-weight: 800; color: var(--bd-navy); }

    .bd-address-grid { display: flex; gap: 15px; margin-bottom: 15px; }
    .bd-addr-card { flex: 1; padding: 10px 12px; border: 1px solid var(--bd-border); border-radius: 4px; }
    .bd-addr-card .badge-title { display: inline-block; color: white; background: var(--bd-navy); font-size: 9px; font-weight: 700; padding: 3px 8px; border-radius: 3px; margin-bottom: 6px; text-transform: uppercase; }
    .bd-addr-card h6 { font-weight: 800; margin: 0 0 4px 0; font-size: 13px; color: var(--bd-text); }
    .bd-addr-card p { margin: 0 0 2px 0; font-size: 11px; color: var(--bd-muted); font-weight: 500; }
    .bd-addr-card .tax-info { margin-top: 6px; font-size: 10px; font-weight: 700; color: var(--bd-text); }

    .bd-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; border: 1px solid var(--bd-border); }
    .bd-table th, .bd-table td { border: 1px solid var(--bd-border); padding: 8px 10px; font-size: 11px; }
    .bd-table th { background-color: #f8fafc; color: var(--bd-navy); text-align: center; text-transform: uppercase; font-weight: 800; font-size: 10px;}
    .bd-table td { text-align: center; color: var(--bd-text); font-weight: 500; }
    .bd-table td:first-child, .bd-table th:first-child { text-align: left; }
    .bd-table td:last-child { text-align: right; font-weight: 700; }
    .bd-table-item-name { font-size: 11.5px; font-weight: 700; color: var(--bd-text); }

    .bd-bottom-grid { display: flex; gap: 20px; margin-top: auto; }
    
    .bd-info-col { flex: 1.2; display: flex; flex-direction: column; gap: 12px; }
    .bd-bank-card { border: 1px solid var(--bd-border); padding: 10px; background: #f8fafc; border-radius: 4px;}
    .bd-bank-card h6 { font-size: 10px; font-weight: 800; color: var(--bd-navy); margin-bottom: 6px; text-transform: uppercase; border-bottom: 1px solid var(--bd-border); padding-bottom: 4px; }
    .bd-bank-table { width: 100%; font-size: 10.5px; }
    .bd-bank-table td { padding: 3px 0; }
    .bd-bank-table td:first-child { font-weight: 700; color: var(--bd-muted); width: 85px; }
    .bd-bank-table td:last-child { font-weight: 700; color: var(--bd-text); }

    .bd-terms-container { font-size: 10px; color: var(--bd-muted); line-height: 1.5; border-left: 2px solid var(--bd-navy); padding-left: 10px;}
    .bd-terms-container h6 { font-size: 10.5px; font-weight: 800; color: var(--bd-text); margin-bottom: 4px; text-transform: uppercase; }

    .bd-totals-card { flex: 1; }
    .bd-tot-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 11.5px; font-weight: 500; color: var(--bd-muted); }
    .bd-tot-row span:last-child { color: var(--bd-text); font-weight: 800; }
    
    .bd-grand-tot-box { 
        background: var(--bd-navy); 
        color: white; 
        padding: 10px 12px; 
        margin-top: 8px; 
        border-radius: 4px;
        font-size: 14px; 
        font-weight: 800; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
    }

    .bd-signature-wrapper { text-align: right; margin-top: 20px; display: flex; flex-direction: column; align-items: flex-end; }
    .bd-sig-img { max-height: 80px; max-width: 180px; object-fit: contain; margin-bottom: 5px; }
    .bd-sig-line { border-bottom: 1px solid var(--bd-muted); width: 180px; margin: 25px 0 5px 0; }
    .bd-sig-role { font-size: 9px; font-weight: 700; color: var(--bd-muted); }
    .bd-sig-company { font-size: 11px; font-weight: 800; color: var(--bd-navy); margin-top: 2px; }

    /* --- 3. EXACT FOOTER --- */
    .bd-footer-absolute { 
        position: absolute; 
        bottom: 0; 
        left: 0; 
        width: 100%; 
        background: white; 
    }
    .bd-footer-line { 
        height: 2px; 
        background-color: var(--bd-navy); 
        margin: 0 15mm; 
    }
    .bd-footer-content { 
        text-align: center; 
        padding: 6px 15mm 12px 15mm; 
        font-size: 10.5px; 
        color: var(--bd-text); 
        line-height: 1.6; 
    }
    .bd-footer-content strong { font-weight: 800; color: var(--bd-text); }
    
    .bd-footer-bottom-block { 
        width: 100%; 
        height: 15px; 
        background-color: var(--bd-navy); 
    }
</style>

<?php 
$total_qty = 0; 
foreach($item_chunks as $page_index => $chunk): 
    $is_last_page = ($page_index === $total_pages - 1);
?>

<div class="quotation-page">
    
    <div class="bd-header-wrap">
        <div class="bd-logo-box">
            <img src="assets/img/best-1.jpeg" alt="Best Deals Corporation Logo">
        </div>
        
        <div class="bd-company-info">
            <h1><?= strtoupper($iss_name) ?></h1>
            <p>
                <strong>GSTIN:</strong> <?= $iss_gstin ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
                <strong>Website:</strong> www.bestdealscorp.in
            </p>
        </div>
    </div>
    
    <div class="bd-header-line"></div>

    <div class="bd-body">
        
        <div class="bd-meta-bar">
            <div>
                <span class="bd-meta-label"><?= $doc_label ?></span>
                <span class="bd-meta-value">#<?= $quotation['quotation_no'] ?></span>
            </div>
            <div style="text-align: right;">
                <span class="bd-meta-label">DATE ISSUED</span>
                <span class="bd-meta-value"><?= $display_date ?></span>
            </div>
        </div>

        <div class="bd-address-grid">
            <div class="bd-addr-card">
                <span class="badge-title">Billed To</span>
                <h6><?= $cust_company ?: $cust_name ?></h6>
                <p><?= $cust_name ?></p>
                <p>Phone: <?= $cust_phone ?></p>
                <div class="tax-info">GSTIN: <?= $cust_gst ?> &nbsp;|&nbsp; PAN: <?= $cust_pan ?></div>
            </div>
            <div class="bd-addr-card">
                <span class="badge-title">Shipped To</span>
                <h6><?= $cust_company ?: $cust_name ?></h6>
                <p style="font-style:italic; color:#94a3b8; margin-top:8px;">Same as billing address unless specified otherwise.</p>
            </div>
        </div>

        <table class="bd-table">
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
                    <td><div class="bd-table-item-name"><?= htmlspecialchars($item['item_name']) ?></div></td>
                    <td><?= $item['qty'] ?></td>
                    <td>₹<?= number_format($item['price'], 2) ?></td>
                    <td>
                        ₹<?= number_format($tax_amt, 2) ?><br>
                        <span style="font-size: 9px; color: #64748b; font-weight:700;">(<?= $item['tax_percent'] ?>%)</span>
                    </td>
                    <td>₹<?= number_format($item['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if($is_last_page): ?>
        <div class="bd-bottom-grid">
            
            <div class="bd-info-col">
                <div class="bd-bank-card">
                    <h6>Payment Information</h6>
                    <table class="bd-bank-table">
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
                <div class="bd-terms-container">
                    <h6>Terms & Conditions</h6>
                    <div><?= nl2br(htmlspecialchars($quotation['terms'])) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="bd-totals-card">
                <div class="bd-tot-row"><span>Total Quantity</span><span><?= $total_qty ?></span></div>
                <div class="bd-tot-row"><span>Taxable Amount</span><span>₹<?= number_format($quotation['sub_total'], 2) ?></span></div>
                <div class="bd-tot-row"><span>Total Tax (GST)</span><span>₹<?= number_format($quotation['total_tax'], 2) ?></span></div>
                
                <?php if($quotation['discount'] > 0): ?>
                <div class="bd-tot-row" style="color:#ef4444;"><span>Discount</span><span>- ₹<?= number_format($quotation['discount'], 2) ?></span></div>
                <?php endif; ?>
                
                <div class="bd-grand-tot-box">
                    <span>GRAND TOTAL</span>
                    <span>₹<?= number_format($quotation['grand_total'], 2) ?></span>
                </div>

                <div class="bd-signature-wrapper">
                    <?php if(!empty($iss_signature) && file_exists('uploads/signatures/' . $iss_signature)): ?>
                        <div style="margin-top: 15px;"></div>
                        <img src="uploads/signatures/<?= $iss_signature ?>" alt="Signature" class="bd-sig-img">
                    <?php else: ?>
                        <div class="bd-sig-line"></div>
                    <?php endif; ?>
                    
                    <div class="bd-sig-role">AUTHORISED SIGNATORY FOR</div>
                    <div class="bd-sig-company"><?= strtoupper($iss_name) ?></div>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div style="text-align: right; font-size: 10px; font-weight: 700; color: var(--bd-muted); margin-top: auto;">
                Continued on next page... (Page <?= $page_index + 1 ?> of <?= $total_pages ?>)
            </div>
        <?php endif; ?>
        
    </div>

    <div class="bd-footer-absolute">
        <div class="bd-footer-line"></div>
        <div class="bd-footer-content">
            <strong>Contact No:</strong> <?= $iss_phone ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <strong>Email:</strong> info@bestdealscorp.in <br>
            <strong>Address:</strong> Registered Office: <?= $iss_address ?>
        </div>
        <div class="bd-footer-bottom-block"></div>
    </div>

</div>

<?php if(!$is_last_page): ?>
    <div class="html2pdf__page-break"></div>
<?php endif; ?>

<?php endforeach; ?>