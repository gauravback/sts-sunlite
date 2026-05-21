<?php 
// Prevent direct access
if (!isset($quotation)) die("Direct access not permitted"); 
?>
<?php foreach ($item_chunks as $page_index => $chunk): 
    $is_last_page = ($page_index == $total_pages - 1);
?>
<div class="quotation-page <?= $layout_class ?>" style="position: relative; background: #fff; display: flex; flex-direction: column; min-height: 296mm;">
    
    <?php if (!empty($issuer['header_image'])): ?>
        <img src="uploads/company_files/<?= htmlspecialchars($issuer['header_image']) ?>" style="width: 100%; max-height: 180px; object-fit: contain; display: block;" alt="Header">
    <?php else: ?>
        <div style="height: 40px;"></div>
    <?php endif; ?>

    <div style="padding: 20px 40px; flex-grow: 1; display: flex; flex-direction: column;">
        
        <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #e0f2fe; padding-bottom: 12px; margin-bottom: 20px;">
            <div>
                <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;"><?= $doc_label ?></span>
                <strong style="color: #0284c7; font-size: 15px; display: block; margin-top: 4px;">#<?= htmlspecialchars($quotation['quotation_no']) ?></strong>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase;">DATE ISSUED</span>
                <strong style="color: #0284c7; font-size: 15px; display: block; margin-top: 4px;"><?= htmlspecialchars($display_date) ?></strong>
            </div>
        </div>

        <div style="display: flex; gap: 20px; margin-bottom: 25px;">
            <div style="flex: 1; border: 1px solid #e0f2fe; border-radius: 8px; padding: 15px; background: #f0f9ff;">
                <span style="background: #0284c7; color: #fff; font-size: 10px; padding: 4px 10px; border-radius: 4px; font-weight: 700;">BILLED TO</span>
                <h6 style="margin: 12px 0 6px 0; font-weight: 800; font-size: 14px; color: #0f172a;"><?= $cust_company ?></h6>
                <p style="margin: 0; font-size: 11px; color: #475569; line-height: 1.5;">
                    <?= $cust_name ?><br>
                    Phone: <?= $cust_phone ?><br>
                    GSTIN: <span style="font-weight:600;"><?= $cust_gst ?></span> | PAN: <span style="font-weight:600;"><?= $cust_pan ?></span>
                </p>
            </div>
            <div style="flex: 1; border: 1px solid #e0f2fe; border-radius: 8px; padding: 15px; background: #f0f9ff;">
                <span style="background: #0284c7; color: #fff; font-size: 10px; padding: 4px 10px; border-radius: 4px; font-weight: 700;">SHIPPED TO</span>
                <h6 style="margin: 12px 0 6px 0; font-weight: 800; font-size: 14px; color: #0f172a;"><?= $cust_company ?></h6>
                <p style="margin: 0; font-size: 11px; color: #94a3b8; font-style: italic; line-height: 1.5;">
                    Same as billing address unless specified otherwise.
                </p>
            </div>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 11px;">
            <thead>
                <tr style="border-bottom: 2px solid #bae6fd; color: #0284c7;">
                    <th style="padding: 10px 8px; text-align: left; font-weight: 700;">ITEM DESCRIPTION</th>
                    <th style="padding: 10px 8px; text-align: center; font-weight: 700; width: 10%;">QTY</th>
                    <th style="padding: 10px 8px; text-align: right; font-weight: 700; width: 15%;">PRICE</th>
                    <th style="padding: 10px 8px; text-align: right; font-weight: 700; width: 15%;">TAX</th>
                    <th style="padding: 10px 8px; text-align: right; font-weight: 700; width: 20%;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chunk as $item): 
                    $tax_amt = ($item['price'] * $item['qty'] * $item['tax_percent']) / 100;
                ?>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 12px 8px; color: #0284c7; font-weight: 600;"><?= htmlspecialchars($item['item_name']) ?></td>
                    <td style="padding: 12px 8px; text-align: center; color: #475569;"><?= $item['qty'] ?></td>
                    <td style="padding: 12px 8px; text-align: right; color: #475569;">₹<?= number_format($item['price'], 2) ?></td>
                    <td style="padding: 12px 8px; text-align: right; color: #475569;">
                        ₹<?= number_format($tax_amt, 2) ?><br>
                        <span style="font-size: 9px; color: #94a3b8;">(<?= $item['tax_percent'] ?>%)</span>
                    </td>
                    <td style="padding: 12px 8px; text-align: right; color: #0f172a; font-weight: 700;">₹<?= number_format($item['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($is_last_page): ?>
        <div style="margin-top: auto; display: flex; gap: 25px; align-items: stretch;">
            
            <div style="flex: 1.2; display: flex; flex-direction: column; gap: 15px;">
                <div style="border: 1px solid #e0f2fe; padding: 12px; border-radius: 8px; background: #f8fafc;">
                    <h6 style="font-size: 11px; color: #0284c7; font-weight: 800; margin: 0 0 8px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">PAYMENT INFORMATION</h6>
                    <table style="width: 100%; font-size: 10px; color: #475569; line-height: 1.5;">
                        <tr><td style="width: 80px; color:#64748b;">A/c Holder:</td><td style="font-weight: 700; color: #0f172a;"><?= $acc_holder ?></td></tr>
                        <tr><td style="color:#64748b;">A/c Number:</td><td style="font-weight: 700; color: #0f172a;"><?= $acc_num ?></td></tr>
                        <tr><td style="color:#64748b;">Bank:</td><td style="font-weight: 700; color: #0f172a;"><?= $bank_name ?>, <?= $branch ?></td></tr>
                        <tr><td style="color:#64748b;">IFSC Code:</td><td style="font-weight: 700; color: #0f172a;"><?= $ifsc ?></td></tr>
                    </table>
                </div>
                
                <div>
                    <h6 style="font-size: 10px; color: #0f172a; font-weight: 800; margin: 0 0 6px 0;">TERMS & CONDITIONS</h6>
                    <div style="font-size: 9px; color: #64748b; white-space: pre-wrap; line-height: 1.4; padding-left: 5px; border-left: 2px solid #bae6fd;"><?= htmlspecialchars($quotation['terms']) ?></div>
                </div>
            </div>

            <div style="flex: 1;">
                <table style="width: 100%; font-size: 11px; margin-bottom: 15px;">
                    <tr>
                        <td style="padding: 4px 0; color:#64748b;">Taxable Amount</td>
                        <td style="text-align: right; color:#0f172a; font-weight:600;">₹<?= number_format($quotation['sub_total'], 2) ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color:#64748b;">Total Tax</td>
                        <td style="text-align: right; color:#0f172a; font-weight:600;">₹<?= number_format($quotation['total_tax'], 2) ?></td>
                    </tr>
                    <?php if($quotation['discount'] > 0): ?>
                    <tr>
                        <td style="padding: 4px 0; color:#0ea5e9; font-weight:600;">Discount</td>
                        <td style="text-align: right; color:#0ea5e9; font-weight:700;">-₹<?= number_format($quotation['discount'], 2) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <div style="background: #0284c7; color: white; padding: 12px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(2, 132, 199, 0.2);">
                    <strong style="font-size: 12px; letter-spacing: 0.5px;">GRAND TOTAL</strong>
                    <strong style="font-size: 16px;">₹<?= number_format($quotation['grand_total'], 2) ?></strong>
                </div>

                <div style="margin-top: 30px; text-align: right;">
                    <?php if(!empty($iss_signature)): ?>
                        <img src="uploads/signatures/<?= htmlspecialchars($iss_signature) ?>" style="max-height: 60px; max-width: 160px; display: inline-block; margin-bottom: 6px;">
                    <?php else: ?>
                        <div style="height: 60px;"></div>
                    <?php endif; ?>
                    <div style="border-top: 2px solid #e0f2fe; padding-top: 6px; display: inline-block; min-width: 160px;">
                        <span style="font-size: 9px; color: #64748b; display: block; font-weight: 600;">AUTHORISED SIGNATORY FOR</span>
                        <strong style="font-size: 11px; color: #0284c7; display: block; margin-top: 2px;"><?= htmlspecialchars($iss_name) ?></strong>
                    </div>
                </div>
            </div>

        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($issuer['footer_image'])): ?>
        <div style="margin-top: auto; width: 100%;">
            <img src="uploads/company_files/<?= htmlspecialchars($issuer['footer_image']) ?>" style="width: 100%; max-height: 120px; object-fit: contain; display: block;" alt="Footer">
        </div>
    <?php endif; ?>

</div>
<?php endforeach; ?>