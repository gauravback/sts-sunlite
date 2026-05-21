<?php
require_once 'config/auth.php';
require_once 'config/database.php';
include 'include/header.php'; // SweetAlert load karne ke liye header include karna zaroori hai

// 1. SECURITY CHECK: Sirf Admin ko allow karo
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
        window.onload = function() {
            Swal.fire({
                title: 'Access Denied!',
                text: 'Only Admins have permission to delete leads.',
                icon: 'error',
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Back to Leads'
            }).then((result) => {
                window.location = 'leads.php';
            });
        };
    </script>";
    include 'include/footer.php';
    exit;
}

// 2. ID CHECK: Validate karo ki ID aayi hai ya nahi
if(!isset($_GET['id']) || empty($_GET['id'])){
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
        window.onload = function() {
            Swal.fire('Error', 'Lead ID is missing.', 'warning')
            .then(() => { window.location = 'leads.php'; });
        };
    </script>";
    include 'include/footer.php';
    exit;
}

$id = intval($_GET['id']);

// 3. DELETE LOGIC: Pehle history delete karo, phir main lead delete karo 
// (Taki database mein error na aaye agar inka aapas mein connection ho)

$stmt_history = $conn->prepare("DELETE FROM lead_history WHERE lead_id=?");
$stmt_history->bind_param("i", $id);
$stmt_history->execute();

$stmt_lead = $conn->prepare("DELETE FROM leads WHERE id=?");
$stmt_lead->bind_param("i", $id);

if($stmt_lead->execute()){
    // Delete Success Popup
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
        window.onload = function() {
            Swal.fire({
                title: 'Deleted!',
                text: 'The lead has been permanently deleted.',
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'OK'
            }).then((result) => {
                window.location = 'leads.php';
            });
        };
    </script>";
} else {
    // Delete Error Popup
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
        window.onload = function() {
            Swal.fire('Error!', 'Something went wrong while deleting the lead.', 'error')
            .then(() => { window.location = 'leads.php'; });
        };
    </script>";
}

include 'include/footer.php';
?>