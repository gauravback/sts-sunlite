<?php
// Session aur DB connection
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'config/database.php'; 

// Check agar ID URL mein aayi hai ya nahi
if (isset($_GET['id'])) {
    // ID ko integer mein convert karein (Security ke liye taaki SQL Injection na ho)
    $id = intval($_GET['id']);

    if ($id > 0) {
        // --- OPTION 1: Soft Delete (Recommended for CRM) ---
        // Ye record ko database mein rakhega par status 'Inactive' kar dega
        $sql = "UPDATE employees SET status = 'Inactive' WHERE id = $id";

        // --- OPTION 2: Hard Delete (Permanent Delete) ---
        // Agar aap chahte hain ki data completely database se udd jaye, toh upar wali $sql query comment karke neeche wali use karein:
        // $sql = "DELETE FROM employees WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Employee deleted successfully!'); window.location.href='employee_list.php';</script>";
        } else {
            echo "<script>alert('Error: " . mysqli_error($conn) . "'); window.location.href='employee_list.php';</script>";
        }
    } else {
        echo "<script>alert('Invalid Employee ID!'); window.location.href='employee_list.php';</script>";
    }
} else {
    echo "<script>alert('No ID provided!'); window.location.href='employee_list.php';</script>";
}
?>