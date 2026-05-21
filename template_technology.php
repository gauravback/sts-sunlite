<style>
    /* =======================================================
       TCI (TECHNOLOGY CENTRE IMARAT) - EXACT MATCH TEMPLATE
       ======================================================= */
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap');

    :root {
        --tci-primary: #123e75; /* Exact matching deep navy blue from image */
        --tci-secondary: #f0f4f8; 
        --tci-text: #000000; /* Solid black for precise text matching */
        --tci-muted: #475569;
        --tci-border: #cbd5e1;
    }
    
    .quotation-page {
        width: 210mm; 
        height: 296.5mm; 
        background: #ffffff;
        box-sizing: border-box; 
        position: relative; 
        display: flex;
        flex-direction: column; 
        overflow: hidden; 
        box-shadow: 0 5px 20px rgba(0,0,0,0.1); 
        margin: 0 auto;
        font-family: 'Roboto', sans-serif;
    }

    /* --- 1. TCI EXACT HEADER --- */
    .tci-header-container {
        padding: 15mm 15mm 5mm 15mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
    }
    
    /* Logo - Border and shadow adjusted to match image's flat look */
    .tci-logo-box {
        width: 75px; 
        height: 75px; 
        background: #ffffff;
        /*border: 2px solid var(--tci-primary); */
        border-radius: 50%;
        display: flex; 
        justify-content: center; 
        align-items: center; 
        font-weight: bold; 
        color: var(--tci-primary); 
        font-size: 28px;
        font-style: italic;
        font-family: Arial, sans-serif;
        box-shadow: none; 
    }
    /* Note: Agar aapke paas actual logo image hai, toh isse uncomment karein
    .tci-logo-box { border: none; }
    .tci-logo-box img { width: 100%; height: auto; object-fit: contain; } 
    */

    .tci-company-info {
        text-align: center; 
        flex: 1; 
        padding: 0 15px;
    }
    .tci-company-info h1 {
        color: var(--tci-primary); 
        font-weight: bold; 
        font-size: 26px; /* Perfect scale */
        margin: 0 0 5px 0; 
        font-family: 'Times New Roman', Times, serif; /* Exact serif font from image */
        letter-spacing: 0.5px;
    }
    .tci-company-info p {
        font-size: 11px; 
        color: var(--tci-text); 
        margin: 0 0 3px 0; 
        font-family: Arial, sans-serif;
    }
    .tci-company-info p strong {
        font-weight: bold;
    }
    
    .tci-spacer { width: 75px; } /* Same width as logo to keep text perfectly centered */

    .tci-hr-blue { 
        width: 100%; 
        height: 2.5px; 
        background-color: var(--tci-primary); 
    }

    /* --- 2. MAIN BODY CONTENT (Kept intact) --- */
    .tci-body {
        padding: 8mm 15mm 20mm 15mm; 
        flex: 1; 
        display: flex; 
        flex-direction: column; 
    }
    
    .tci-meta-bar {
        display: flex; justify-content: space-between; align-items: center;
        background: var(--tci-secondary);
        border-left: 5px solid var(--tci-primary);
        padding: 10px 15px; margin-bottom: 18px; border-radius: 0 6px 6px 0;
    }
    .tci-meta-bar div { display: flex; flex-direction: column; gap: 2px; }
    .tci-meta-label { font-size: 10px; font-weight: 700; color: var(--tci-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .tci-meta-value { font-size: 15px; font-weight: 900; color: var(--tci-primary); }

    .tci-address-grid { display: flex; gap: 18px; margin-bottom: 20px; }
    .tci-addr-card { flex: 1; padding: 12px 15px; background: #ffffff; border: 1px solid var(--tci-border); border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
    .tci-addr-card .badge-title { display: inline-block; color: #ffffff; background: var(--tci-primary); font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 4px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
    .tci-addr-card h6 { font-weight: 800; margin: 0 0 5px 0; font-size: 14px; color: var(--tci-text); }
    .tci-addr-card p { margin: 0 0 3px 0; font-size: 11.5px; color: var(--tci-muted); line-height: 1.4; }
    .tci-addr-card .tax-info { margin-top: 8px; font-size: 11px; font-weight: 700; color: var(--tci-text); background: var(--tci-secondary); padding: 5px; border-radius: 4px;}

    .tci-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 20px; border: 1px solid var(--tci-border); border-radius: 6px; overflow: hidden; }
    .tci-table th, .tci-table td { border-bottom: 1px solid var(--tci-border); border-right: 1px solid var(--tci-border); padding: 10px 12px; font-size: 11px; }
    .tci-table th:last-child, .tci-table td:last-child { border-right: none; }
    .tci-table tbody tr:last-child td { border-bottom: none; }
    .tci-table th { background-color: var(--tci-secondary); color: var(--tci-primary); text-align: center; text-transform: uppercase; font-weight: 800; font-size: 11px; letter-spacing: 0.5px; }
    .tci-table td { text-align: center; color: var(--tci-text); font-weight: 500; }
    .tci-table td:first-child, .tci-table th:first-child { text-align: left; }
    .tci-table td:last-child { text-align: right; font-weight: 800; font-size: 12px;}
    .tci-table-item-name { font-size: 12px; font-weight: 700; color: #000; }

    .tci-bottom-grid { display: flex; gap: 25px; margin-top: auto; }
    
    .tci-info-col { flex: 1.3; display: flex; flex-direction: column; gap: 15px; }
    .tci-bank-card { border: 1px solid var(--tci-border); padding: 12px; background: #fff; border-radius: 8px; }
    .tci-bank-card h6 { font-size: 11px; font-weight: 800; color: var(--tci-primary); margin-bottom: 10px; text-transform: uppercase; display:flex; align-items:center; gap: 5px;}
    .tci-bank-table { width: 100%; font-size: 11px; }
    .tci-bank-table td { padding: 4px 0; }
    .tci-bank-table td:first-child { font-weight: 700; color: var(--tci-muted); width: 90px; }
    .tci-bank-table td:last-child { font-weight: 700; color: var(--tci-text); }

    .tci-terms-container { font-size: 10px; color: var(--tci-muted); line-height: 1.6; border-left: 3px solid var(--tci-primary); padding-left: 10px;}
    .tci-terms-container h6 { font-size: 11px; font-weight: 800; color: var(--tci-text); margin-bottom: 5px; text-transform: uppercase; }

    .tci-totals-card { flex: 1; }
    .tci-tot-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; font-weight: 600; color: var(--tci-muted); }
    .tci-tot-row span:last-child { color: var(--tci-text); font-weight: 800; }
    
    .tci-grand-tot-box { 
        background: var(--tci-primary); 
        color: white; 
        padding: 12px 15px; 
        margin-top: 10px; 
        border-radius: 8px;
        font-size: 16px; 
        font-weight: 900; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        box-shadow: 0 4px 15px rgba(18, 62, 117, 0.2);
    }

    .tci-signature-wrapper { text-align: right; margin-top: 25px; display: flex; flex-direction: column; align-items: flex-end; }
    .tci-sig-img { max-height: 90px; max-width: 200px; object-fit: contain; margin-bottom: 5px; }
    .tci-sig-line { border-bottom: 1.5px solid var(--tci-text); width: 200px; margin: 30px 0 8px 0; }
    .tci-sig-role { font-size: 10px; font-weight: 700; color: var(--tci-muted); }
    .tci-sig-company { font-size: 13px; font-weight: 900; color: var(--tci-primary); margin-top: 3px; }

    /* --- 3. EXACT FOOTER --- */
    .tci-footer-absolute { 
        position: absolute; 
        bottom: 0; 
        left: 0; 
        width: 100%; 
        background: white; 
    }
    .tci-address-footer { 
        text-align: center; 
        font-size: 10px; 
        color: var(--tci-text); /* Black text just like the image */
        padding: 10px 15mm 15px 15mm; 
        font-family: Arial, sans-serif;
    }
  .tci-logo-box {
        width: 85px; 
        height: 85px; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        background: transparent;
    }
    
    .tci-logo-box img {
        width: 100%;
        height: 100%;
        object-fit: contain; /* Ye logo ko fटने nahi dega aur perfect fit karega */
    }
</style>

<?php 
$total_qty = 0; 
foreach($item_chunks as $page_index => $chunk): 
    $is_last_page = ($page_index === $total_pages - 1);
?>

<div class="quotation-page">
    
    <div class="tci-header-container">
        <div class="tci-logo-box">
            <img src="assets/img/tci.jpeg" alt="TCI Logo">
        </div>
        
        <div class="tci-company-info">
            <h1><?= strtoupper($iss_name) ?></h1>
            <p>
                <strong>GST No:</strong> - <?= $iss_gstin ?>, &nbsp;&nbsp;&nbsp; 
                <strong>E-mail ID:</strong> - technologycentreimarat@gmail.com
            </p>
            <p><strong>Contact No.:</strong> - <?= $iss_phone ?></p>
        </div>
        
        <div class="tci-spacer"></div> 
    </div>
    
    <div class="tci-hr-blue"></div>

    <div class="tci-body">
        
        <div class="tci-meta-bar">
            <div>
                <span class="tci-meta-label"><?= $doc_label ?></span>
                <span class="tci-meta-value">#<?= $quotation['quotation_no'] ?></span>
            </div>
            <div style="text-align: right;">
                <span class="tci-meta-label">DATE ISSUED</span>
                <span class="tci-meta-value"><?= $display_date ?></span>
            </div>
        </div>

        <div class="tci-address-grid">
            <div class="tci-addr-card">
                <span class="badge-title">Billed To</span>
                <h6><?= $cust_company ?: $cust_name ?></h6>
                <p><?= $cust_name ?></p>
                <p>Phone: <?= $cust_phone ?></p>
                <div class="tax-info">GSTIN: <?= $cust_gst ?> &nbsp;|&nbsp; PAN: <?= $cust_pan ?></div>
            </div>
            <div class="tci-addr-card">
                <span class="badge-title">Shipped To</span>
                <h6><?= $cust_company ?: $cust_name ?></h6>
                <p style="font-style:italic; color:#94a3b8; margin-top:8px;">Same as billing address unless specified otherwise.</p>
            </div>
        </div>

        <table class="tci-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Item Description</th>
                    <th style="width: 12%;">Qty</th>
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
                    <td><div class="tci-table-item-name"><?= htmlspecialchars($item['item_name']) ?></div></td>
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
        <div class="tci-bottom-grid">
            
            <div class="tci-info-col">
                <div class="tci-bank-card">
                    <h6><i class="fas fa-university"></i> Payment Information</h6>
                    <table class="tci-bank-table">
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
                <div class="tci-terms-container">
                    <h6>Terms & Conditions</h6>
                    <div><?= nl2br(htmlspecialchars($quotation['terms'])) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="tci-totals-card">
                <div class="tci-tot-row"><span>Total Quantity</span><span><?= $total_qty ?></span></div>
                <div class="tci-tot-row"><span>Taxable Amount</span><span>₹<?= number_format($quotation['sub_total'], 2) ?></span></div>
                <div class="tci-tot-row"><span>Total Tax (GST)</span><span>₹<?= number_format($quotation['total_tax'], 2) ?></span></div>
                
                <?php if($quotation['discount'] > 0): ?>
                <div class="tci-tot-row" style="color:#ef4444;"><span>Discount</span><span>- ₹<?= number_format($quotation['discount'], 2) ?></span></div>
                <?php endif; ?>
                
                <div class="tci-grand-tot-box">
                    <span>GRAND TOTAL</span>
                    <span>₹<?= number_format($quotation['grand_total'], 2) ?></span>
                </div>

                <div class="tci-signature-wrapper">
                    <?php if(!empty($iss_signature) && file_exists('uploads/signatures/' . $iss_signature)): ?>
                        <div style="margin-top: 20px;"></div>
                        <img src="uploads/signatures/<?= $iss_signature ?>" alt="Signature" class="tci-sig-img">
                    <?php else: ?>
                        <div class="tci-sig-line"></div>
                    <?php endif; ?>
                    
                    <div class="tci-sig-role">AUTHORISED SIGNATORY FOR</div>
                    <div class="tci-sig-company"><?= strtoupper($iss_name) ?></div>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div style="text-align: right; font-size: 11px; font-weight: 800; color: var(--tci-muted); margin-top: auto;">
                Continued on next page... (Page <?= $page_index + 1 ?> of <?= $total_pages ?>)
            </div>
        <?php endif; ?>
        
    </div>

    <div class="tci-footer-absolute">
        <div class="tci-hr-blue"></div>
        <div class="tci-address-footer">
            <strong>Address:</strong> - <?= $iss_address ?>
        </div>
    </div>

</div>

<?php if(!$is_last_page): ?>
    <div class="html2pdf__page-break"></div>
<?php endif; ?>

<?php endforeach; ?>