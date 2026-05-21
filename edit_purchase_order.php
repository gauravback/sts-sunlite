<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config/database.php';

$message = "";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("<h2 class='text-center text-danger mt-5'>Invalid PO ID!</h2>");
}

$po_id = (int)$_GET['id'];

// --- FORM UPDATE LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $po_date = mysqli_real_escape_string($conn, $_POST['po_date']);
    $issuer_id = (int)$_POST['issuer_id'];
    $vendor_id = (int)$_POST['vendor_id'];
    $payment_terms = mysqli_real_escape_string($conn, $_POST['payment_terms']);
    $delivery_terms = mysqli_real_escape_string($conn, $_POST['delivery_terms']);
    $authorized_signatory = mysqli_real_escape_string($conn, $_POST['authorized_signatory']);
    $terms_conditions = mysqli_real_escape_string($conn, $_POST['terms_conditions']);
    
    $sub_total = $_POST['final_sub_total'];
    $tax_total = $_POST['final_tax_total'];
    $grand_total = $_POST['final_grand_total'];

    // Update main PO record
    $update_query = "UPDATE purchase_orders SET po_date=?, issuer_id=?, vendor_id=?, payment_terms=?, delivery_terms=?, sub_total=?, tax_total=?, grand_total=?, authorized_signatory=?, terms_conditions=? WHERE id=?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("siisssssssi", $po_date, $issuer_id, $vendor_id, $payment_terms, $delivery_terms, $sub_total, $tax_total, $grand_total, $authorized_signatory, $terms_conditions, $po_id);
    
    if ($stmt->execute()) {
        // Purane items delete karo
        mysqli_query($conn, "DELETE FROM po_items WHERE po_id = $po_id");
        
        // Naye items (form wale) insert karo
        if (isset($_POST['items']) && count($_POST['items']) > 0) {
            foreach ($_POST['items'] as $item) {
                $desc = mysqli_real_escape_string($conn, $item['description']);
                $gst = (int)$item['gst'];
                $qty = (int)$item['qty'];
                $rate = (float)$item['rate'];
                $amount = (float)$item['amount'];
                $gst_amt = (float)$item['gst_amount'];
                $total = (float)$item['total'];

                $item_query = "INSERT INTO po_items (po_id, item_description, gst_percent, quantity, rate, amount, gst_amount, total) 
                               VALUES ('$po_id', '$desc', '$gst', '$qty', '$rate', '$amount', '$gst_amt', '$total')";
                mysqli_query($conn, $item_query);
            }
        }
        echo "<script>alert('Purchase Order Updated Successfully!'); window.location.href='view_purchase_orders.php';</script>";
        exit();
    } else {
        $message = "<div class='alert alert-danger'>Error updating PO: " . $stmt->error . "</div>";
    }
}

// --- FETCH EXISTING PO DATA ---
$po_result = mysqli_query($conn, "SELECT * FROM purchase_orders WHERE id = $po_id");
$po = mysqli_fetch_assoc($po_result);

// Fetch existing items into an array
$items_result = mysqli_query($conn, "SELECT * FROM po_items WHERE po_id = $po_id");
$existing_items = [];
while($row = mysqli_fetch_assoc($items_result)){
    $existing_items[] = $row;
}

// Fetch Dropdown data
$vendors = mysqli_query($conn, "SELECT id, vendor_name, gstin FROM vendors WHERE status = 'Active' ORDER BY vendor_name ASC");
$issuers_query = mysqli_query($conn, "SELECT * FROM po_issuer_companies WHERE status = 'Active'");
$issuers = [];
while($row = mysqli_fetch_assoc($issuers_query)) { $issuers[] = $row; }
$issuers_json = json_encode($issuers);
?>

