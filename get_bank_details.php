<?php
include 'config/database.php';

if(isset($_POST['issuer_id'])) {
    $id = (int)$_POST['issuer_id'];
    
    if(empty($id)) {
        echo json_encode(['success' => false]);
        exit;
    }

    $query = "SELECT * FROM issuer_companies WHERE id = $id LIMIT 1";
    $res = mysqli_query($conn, $query);

    if($res && mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
        echo json_encode([
            'success' => true,
            'account_holder' => $data['account_holder'],
            'account_number' => $data['account_number'],
            'bank_name' => $data['bank_name'],
            'branch' => $data['branch'],
            'ifsc_code' => $data['ifsc_code'],
            'upi_id' => $data['upi_id']
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>