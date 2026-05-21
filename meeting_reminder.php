<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config/database.php';

date_default_timezone_set('Asia/Kolkata');

$current = date("Y-m-d H:i:s");
$next = date("Y-m-d H:i:s", strtotime("+30 minutes"));

echo "Checking followups between $current and $next <br>";

$query = mysqli_query($conn,"
SELECT * FROM leads 
WHERE followup_time BETWEEN '$current' AND '$next'
AND reminder_sent=0
");

if(mysqli_num_rows($query)==0){
echo "No followups found<br>";
}

while($row = mysqli_fetch_assoc($query)){

echo "Reminder Found for Lead ID: ".$row['id']."<br>";

$lead_email = $row['email'];
$sales_name = $row['lead_by'];

$subject = "Follow-up Reminder";

$message = "
Hello,

This is a reminder for your scheduled follow-up.

Customer: ".$row['contact_person']."
Company: ".$row['company_name']."
Follow-up Time: ".$row['followup_time']."

CRM System
";

$headers = "From: noreply@sts.sunlitesystems.com";

if($lead_email){
mail($lead_email,$subject,$message,$headers);
echo "Lead email sent<br>";
}

/* SALES USER FIND */

$user_q = mysqli_query($conn,"
SELECT id FROM users 
WHERE name='$sales_name'
");

$user = mysqli_fetch_assoc($user_q);

$user_id = $user['id'];

/* PANEL NOTIFICATION */

mysqli_query($conn,"
INSERT INTO notifications (user_id,message,created_at)
VALUES (
'$user_id',
'Follow-up reminder for ".$row['contact_person']." at ".$row['followup_time']."',
NOW()
)
");

/* ADMIN NOTIFICATION ALSO */

mysqli_query($conn,"
INSERT INTO notifications (user_id,message,created_at)
VALUES (
'1',
'Sales reminder for ".$row['contact_person']." assigned to ".$sales_name."',
NOW()
)
");

mysqli_query($conn,"
UPDATE leads 
SET reminder_sent=1
WHERE id=".$row['id']."
");

}

echo "Script finished.";

?>