<?php include 'include/header.php'; ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    .po-wrapper { background-color: #f4f7f9; min-height: calc(100vh - 70px); padding: 2rem 1rem; }
    .card-custom { border-radius: 12px; border: none; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.04); background: #fff; }
    .section-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; display: flex; align-items: center; }
    .section-title i { background: #eff6ff; color: #3b82f6; padding: 6px; border-radius: 6px; margin-right: 10px; font-size: 1rem; }
    .form-label { font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px; }
    .form-control, .form-select, textarea { border-radius: 8px; padding: 10px 15px; font-size: 0.95rem; border: 1px solid #cbd5e1; box-shadow: none; background-color: #ffffff; }
    .form-control:focus, textarea:focus { background-color: #ffffff; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
    
    .table th { background-color: #f8fafc; font-size: 0.8rem; text-transform: uppercase; color: #64748b; padding: 12px; }
    .table td { vertical-align: middle; padding: 8px; }
    .table input { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; width: 100%; font-size: 0.9rem; transition: 0.3s; }
    .table input:focus { border-color: #3b82f6; outline: none; }
    .table input[readonly], .readonly-total { background-color: #e2e8f0; cursor: not-allowed; font-weight: 600; color: #0f172a; border-color: #cbd5e1; }
    
    .btn-remove { color: #ef4444; background: none; border: none; cursor: pointer; font-size: 1.2rem; transition: 0.3s; padding: 5px 10px; border-radius: 4px; }
    .btn-remove:hover { color: #ffffff; background-color: #ef4444; }
    
    .select2-container--default .select2-selection--single { height: 44px; border: 1px solid #cbd5e1; border-radius: 8px; background-color: #ffffff; display: flex; align-items: center; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { padding-left: 15px; color: #1e293b; font-size: 0.95rem; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 42px; right: 10px; }

    .company-preview-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 15px; font-size: 0.85rem; color: #475569; display: none; margin-top: 10px; }
    .company-preview-box strong { color: #1e293b; }
</style>

<div class="po-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Edit Purchase Order</h3>
                <p class="text-muted small mb-0">Updating PO No: <strong><?= htmlspecialchars($po['po_number']) ?></strong></p>
            </div>
            <a href="view_purchase_orders.php" class="btn btn-light border fw-semibold"><i class="fas fa-arrow-left me-2"></i> Back to List</a>
        </div>
        
        <?php echo $message; ?>

        <form method="POST" action="" id="poForm">
            <div class="card card-custom mb-4">
                <div class="card-body p-4">
                    <h5 class="section-title"><i class="fas fa-info-circle"></i> General Details</h5>
                    
                    <div class="row g-4 mb-4 pb-4 border-bottom">
                        <div class="col-md-12">
                            <label class="form-label">Order By (Issuing Company) <span class="text-danger">*</span></label>
                            <select name="issuer_id" id="issuerSelect" class="form-select" required>
                                <option value="">-- Select Your Issuing Company --</option>
                                <?php foreach($issuers as $iss): ?>
                                    <option value="<?= $iss['id'] ?>" <?= ($po['issuer_id'] == $iss['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($iss['company_name']) ?> (GST: <?= htmlspecialchars($iss['gstin']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <div class="company-preview-box" id="issuerDetailsBox">
                                <div class="row">
                                    <div class="col-md-6"><i class="fas fa-map-marker-alt text-primary me-1"></i> <span id="iss_address"></span></div>
                                    <div class="col-md-3"><strong>GSTIN:</strong> <span id="iss_gstin"></span><br><strong>PAN:</strong> <span id="iss_pan"></span></div>
                                    <div class="col-md-3"><strong>Email:</strong> <span id="iss_email"></span><br><strong>Phone:</strong> <span id="iss_phone"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-3">
                            <label class="form-label">PO Number</label>
                            <input type="text" name="po_number" class="form-control fw-bold text-primary bg-light" value="<?= htmlspecialchars($po['po_number']) ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">PO Date <span class="text-danger">*</span></label>
                            <input type="date" name="po_date" class="form-control" value="<?= $po['po_date'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Order To (Vendor) <span class="text-danger">*</span></label>
                            <select name="vendor_id" id="vendorSelect" class="form-select" required>
                                <option value="">-- Search & Select Vendor --</option>
                                <?php while($v = mysqli_fetch_assoc($vendors)): ?>
                                    <option value="<?= $v['id'] ?>" <?= ($po['vendor_id'] == $v['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($v['vendor_name']) ?> (<?= htmlspecialchars($v['gstin']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Terms</label>
                            <input type="text" name="payment_terms" class="form-control" value="<?= htmlspecialchars($po['payment_terms']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Delivery Terms</label>
                            <input type="text" name="delivery_terms" class="form-control" value="<?= htmlspecialchars($po['delivery_terms']) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-custom mb-4">
                <div class="card-body p-4">
                    <h5 class="section-title"><i class="fas fa-boxes"></i> Items & Specifications</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="itemsTable">
                            <thead>
                                <tr>
                                    <th width="30%">Specifications / Item</th>
                                    <th width="10%">GST %</th>
                                    <th width="12%">Quantity</th>
                                    <th width="12%">Rate (₹)</th>
                                    <th width="12%">Amount (₹)</th>
                                    <th width="10%">Tax Amt (₹)</th>
                                    <th width="12%">Total (₹)</th>
                                    <th width="5%" class="text-center"><i class="fas fa-trash"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($existing_items as $idx => $item): ?>
                                <tr>
                                    <td><input type="text" name="items[<?= $idx ?>][description]" value="<?= htmlspecialchars($item['item_description']) ?>" required></td>
                                    <td><input type="number" name="items[<?= $idx ?>][gst]" class="gst-input" value="<?= $item['gst_percent'] ?>" min="0" required></td>
                                    <td><input type="number" name="items[<?= $idx ?>][qty]" class="qty-input" value="<?= $item['quantity'] ?>" min="1" required></td>
                                    <td><input type="number" name="items[<?= $idx ?>][rate]" class="rate-input" value="<?= $item['rate'] ?>" step="0.01" required></td>
                                    
                                    <td><input type="text" name="items[<?= $idx ?>][amount]" class="amount-input" value="<?= $item['amount'] ?>" readonly></td>
                                    <td><input type="text" name="items[<?= $idx ?>][gst_amount]" class="gst-amt-input" value="<?= $item['gst_amount'] ?>" readonly></td>
                                    <td><input type="text" name="items[<?= $idx ?>][total]" class="total-input" value="<?= $item['total'] ?>" readonly></td>
                                    
                                    <td class="text-center"><button type="button" class="btn-remove"><i class="fas fa-times"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold px-3 py-2 mt-2" id="addRowBtn"><i class="fas fa-plus me-1"></i> Add Another Item</button>
                    </div>

                    <div class="row mt-4 justify-content-end">
                        <div class="col-md-5 col-lg-4">
                            <table class="table table-borderless border shadow-sm rounded">
                                <tr>
                                    <td class="text-end fw-bold align-middle text-muted">Sub Total (₹):</td>
                                    <td width="55%"><input type="text" id="disp_sub_total" class="form-control readonly-total text-end fw-bold" value="<?= $po['sub_total'] ?>" readonly></td>
                                </tr>
                                <tr>
                                    <td class="text-end fw-bold align-middle text-muted">Total Tax (₹):</td>
                                    <td><input type="text" id="disp_tax_total" class="form-control readonly-total text-end fw-bold" value="<?= $po['tax_total'] ?>" readonly></td>
                                </tr>
                                <tr class="bg-light">
                                    <td class="text-end fw-bold fs-5 text-primary align-middle">Grand Total (₹):</td>
                                    <td><input type="text" id="disp_grand_total" class="form-control readonly-total text-end fw-bold fs-5 text-primary border-primary" value="<?= $po['grand_total'] ?>" readonly></td>
                                </tr>
                            </table>
                            <input type="hidden" name="final_sub_total" id="final_sub_total" value="<?= $po['sub_total'] ?>">
                            <input type="hidden" name="final_tax_total" id="final_tax_total" value="<?= $po['tax_total'] ?>">
                            <input type="hidden" name="final_grand_total" id="final_grand_total" value="<?= $po['grand_total'] ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-custom mb-4">
                <div class="card-body p-4">
                    <h5 class="section-title"><i class="fas fa-file-signature"></i> Terms & Signatory</h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Authorized Signatory Name</label>
                            <input type="text" name="authorized_signatory" class="form-control" value="<?= htmlspecialchars($po['authorized_signatory']) ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Terms and Conditions</label>
                            <textarea name="terms_conditions" class="form-control" rows="4"><?= htmlspecialchars($po['terms_conditions']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end mb-5 pb-5">
                <a href="view_purchase_orders.php" class="btn btn-light border px-4 py-2 me-2 fw-semibold">Cancel</a>
                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold" style="background-color: #2563eb;">
                    <i class="fas fa-save me-2"></i> Update Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Select2 Initialization
    if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
        jQuery('#vendorSelect').select2({ placeholder: "-- Search & Select Vendor --", allowClear: true, width: '100%' });
        jQuery('#issuerSelect').select2({ placeholder: "-- Select Your Issuing Company --", allowClear: true, width: '100%' });
        
        jQuery('#issuerSelect').on('change', function() {
            updateIssuerDetails(this.value);
        });
    }

    // Load Preview details for Issuer on page load (if already selected)
    const issuersData = <?= $issuers_json ?>;
    const issuerDetailsBox = document.getElementById('issuerDetailsBox');
    const currIssuer = document.getElementById('issuerSelect').value;

    function updateIssuerDetails(id) {
        if(!id) {
            issuerDetailsBox.style.display = 'none';
            return;
        }
        const data = issuersData.find(item => item.id == id);
        if(data) {
            document.getElementById('iss_address').innerText = data.address;
            document.getElementById('iss_gstin').innerText = data.gstin;
            document.getElementById('iss_pan').innerText = data.pan;
            document.getElementById('iss_email').innerText = data.email || 'N/A';
            document.getElementById('iss_phone').innerText = data.phone || 'N/A';
            issuerDetailsBox.style.display = 'block';
        }
    }
    
    if(currIssuer) {
        updateIssuerDetails(currIssuer);
    }

    const tbody = document.querySelector('#itemsTable tbody');
    const addRowBtn = document.getElementById('addRowBtn');
    
    // JS needs to know how many rows already exist to continue indexing properly
    let rowCount = <?= count($existing_items) ?>;

    // 1. Add New Row
    addRowBtn.addEventListener('click', function() {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="items[${rowCount}][description]" placeholder="Item description" required></td>
            <td><input type="number" name="items[${rowCount}][gst]" class="gst-input" value="18" min="0" required></td>
            <td><input type="number" name="items[${rowCount}][qty]" class="qty-input" value="1" min="1" required></td>
            <td><input type="number" name="items[${rowCount}][rate]" class="rate-input" step="0.01" required></td>
            <td><input type="text" name="items[${rowCount}][amount]" class="amount-input" readonly placeholder="0.00"></td>
            <td><input type="text" name="items[${rowCount}][gst_amount]" class="gst-amt-input" readonly placeholder="0.00"></td>
            <td><input type="text" name="items[${rowCount}][total]" class="total-input" readonly placeholder="0.00"></td>
            <td class="text-center"><button type="button" class="btn-remove"><i class="fas fa-times"></i></button></td>
        `;
        tbody.appendChild(tr);
        rowCount++;
    });

    // 2. Remove Row
    tbody.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-remove');
        if (btn) {
            const rows = tbody.querySelectorAll('tr');
            if (rows.length > 1) {
                btn.closest('tr').remove();
                calculateGrandTotal(); 
            } else {
                alert("You must have at least one item in the Purchase Order.");
            }
        }
    });

    // 3. Real-Time Math Calculations
    tbody.addEventListener('input', function(e) {
        if (e.target.classList.contains('qty-input') || 
            e.target.classList.contains('rate-input') || 
            e.target.classList.contains('gst-input')) {
            
            const row = e.target.closest('tr');
            const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
            const rate = parseFloat(row.querySelector('.rate-input').value) || 0;
            const gst = parseFloat(row.querySelector('.gst-input').value) || 0;

            const amount = qty * rate;
            const gstAmt = amount * (gst / 100);
            const total = amount + gstAmt;

            row.querySelector('.amount-input').value = amount.toFixed(2);
            row.querySelector('.gst-amt-input').value = gstAmt.toFixed(2);
            row.querySelector('.total-input').value = total.toFixed(2);

            calculateGrandTotal();
        }
    });

    // 4. Update Main Totals
    function calculateGrandTotal() {
        let subTotal = 0, taxTotal = 0, grandTotal = 0;

        document.querySelectorAll('.amount-input').forEach(input => { subTotal += parseFloat(input.value) || 0; });
        document.querySelectorAll('.gst-amt-input').forEach(input => { taxTotal += parseFloat(input.value) || 0; });
        document.querySelectorAll('.total-input').forEach(input => { grandTotal += parseFloat(input.value) || 0; });

        document.getElementById('disp_sub_total').value = subTotal.toFixed(2);
        document.getElementById('disp_tax_total').value = taxTotal.toFixed(2);
        document.getElementById('disp_grand_total').value = grandTotal.toFixed(2);
        
        document.getElementById('final_sub_total').value = subTotal.toFixed(2);
        document.getElementById('final_tax_total').value = taxTotal.toFixed(2);
        document.getElementById('final_grand_total').value = grandTotal.toFixed(2);
    }
});
</script>

<?php include 'include/footer.php'; ?>