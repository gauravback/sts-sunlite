<?php
session_start();

// Enable errors for debugging (Remove or comment out in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
include 'config/database.php'; 

// Check if 'id' is present in the URL and is a valid numeric value
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $bid_id = $_GET['id'];

    // STEP 1: Logic to delete files from the server
    $file_query = "SELECT uploaded_files FROM bids WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $file_query)) {
        mysqli_stmt_bind_param($stmt, "i", $bid_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $uploaded_files);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // If files exist, delete them from the directory
        if (!empty($uploaded_files)) {
            $files_array = explode(',', $uploaded_files);
            foreach ($files_array as $file) {
                $file_path = 'uploads/bids/' . trim($file);
                // Check if the file actually exists before unlinking it
                if (file_exists($file_path)) {
                    unlink($file_path); 
                }
            }
        }
    }

    // STEP 2: Delete Bid from the Database
    // Note: Since 'ON DELETE CASCADE' is set on the table, 
    // deleting from 'bids' will automatically delete associated history in 'bid_remarks'!
    $delete_query = "DELETE FROM bids WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $delete_query)) {
        mysqli_stmt_bind_param($stmt, "i", $bid_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Delete successful
            echo "<script>
                    alert('The bid and all its associated files have been deleted successfully!');
                    window.location.href = 'bid_report.php';
                  </script>";
        } else {
            // Delete failed
            echo "<script>
                    alert('Error deleting bid: " . addslashes(mysqli_error($conn)) . "');
                    window.location.href = 'bid_report.php';
                  </script>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<script>
                alert('Database system error!');
                window.location.href = 'bid_report.php';
              </script>";
    }

} else {
    // If ID is missing or invalid
    echo "<script>
            alert('Invalid Bid ID!');
            window.location.href = 'bid_report.php';
          </script>";
}

mysqli_close($conn);
?>