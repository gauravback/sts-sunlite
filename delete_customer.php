<?php
require_once 'config/database.php';
session_start();

header('Content-Type: application/json');

// Check if POST request has the delete_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    
    // ID ko secure/integer format me convert karna
    $id = intval($_POST['delete_id']);

    if ($id > 0) {
        // Transaction start karke delete karenge taaki error aane pe rollback ho sake
        $conn->begin_transaction();

        try {
            // 1. Pehle customer_history se data delete karein (Foreign Key constraints avoid karne ke liye)
            $stmt1 = $conn->prepare("DELETE FROM customer_history WHERE customer_id = ?");
            $stmt1->bind_param("i", $id);
            $stmt1->execute();
            $stmt1->close();

            // 2. Ab main customers table se delete karein
            $stmt2 = $conn->prepare("DELETE FROM customers WHERE id = ?");
            $stmt2->bind_param("i", $id);
            
            if (!$stmt2->execute()) {
                throw new Exception("Error deleting from customers table: " . $stmt2->error);
            }
            $stmt2->close();

            // Agar dono delete successful rahe, toh commit kar do
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Customer deleted successfully!']);

        } catch (Exception $e) {
            // Agar koi error aayi toh wapas pehle jaisa kar do (Rollback)
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Customer ID.']);
    }
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request.']);
    exit;
}
?>