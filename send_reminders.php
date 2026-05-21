<?php

date_default_timezone_set('Asia/Kolkata');

require_once 'config/database.php';

require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$current_time = date("Y-m-d H:i:s");

$query = mysqli_query($conn,"
SELECT * FROM leads
WHERE followup_time <= '$current_time'
AND reminder_sent = 0
AND followup_time IS NOT NULL
AND followup_time != '0000-00-00 00:00:00'
");

while($lead = mysqli_fetch_assoc($query)){

$id = $lead['id'];
$client_email = $lead['email'];
$client_name = $lead['contact_person'];
$company = $lead['company_name'];
$followup_time = $lead['followup_time'];
$sales_person = $lead['lead_by'];

$mail = new PHPMailer(true);

try{

$mail->isSMTP();
$mail->Host       = 'smtp.hostinger.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'sts@ssplworld.com'; 
$mail->Password   = 'nyjbbkvmksegslfg'; 
$mail->SMTPSecure = 'tls';
$mail->Port       = 587;

$mail->setFrom('sts@ssplworld.com', 'STS CRM');

$mail->addAddress($client_email);

$mail->isHTML(true);

$mail->Subject = "Meeting Follow-up Reminder";

$mail->Body = "
<h3>Hello $client_name,</h3>

<p>This is a reminder for your scheduled meeting.</p>

<b>Company:</b> $company <br>
<b>Meeting Time:</b> $followup_time <br>
<b>Sales Executive:</b> $sales_person <br><br>

Thank you<br>
STS Team
";

if($mail->send()){

mysqli_query($conn,"UPDATE leads SET reminder_sent=1 WHERE id='$id'");

}

}catch(Exception $e){

echo "Mail Error: ".$mail->ErrorInfo;

}

}

echo "Reminder script executed successfully.";

?>