<?php
session_start();
include 'config/database.php';

// Check Valid ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<script>alert('Invalid Quotation ID'); window.location.href='view_quotations.php';</script>");
}

$q_id = (int)$_GET['id'];

// Handling the UPDATE logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_quotation'])) {
    
    $issuer_id      = (int)$_POST['issuer_id'];
    $quotation_date = mysqli_real_escape_string($conn, $_POST['quotation_date']);
    $notes          = mysqli_real_escape_string($conn, $_POST['notes']);
    $terms          = mysqli_real_escape_string($conn, $_POST['terms']);
    
    $sub_total      = (float)$_POST['sub_total'];
    $total_tax      = (float)$_POST['total_tax'];
    $discount       = (float)$_POST['discount'];
    $grand_total    = (float)$_POST['grand_total'];

    $company_selection = $_POST['company_selection'];
    $parts = explode('_', $company_selection);
    $company_type = $parts[0];
    $company_id = (int)$parts[1];

    // Update Quotation details
    $update_q = "UPDATE quotations SET 
                 issuer_id = $issuer_id, 
                 company_type = '$company_type', 
                 company_id = $company_id, 
                 quotation_date = '$quotation_date', 
                 sub_total = $sub_total, 
                 total_tax = $total_tax, 
                 discount = $discount, 
                 grand_total = $grand_total, 
                 notes = '$notes', 
                 terms = '$terms' 
                 WHERE id = $q_id";
                 
    if (mysqli_query($conn, $update_q)) {
        
        // Remove old items
        mysqli_query($conn, "DELETE FROM quotation_items WHERE quotation_id = $q_id");

        // Insert new items
        $item_names   = $_POST['item_name'] ?? [];
        $qtys         = $_POST['qty'] ?? [];
        $prices       = $_POST['price'] ?? [];
        $tax_percents = $_POST['tax_percent'] ?? [];
        $row_totals   = $_POST['row_total'] ?? [];

        for ($i = 0; $i < count($item_names); $i++) {
            $name  = mysqli_real_escape_string($conn, $item_names[$i]);
            $qty   = (float)$qtys[$i];
            $price = (float)$prices[$i];
            $tax_p = (float)$tax_percents[$i];
            $total = (float)$row_totals[$i];

            if(!empty(trim($name))) {
                $insert_item = "INSERT INTO quotation_items (quotation_id, item_name, qty, price, tax_percent, total) 
                                VALUES ($q_id, '$name', $qty, $price, $tax_p, $total)";
                mysqli_query($conn, $insert_item);
            }
        }

        echo "<script>alert('Quotation Updated Successfully!'); window.location.href='view_quotations.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error Updating Quotation.');</script>";
    }
}

// Fetch Quotation Data
$q_query = mysqli_query($conn, "SELECT * FROM quotations WHERE id = $q_id");
$quotation = mysqli_fetch_assoc($q_query);

if (!$quotation) {
    die("<script>alert('Quotation Not Found'); window.location.href='view_quotations.php';</script>");
}

// Current Selection formatting (e.g., Customer_5)
$current_selection = $quotation['company_type'] . "_" . $quotation['company_id'];

// Get Company Name for the Custom Dropdown Pre-fill
$client_name_query = ($quotation['company_type'] == 'Customer') 
    ? "SELECT company_name FROM customers WHERE id = {$quotation['company_id']}"
    : "SELECT company_name FROM leads WHERE id = {$quotation['company_id']}";
$client_res = mysqli_query($conn, $client_name_query);
$client_data = mysqli_fetch_assoc($client_res);
$current_client_name = $client_data['company_name'] ?? 'Select Company';

