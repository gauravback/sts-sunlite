<style>
    /* =======================================================
       SUNLITE SYSTEMS - EXACT MATCH LETTERHEAD TEMPLATE
       ======================================================= */
    
    :root {
        --sl-navy: #0a2054; /* Exact deep corporate navy blue */
        --sl-yellow: #ffb800; /* Exact golden yellow */
        --sl-text: #000000; /* Pure black for clear printing */
        --sl-muted: #475569;
        --sl-border: #cbd5e1;
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
        box-shadow: 0 5px 20px rgba(0,0,0,0.05); 
        margin: 0 auto;
        font-family: Arial, Helvetica, sans-serif;
    }

    /* --- 1. EXACT HEADER --- */
    .sl-header-container {
        padding: 12mm 15mm 5mm 15mm;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .sl-logo-box {
        width: 65px; 
        height: 65px; 
        display: flex; 
        justify-content: flex-start; 
        align-items: center; 
        flex-shrink: 0;
    }
    .sl-logo-box img {
        width: 100%;
        height: 100%;
        object-fit: contain; 
    }

    /* Center Company Info */
    .sl-company-info {
        flex: 1; 
        text-align: center; 
        padding: 0 10px;
    }
    .sl-company-info h1 {
        color: var(--sl-navy); 
        font-weight: 700; /* Boldness reduced */
        font-size: 24px; 
        margin: 0 0 6px 0; 
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .sl-company-info p {
        font-size: 12px; /* Size increased */
        color: var(--sl-text); 
        margin: 0; 
        font-weight: 500; /* Boldness reduced */
    }
    .sl-company-info p strong {
        font-weight: 700; /* Boldness reduced */
        color: var(--sl-text);
    }
    
    /* Right Contact Info */
    .sl-contact-info {
        text-align: right; 
        font-size: 12px; /* Size increased */
        font-weight: 500; /* Boldness reduced */
        color: var(--sl-text); 
        line-height: 1.5;
        flex-shrink: 0;
    }
    .sl-icon { 
        color: var(--sl-navy); 
        margin-left: 6px; 
        font-size: 12px; 
    }

    /* Exact Split-Color Line (Yellow -> Blue -> Yellow) */
    .sl-split-line { 
        width: calc(100% - 30mm); 
        height: 3.5px; 
        margin: 5px auto 0 auto;
        background: linear-gradient(to right, var(--sl-yellow) 0%, var(--sl-yellow) 35%, var(--sl-navy) 35%, var(--sl-navy) 65%, var(--sl-yellow) 65%, var(--sl-yellow) 100%);
    }

    /* --- 2. MAIN BODY CONTENT --- */
    .sl-body {
        padding: 6mm 15mm 20mm 15mm; 
        flex: 1; 
        display: flex; 
        flex-direction: column; 
    }
    
    .sl-meta-bar {
        display: flex; justify-content: space-between; align-items: center;
        background: #f8fafc; border: 1px solid var(--sl-border); border-radius: 4px;
        padding: 8px 12px; margin-bottom: 15px;
    }
    .sl-meta-bar div { display: flex; flex-direction: column; gap: 2px;}
    .sl-meta-label { font-size: 10px; font-weight: 600; color: var(--sl-muted); text-transform: uppercase; } /* Size up, bold down */
    .sl-meta-value { font-size: 13.5px; font-weight: 700; color: var(--sl-navy); } /* Bold down */

    .sl-address-grid { display: flex; gap: 15px; margin-bottom: 15px; }
    .sl-addr-card { flex: 1; border: 1px solid var(--sl-border); border-radius: 4px; padding: 10px; }
    .sl-addr-card .badge-title { display: inline-block; background: var(--sl-navy); color: white; font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 3px; margin-bottom: 8px; text-transform: uppercase; }
    .sl-addr-card h6 { font-weight: 700; margin: 0 0 4px 0; font-size: 13px; color: var(--sl-text); }
    .sl-addr-card p { margin: 0 0 2px 0; font-size: 11.5px; color: var(--sl-text); font-weight: 400; } /* Size up, normal weight */
    .sl-addr-card .tax-info { margin-top: 6px; font-size: 11px; font-weight: 600; color: var(--sl-muted); }

    .sl-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .sl-table th, .sl-table td { border: 1px solid var(--sl-border); padding: 8px 10px; font-size: 11.5px; } /* Size up */
    .sl-table th { background-color: var(--sl-navy); color: white; text-align: center; text-transform: uppercase; font-weight: 600; font-size: 11px; border-color: var(--sl-navy); } /* Bold down */
    .sl-table td { text-align: center; color: var(--sl-text); font-weight: 400; } /* Bold down */
    .sl-table td:first-child, .sl-table th:first-child { text-align: left; }
    .sl-table td:last-child { text-align: right; font-weight: 600; }
    .sl-table-item-name { font-size: 12px; font-weight: 600; color: var(--sl-navy); }

    .sl-bottom-grid { display: flex; gap: 20px; margin-top: auto; }
    
    .sl-info-col { flex: 1.2; display: flex; flex-direction: column; gap: 12px; }
    .sl-bank-card { border: 1px solid var(--sl-border); border-radius: 4px; padding: 10px; background: #f8fafc; }
    .sl-bank-card h6 { font-size: 11px; font-weight: 700; color: var(--sl-navy); margin-bottom: 8px; text-transform: uppercase; border-bottom: 1px solid var(--sl-border); padding-bottom: 4px; }
    .sl-bank-table { width: 100%; font-size: 11.5px; } /* Size up */
    .sl-bank-table td { padding: 3px 0; }
    .sl-bank-table td:first-child { font-weight: 600; color: var(--sl-muted); width: 85px; }
    .sl-bank-table td:last-child { font-weight: 600; color: var(--sl-text); } /* Bold down */

    .sl-terms-container { font-size: 11px; color: var(--sl-muted); line-height: 1.5; } /* Size up */
    .sl-terms-container h6 { font-size: 11.5px; font-weight: 700; color: var(--sl-navy); margin-bottom: 4px; text-transform: uppercase; }

    .sl-totals-card { flex: 1; }
    .sl-tot-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; font-weight: 500; color: var(--sl-muted); } /* Size up, bold down */
    .sl-tot-row span:last-child { color: var(--sl-text); font-weight: 700; } /* Bold down */
    
    .sl-grand-tot-box {
        background: var(--sl-navy); 
        color: white; 
        padding: 10px 12px; 
        border-radius: 4px; 
        margin-top: 8px; 
        font-size: 14px; 
        font-weight: 700; /* Bold down */
        display: flex; 
        justify-content: space-between; 
        align-items: center;
    }

    .sl-signature-wrapper { text-align: right; margin-top: 20px; display: flex; flex-direction: column; align-items: flex-end; }
    .sl-sig-img { max-height: 80px; max-width: 180px; object-fit: contain; margin-bottom: 5px; }
    .sl-sig-line { border-bottom: 1px solid var(--sl-muted); width: 180px; margin: 25px 0 5px 0; }
    .sl-sig-role { font-size: 10px; font-weight: 600; color: var(--sl-muted); }
    .sl-sig-company { font-size: 12px; font-weight: 700; color: var(--sl-navy); margin-top: 2px; }

    /* --- 3. EXACT FOOTER --- */
    .sl-footer-absolute {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        background: white;
    }
    .sl-footer-split-line {
        width: 100%;
        height: 3.5px;
        background: linear-gradient(to right, var(--sl-yellow) 0%, var(--sl-yellow) 35%, var(--sl-navy) 35%, var(--sl-navy) 65%, var(--sl-yellow) 65%, var(--sl-yellow) 100%);
    }
    .sl-address-footer {
        text-align: center; 
        font-size: 12.5px; /* Size up */
        font-weight: 600; /* Boldness reduced */
        color: var(--sl-text); 
        padding: 8px 15mm 12px 15mm;
    }
</style>

<?php 
$total_qty = 0; 
foreach($item_chunks as $page_index => $chunk): 
    $is_last_page = ($page_index === $total_pages - 1);
?>

<div class="quotation-page">
    
    <div class="sl-header-container">
        <div class="sl-logo-box">
            <img src="assets/img/sunlite-1.jpeg" alt="Sunlite Logo">
        </div>
        
        <div class="sl-company-info">
            <h1><?= strtoupper($iss_name) ?></h1>
            <p><strong>Gst No.:-</strong> <?= $iss_gstin ?> &nbsp;&nbsp;&nbsp; <strong>Website:-</strong> www.sunlitesystems.com</p>
        </div>
        
        <div class="sl-contact-info">
            <p style="margin: 0 0 3px 0;"><?= $iss_phone ?> <i class="fas fa-phone-alt sl-icon"></i></p>
            <p style="margin: 0;">sales@sunlitesystems.com <i class="fas fa-envelope sl-icon"></i></p>
        </div>
    </div>
    
    <div class="sl-split-line"></div>

    <div class="sl-body">
        
        <div class="sl-meta-bar">
            <div>
                <span class="sl-meta-label"><?= $doc_label ?></span>
                <span class="sl-meta-value">#<?= $quotation['quotation_no'] ?></span>
            </div>
            <div style="text-align: right;">
                <span class="sl-meta-label">DATE ISSUED</span>
                <span class="sl-meta-value"><?= $display_date ?></span>
            </div>
        </div>

        <div class="sl-address-grid">
            <div class="sl-addr-card">
                <span class="badge-title">Billed To</span>
                <h6><?= $cust_company ?: $cust_name ?></h6>
                <p><?= $cust_name ?></p>
                <p>Phone: <?= $cust_phone ?></p>
                <div class="tax-info">GSTIN: <?= $cust_gst ?> | PAN: <?= $cust_pan ?></div>
            </div>
            <div class="sl-addr-card">
                <span class="badge-title">Shipped To</span>
                <h6><?= $cust_company ?: $cust_name ?></h6>
                <p style="font-style:italic; color:#94a3b8; font-size:10.5px; margin-top:5px;">Same as billing address unless specified otherwise.</p>
            </div>
        </div>

        <table class="sl-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Item Description</th>
                    <th style="width: 10%;">Qty</th>
                    <th style="width: 15%;">Price</th>
                    <th style="width: 15%;">Tax</th>
                    <th style="width: 15%;">Amount</th>
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
                    <td><div class="sl-table-item-name"><?= htmlspecialchars($item['item_name']) ?></div></td>
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
        <div class="sl-bottom-grid">
            <div class="sl-info-col">
                <div class="sl-bank-card">
                    <h6><i class="fas fa-university me-1"></i> Payment Information</h6>
                    <table class="sl-bank-table">
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
                <div class="sl-terms-container">
                    <h6>Terms & Conditions</h6>
                    <div><?= nl2br(htmlspecialchars($quotation['terms'])) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <div class="sl-totals-card">
                <div class="sl-tot-row"><span>Total Quantity</span><span><?= $total_qty ?></span></div>
                <div class="sl-tot-row"><span>Taxable Amount</span><span>₹<?= number_format($quotation['sub_total'], 2) ?></span></div>
                <div class="sl-tot-row"><span>Total Tax (GST)</span><span>₹<?= number_format($quotation['total_tax'], 2) ?></span></div>
                
                <?php if($quotation['discount'] > 0): ?>
                <div class="sl-tot-row" style="color:#ef4444;"><span>Discount</span><span>- ₹<?= number_format($quotation['discount'], 2) ?></span></div>
                <?php endif; ?>
                
                <div class="sl-grand-tot-box">
                    <span>GRAND TOTAL</span>
                    <span>₹<?= number_format($quotation['grand_total'], 2) ?></span>
                </div>

                <div class="sl-signature-wrapper">
                    <?php if(!empty($iss_signature) && file_exists('uploads/signatures/' . $iss_signature)): ?>
                        <div style="margin-top: 15px;"></div>
                        <img src="uploads/signatures/<?= $iss_signature ?>" alt="Signature" class="sl-sig-img">
                    <?php else: ?>
                        <div class="sl-sig-line"></div>
                    <?php endif; ?>
                    
                    <div class="sl-sig-role">AUTHORISED SIGNATORY FOR</div>
                    <div class="sl-sig-company"><?= strtoupper($iss_name) ?></div>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div style="text-align: right; font-size: 10px; font-weight: 700; color: var(--sl-muted); margin-top: auto;">
                Continued on next page... (Page <?= $page_index + 1 ?> of <?= $total_pages ?>)
            </div>
        <?php endif; ?>
        
    </div>

    <div class="sl-footer-absolute">
        <div class="sl-footer-split-line"></div>
        <div class="sl-address-footer">
            <strong>Address:</strong> - <?= $iss_address ?>
        </div>
    </div>

</div>

<?php if(!$is_last_page): ?>
    <div class="html2pdf__page-break"></div>
<?php endif; ?>

<?php endforeach; ?>