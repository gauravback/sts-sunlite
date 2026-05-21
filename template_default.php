<style>
    /* =======================================================
       EXACT DESIGN - BLUE THEME (A4 SIZE)
       ======================================================= */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    :root {
        --def-primary: #2563eb;       
        --def-secondary: #3b82f6;     
        --def-light-bg: #eff6ff;      
        --def-light-border: #93c5fd;  
        --def-text: #1e293b;          
        --def-muted: #475569;         
    }
    
    .quotation-page {
        width: 210mm; 
        min-height: 296mm; 
        background: #ffffff;
        box-sizing: border-box; 
        position: relative; 
        display: flex;
        flex-direction: column; 
        font-family: 'Inter', sans-serif;
        padding: 15mm; 
        margin: 0 auto;
    }

    /* HEADER */
    .def-header-wrap {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px;
    }
    
    .def-header-left h1 {
        color: var(--def-primary);
        font-weight: 800;
        font-size: 26px;
        margin: 0 0 5px 0;
        text-transform: uppercase;
    }
    
    .def-header-left p {
        font-size: 11px;
        color: var(--def-muted);
        margin: 0;
        line-height: 1.4;
    }

    .def-doc-title {
        font-size: 24px;
        font-weight: 900;
        color: var(--def-secondary);
        text-transform: uppercase;
        text-align: right;
    }

    /* META BAR (Quotation # & Date) */
    .def-meta-bar {
        display: flex; 
        justify-content: space-between; 
        border: 1px solid var(--def-light-border);
        border-radius: 6px;
        padding: 12px 15px;
        margin-bottom: 20px;
    }
    .def-meta-item label { font-size: 9px; font-weight: 800; color: var(--def-primary); display: block; margin-bottom: 2px;}
    .def-meta-item span { font-size: 13px; font-weight: 800; color: var(--def-text); }

    /* ADDRESSES */
    .def-address-grid { display: flex; gap: 15px; margin-bottom: 20px; }
    .def-addr-card { 
        flex: 1; 
        padding: 12px; 
        background: var(--def-light-bg); 
        border: 1px solid var(--def-light-border); 
        border-radius: 6px; 
    }
    .def-addr-card .badge-title { font-size: 9px; font-weight: 800; color: var(--def-primary); margin-bottom: 6px; display: block;}
    .def-addr-card h6 { font-weight: 700; margin: 0 0 4px 0; font-size: 13px; color: var(--def-primary); }
    .def-addr-card p { margin: 0; font-size: 11px; color: var(--def-text); line-height: 1.4; }

    /* TABLE */
    .def-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .def-table th { background-color: var(--def-light-bg); color: var(--def-primary); border: 1px solid var(--def-light-border); padding: 10px; font-size: 10px; text-transform: uppercase; font-weight: 800;}
    .def-table td { border: 1px solid var(--def-light-border); padding: 10px; font-size: 11px; text-align: center; font-weight: 500; color: var(--def-text);}
    .def-table td:first-child { text-align: left; font-weight: 600; }
    .def-table td:last-child { text-align: right; font-weight: 700; }

    /* BOTTOM CALCULATIONS */
    .def-bottom-grid { display: flex; gap: 30px; }
    .def-info-col { flex: 1.2; }
    .def-totals-col { flex: 0.8; }

    .section-title { font-size: 10px; font-weight: 800; color: var(--def-primary); margin-bottom: 8px; border-bottom: 1px solid var(--def-light-border); padding-bottom: 4px;}
    .def-bank-table { width: 100%; font-size: 10px; border-collapse: collapse; }
    .def-bank-table td { padding: 4px 0; font-weight: 600; }
    .def-bank-table td:first-child { color: var(--def-muted); width: 110px; }

    .def-tot-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 11px; font-weight: 600; }
    .def-grand-tot-box { 
        background: var(--def-light-bg); 
        color: var(--def-primary); 
        padding: 12px; 
        margin-top: 10px; 
        border-radius: 6px;
        font-size: 15px; 
        font-weight: 800; 
        display: flex; 
        justify-content: space-between; 
        border: 1px solid var(--def-light-border);
    }

    /* SIGNATURE - FIXED */
    .def-signature-wrapper { text-align: center; margin-top: 40px; }
    .def-sig-img { max-height: 70px; max-width: 180px; object-fit: contain; display: block; margin: 0 auto 5px auto; }
    .def-sig-line { border-top: 1px solid var(--def-text); width: 160px; margin: 15px auto 5px auto; }
    .def-sig-role { font-size: 10px; font-weight: 700; color: var(--def-muted); }
    .def-sig-company { font-size: 11px; font-weight: 800; color: var(--def-primary); text-transform: uppercase; margin-top: 2px;}

    .page-number { text-align: right; font-size: 10px; color: var(--def-muted); margin-top: 5px;}
</style>

<?php 
$total_qty = 0; 
foreach($item_chunks as $page_index => $chunk): 
    $is_last_page = ($page_index === $total_pages - 1);
?>