// Fetch Items
$items_query = mysqli_query($conn, "SELECT * FROM quotation_items WHERE quotation_id = $q_id");
?>
<?php include 'include/header.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
    body { background-color: #f8fafc; }
    .modern-card { border-radius: 12px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); background: #fff; }
    .modern-label { font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 5px; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 10px 14px; font-size: 0.9rem; }
    .table-input { border: 1px solid transparent; background: transparent; transition: 0.2s; width: 100%; padding: 5px;}
    .table-input:focus, .table-input:hover { background: #fff; border-color: #cbd5e1; border-radius: 4px;}
    
    .custom-search-container { position: relative; width: 100%; }
    .custom-search-input { width: 100%; padding-right: 35px; }
    .custom-search-icon { position: absolute; right: 12px; top: 12px; color: #94a3b8; pointer-events: none; }
    .custom-search-list { position: absolute; top: 100%; left: 0; right: 0; z-index: 1000; background: #fff; border: 1px solid #cbd5e1; border-radius: 0 0 8px 8px; max-height: 250px; overflow-y: auto; margin: 0; padding: 0; list-style: none; display: none; box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
    .custom-search-list li { padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; transition: 0.2s; }
    .custom-search-list li:hover { background-color: #f8fafc; color: #4f46e5; font-weight: 600; }
    .badge-cust { background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; float: right; }
    .badge-lead { background: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; float: right; }
    .search-no-result { padding: 12px 15px; color: #ef4444; font-size: 0.9rem; display: none; text-align: center; }
</style>

<div class="container-fluid mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark">Edit Quotation <span class="text-primary">#<?= $quotation['quotation_no'] ?></span></h3>
        <a href="view_quotations.php" class="btn btn-outline-secondary">Back to List</a>
    </div>

    <form action="" method="POST" id="quotationForm">
        <div class="card modern-card p-4 mb-4">
            <h5 class="text-primary fw-bold mb-4 border-bottom pb-2">Client & Basic Details</h5>
            <div class="row g-4">
                
                <div class="col-md-3">
                    <label class="modern-label">Your Company (Issuer) <span class="text-danger">*</span></label>
                    <select name="issuer_id" class="form-select" required>
                        <?php
                        $iss_query = mysqli_query($conn, "SELECT * FROM issuer_companies ORDER BY company_name ASC");
                        while($iss = mysqli_fetch_assoc($iss_query)) {
                            $sel = ($iss['id'] == $quotation['issuer_id']) ? "selected" : "";
                            echo "<option value='{$iss['id']}' $sel>{$iss['company_name']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="modern-label">Quotation No.</label>
                    <input type="text" class="form-control fw-bold text-muted" value="<?= $quotation['quotation_no'] ?>" readonly>
                </div>
                
                <div class="col-md-3">
                    <label class="modern-label">Quotation Date <span class="text-danger">*</span></label>
                    <input type="date" name="quotation_date" class="form-control" value="<?= $quotation['quotation_date'] ?>" required>
                </div>
                
                <div class="col-md-3">
                    <label class="modern-label">Client (Lead/Customer) <span class="text-danger">*</span></label>
                    <div class="custom-search-container">
                        <input type="text" id="customSearchInput" class="form-control custom-search-input" placeholder="Type to search..." autocomplete="off" value="<?= $current_client_name ?>" required>
                        <i class="fas fa-search custom-search-icon"></i>
                        <input type="hidden" name="company_selection" id="hiddenCompanySelection" value="<?= $current_selection ?>" required>
                        
                        <ul id="customSearchList" class="custom-search-list">
                            <?php
                            $cust_query = mysqli_query($conn, "SELECT id, company_name FROM customers ORDER BY company_name ASC");
                            while($cust = mysqli_fetch_assoc($cust_query)) {
                                $cName = htmlspecialchars($cust['company_name'], ENT_QUOTES);
                                echo "<li data-value='Customer_{$cust['id']}' data-text='{$cName}'>{$cName} <span class='badge-cust'>Customer</span></li>";
                            }
                            $lead_query = mysqli_query($conn, "SELECT id, company_name FROM leads ORDER BY company_name ASC");
                            while($lead = mysqli_fetch_assoc($lead_query)) {
                                $lName = htmlspecialchars($lead['company_name'], ENT_QUOTES);
                                echo "<li data-value='Lead_{$lead['id']}' data-text='{$lName}'>{$lName} <span class='badge-lead'>Lead</span></li>";
                            }
                            ?>
                            <div class="search-no-result" id="searchNoResult">No company found</div>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="card modern-card p-4 mb-4">
            <h5 class="text-primary fw-bold mb-4 border-bottom pb-2">Products / Items</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="itemTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th>Item Details</th>
                            <th width="10%">Qty</th>
                            <th width="15%">Price/Unit (₹)</th>
                            <th width="15%">Tax (%)</th>
                            <th width="15%">Amount (₹)</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody id="itemBody">
                        <?php while($item = mysqli_fetch_assoc($items_query)): ?>
                        <tr>
                            <td><input type="text" name="item_name[]" class="form-control border-0 bg-transparent table-input" value="<?= htmlspecialchars($item['item_name']) ?>" required></td>
                            <td><input type="number" name="qty[]" class="form-control border-0 bg-transparent table-input calc-input" value="<?= $item['qty'] ?>" min="1" required></td>
                            <td><input type="number" name="price[]" class="form-control border-0 bg-transparent table-input calc-input" value="<?= $item['price'] ?>" step="0.01" required></td>
                            <td>
                                <select name="tax_percent[]" class="form-select form-select-sm border-0 bg-transparent table-input calc-input">
                                    <option value="0" <?= ($item['tax_percent'] == 0) ? 'selected' : '' ?>>0%</option>
                                    <option value="5" <?= ($item['tax_percent'] == 5) ? 'selected' : '' ?>>5%</option>
                                    <option value="12" <?= ($item['tax_percent'] == 12) ? 'selected' : '' ?>>12%</option>
                                    <option value="18" <?= ($item['tax_percent'] == 18) ? 'selected' : '' ?>>18%</option>
                                    <option value="28" <?= ($item['tax_percent'] == 28) ? 'selected' : '' ?>>28%</option>
                                </select>
                            </td>
                            <td><input type="text" name="row_total[]" class="form-control border-0 bg-transparent table-input fw-bold text-dark row-total" value="<?= $item['total'] ?>" readonly></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-light text-danger remove-row"><i class="fas fa-trash"></i></button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" id="addRowBtn" class="btn btn-sm btn-outline-primary mt-2 w-auto"><i class="fas fa-plus me-1"></i> Add Another Item</button>
        </div>

        <div class="row">
            <div class="col-md-7">
                <div class="card modern-card p-4 mb-4 h-100">
                    <label class="modern-label">Notes</label>
                    <textarea name="notes" class="form-control mb-3" rows="3"><?= htmlspecialchars($quotation['notes']) ?></textarea>
                    
                    <label class="modern-label">Terms & Conditions</label>
                    <textarea name="terms" class="form-control" rows="4"><?= htmlspecialchars($quotation['terms']) ?></textarea>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card modern-card p-4 mb-4 bg-light border">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="fw-bold">Sub Total:</td>
                            <td class="text-end">₹<input type="text" name="sub_total" id="sub_total" class="border-0 bg-transparent text-end fw-bold" value="<?= $quotation['sub_total'] ?>" readonly></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Total Tax Amount:</td>
                            <td class="text-end">₹<input type="text" name="total_tax" id="total_tax" class="border-0 bg-transparent text-end fw-bold" value="<?= $quotation['total_tax'] ?>" readonly></td>
                        </tr>
                        <tr class="border-bottom border-dark border-opacity-10">
                            <td class="fw-bold text-danger">Discount (Flat ₹):</td>
                            <td class="text-end">₹<input type="number" name="discount" id="discount" class="form-control form-control-sm text-end d-inline-block text-danger fw-bold calc-input" style="width: 100px;" value="<?= $quotation['discount'] ?>" step="0.01"></td>
                        </tr>
                        <tr>
                            <td class="fw-bold fs-5 text-primary pt-3">Grand Total:</td>
                            <td class="text-end fs-5 text-primary fw-bolder pt-3">₹<input type="text" name="grand_total" id="grand_total" class="border-0 bg-transparent text-end fw-bolder text-primary" value="<?= $quotation['grand_total'] ?>" readonly></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" name="update_quotation" class="btn btn-primary btn-lg px-5 shadow-sm"><i class="fas fa-save me-2"></i> Update Quotation</button>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    // --- Searchable Dropdown Logic ---
    const searchInput = document.getElementById('customSearchInput');
    const searchList = document.getElementById('customSearchList');
    const hiddenSelect = document.getElementById('hiddenCompanySelection');
    const listItems = searchList.querySelectorAll('li');
    const noResult = document.getElementById('searchNoResult');

    searchInput.addEventListener('focus', function() {
        searchList.style.display = 'block';
    });

    searchInput.addEventListener('input', function() {
        const filter = searchInput.value.toLowerCase();
        let hasVisible = false;
        hiddenSelect.value = '';

        listItems.forEach(item => {
            if (item.getAttribute('data-text').toLowerCase().includes(filter)) {
                item.style.display = 'block';
                hasVisible = true;
            } else {
                item.style.display = 'none';
            }
        });
        noResult.style.display = hasVisible ? 'none' : 'block';
    });

    searchList.addEventListener('click', function(e) {
        const li = e.target.closest('li');
        if (li) {
            searchInput.value = li.getAttribute('data-text');
            hiddenSelect.value = li.getAttribute('data-value');
            searchList.style.display = 'none';
        }
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-search-container')) {
            searchList.style.display = 'none';
        }
    });

    document.getElementById('quotationForm').addEventListener('submit', function(e) {
        if (!hiddenSelect.value) {
            e.preventDefault();
            alert("Error: Please select a valid Client from the dropdown list.");
            searchInput.focus();
        }
    });

    // --- Calculation Logic ---
    function calculateTotals() {
        let subTotal = 0; let totalTax = 0;
        document.querySelectorAll('#itemBody tr').forEach(row => {
            let qty = parseFloat(row.querySelector('input[name="qty[]"]').value) || 0;
            let price = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
            let taxPercent = parseFloat(row.querySelector('select[name="tax_percent[]"]').value) || 0;
            
            let baseAmount = qty * price;
            let taxAmount = (baseAmount * taxPercent) / 100;
            let rowTotal = baseAmount + taxAmount;

            row.querySelector('input[name="row_total[]"]').value = rowTotal.toFixed(2);
            subTotal += baseAmount;
            totalTax += taxAmount;
        });

        let discount = parseFloat(document.getElementById('discount').value) || 0;
        let grandTotal = (subTotal + totalTax) - discount;

        document.getElementById('sub_total').value = subTotal.toFixed(2);
        document.getElementById('total_tax').value = totalTax.toFixed(2);
        document.getElementById('grand_total').value = grandTotal.toFixed(2);
    }

    document.addEventListener('input', function(e) {
        if(e.target.classList.contains('calc-input')) calculateTotals();
    });
    document.addEventListener('change', function(e) {
        if(e.target.classList.contains('calc-input')) calculateTotals();
    });

    document.getElementById('addRowBtn').addEventListener('click', function() {
        const tbody = document.getElementById('itemBody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="item_name[]" class="form-control border-0 bg-transparent table-input" placeholder="Details" required></td>
            <td><input type="number" name="qty[]" class="form-control border-0 bg-transparent table-input calc-input" value="1" min="1" required></td>
            <td><input type="number" name="price[]" class="form-control border-0 bg-transparent table-input calc-input" value="0" step="0.01" required></td>
            <td>
                <select name="tax_percent[]" class="form-select form-select-sm border-0 bg-transparent table-input calc-input">
                    <option value="0">0%</option>
                    <option value="5" selected>5%</option>
                    <option value="12">12%</option>
                    <option value="18">18%</option>
                    <option value="28">28%</option>
                </select>
            </td>
            <td><input type="text" name="row_total[]" class="form-control border-0 bg-transparent table-input fw-bold text-dark row-total" value="0.00" readonly></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-light text-danger remove-row"><i class="fas fa-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
        calculateTotals(); 
    });

    document.addEventListener('click', function(e) {
        if(e.target.closest('.remove-row')) {
            e.target.closest('tr').remove();
            calculateTotals(); 
        }
    });
});
</script>
<?php include 'include/footer.php'; ?>