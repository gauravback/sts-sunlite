<?php
// Apni database connection file include karein
// include 'config.php'; 

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Delete Query
    $delete_sql = "DELETE FROM uom_master WHERE id = '$id'";
    
    if (mysqli_query($conn, $delete_sql)) {
        echo "<script>alert('UOM Delete ho gaya!'); window.location.href='uom-list.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    // Agar direct URL open kare toh wapas list pe bhej do
    header("Location: uom-list.php");
}
?>