<div class="quotation-page">
    
    <div class="def-header-wrap">
        <div class="def-header-left">
            <h1><?= strtoupper($iss_name) ?></h1>
            <p><?= nl2br($iss_address) ?></p>
            <p>Phone: <?= $iss_phone ?></p>
            <p>GSTIN: <b><?= $iss_gstin ?></b> &nbsp;|&nbsp; PAN: <b><?= $iss_pan ?></b></p>
        </div>
        
        <div class="def-header-right">
            <div class="def-doc-title"><?= $doc_type ?></div>
            <?php if($total_pages > 1): ?>
                <div class="page-number">Page <?= $page_index + 1 ?> of <?= $total_pages ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="def-meta-bar">
        <div class="def-meta-item">
            <label><?= strtoupper($doc_label) ?> NO.</label>
            <span>#<?= $quotation['quotation_no'] ?></span>
        </div>
        <div class="def-meta-item" style="text-align: right;">
            <label>DATE ISSUED</label>
            <span><?= $display_date ?></span>
        </div>
    </div>

    <div class="def-address-grid">
        <div class="def-addr-card">
            <span class="badge-title">BILLED TO</span>
            <h6><?= $cust_company ?: $cust_name ?></h6>
            <p><?= $cust_name ?></p>
            <p>Phone: <?= $cust_phone ?></p>
            <p>GSTIN: <?= $cust_gst ?: 'N/A' ?></p>
        </div>
        <div class="def-addr-card">
            <span class="badge-title">SHIPPED TO</span>
            <h6><?= $cust_company ?: $cust_name ?></h6>
            <p style="font-style:italic; color:#94a3b8; font-size: 10px;">Same as billing address unless specified otherwise.</p>
        </div>
    </div>

    <table class="def-table">
        <thead>
            <tr>
                <th style="width: 42%;">ITEM DESCRIPTION</th>
                <th style="width: 10%;">QTY</th>
                <th style="width: 16%;">UNIT PRICE</th>
                <th style="width: 16%;">TAX (GST)</th>
                <th style="width: 16%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            foreach($chunk as $item): 
                $total_qty += $item['qty'];
                $tax_amt = ($item['qty'] * $item['price'] * $item['tax_percent']) / 100;
            ?>
            <tr>
                <td><?= htmlspecialchars($item['item_name']) ?></td>
                <td><?= $item['qty'] ?></td>
                <td>₹<?= number_format($item['price'], 2) ?></td>
                <td>₹<?= number_format($tax_amt, 2) ?><br><small style="color:#94a3b8">(<?= $item['tax_percent'] ?>%)</small></td>
                <td>₹<?= number_format($item['total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if($is_last_page): ?>
    <div class="def-bottom-grid">
        
        <div class="def-info-col">
            <div class="section-title">PAYMENT INFORMATION</div>
            <table class="def-bank-table">
                <tr><td>Account holder:</td><td><?= $acc_holder ?></td></tr>
                <tr><td>Account number:</td><td><?= $acc_num ?></td></tr>
                <tr><td>Bank & Branch:</td><td><?= $bank_name ?>, <?= $branch ?></td></tr>
                <tr><td>IFSC code:</td><td><?= $ifsc ?></td></tr>
                <?php if(!empty($upi) && $upi !== 'N/A'): ?>
                    <tr><td>UPI ID:</td><td><?= $upi ?></td></tr>
                <?php endif; ?>
            </table>
            
            <?php if(!empty($quotation['terms'])): ?>
            <div class="section-title" style="margin-top:20px;">TERMS & CONDITIONS</div>
            <div style="font-size: 10px; color: var(--def-muted); line-height: 1.5;">
                <?= nl2br(htmlspecialchars($quotation['terms'])) ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="def-totals-col">
            <div class="def-tot-row"><span>Taxable Amount</span><span>₹<?= number_format($quotation['sub_total'], 2) ?></span></div>
            <div class="def-tot-row"><span>Total Tax</span><span>₹<?= number_format($quotation['total_tax'], 2) ?></span></div>
            
            <?php if($quotation['discount'] > 0): ?>
                <div class="def-tot-row" style="color:#ef4444;"><span>Discount</span><span>- ₹<?= number_format($quotation['discount'], 2) ?></span></div>
            <?php endif; ?>
            
            <div class="def-grand-tot-box">
                <span>Grand Total</span>
                <span>₹<?= number_format($quotation['grand_total'], 2) ?></span>
            </div>

            <div class="def-signature-wrapper">
                <?php if(!empty($iss_signature)): ?>
                    <img src="uploads/signatures/<?= $iss_signature ?>" alt="Signature" class="def-sig-img">
                <?php else: ?>
                    <div style="height: 60px;"></div> <?php endif; ?>
                
                <div class="def-sig-line"></div>
                <div class="def-sig-role">AUTHORISED SIGNATORY FOR</div>
                <div class="def-sig-company"><?= strtoupper($iss_name) ?></div>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div style="text-align: right; font-size: 11px; font-weight: 700; color: var(--def-muted); margin-top: auto;">
            Continued on next page... 
        </div>
    <?php endif; ?>
    
</div>

<?php if(!$is_last_page): ?>
    <div class="html2pdf__page-break"></div>
<?php endif; ?>

<?php endforeach; ?>