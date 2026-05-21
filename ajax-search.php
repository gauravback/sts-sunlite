<?php
// Session aur Database connect karo
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config/database.php';

if(isset($_POST['search'])){
    $search = mysqli_real_escape_string($conn, $_POST['search']);
    
    // Naya Logic: Check karo ki AJAX se 'type' bheja gaya hai ya nahi
    $search_type = isset($_POST['type']) ? $_POST['type'] : '';

    if ($search_type === 'customer_only') {
        // Condition 1: Agar Govt Sale wale page se aaya hai, toh sirf CUSTOMERS me search karo
        $query = "
            SELECT id, company_name, customer_name AS person, 'customer' AS entity_type 
            FROM customers 
            WHERE company_name LIKE '%$search%' OR customer_name LIKE '%$search%'
            LIMIT 10
        ";
    } else {
        // Condition 2: Purana Logic (Baaki sabhi pages ke liye) - LEADS aur CUSTOMERS dono me search karega
        $query = "
            SELECT id, company_name, contact_person AS person, 'lead' AS entity_type 
            FROM leads 
            WHERE company_name LIKE '%$search%' OR contact_person LIKE '%$search%'
            
            UNION 
            
            SELECT id, company_name, customer_name AS person, 'customer' AS entity_type 
            FROM customers 
            WHERE company_name LIKE '%$search%' OR customer_name LIKE '%$search%'
            
            LIMIT 10
        ";
    }

    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        // Agar data mila, toh list bana kar wapas bhejo
        while($row = mysqli_fetch_assoc($result)){
            $id = $row['id'];
            $company = htmlspecialchars($row['company_name']);
            $person = htmlspecialchars($row['person'] ?? 'N/A');
            $type = $row['entity_type'];
            
            // Design logic: Lead hai ya Customer us hisab se rang badlo
            $badge_class = ($type == 'lead') ? 'badge-lead' : 'badge-customer';
            $type_label = strtoupper($type);

            /* =========================================
                NAYA LOGIC: Redirect URL define karna
            ========================================= */
            if ($type == 'lead') {
                $redirect_url = "lead-update.php?id=" . $id;
            } else {
                $redirect_url = "customer-details.php?id=" . $id;
            }

            // A tag mein $redirect_url laga diya hai
            echo "
            <a href='$redirect_url' class='result-item'>
                <div>
                    <p class='company-title'>$company</p>
                    <p class='contact-name'><i class='ti ti-user'></i> $person</p>
                </div>
                <div>
                    <span class='$badge_class'>$type_label</span>
                </div>
            </a>
            ";
        }
    } else {
        // Agar kuch nahi mila
        echo "<div class='result-item text-center text-muted py-4'>No matching results found.</div>";
    }
}
?>